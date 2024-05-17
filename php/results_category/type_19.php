<?php
    /*
     * Hlučín plavání, celkový čas s kolama, kategorie
     */
    $hash_url = '#'.$this->race_year.'/'.$this->race_id.'/';
    $colspan = 4;
    if($category_id == 'all'){
	$str .= '<h4 class="headline-results">'.$this->race_name.', výsledky podle kategorí</h4>';
	$sql = "SELECT id_kategorie,kod_k AS kod_kategorie,nazev_k AS nazev_kategorie,poradi_podzavodu FROM $this->sqlkategorie WHERE id_zavodu = :race_id ORDER BY id_kategorie";
	$sth = $this->db->prepare($sql);
	$sth->execute(Array(':race_id' => $this->race_id));
	if($sth->rowCount()){
	    $str .= '<table id="table2excel" class="table table-bordered table-striped table-hover noborder table_vysledky">';
	    $k = 1;
	    while($data = $sth->fetchObject()){
		$sql1 = "SELECT CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,osoby.psc AS stat,tymy.nazev_tymu,$this->sqlvysledky.ids,$this->sqlvysledky.cip,$this->sqlvysledky.ids_alias,MAX($this->sqlvysledky.race_time) AS finish_time,COUNT($this->sqlvysledky.id) AS pocet_kol,$this->sqlkategorie.nazev_k AS nazev_kategorie FROM $this->sqlvysledky,$this->sqlzavod,$this->sqlkategorie,tymy,osoby "
			  . "WHERE $this->sqlvysledky.race_time > '0' "
			  . "AND $this->sqlzavod.id_kategorie = :id_kategorie  "
			  . "AND $this->sqlzavod.id_kategorie = $this->sqlkategorie.id_kategorie "
			  . "AND $this->sqlkategorie.poradi_podzavodu = :event_order "
			  . "AND $this->sqlkategorie.poradi_podzavodu = $this->sqlvysledky.poradi_podzavodu "
			  . "AND $this->sqlzavod.cip = $this->sqlvysledky.cip "
			  . "AND $this->sqlzavod.id_tymu = tymy.id_tymu "
			  . "AND $this->sqlzavod.ido = osoby.ido "
			  . "AND $this->sqlvysledky.false_time IS NULL "
			  . "AND $this->sqlvysledky.lap_only IS NULL "
			  . "GROUP BY $this->sqlzavod.ids "
			  . "ORDER BY pocet_kol DESC,finish_time ASC";
		 // echo $sql1."\n\n";
		$sth1 =  $this->db->prepare($sql1);
		$sth1->execute(Array(':id_kategorie' => $data->id_kategorie,':event_order' => $this->event_order));
		if($sth1->rowCount()){
		    $class = $k == 1 ? $class = 'nadpis nopadding' : 'nadpis';
		    $str .= '<tr><td class="'.$class.'" colspan="3">'.$data->nazev_kategorie.'</td></tr>';
		    $str .= '<tr class="header"><th class="text-center">#</th><th class="text-center">St.č</th><th>Jméno</th><th class="text-center">Ročník</th><th>Tým/Bydliště</th><th class="text-center">Stát</th><th class="text-center">Kola</th><th class="text-center">Celkový čas</th><th class="text-center">Odstup</th></tr>';
		    $poradi = 1;
		    while($dbdata1 = $sth1->fetchObject()){
			if($poradi == 1) $max_pocet_kol = $dbdata1->pocet_kol;// nejvyšší počet kol pro počítání odstupů
			    $sql2 = "SELECT $this->sqlvysledky.distance_category FROM $this->sqlvysledky WHERE $this->sqlvysledky.ids = :ids AND time_order = :max_pocet_kol AND poradi_podzavodu = :event_order"; //vezmeme odstupy, v tomto případě ve zvláštním dotaze
			    $sth2 = $this->db->prepare($sql2);
			    $sth2->execute(Array(':ids' => $dbdata1->ids,':max_pocet_kol' => $max_pocet_kol,':event_order' => $this->event_order));
			    if($sth2->rowCount()){ //pokud je někdo se stejným, tzn. nejvyšším počttem kol, použijeme časový odstup
				$dbdata2 = $sth2->fetchObject();
				($dbdata2->distance_category != '00:00:00.00') ? ($distance_category = $dbdata2->distance_category) : ($distance_category = '-'); //pokud nemá odstup, tak je první a bude tam pomlčka
			    }
			    else{ // pokud ne, spočítáme odstup v kolech
				$distance_category = $dbdata1->pocet_kol - $max_pocet_kol; 
				if($distance_category == -1){
				    $kola = 'kolo';
				}
				elseif(($distance_category < -1 AND $distance_category > -5) OR $distance_category > -1){
				    $kola = 'kola';
				}
				else{
				    $kola = 'kol';
				}
				$distance_category = $distance_category.' '.$kola;
			    }
			    $str .= '<tr id="'.$dbdata1->cip.'">';
			    $str .= '<td class="text-center">'.$poradi.'</td>';
			    $str .= '<td class="text-center">'.$dbdata1->ids_alias.'</td>';
			    $str .= '<td><a onclick="detail_cipu_plavani('.$dbdata1->cip.','.$this->race_id.','.$this->race_year.','.$this->event_order.')" href="'.$hash_url.'vysledky">'.$dbdata1->jmeno.'</a></td>';
			    $str .= '<td class="text-center">'.$dbdata1->rocnik.'</td>';
			    $str .= '<td>'.$dbdata1->nazev_tymu.'</td>';
			    $str .= '<td class="text-center">'.$dbdata1->stat.'</td>';
			    $str .= '<td class="text-center">'.$dbdata1->pocet_kol.'</td>';
			    $str .= '<td class="text-center">'.$dbdata1->finish_time.'</td>';
			    $str .= '<td class="text-center">'.$distance_category.'</td>';
	   // echo $data->id_kategorie;
			    $str .= '</tr>';
			$poradi++;
		    }
		}
		$str .= $this->DNFCategory(19,$data->id_kategorie);

		$k++;
		
	    }
	    $str .= '</table>';
	}
    }
    else{ // každá kategorie zvlášť
	$sql = "SELECT nazev_k AS nazev_kategorie FROM $this->sqlkategorie WHERE id_zavodu = :race_id AND id_kategorie = :category_id";
	$sth = $this->db->prepare($sql);
	$sth->execute(Array(':race_id' => $this->race_id,':category_id' => $category_id));
	if($sth->rowCount()){
	    $dbdata = $sth->fetchObject();
	    $str .= '<h4 class="headline-results">'.$this->race_name.', kategorie '.$dbdata->nazev_kategorie.'</h4>';
	    $str .= '<table id="table2excel" class="table table-bordered table-hover table_vysledky">';
	    $k = 1;
	    $sql1 = "SELECT CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,osoby.psc AS stat,tymy.nazev_tymu,$this->sqlvysledky.ids,$this->sqlvysledky.cip,$this->sqlvysledky.ids_alias,MAX($this->sqlvysledky.race_time) AS finish_time,COUNT($this->sqlvysledky.id) AS pocet_kol,$this->sqlkategorie.nazev_k AS nazev_kategorie FROM $this->sqlvysledky,$this->sqlzavod,$this->sqlkategorie,tymy,osoby "
		    . "WHERE $this->sqlvysledky.race_time > '0' "
		    . "AND $this->sqlzavod.id_kategorie = :category_id  "
		    . "AND $this->sqlzavod.id_kategorie = $this->sqlkategorie.id_kategorie "
		    . "AND $this->sqlkategorie.poradi_podzavodu = :event_order "
		    . "AND $this->sqlkategorie.poradi_podzavodu = $this->sqlvysledky.poradi_podzavodu "
		    . "AND $this->sqlzavod.cip = $this->sqlvysledky.cip "
		    . "AND $this->sqlzavod.tym = tymy.id_tymu "
		    . "AND $this->sqlzavod.ido = osoby.ido "
		    . "AND $this->sqlvysledky.false_time IS NULL "
		    . "AND $this->sqlvysledky.lap_only IS NULL "
		    . "GROUP BY $this->sqlzavod.ids "
		    . "ORDER BY pocet_kol DESC,finish_time ASC";
	    $sth1 =  $this->db->prepare($sql1);
	    $sth1->execute(Array(':category_id' => $category_id,':event_order' => $this->event_order));
	    if($sth1->rowCount()){
		$str .= '<tr class="header"><th class="text-center">#</th><th class="text-center">St.č</th><th>Jméno</th><th class="text-center">Ročník</th><th>Tým/Bydliště</th><th class="text-center">Stát</th><th class="text-center">Kola</th><th class="text-center">Celkový čas</th><th class="text-center">Odstup</th></tr>';
		$poradi = 1;
		while($dbdata1 = $sth1->fetchObject()){
		    if($poradi == 1) $max_pocet_kol = $dbdata1->pocet_kol;// nejvyšší počet kol pro počítání odstupů
			$sql2 = "SELECT $this->sqlvysledky.distance_category FROM $this->sqlvysledky WHERE $this->sqlvysledky.ids = :ids AND time_order = :max_pocet_kol AND poradi_podzavodu = :event_order"; //vezmeme odstupy, v tomto případě ve zvláštním dotaze
			$sth2 = $this->db->prepare($sql2);
			$sth2->execute(Array(':ids' => $dbdata1->ids,':max_pocet_kol' => $max_pocet_kol,':event_order' => $this->event_order));
			if($sth2->rowCount()){ //pokud je někdo se stejným, tzn. nejvyšším počttem kol, použijeme časový odstup
			    $dbdata2 = $sth2->fetchObject();
			    ($dbdata2->distance_category != '00:00:00.00') ? ($distance_category = $dbdata2->distance_category) : ($distance_category = '-'); //pokud nemá odstup, tak je první a bude tam pomlčka
			}
		    else{ // pokud ne, spočítáme odstup v kolech
			$distance_category = $dbdata1->pocet_kol - $max_pocet_kol; 
			if($distance_category == -1){
			    $kola = 'kolo';
			}
			elseif(($distance_category < -1 AND $distance_category > -5) OR $distance_category > -1){
			    $kola = 'kola';
			}
			else{
			    $kola = 'kol';
			}
			$distance_category = $distance_category.' '.$kola;
		    }
		    $str .= '<tr id="'.$dbdata1->cip.'">';
		    $str .= '<td class="text-center">'.$poradi.'</td>';
		    $str .= '<td class="text-center">'.$dbdata1->ids_alias.'</td>';
		    $str .= '<td><a onclick="detail_cipu_plavani('.$dbdata1->cip.','.$this->race_id.','.$this->race_year.','.$this->event_order.')" href="'.$hash_url.'vysledky">'.$dbdata1->jmeno.'</a></td>';
		    $str .= '<td class="text-center">'.$dbdata1->rocnik.'</td>';
		    $str .= '<td>'.$dbdata1->nazev_tymu.'</td>';
		    $str .= '<td class="text-center">'.$dbdata1->stat.'</td>';
		    $str .= '<td class="text-center">'.$dbdata1->pocet_kol.'</td>';
		    $str .= '<td class="text-center">'.$dbdata1->finish_time.'</td>';
		    $str .= '<td class="text-center">'.$distance_category.'</td>';
		    $str .= '</tr>';
		    $poradi++;
		}
	    }
	    else{
		$str .= '<p>Žádný výsledek</p>';
	    }

		$k++;
	    $str .= $this->DNFCategory(19,$category_id);
	    $str .= '</tbody></table>';
	}
    }
?>
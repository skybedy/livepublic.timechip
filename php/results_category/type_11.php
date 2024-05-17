<?php

/* 
 * Sareza, kategorie
 */


    $colspan = 4;
    if($category_id == 'all'){
	$str .= '<h4 class="headline-results">'.$this->race_name.', výsledky podle kategorí</h4>';
	$sql1 = "SELECT id_kategorie,kod_k AS kod_kategorie,nazev_k AS nazev_kategorie FROM $this->sqlkategorie WHERE id_zavodu = :race_id AND $this->sqlkategorie.poradi_podzavodu = :event_order ORDER BY id_kategorie";
	$sth1 = $this->db->prepare($sql1);
	$sth1->execute(Array(':race_id' => $this->race_id,':event_order' => $this->event_order));
	if($sth1->rowCount()){
	    $str .= '<table class="table table-bordered table-hover noborder table_vysledky">';
	    $k = 1;
	    while($dbdata1 = $sth1->fetchObject()){
		$class = $k == 1 ? $class = 'nadpis nopadding' : 'nadpis';
		$str .= '<tr><td class="'.$class.'" colspan="3">'.$dbdata1->nazev_kategorie.'</td></tr>';
		$str .= '<tr class="header"><th class="text-center">#</th><th class="text-center">St.č</th><th>Jméno</th><th class="text-center">Ročník</th><th>Tým/Bydliště</th><th class="text-center">Počet kol</th><th class="text-center">Celkový čas</th><th class="text-center">Odstup</th></tr>';
		$sql2 = "SELECT CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,osoby.psc AS stat,tymy.nazev_tymu,COUNT($this->sqlvysledky.id) AS pocet_kol,MAX($this->sqlvysledky.race_time) AS finish_time,$this->sqlzavod.cip,$this->sqlzavod.ids_alias,$this->sqlkategorie.nazev_k AS nazev_kategorie FROM $this->sqlvysledky,$this->sqlzavod,osoby,$this->sqlkategorie,tymy "
	      . "WHERE "
	      . "$this->sqlvysledky.false_time IS NULL AND "
	      . "$this->sqlvysledky.race_time > '0' AND "
	      . "$this->sqlvysledky.cip = $this->sqlzavod.cip AND "
	      . "$this->sqlzavod.ido = osoby.ido AND "
	      . "$this->sqlzavod.id_kategorie = '$dbdata1->id_kategorie' AND "
	      . "$this->sqlzavod.id_kategorie = $this->sqlkategorie.id_kategorie AND "
	      . "$this->sqlzavod.id_tymu = tymy.id_tymu "
	      . "GROUP BY $this->sqlzavod.cip "
	      . "ORDER BY pocet_kol DESC,finish_time ASC";
		//echo $sql2;
		$sth2 = $this->db->query($sql2);
		$sth2->execute();
		if($sth2->rowCount()){
		    $poradi = 1;
		    while($dbdata2 = $sth2->fetchObject()){
			if($poradi == 1) $max_pocet_kol = $dbdata2->pocet_kol;// nejvyšší počet časů pro počítání odstupů
			$sql3 = "SELECT $this->sqlvysledky.distance_category FROM $this->sqlvysledky WHERE $this->sqlvysledky.cip = :cip AND time_order = :max_pocet_kol"; //vezmeme odstupy, v tomto případě ve zvláštním dotaze
			$sth3 = $this->db->prepare($sql3);
			$sth3->execute(Array(':cip' => $dbdata2->cip,':max_pocet_kol' => $max_pocet_kol));
			if($sth3->rowCount()){ //pokud je někdo se stejným, tzn. nejvyšším počttem kol, použijeme časový odstup
			    $dbdata3 = $sth3->fetchObject();
			    ($dbdata3->distance_category != '00:00:00.00') ? ($distance_category = $dbdata3->distance_category) : ($distance_category = '-'); //pokud nemá odstup, tak je první a bude tam pomlčka
			}
			else{ // pokud ne, spočítáme odstup v kolech
			     $distance_category = $dbdata2->pocet_kol - $max_pocet_kol; 
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
			$str .= '<tr id="'.$dbdata2->cip.'">';
			$str .= '<td class="text-center">'.$poradi.'</td>';
			$str .= '<td class="text-center">'.$dbdata2->ids_alias.'</td>';
			$str .= '<td>'.$dbdata2->jmeno.'</td>';
			$str .= '<td class="text-center">'.$dbdata2->rocnik.'</td>';
			$str .= '<td>'.$dbdata2->nazev_tymu.'</td>';
			$str .= '<td class="text-center">'.$dbdata2->pocet_kol.'</td>';
			$str .= '<td class="text-center">'.$dbdata2->finish_time.'</td>';
			$str .= ($distance_category != '00:00:00.00') ?  ('<td class="text-center">'.$distance_category.'</td>') : ('<td class="text-center">-</td>');
			$str .= '</tr>';
			$poradi++;	
		    }
		}
	    }
	}
    }
    else{
	$sql1 = "SELECT nazev_k AS nazev_kategorie FROM $this->sqlkategorie WHERE id_zavodu = :race_id AND id_kategorie = :category_id";
	$sth1 = $this->db->prepare($sql1);
	$sth1->execute(Array(':race_id' => $this->race_id,':category_id' => $category_id));
	if($sth1->rowCount()){
	    $dbdata1 = $sth1->fetchObject();
	    $str .= '<h4 class="headline-results">'.$this->race_name.', kategorie '.$dbdata1->nazev_kategorie.'</h4>';
	    $str .= '<table class="table table-bordered table-hover table_vysledky">';
	    $k = 1;
	    $sql2 = "SELECT CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,osoby.psc AS stat,tymy.nazev_tymu,COUNT($this->sqlvysledky.id) AS pocet_kol,MAX($this->sqlvysledky.race_time) AS finish_time,$this->sqlzavod.cip,$this->sqlzavod.ids_alias,$this->sqlkategorie.nazev_k AS nazev_kategorie FROM $this->sqlvysledky,$this->sqlzavod,osoby,$this->sqlkategorie,tymy "
	      . "WHERE "
	      . "$this->sqlvysledky.false_time IS NULL AND "
	      . "$this->sqlvysledky.race_time > '0' AND "
	      . "$this->sqlvysledky.cip = $this->sqlzavod.cip AND "
	      . "$this->sqlzavod.ido = osoby.ido AND "
	      . "$this->sqlzavod.id_kategorie = '$category_id' AND "
	      . "$this->sqlzavod.id_kategorie = $this->sqlkategorie.id_kategorie AND "
	      . "$this->sqlzavod.id_tymu = tymy.id_tymu "
	      . "GROUP BY $this->sqlzavod.cip "
	      . "ORDER BY pocet_kol DESC,finish_time ASC";
		//echo $sql2;
		$sth2 = $this->db->query($sql2);
		$sth2->execute();
		if($sth2->rowCount()){
		    $str .= '<tr class="header"><th class="text-center">#</th><th class="text-center">St.č</th><th>Jméno</th><th class="text-center">Ročník</th><th>Tým/Bydliště</th><th class="text-center">Počet kol</th><th class="text-center">Celkový čas</th><th class="text-center">Odstup</th></tr>';
		    $poradi = 1;
		    while($dbdata2 = $sth2->fetchObject()){
			if($poradi == 1) $max_pocet_kol = $dbdata2->pocet_kol;// nejvyšší počet časů pro počítání odstupů
			$sql3 = "SELECT $this->sqlvysledky.distance_category FROM $this->sqlvysledky WHERE $this->sqlvysledky.cip = :cip AND time_order = :max_pocet_kol"; //vezmeme odstupy, v tomto případě ve zvláštním dotaze
			$sth3 = $this->db->prepare($sql3);
			$sth3->execute(Array(':cip' => $dbdata2->cip,':max_pocet_kol' => $max_pocet_kol));
			if($sth3->rowCount()){ //pokud je někdo se stejným, tzn. nejvyšším počttem kol, použijeme časový odstup
			    $dbdata3 = $sth3->fetchObject();
			    ($dbdata3->distance_category != '00:00:00.00') ? ($distance_category = $dbdata3->distance_category) : ($distance_category = '-'); //pokud nemá odstup, tak je první a bude tam pomlčka
			}
			else{ // pokud ne, spočítáme odstup v kolech
			     $distance_category = $dbdata2->pocet_kol - $max_pocet_kol; 
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
			$str .= '<tr id="'.$dbdata2->cip.'">';
			$str .= '<td class="text-center">'.$poradi.'</td>';
			$str .= '<td class="text-center">'.$dbdata2->ids_alias.'</td>';
			$str .= '<td>'.$dbdata2->jmeno.'</td>';
			$str .= '<td class="text-center">'.$dbdata2->rocnik.'</td>';
			$str .= '<td>'.$dbdata2->nazev_tymu.'</td>';
			$str .= '<td class="text-center">'.$dbdata2->pocet_kol.'</td>';
			$str .= '<td class="text-center">'.$dbdata2->finish_time.'</td>';
			$str .= ($distance_category != '00:00:00.00') ?  ('<td class="text-center">'.$distance_category.'</td>') : ('<td class="text-center">-</td>');
			$str .= '</tr>';
			$poradi++;	
		    }
		}


	    $k++;
	    $str .= '</tbody></table>';
	}

	
	
	
	
	
    }


	$str .= '</table>';

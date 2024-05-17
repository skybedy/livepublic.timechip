<?php
    /*
     * Radegastova výzva
     */
    $hash_url = '#'.$this->race_year.'/'.$this->race_id.'/';
    $colspan = 4;
    if($category_id == 'all'){
	$str .= '<h4 class="headline-results">'.$this->race_name.', výsledky podle kategorí</h4>';
	$sql = "SELECT id_kategorie,kod_k AS kod_kategorie,nazev_k AS nazev_kategorie,poradi_podzavodu FROM $this->sqlkategorie WHERE id_zavodu = :race_id ORDER BY id_kategorie";
	$sth = $this->db->prepare($sql);
	$sth->execute(Array(':race_id' => $this->race_id));
	if($sth->rowCount()){
	    $str .= '<table class="table table-bordered noborder table_vysledky">';
	    $k = 1;
	    while($data = $sth->fetchObject()){
		$sql1 = "SELECT tymy.nazev_tymu,$this->sqlvysledky.ids,$this->sqlvysledky.cip,$this->sqlvysledky.ids_alias,MAX($this->sqlvysledky.race_time) AS finish_time,MAX($this->sqlvysledky.lap_count) AS pocet_kol,$this->sqlkategorie.nazev_k AS nazev_kategorie FROM $this->sqlvysledky,$this->sqlzavod,$this->sqlkategorie,tymy "
			  . "WHERE race_time > '0' "
			  . "AND $this->sqlzavod.id_kategorie = :id_kategorie  "
			  . "AND $this->sqlzavod.id_kategorie = $this->sqlkategorie.id_kategorie "
			  . "AND $this->sqlkategorie.poradi_podzavodu = '$this->event_order' "
			  . "AND $this->sqlzavod.ids = $this->sqlvysledky.ids "
			  . "AND $this->sqlzavod.tym = tymy.id_tymu "
			  . "AND false_time IS NULL "
			  . "AND lap_only IS NULL "
			  . "GROUP BY $this->sqlzavod.ids "
			  . "ORDER BY pocet_kol DESC,finish_time ASC".$this->rows_limit;
		  // echo $sql1."\n\n";
		$sth1 =  $this->db->prepare($sql1);
		$sth1->execute(Array(':id_kategorie' => $data->id_kategorie));
		if($sth1->rowCount()){
		    $class = $k == 1 ? $class = 'nadpis nopadding' : 'nadpis';
		    $str .= '<tr><td class="'.$class.'" colspan="3">'.$data->nazev_kategorie.'</td></tr>';
		    $str .= '<tr class="header"><th class="text-center">#</th><th class="text-center">St.č</th><th>Tým</th><th>Členové týmu</th><th class="text-center">Ročník</th><th class="text-center">Počet kol</th><th class="text-center">Počet km</th><th class="text-center">Celkový čas</th><th class="text-center">Odstup</th></tr>';
		    $poradi = 1;
		    while($data1 = $sth1->fetchObject()){
			$sql3 = "SELECT CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,$this->sqlzavod.cip FROM osoby,$this->sqlzavod WHERE $this->sqlzavod.ids = :ids AND $this->sqlzavod.ido = osoby.ido ORDER BY $this->sqlzavod.id";
			$sth3 = $this->db->prepare($sql3);
			$sth3->execute(Array(':ids' => $data1->ids));
			$pocet_clenu = $sth3->rowCount(); //počet členů týmu, který potřebujeme na rowspan

			$z = 1;
			while($dbdata3 = $sth3->fetchObject()){
			    if($poradi == 1) $max_pocet_kol = $data1->pocet_kol;// nejvyšší počet kol pro počítání odstupů

			    
			    //$sql2 = "SELECT $this->sqlvysledky.distance_category FROM $this->sqlvysledky WHERE $this->sqlvysledky.ids = :ids AND time_order = :max_pocet_kol"; //vezmeme odstupy, v tomto případě ve zvláštním dotaze
			    //$sth2 = $this->db->prepare($sql2);
			    //$sth2->execute(Array(':ids' => $data1->ids,':max_pocet_kol' => $max_pocet_kol));
			    
			    $sql2 = "SELECT $this->sqlvysledky.distance_category FROM $this->sqlvysledky WHERE $this->sqlvysledky.ids = '$data1->ids' AND lap_count = '$max_pocet_kol'"; //vezmeme odstupy, v tomto případě ve zvláštním dotaze
			    //echo $sql2."\n";
			    $sth2 = $this->db->prepare($sql2);
			    $sth2->execute();

			    
			    if($sth2->rowCount()){ //pokud je někdo se stejným, tzn. nejvyšším počttem kol, použijeme časový odstup
				$dbdata2 = $sth2->fetchObject();
				($dbdata2->distance_category != '00:00:00.00') ? ($distance_category = $dbdata2->distance_category) : ($distance_category = '-'); //pokud nemá odstup, tak je první a bude tam pomlčka
			    }
			    else{ // pokud ne, spočítáme odstup v kolech
				$distance_category = $data1->pocet_kol - $max_pocet_kol; 
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

			    if($z == 1){
				$str .= '<tr id="'.$data1->ids_alias.'">';
				$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$poradi.'</td>';
				$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$data1->ids_alias.'</td>';
				$str .= '<td rowspan="'.$pocet_clenu.'"><a onclick="detail_ids_rv('.$data1->ids.','.$this->race_id.','.$this->race_year.')" href="'.$hash_url.'vysledky">'.$data1->nazev_tymu.'</a></td>';
                                $str .= '<td><a style="text-decoration:none"  href="'.$hash_url.'vysledky" onclick="detail_cipu_rv('.$dbdata3->cip.','.$this->race_id.','.$this->race_year.')">'.$dbdata3->jmeno.'</a></td>';
				$str .= '<td class="text-center">'.$dbdata3->rocnik.'</td>';
				$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$data1->pocet_kol.'</td>';
				$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.($data1->pocet_kol * $this->delka_kola ).'</td>';
				$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$data1->finish_time.'</td>';
				$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$distance_category.'</td>';
				$str .= '</tr>';
			    }
			    else{
				$str .= '<tr>';
                                $str .= '<td><a style="text-decoration:none"  href="'.$hash_url.'vysledky" onclick="detail_cipu_rv('.$dbdata3->cip.','.$this->race_id.','.$this->race_year.')">'.$dbdata3->jmeno.'</a></td>';
				$str .= '<td class="text-center">'.$dbdata3->rocnik.'</td>';
				$str .= '</tr>';
			    }
			    $z++;
			}
			$poradi++;
		    }
		}
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
	    $str .= '<table class="table table-bordered table_vysledky">';
	    $k = 1;
	    $sql1 = "SELECT tymy.nazev_tymu,$this->sqlvysledky.ids,$this->sqlvysledky.cip,$this->sqlvysledky.ids_alias,MAX($this->sqlvysledky.race_time) AS finish_time,MAX($this->sqlvysledky.lap_count) AS pocet_kol,$this->sqlkategorie.nazev_k AS nazev_kategorie FROM $this->sqlvysledky,$this->sqlzavod,$this->sqlkategorie,tymy "
		    . "WHERE race_time > '0' "
		    . "AND $this->sqlzavod.id_kategorie = :category_id  "
		    . "AND $this->sqlzavod.id_kategorie = $this->sqlkategorie.id_kategorie "
		    . "AND $this->sqlkategorie.poradi_podzavodu = '$this->event_order' "
		    . "AND $this->sqlzavod.ids = $this->sqlvysledky.ids "
		    . "AND $this->sqlzavod.tym = tymy.id_tymu "
		    . "AND false_time IS NULL "
		    . "AND lap_only IS NULL "
		    . "GROUP BY $this->sqlzavod.ids "
		    . "ORDER BY pocet_kol DESC,finish_time ASC".$this->rows_limit;
	    $sth1 =  $this->db->prepare($sql1);
	    $sth1->execute(Array(':category_id' => $category_id));
	    if($sth1->rowCount()){
		$str .= '<tr class="header"><th class="text-center">#</th><th class="text-center">St.č</th><th>Tým</th><th>Členové týmu</th><th class="text-center">Ročník</th><th class="text-center">Počet kol</th><th class="text-center">Počet km</th><th class="text-center">Celkový čas</th><th class="text-center">Odstup</th></tr>';
		$poradi = 1;
		while($data1 = $sth1->fetchObject()){
		    $sql3 = "SELECT CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,$this->sqlzavod.cip FROM osoby,$this->sqlzavod WHERE $this->sqlzavod.ids = :ids AND $this->sqlzavod.ido = osoby.ido ORDER BY $this->sqlzavod.id";
		    $sth3 = $this->db->prepare($sql3);
		    $sth3->execute(Array(':ids' => $data1->ids));
		    $pocet_clenu = $sth3->rowCount(); //počet členů týmu, který potřebujeme na rowspan
		    $z = 1;
		    while($dbdata3 = $sth3->fetchObject()){
			    if($poradi == 1) $max_pocet_kol = $data1->pocet_kol;// nejvyšší počet kol pro počítání odstupů

			    
			    //$sql2 = "SELECT $this->sqlvysledky.distance_category FROM $this->sqlvysledky WHERE $this->sqlvysledky.ids = :ids AND time_order = :max_pocet_kol"; //vezmeme odstupy, v tomto případě ve zvláštním dotaze
			    //$sth2 = $this->db->prepare($sql2);
			    //$sth2->execute(Array(':ids' => $data1->ids,':max_pocet_kol' => $max_pocet_kol));
			    
			    $sql2 = "SELECT $this->sqlvysledky.distance_category FROM $this->sqlvysledky WHERE $this->sqlvysledky.ids = '$data1->ids' AND lap_count = '$max_pocet_kol'"; //vezmeme odstupy, v tomto případě ve zvláštním dotaze
			    //echo $sql2."\n";
			    $sth2 = $this->db->prepare($sql2);
			    $sth2->execute();

			    
			    if($sth2->rowCount()){ //pokud je někdo se stejným, tzn. nejvyšším počttem kol, použijeme časový odstup
				$dbdata2 = $sth2->fetchObject();
				($dbdata2->distance_category != '00:00:00.00') ? ($distance_category = $dbdata2->distance_category) : ($distance_category = '-'); //pokud nemá odstup, tak je první a bude tam pomlčka
			    }
			    else{ // pokud ne, spočítáme odstup v kolech
				$distance_category = $data1->pocet_kol - $max_pocet_kol; 
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

			    if($z == 1){
				$str .= '<tr>';
				$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$poradi.'</td>';
				$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$data1->ids_alias.'</td>';
				$str .= '<td rowspan="'.$pocet_clenu.'"><a onclick="detail_ids_rv('.$data1->ids.','.$this->race_id.','.$this->race_year.')" href="'.$hash_url.'vysledky">'.$data1->nazev_tymu.'</a></td>';
                                $str .= '<td><a style="text-decoration:none"  href="'.$hash_url.'vysledky" onclick="detail_cipu_rv('.$dbdata3->cip.','.$this->race_id.','.$this->race_year.')">'.$dbdata3->jmeno.'</a></td>';
				$str .= '<td class="text-center">'.$dbdata3->rocnik.'</td>';
                                $str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$data1->pocet_kol.'</td>';
				$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.($data1->pocet_kol * $this->delka_kola ).'</td>';
				$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$data1->finish_time.'</td>';
				$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$distance_category.'</td>';
				$str .= '</tr>';
			    }
			    else{
				$str .= '<tr>';
                                $str .= '<td><a style="text-decoration:none"  href="'.$hash_url.'vysledky" onclick="detail_cipu_rv('.$dbdata3->cip.','.$this->race_id.','.$this->race_year.')">'.$dbdata3->jmeno.'</a></td>';
				$str .= '<td class="text-center">'.$dbdata3->rocnik.'</td>';
                                $str .= '</tr>';
			    }
			    $z++;
		    }
		    $poradi++;
		}
	    }
	    else{
		$str .= '<p>Žádný výsledek</p>';
	    }

		$k++;
	    $str .= '</tbody></table>';
	}
    }
?>
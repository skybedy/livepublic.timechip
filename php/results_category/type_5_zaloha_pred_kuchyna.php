<?php
    /*
     * 24 hodin družstva
     * oproti Radegastově výzvě je tady v dotaze přidán jen reader = 'CIL', i když se zdá že dotaz jede i bez toho
     * vůbec - dotazy by se měly za dlouhých zimních večerů prozkoumat
     */
    $hash_url = '#'.$this->race_year.'/'.$this->race_id.'/';
    $colspan = 4;
    if($category_id == 'all'){
	$str .= '<h4 class="headline-results">'.$this->race_name.', výsledky podle kategorí</h4>';
	$sql = "SELECT id_kategorie,kod_k AS kod_kategorie,nazev_k AS nazev_kategorie,poradi_podzavodu FROM $this->sqlkategorie WHERE id_zavodu = :race_id ORDER BY id_kategorie";
	$sth = $this->db->prepare($sql);
	$sth->execute(Array(':race_id' => $this->race_id));
	if($sth->rowCount()){
	    $str .= '<table class="table table-bordered table-hove noborder table_vysledky">';
	    $k = 1;
	    while($data = $sth->fetchObject()){
		$sql1 = "SELECT tymy.nazev_tymu,$this->sqlvysledky.ids,$this->sqlvysledky.ids_alias,MAX($this->sqlvysledky.race_time) AS finish_time,COUNT($this->sqlvysledky.id) AS pocet_kol,$this->sqlkategorie.nazev_k AS nazev_kategorie FROM $this->sqlvysledky,$this->sqlzavod,$this->sqlkategorie,tymy "
			  . "WHERE $this->sqlvysledky.race_time > '0' "
			  . "AND $this->sqlvysledky.reader LIKE 'CIL' "
			  . "AND $this->sqlzavod.id_kategorie = :id_kategorie  "
			  . "AND $this->sqlzavod.id_kategorie = $this->sqlkategorie.id_kategorie "
			  . "AND $this->sqlkategorie.poradi_podzavodu = '$this->event_order' "
			  . "AND $this->sqlzavod.cip = $this->sqlvysledky.cip "
			  . "AND $this->sqlzavod.tym = tymy.id_tymu "
			  . "AND $this->sqlvysledky.false_time IS NULL "
			  . "AND $this->sqlvysledky.lap_only IS NULL "
			  . "GROUP BY $this->sqlzavod.ids "
			  . "ORDER BY pocet_kol DESC,finish_time ASC";
		// echo $sql1."\n\n";
		$sth1 =  $this->db->prepare($sql1);
		$sth1->execute(Array(':id_kategorie' => $data->id_kategorie));
		if($sth1->rowCount()){
		    $class = $k == 1 ? $class = 'nadpis nopadding' : 'nadpis';
		    $str .= '<tr><td class="'.$class.'" colspan="3">'.$data->nazev_kategorie.'</td></tr>';
		    $str .= '<tr class="header"><th class="text-center">#</th><th class="text-center">St.č</th><th>Tým</th><th>Členové týmu</th><th class="text-center">Ročník</th><th class="text-center">St.č</th><th class="text-center">Kola</th><th class="text-center">Km</th><th class="text-center">Poslední start</th><th class="text-center">Celkový čas</th><th class="text-center">Odstup</th></tr>';
		    $poradi = 1;
		    while($data1 = $sth1->fetchObject()){
			$sql3 = "SELECT CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,$this->sqlzavod.cip FROM osoby,$this->sqlzavod WHERE $this->sqlzavod.ids = :ids AND $this->sqlzavod.ido = osoby.ido ORDER BY $this->sqlzavod.id";
			$sth3 = $this->db->prepare($sql3);
			$sth3->execute(Array(':ids' => $data1->ids));
			$pocet_clenu = $sth3->rowCount(); //počet členů týmu, který potřebujeme na rowspan

			$z = 1;
			while($dbdata3 = $sth3->fetchObject()){
			    if($poradi == 1) $max_pocet_kol = $data1->pocet_kol;// nejvyšší počet kol pro počítání odstupů
			    
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
                $sql4 = "SELECT $this->sqlvysledky.day_time AS last_time FROM $this->sqlvysledky WHERE $this->sqlvysledky.ids = :ids AND $this->sqlvysledky.reader LIKE :reader  ORDER BY race_time DESC LIMIT 0,1";
                $sth4 = $this->db->prepare($sql4);
                $sth4->execute(Array(':ids' => $data1->ids,':reader' => 'START'));
                if($sth4->rowCount()){
                    $data4 = $sth4->fetchObject();
                }

			    if($z == 1){
				$str .= '<tr id="'.$data1->ids_alias.'">';
				$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$poradi.'</td>';
				$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$data1->ids_alias.'</td>';
				//$str .= '<td rowspan="'.$pocet_clenu.'"><a class="detail_ids" href="'.$hash_url.'vysledky">'.$data1->nazev_tymu.'</a></td>';
				$str .= '<td rowspan="'.$pocet_clenu.'"><a onclick="detail_ids('.$data1->ids_alias.','.$this->race_id.','.$this->race_year.')" href="'.$hash_url.'vysledky">'.$data1->nazev_tymu.'</a></td>';
				$str .= '<td><a onclick="detail_cipu('.$dbdata3->cip.','.$this->race_id.','.$this->race_year.')" href="'.$hash_url.'vysledky">'.$dbdata3->jmeno.'</a></td>';
				$str .= '<td class="text-center">'.$dbdata3->rocnik.'</td>';
				$str .= '<td class="text-center">'.$dbdata3->cip.'</td>';
				$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$data1->pocet_kol.'</td>';
				$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.($data1->pocet_kol * $this->delka_kola ).'</td>';
				//$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$data4->last_time.'</td>';
				if(isset($data4->last_time)){ //před prvním projetím cílem to bez téhle podmínky vyhazuje chybu, tak proto.. dá se to určutě řešit i jinak
				    $str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$data4->last_time.'</td>';
				}
				else{
				    $str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">&nbsp;</td>';
				}

				$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$data1->finish_time.'</td>';
				$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$distance_category.'</td>';
				$str .= '</tr>';
			    }
			    else{
				$str .= '<tr>';
				$str .= '<td><a onclick="detail_cipu('.$dbdata3->cip.','.$this->race_id.','.$this->race_year.')" href="'.$hash_url.'vysledky">'.$dbdata3->jmeno.'</a></td>';
				$str .= '<td class="text-center">'.$dbdata3->rocnik.'</td>';
				$str .= '<td class="text-center">'.$dbdata3->cip.'</td>';
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
	    $str .= '<table class="table table-bordered table-hove table_vysledky">';
	    $k = 1;
	    $sql1 = "SELECT tymy.nazev_tymu,$this->sqlvysledky.ids,$this->sqlvysledky.ids_alias,MAX($this->sqlvysledky.race_time) AS finish_time,COUNT($this->sqlvysledky.id) AS pocet_kol,$this->sqlkategorie.nazev_k AS nazev_kategorie FROM $this->sqlvysledky,$this->sqlzavod,$this->sqlkategorie,tymy "
		    . "WHERE race_time > '0' "
		    . "AND $this->sqlzavod.id_kategorie = :category_id  "
		    . "AND $this->sqlzavod.id_kategorie = $this->sqlkategorie.id_kategorie "
		    . "AND $this->sqlkategorie.poradi_podzavodu = '$this->event_order' "
		    . "AND $this->sqlzavod.cip = $this->sqlvysledky.cip "
		    . "AND $this->sqlzavod.tym = tymy.id_tymu "
		    . "AND $this->sqlvysledky.false_time IS NULL "
		    . "AND $this->sqlvysledky.lap_only IS NULL "
		    . "AND $this->sqlvysledky.reader LIKE 'CIL' "
		    . "GROUP BY $this->sqlzavod.ids "
		    . "ORDER BY pocet_kol DESC,finish_time ASC";
	    $sth1 =  $this->db->prepare($sql1);
	    $sth1->execute(Array(':category_id' => $category_id));
	    if($sth1->rowCount()){
		$str .= '<tr class="header"><th class="text-center">#</th><th class="text-center">St.č</th><th>Tým</th><th>Členové týmu</th><th class="text-center">Ročník</th><th class="text-center">St.č</th><th class="text-center">Kola</th><th class="text-center">Km</th><th class="text-center">Poslední start</th><th class="text-center">Celkový čas</th><th class="text-center">Odstup</th></tr>';
		$str .= $this->TableHeader($this->time_order,$this->event_order,$this->cislo_kategorie).'</tr>';
		$poradi = 1;
		while($data1 = $sth1->fetchObject()){
		    $sql3 = "SELECT CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,$this->sqlzavod.cip FROM osoby,$this->sqlzavod WHERE $this->sqlzavod.ids = :ids AND $this->sqlzavod.ido = osoby.ido ORDER BY $this->sqlzavod.id";
		    $sth3 = $this->db->prepare($sql3);
		    $sth3->execute(Array(':ids' => $data1->ids));
		    $pocet_clenu = $sth3->rowCount(); //počet členů týmu, který potřebujeme na rowspan
		    $z = 1;
            $sql4 = "SELECT $this->sqlvysledky.day_time AS last_time FROM $this->sqlvysledky WHERE $this->sqlvysledky.ids = :ids AND $this->sqlvysledky.reader LIKE :reader  ORDER BY race_time DESC LIMIT 0,1";
            $sth4 = $this->db->prepare($sql4);
            $sth4->execute(Array(':ids' => $data1->ids,':reader' => 'START'));
            if($sth4->rowCount()){
                $data4 = $sth4->fetchObject();
            }

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
				$str .= '<td rowspan="'.$pocet_clenu.'"><a onclick="detail_ids('.$data1->ids_alias.','.$this->race_id.','.$this->race_year.')" href="'.$hash_url.'vysledky">'.$data1->nazev_tymu.'</a></td>';
				$str .= '<td><a onclick="detail_cipu('.$dbdata3->cip.','.$this->race_id.','.$this->race_year.')" href="'.$hash_url.'vysledky">'.$dbdata3->jmeno.'</a></td>';
				$str .= '<td class="text-center">'.$dbdata3->rocnik.'</td>';
				$str .= '<td class="text-center">'.$dbdata3->cip.'</td>';
				$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$data1->pocet_kol.'</td>';
				$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.($data1->pocet_kol * $this->delka_kola ).'</td>';
				if(isset($data4->last_time)){ //před prvním projetím cílem to bez téhle podmínky vyhazuje chybu, tak proto.. dá se to určutě řešit i jinak
				    $str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$data4->last_time.'</td>';
				}
				else{
				    $str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">&nbsp;</td>';
				}
				$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$data1->finish_time.'</td>';
				$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$distance_category.'</td>';
				$str .= '</tr>';
			    }
			    else{
				$str .= '<tr>';
				$str .= '<td><a onclick="detail_cipu('.$dbdata3->cip.','.$this->race_id.','.$this->race_year.')" href="'.$hash_url.'vysledky">'.$dbdata3->jmeno.'</a></td>';
				$str .= '<td class="text-center">'.$dbdata3->rocnik.'</td>';
				$str .= '<td class="text-center">'.$dbdata3->cip.'</td>';
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
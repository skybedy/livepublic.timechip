<?php  
    /* varianta týmy, všichni jeden čip, Radegastova výzva,
     * ale hlavně výsledek je počet kol, navíc můžou natočit půlkolo na konci závodu, které se počítá
     * a samozřejmě čas 
     *   
     */
    // v jednom dotazu se vyberou týmy, počet kol, celkový čas....
    $hash_url = '#'.$this->race_year.'/'.$this->race_id.'/';
    //$sql1 = "SELECT tymy.nazev_tymu,$this->sqlvysledky.ids,$this->sqlvysledky.cip,$this->sqlvysledky.ids_alias,MAX($this->sqlvysledky.race_time) AS finish_time,SEC_TO_TIME(MAX(day_time_sec)-86400) AS last_day_time,MAX($this->sqlvysledky.lap_count) AS pocet_kol,MAX($this->sqlvysledky.time_order) AS time_order, $this->sqlkategorie.nazev_k AS nazev_kategorie FROM $this->sqlvysledky,tymy,$this->sqlzavod,$this->sqlkategorie WHERE race_time > 0 AND $this->sqlkategorie.id_zavodu = :race_id AND $this->sqlkategorie.poradi_podzavodu = :event_order AND $this->sqlzavod.ids = $this->sqlvysledky.ids AND $this->sqlzavod.tym = tymy.id_tymu AND $this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie AND $this->sqlvysledky.false_time IS NULL AND $this->sqlvysledky.lap_only IS NULL GROUP BY $this->sqlvysledky.ids ORDER BY pocet_kol DESC,finish_time ASC".$this->rows_limit;
    $sql1 = "SELECT tymy.nazev_tymu,$this->sqlvysledky.ids,$this->sqlvysledky.cip,$this->sqlvysledky.ids_alias,MAX($this->sqlvysledky.race_time) AS finish_time,MAX($this->sqlvysledky.lap_count) AS pocet_kol,MAX($this->sqlvysledky.time_order) AS time_order, $this->sqlkategorie.nazev_k AS nazev_kategorie FROM $this->sqlvysledky,tymy,$this->sqlzavod,$this->sqlkategorie WHERE race_time > 0 AND $this->sqlkategorie.id_zavodu = :race_id AND $this->sqlkategorie.poradi_podzavodu = :event_order AND $this->sqlzavod.ids = $this->sqlvysledky.ids AND $this->sqlzavod.tym = tymy.id_tymu AND $this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie AND $this->sqlvysledky.false_time IS NULL AND $this->sqlvysledky.lap_only IS NULL GROUP BY $this->sqlvysledky.ids ORDER BY pocet_kol DESC,finish_time ASC".$this->rows_limit;
    $sth1 =  $this->db->prepare($sql1);
    $sth1->execute(Array(':race_id' => $this->race_id,':event_order' => $this->event_order));
    if($sth1->rowCount()){
	$str .= '<h4 class="headline-results">'.$this->race_name.', výsledky bez rozdílu kategorií</h4>';
	$str .= '<table class="table table-bordered table_vysledky">';
	$str .= '<thead><tr class="header"><th class="text-center">#</th><th class="text-center">St.č</th><th>Tým</th><th class="text-center">Kategorie</th><th>Členové týmu</th><th class="text-center">Ročník</th><th class="text-center">Počet kol</th><th class="text-center">Počet km</th><th class="text-center">Poslední doběh</th><th class="text-center">Celkový čas</th><th class="text-center">Odstup</th>';
	$str .= '</tr></thead><tbody>';
	$poradi = 1;
	while($data1 = $sth1->fetchObject()){ //začneme vypisovat první dotaz
	    if($poradi == 1) $max_pocet_kol = $data1->pocet_kol;// nejvyšší počet kol pro počítání odstupů
	    if($poradi == 1) $max_time_order = $data1->time_order;// nejvyšší počet časů pro počítání odstupů
	    //$sql2 = "SELECT $this->sqlvysledky.distance_overall FROM $this->sqlvysledky WHERE $this->sqlvysledky.ids = :ids AND time_order = :max_time_order"; //vezmeme odstupy, v tomto případě ve zvláštním dotaze
	    $sql2 = "SELECT $this->sqlvysledky.distance_overall,$this->sqlvysledky.day_time AS last_day_time FROM $this->sqlvysledky WHERE $this->sqlvysledky.ids = $data1->ids AND lap_count = '$max_pocet_kol'"; //vezmeme odstupy, v tomto případě ve zvláštním dotaze
	    $sth2 = $this->db->prepare($sql2);
	    //$sth2->execute(Array(':ids' => $data1->ids,':max_time_order' => $max_time_order));
	    $sth2->execute();
	    if($sth2->rowCount()){ //pokud je někdo se stejným, tzn. nejvyšším počttem kol, použijeme časový odstup
		$dbdata2 = $sth2->fetchObject();
		($dbdata2->distance_overall != '00:00:00.00') ? ($distance_overall = $dbdata2->distance_overall) : ($distance_overall = '-'); //pokud nemá odstup, tak je první a bude tam pomlčka
	    }
	    else{ // pokud ne, spočítáme odstup v kolech
		 $distance_overall = $data1->pocet_kol - $max_pocet_kol; 
		if($distance_overall == -1){
		    $kola = 'kolo';
		}
		elseif(($distance_overall < -1 AND $distance_overall > -5) OR $distance_overall > -1){
		    $kola = 'kola';
		}
		else{
		    $kola = 'kol';
		}
		$distance_overall = $distance_overall.' '.$kola;
	    }
	    // tady vybereme jednotlivé členy týmu
	    $sql3 = "SELECT CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,$this->sqlzavod.cip FROM osoby,$this->sqlzavod WHERE $this->sqlzavod.ids = :ids AND $this->sqlzavod.ido = osoby.ido ORDER BY $this->sqlzavod.id";
		$sth3 = $this->db->prepare($sql3);
		$sth3->execute(Array(':ids' => $data1->ids));
		$pocet_clenu = $sth3->rowCount(); //počet členů týmu, který potřebujeme na rowspan
		$k = 1;
		while($dbdata3 = $sth3->fetchObject()){
		    if($k == 1){
			$str .= '<tr id="'.$data1->ids_alias.'">';
			$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$poradi.'</td>';
			$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$data1->ids_alias.'</td>';
			$str .= '<td rowspan="'.$pocet_clenu.'"><a onclick="detail_ids_rv('.$data1->ids.','.$this->race_id.','.$this->race_year.')" href="'.$hash_url.'vysledky">'.$data1->nazev_tymu.'</a></td>';
			$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$data1->nazev_kategorie.'</td>';
                        $str .= '<td><a style="text-decoration:none"  href="'.$hash_url.'vysledky" onclick="detail_cipu_rv('.$dbdata3->cip.','.$this->race_id.','.$this->race_year.')">'.$dbdata3->jmeno.'</a></td>';
			$str .= '<td class="text-center">'.$dbdata3->rocnik.'</td>';
			$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$data1->pocet_kol.'</td>';
			$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.($data1->pocet_kol * $this->delka_kola ).'</td>';  
                        $str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$dbdata2->last_day_time.'</td>'; 
			$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$data1->finish_time.'</td>';
			$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$distance_overall.'</td>';
			$str .= '</tr>';
		    }
		    else{
			$str .= '<tr>';
                        $str .= '<td><a style="text-decoration:none"  href="'.$hash_url.'vysledky" onclick="detail_cipu_rv('.$dbdata3->cip.','.$this->race_id.','.$this->race_year.')">'.$dbdata3->jmeno.'</a></td>';
			$str .= '<td class="text-center">'.$dbdata3->rocnik.'</td>';
			$str .= '</tr>';
		    }
		    $k++;
		}
		$poradi++;
	}
	$str .= '</tbody></table>';
    }
?>
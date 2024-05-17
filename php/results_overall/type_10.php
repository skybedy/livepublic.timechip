<?php  
    /*
     * varianta týmy, použito 100 pro Adru, počet kol + celkový čas
     * 
     */
     

    $hash_url = '#'.$this->race_year.'/'.$this->race_id.'/';
    $sql1 = "SELECT tymy.nazev_tymu,$this->sqlvysledky.ids,$this->sqlvysledky.ids_alias,MAX($this->sqlvysledky.race_time) AS finish_time,COUNT($this->sqlvysledky.id) AS pocet_kol,$this->sqlkategorie.nazev_k AS nazev_kategorie FROM $this->sqlvysledky,tymy,$this->sqlzavod,$this->sqlkategorie WHERE $this->sqlvysledky.race_time > 0 AND $this->sqlkategorie.poradi_podzavodu = :event_order AND $this->sqlzavod.cip = $this->sqlvysledky.cip AND $this->sqlzavod.tym = tymy.id_tymu AND $this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie AND $this->sqlvysledky.false_time IS NULL AND $this->sqlvysledky.lap_only IS NULL GROUP BY $this->sqlvysledky.ids ORDER BY pocet_kol DESC,finish_time ASC";
    //echo $sql1;
    $sth1 =  $this->db->prepare($sql1);
    $sth1->execute(Array(':event_order' => $this->event_order));
    if($sth1->rowCount()){
	$str .= '<h4 class="headline-results">'.$this->race_name.', výsledky bez rozdílu kategorií</h4>';
	$str .= '<table class="table table-bordered table-hover table_vysledky">';
	$str .= '<thead><tr class="header"><th class="text-center">#</th><th>Tým</th><th>Kategorie</th><th>Člen týmu</th><th class="text-center">Ročník</th><th class="text-center">St.č</th><th class="text-center">Počet kol</th><th class="text-center">Celkový čas</th><th class="text-center">Odstup</th>';
	$str .= '</tr></thead><tbody>';
	$poradi = 1;
	while($data1 = $sth1->fetchObject()){ //začneme vypisovat první dotaz
	    if($poradi == 1) $max_pocet_kol = $data1->pocet_kol;// nejvyšší počet časů pro počítání odstupů
	    $sql2 = "SELECT $this->sqlvysledky.distance_overall FROM $this->sqlvysledky WHERE $this->sqlvysledky.ids = :ids AND time_order = :max_pocet_kol"; //vezmeme odstupy, v tomto případě ve zvláštním dotaze
	    $sth2 = $this->db->prepare($sql2);
	    $sth2->execute(Array(':ids' => $data1->ids,':max_pocet_kol' => $max_pocet_kol));
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
			$str .= '<td rowspan="'.$pocet_clenu.'"><a onclick="detail_ids_2('.$data1->ids_alias.','.$this->race_id.','.$this->race_year.')" href="'.$hash_url.'vysledky">'.$data1->nazev_tymu.'</a></td>';
			$str .= '<td rowspan="'.$pocet_clenu.'">'.$data1->nazev_kategorie.'</td>';
			$str .= '<td><a onclick="detail_cipu_2('.$dbdata3->cip.','.$this->race_id.','.$this->race_year.')" href="'.$hash_url.'vysledky">'.$dbdata3->jmeno.'</a></td>';
			$str .= '<td class="text-center">'.$dbdata3->rocnik.'</td>';
			$str .= '<td class="text-center">'.$dbdata3->cip.'</td>';
			$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$data1->pocet_kol.'</td>';
			$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$data1->finish_time.'</td>';
			$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$distance_overall.'</td>';
			$str .= '</tr>';
		    }
		    else{
			$str .= '<tr>';
			$str .= '<td><a onclick="detail_cipu_2('.$dbdata3->cip.','.$this->race_id.','.$this->race_year.')" href="'.$hash_url.'vysledky">'.$dbdata3->jmeno.'</a></td>';
			$str .= '<td class="text-center">'.$dbdata3->rocnik.'</td>';
			$str .= '<td class="text-center">'.$dbdata3->cip.'</td>';
			$str .= '</tr>';
		    }
		    $k++;
		}
		$poradi++;
	}
	$str .= '</tbody></table>';
    }
?>
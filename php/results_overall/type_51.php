<?php  
    /*
     * varianta štafety, Zátopek
     * 
     */
     

    $hash_url = '#'.$this->race_year.'/'.$this->race_id.'/';
    $sql1 = "SELECT tymy.nazev_tymu,$this->sqlvysledky.ids,$this->sqlvysledky.ids_alias,MAX($this->sqlvysledky.race_time) AS finish_time,MAX($this->sqlvysledky.race_time_sec) AS finish_time_sec,$this->sqlkategorie.nazev_k AS nazev_kategorie FROM $this->sqlvysledky,tymy,$this->sqlzavod,$this->sqlkategorie WHERE $this->sqlvysledky.race_time > 0 AND $this->sqlkategorie.poradi_podzavodu = :event_order AND $this->sqlzavod.cip = $this->sqlvysledky.cip AND $this->sqlzavod.tym = tymy.id_tymu AND $this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie AND $this->sqlvysledky.false_time IS NULL AND $this->sqlvysledky.lap_only IS NULL GROUP BY $this->sqlvysledky.ids ORDER BY finish_time_sec ASC";
    //echo $sql1;
    $sth1 =  $this->db->prepare($sql1);
    $sth1->execute(Array(':event_order' => $this->event_order));
    if($sth1->rowCount()){
	$str .= '<h4 class="headline-results">'.$this->race_name.', výsledky bez rozdílu kategorií</h4>';
	$str .= '<table class="table table-bordered table-hover table_vysledky">';
	$str .= '<thead><tr class="header"><th class="text-center">#</th><th class="text-center">Stč</th><th>Štafeta</th><th>Kategorie</th><th class="text-center">Celkový čas</th><th class="text-center">Odstup</th>';
	$str .= '</tr></thead><tbody>';
	$poradi = 1;
	while($data1 = $sth1->fetchObject()){ //začneme vypisovat první dotaz
	    $sql2 = "SELECT $this->sqlvysledky.distance_overall FROM $this->sqlvysledky WHERE $this->sqlvysledky.ids = :ids AND time_order = :time_count"; //vezmeme odstupy, v tomto případě ve zvláštním dotaze
	    $sth2 = $this->db->prepare($sql2);
	    $sth2->execute(Array(':ids' => $data1->ids,':time_count' => $this->time_count));
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
	$sql3 = "SELECT CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,$this->sqlzavod.cip,$this->sqlvysledky.lap_time FROM osoby,$this->sqlzavod,$this->sqlvysledky WHERE $this->sqlzavod.ids = :ids AND $this->sqlzavod.ido = osoby.ido AND $this->sqlvysledky.cip = $this->sqlzavod.cip AND $this->sqlvysledky.false_time IS NULL AND $this->sqlvysledky.lap_only IS NULL ORDER BY $this->sqlzavod.id";
		$sth3 = $this->db->prepare($sql3);
		$sth3->execute(Array(':ids' => $data1->ids));
		$pocet_clenu = $sth3->rowCount(); //počet členů týmu, který potřebujeme na rowspan
		$k = 1;
		while($dbdata3 = $sth3->fetchObject()){      
		    if($k == 1){
			$str .= '<tr>';
			$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$poradi.'</td>';
			$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.($data1->ids - 1000).'</td>';
			$str .= '<td rowspan="'.$pocet_clenu.'">'.$data1->nazev_tymu.'</td>';
			$str .= '<td rowspan="'.$pocet_clenu.'">'.$data1->nazev_kategorie.'</td>';
		//	$str .= '<td>'   .$dbdata3->jmeno.'</td>';
		//	$str .= '<td class="text-center">'.$dbdata3->lap_time.'</td>';
			//$str .= '<td class="text-center">'.$dbdata3->cip.'</td>';
			$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$data1->finish_time.'</td>';
			$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$distance_overall.'</td>';
			$str .= '</tr>';
		    }
		    else{
			$str .= '<tr>';
			$str .= '<td>'.$dbdata3->jmeno.'</td>';
			$str .= '<td class="text-center">'.$dbdata3->lap_time.'</td>';
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
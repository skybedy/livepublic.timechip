<?php  
    /* varianta týmy, bez počtu kol
     *  je zde použito $this->sqlzavod.cip = $this->sqlvysledky.cip a asi by to mělo být vyzkušeno všude
     * původně bylo použito $this->sqlzavod.ids = $this->sqlvysledky.ids, ale funkce SUM ty časy násobila 2x 
     */
     

    $sql1 = "SELECT tymy.nazev_tymu,COUNT($this->sqlvysledky.id) AS pocet,$this->sqlvysledky.ids,$this->sqlvysledky.ids_alias,SEC_TO_TIME(SUM($this->sqlvysledky.race_time_sec)) AS finish_time, SUM($this->sqlvysledky.race_time_sec) AS finish_time_sec,$this->sqlkategorie.nazev_k AS nazev_kategorie FROM $this->sqlvysledky,tymy,$this->sqlzavod,$this->sqlkategorie WHERE race_time > 0 AND $this->sqlkategorie.poradi_podzavodu = :event_order AND $this->sqlzavod.cip = $this->sqlvysledky.cip AND $this->sqlzavod.tym = tymy.id_tymu AND $this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie AND $this->sqlvysledky.false_time IS NULL AND $this->sqlvysledky.lap_only IS NULL GROUP BY $this->sqlvysledky.ids ORDER BY finish_time ASC";
    $sth1 =  $this->db->prepare($sql1);
    $sth1->execute(Array(':event_order' => $this->event_order));
    if($sth1->rowCount()){
	$str .= '<h4 class="headline-results">'.$this->race_name.', výsledky bez rozdílu kategorií</h4>';
	$str .= '<table class="table table-bordered table-hover table_vysledky">';
	$str .= '<thead><tr class="header"><th class="text-center">#</th><th class="text-center">St.č</th><th>Členové týmu</th><th class="text-center">Ročník</th><th class="text-center">Čas</th><th>Příslušnost</th><th class="text-center">Kategorie</th><th class="text-center">Celkový čas</th><th class="text-center">Odstup</th>';
	$str .= '</tr></thead><tbody>';
	$poradi = 1;
	while($data1 = $sth1->fetchObject()){ //začneme vypisovat první dotaz
	    // tady vybereme jednotlivé členy týmu
	
	if($data1->pocet < $this->time_count){ // pokud má tým méně časů, než má, tak se nezobrazí
	    continue 1;
	}    
	if($poradi == 1){
	    $best_time = $data1->finish_time_sec;  
	}
	$distance = $data1->finish_time_sec - $best_time;
	//echo $distance."\n";
	$pole_casu = explode(".",$distance);
	if(isset($pole_casu[1])){
	    $distance_time = gmdate("H:i:s",$pole_casu[0]).'.'.$pole_casu[1];
	}
	else{
	    $distance_time = gmdate("H:i:s",$pole_casu[0]).'.00';
	}
	    $sql3 = "SELECT CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,$this->sqlvysledky.race_time FROM osoby,$this->sqlzavod,$this->sqlvysledky WHERE $this->sqlzavod.ids = :ids AND $this->sqlzavod.ido = osoby.ido AND $this->sqlzavod.cip = $this->sqlvysledky.cip AND $this->sqlvysledky.false_time IS NULL ORDER BY $this->sqlzavod.id";
	    $sth3 = $this->db->prepare($sql3);
	    $sth3->execute(Array(':ids' => $data1->ids));
	    $pocet_clenu = $sth3->rowCount(); //počet členů týmu, který potřebujeme na rowspan
	    $k = 1;
	    while($dbdata3 = $sth3->fetchObject()){
		if($k == 1){
		    $str .= '<tr>';
		    $str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$poradi.'</td>';
		    $str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$data1->ids_alias.'</td>';
		    $str .= '<td>'.$dbdata3->jmeno.'</td>';
		    $str .= '<td class="text-center">'.$dbdata3->rocnik.'</td>';
		    $str .= '<td class="text-center">'.$dbdata3->race_time.'</td>';
		    $str .= '<td rowspan="'.$pocet_clenu.'">'.$data1->nazev_tymu.'</td>';
		    $str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$data1->nazev_kategorie.'</td>';
		    $str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$data1->finish_time.'</td>';
		    $str .= ($distance_time != '00:00:00.00') ?  ('<td rowspan="'.$pocet_clenu.'" class="text-center">'.$distance_time.'</td>') : ('<td rowspan="'.$pocet_clenu.'" class="text-center">&nbsp;</td>');
		    $str .= '</tr>';
		}
		else{
		    $str .= '<tr>';
		    $str .= '<td>'.$dbdata3->jmeno.'</td>';
		    $str .= '<td class="text-center">'.$dbdata3->rocnik.'</td>';
                    $str .= '<td class="text-center">'.$dbdata3->race_time.'</td>';
		    $str .= '</tr>';
		}
		$k++;
	    }
	    $poradi++;
	}
	$str .= '</tbody></table>';
    }
?>
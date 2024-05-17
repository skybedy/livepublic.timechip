<?php  
    /* týmy, teribear
      */

     $castka_na_kolo = 20;

    $hash_url = '#'.$this->race_year.'/'.$this->race_id.'/';
    if($this->rows_limit_number){
	 $rows_limit_number = $this->rows_limit_number;
     }
     else{
	 $rows_limit_number = 1000;
     }

     
    $tym = Array(); 
    $sql1 = "SELECT tym_2 FROM $this->sqlzavod WHERE poradi_podzavodu_2 = :event_order GROUP BY tym_2 ORDER BY tym_2";
     $sth1 = $this->db->prepare($sql1);
     $sth1->execute(Array(':event_order' => $this->event_order));
     if($sth1->rowCount()){
	 while($dbdata1 = $sth1->fetchObject()){
	    //$sql2 = "SELECT COUNT($this->sqlvysledky.id) AS laps_count FROM $this->sqlvysledky,$this->sqlzavod WHERE $this->sqlvysledky.cip = $this->sqlzavod.cip AND $this->sqlzavod.tym_2 = $dbdata1->tym_2 AND false_time IS NULL";
	    $sql2 = "SELECT COUNT($this->sqlvysledky.id) AS laps_count FROM $this->sqlvysledky  WHERE $this->sqlvysledky.id_tymu = :tym_2 AND false_time IS NULL";
	    //echo $sql2;
	    $sth2 = $this->db->prepare($sql2);
	    $sth2->execute(Array(':tym_2' => $dbdata1->tym_2));
	    if($sth2->rowCount()){
		while($dbdata2 = $sth2->fetchObject()){
		    $tym[$dbdata1->tym_2] = $dbdata2->laps_count;
		}
	    }
	 }
	 
	 
	 
	 if($tym){
	    arsort($tym,SORT_NUMERIC);
	    $str .= '<h4 class="headline-results">'.$this->race_name.', výsledky týmů</h4>';
	    $str .= '<table class="table table-bordered table-hover table_vysledky">';
	    $str .= '<thead><tr class="header"><th class="text-center">Poř</th><th>Název týmu</th><th class="text-center">Počet kol</th><th class="text-center">Vzdálenost</th><th class="text-center">Částka</th></tr></thead><tbody>';
	    $poradi = 1;
	    foreach($tym as $key => $value){
		$sql3 = "SELECT tymy.nazev_tymu FROM tymy,$this->sqlzavod WHERE tymy.id_tymu = :id_tymu AND tymy.id_tymu = $this->sqlzavod.tym_2 GROUP BY tymy.nazev_tymu";
		//$str .= $sql3;
		$sth3 = $this->db->prepare($sql3);
		$sth3->execute(Array(':id_tymu' => $key));
		if($sth3->rowCount()){
                    if($key == 10117){
                        continue;
                    }
		    $dbdata3 = $sth3->fetchObject(); 
                    $vzdalenost = $value * $this->delka_kola;
                    $castka_celkem = $vzdalenost * $castka_na_kolo; 
		    $str .= '<tr><td class="text-center">'.$poradi.'</td><td><a onclick="detail_tymu_teribear('.$key.','.$this->race_id.','.$this->race_year.')" href="'.$hash_url.'vysledky">'.$dbdata3->nazev_tymu.'</a></td><td class="text-center">'.$value.'</td><td class="text-center">'.$vzdalenost.' Km</td><td class="text-center">'.$castka_celkem.' Kč</td></tr>';
		}
		    $poradi++; 

	    }
	 $str .= '</tbody></table>';    
	 }
	 

     }
?>
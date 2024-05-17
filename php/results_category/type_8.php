<?php  
    /* varianta týmy
     * součet časů tří lidí
     */
    
   //$racer_count = 3;
    
    if($category_id == 'all'){
	$str .= '';
    }
    else{ // každá kategorie zvlášť
	$tym = Array(); 
	$sql1 = "SELECT tym_2 FROM $this->sqlzavod WHERE id_kategorie_2 = '$category_id' GROUP BY tym_2 ORDER BY id DESC";
	 $sth1 = $this->db->prepare($sql1);
	 $sth1->execute(Array(':event_order' => $this->event_order));
	 if($sth1->rowCount()){
	     while($dbdata1 = $sth1->fetchObject()){
		//v tomto dotazu musí být tzv. own alais,což je "AS SUBQUERY" 
		$sql2 = "SELECT COUNT(race_time_sec) AS row_count,SUM(race_time_sec) as finish_time FROM (SELECT $this->sqlvysledky.race_time_sec FROM $this->sqlvysledky,$this->sqlzavod WHERE $this->sqlvysledky.cip = $this->sqlzavod.cip AND $this->sqlzavod.tym_2 = :tym_2 AND false_time IS NULL ORDER BY $this->sqlvysledky.race_time_sec ASC LIMIT 0,$this->team_racer_count) AS subquery";
		//echo $sql2;
                $sth2 = $this->db->prepare($sql2);
		$sth2->execute(Array(':tym_2' => $dbdata1->tym_2));
		if($sth2->rowCount()){
		    while($dbdata2 = $sth2->fetchObject()){
			if($dbdata2->row_count > ($this->team_racer_count - 1)){
			    $tym[$dbdata1->tym_2] = $dbdata2->finish_time;
			}
		    }
		}
	     }

	     if($tym){
		asort($tym,SORT_NUMERIC);
		$str .= '<h4 class="headline-results">'.$this->race_name.', výsledky týmů</h4>';
		$str .= '<table class="table table-bordered table-hover table_vysledky">';
		$str .= '<thead><tr class="header"><th class="text-center">#</th><th>Název týmu</th><th>Členové týmu</th><th class="text-center">St.č</th><th class="text-center">Ročník</th><th class="text-center">Čas</th><th class="text-center">Celkový čas</th><th class="text-center">Odstup</th></tr></thead><tbody>';
		$poradi = 1;
		foreach($tym as $key => $value){
		    if($poradi == 1) $best_time = $value;
		    $distance_time = $this->DynamicDistances($poradi,$value,$best_time);
		    $sql3 = "SELECT tymy.nazev_tymu FROM tymy WHERE id_tymu = :id_tymu";
		    $sth3 = $this->db->prepare($sql3);
		    $sth3->execute(Array(':id_tymu' => $key));
		    if($sth3->rowCount()){
			$dbdata3 = $sth3->fetchObject();
			$sql4 = "SELECT $this->sqlzavod.ids_alias,CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,$this->sqlvysledky.race_time FROM $this->sqlvysledky,$this->sqlzavod,osoby WHERE $this->sqlvysledky.cip = $this->sqlzavod.cip AND $this->sqlzavod.tym_2 = :tym_2 AND $this->sqlzavod.ido = osoby.ido AND false_time IS NULL ORDER BY $this->sqlvysledky.race_time ASC LIMIT 0,$this->team_racer_count";
			$sth4 = $this->db->prepare($sql4);
			$sth4->execute(Array(':tym_2' => $key));
			if($sth4->rowCount()){
			    $pocet_clenu = $sth4->rowCount();
			    $k = 1;
			    while($dbdata4 = $sth4->fetchObject()){
				if($k == 1){
				    $str .= '<tr>';
				    $str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$poradi.'</td>';
				    $str .= '<td rowspan="'.$pocet_clenu.'">'.$dbdata3->nazev_tymu.'</td>';
				    $str .= '<td>'.$dbdata4->jmeno.'</td>';
				    $str .= '<td class="text-center">'.$dbdata4->ids_alias.'</td>';
				    $str .= '<td class="text-center">'.$dbdata4->rocnik.'</td>';
				    $str .= '<td class="text-center">'.$dbdata4->race_time.'</td>';
				    $str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$this->SecToTime($value).'</td>';
				    $str .= ($distance_time != '00:00:00.00') ?  ('<td rowspan="'.$pocet_clenu.'" class="text-center">'.$distance_time.'</td>') : ('<td rowspan="'.$pocet_clenu.'" class="text-center">-</td>');
				    $str .= '</tr>';
				}
				else{
				    $str .= '<tr>';
				    $str .= '<td>'.$dbdata4->jmeno.'</td>';
				    $str .= '<td class="text-center">'.$dbdata4->ids_alias.'</td>';
				    $str .= '<td class="text-center">'.$dbdata4->rocnik.'</td>';
				    $str .= '<td class="text-center">'.$dbdata4->race_time.'</td>';
				    $str .= '</tr>';
				}
			    $k++;
			    }
			}
		    }
		    $poradi++; 
		}
	     $str .= '</tbody></table>';    
	     }
	}
    }
?>
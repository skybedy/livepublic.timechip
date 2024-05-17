<?php  
    /* varianta týmy, použito na lahocup poprvé, pak na týmy obecně
     * součet časů lidí daných počtem v db tabulce podzávody ve sloupci pocet_clenu_tymu
     * zatím je to uděláno tak, že se nejdříve vybere seznam relevantních týmů..
     */
    
     
     /*
      * narozdíl od ostatních typů výsledků, kde se omezení počtu dělá v sql dotazu, tady se to dělá podmínkou v cyklu a proto tahle věc
      * krásné to není, ale funguje to, v budoucnu určitě zařadit do oprav
      */

$file =  "C://webapps/raceadmin.timechip/data/export/export_tandem_$this->race_code.csv";
$csv = "poradi;startovniCisla;vysledek;zavod;serie;id;jmenoTymu\r\n";
$serie = SERIE;


    if($this->rows_limit_number){
	 $rows_limit_number = $this->rows_limit_number;
     }
     else{
	 $rows_limit_number = 1000;
     }

    $sql = "SELECT pocet_casu FROM $this->sqlpodzavody WHERE id_zavodu = :race_id AND poradi_podzavodu = :event_order";
    $sth = $this->db->prepare($sql);
    $sth->execute(Array(':race_id' => $this->race_id,':event_order' => $this->event_order));
    $dbdata = $sth->fetchObject();
    
    $time_order = $dbdata->pocet_casu;
    
    
    
    
    $tym = Array(); 
    $sql1 = "SELECT tym_2 FROM $this->sqlzavod WHERE poradi_podzavodu_2 = $this->event_order GROUP BY tym_2 ORDER BY id DESC";
     $sth1 = $this->db->prepare($sql1);
     $sth1->execute(Array(':event_order' => $this->event_order));
     if($sth1->rowCount()){
	 while($dbdata1 = $sth1->fetchObject()){
	    //v tomto dotazu musí být tzv. own alais,což je "AS SUBQUERY" 
	    $sql2 = "SELECT COUNT(race_time_sec) AS row_count,SUM(race_time_sec) as finish_time FROM (SELECT $this->sqlvysledky.race_time_sec FROM $this->sqlvysledky,$this->sqlzavod WHERE $this->sqlvysledky.cip = $this->sqlzavod.cip AND time_order = $time_order AND $this->sqlzavod.tym_2 = $dbdata1->tym_2 AND false_time IS NULL ORDER BY $this->sqlvysledky.race_time_sec ASC LIMIT 0,$this->team_racer_count) AS subquery";
	    //echo $sql2."\n";
            $sth2 = $this->db->prepare($sql2);
	    $sth2->execute(Array(':tym_2' => $dbdata1->tym_2,':time_order' => $time_order));
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
	    $str .= '<h4 class="headline-results">'.$this->race_name.', výsledky týmů, '.$this->event_name.'</h4>';
	    $str .= '<table id="table2excel" class="table table-bordered table_vysledky">';
	    $str .= '<thead><tr class="header"><th class="text-center">#</th><th class="text-left">Název týmu</th><th>Kategorie</th><th class="text-left">Členové</th><th class="text-center">St.č</th><th class="text-center">Ročník</th><th class="text-center">Čas</th><th class="text-center">Poř</th><th class="text-center">Celkový čas</th><th class="text-center">Odstup</th></tr></thead><tbody>';
	    $poradi = 1;
	    foreach($tym as $key => $value){
		$celkovy_cas = $this->SecToTime($value);
		if($poradi == 1) $best_time = $value;
		if($poradi <= $rows_limit_number){
		    $distance_time = $this->DynamicDistances($poradi,$value,$best_time);
		    $sql3 = "SELECT tymy.nazev_tymu,tymy.id_tymu,tymy.id_behej_lesy,$this->sqlkategorie.nazev_k AS kategorie FROM tymy,$this->sqlkategorie,$this->sqlzavod WHERE tymy.id_tymu = '$key' AND tymy.id_tymu = $this->sqlzavod.tym_2 AND $this->sqlzavod.id_kategorie_2 = $this->sqlkategorie.id_kategorie GROUP BY tymy.nazev_tymu";
                    
		   //$str .= $sql3."<br>";
		    $sth3 = $this->db->prepare($sql3);
		    $sth3->execute(Array(':id_tymu' => $key));
		    if($sth3->rowCount()){
			$dbdata3 = $sth3->fetchObject();
			$sql4 = "SELECT $this->sqlzavod.ids_alias,CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,$this->sqlvysledky.race_time,$this->sqlvysledky.rank_overall FROM $this->sqlvysledky,$this->sqlzavod,osoby WHERE $this->sqlvysledky.cip = $this->sqlzavod.cip AND $this->sqlzavod.tym_2 = $key AND $this->sqlzavod.ido = osoby.ido AND time_order = $time_order AND false_time IS NULL ORDER BY $this->sqlvysledky.race_time ASC LIMIT 0,$this->team_racer_count";
			//echo $sql4."<br>";
                        $sth4 = $this->db->prepare($sql4);
			$sth4->execute(Array(':tym_2' => $key,':time_order' => $time_order));
			if($sth4->rowCount()){
			    $pocet_clenu = $sth4->rowCount();
			    $k = 1;
			    while($dbdata4 = $sth4->fetchObject()){
				if($k == 1){
				    $str .= '<tr>';
				    $str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$poradi.'</td>';
				    $str .= '<td rowspan="'.$pocet_clenu.'">'.$dbdata3->nazev_tymu.'</td>';
				    $str .= '<td rowspan="'.$pocet_clenu.'">'.$dbdata3->kategorie.'</td>';
				    $str .= '<td>'.$dbdata4->jmeno.'</td>';
				    $str .= '<td class="text-center">'.$dbdata4->ids_alias.'</td>';
				    $str .= '<td class="text-center">'.$dbdata4->rocnik.'</td>';
				    $str .= '<td class="text-center">'.$dbdata4->race_time.'</td>';
				    $str .= '<td class="text-center">'.$dbdata4->rank_overall.'</td>';
				    $str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$celkovy_cas.'</td>';
				    $str .= ($distance_time != '00:00:00.00') ?  ('<td rowspan="'.$pocet_clenu.'" class="text-center">'.$distance_time.'</td>') : ('<td rowspan="'.$pocet_clenu.'" class="text-center">-</td>');
				    $str .= '</tr>';
				    $csv .= "$poradi;";
				    $csv .= $dbdata4->ids_alias.","; 
				}
				else{
				    $str .= '<tr>';
				    $str .= '<td>'.$dbdata4->jmeno.'</td>';
				    $str .= '<td class="text-center">'.$dbdata4->ids_alias.'</td>';
				    $str .= '<td class="text-center">'.$dbdata4->rocnik.'</td>';
				    $str .= '<td class="text-center">'.$dbdata4->race_time.'</td>';
				    $str .= '<td class="text-center">'.$dbdata4->rank_overall.'</td>';
				    $str .= '</tr>';
				    if($k <= $pocet_clenu - 1){
					$csv .= $dbdata4->ids_alias.","; 
				    }
				    else{
					$csv .= $dbdata4->ids_alias.";"; 
				    }
				}
				
				
			    $k++;
			    }
			    $csv .= substr($celkovy_cas,0,-3).";";
			    $csv .= "tandem;";
			    $csv .= $serie.";";
			    $csv .= $dbdata3->id_behej_lesy.";";
			    $csv .= $dbdata3->nazev_tymu."\r\n";
			}
		    }
		    $poradi++; 

		}
	    }
	    
	    if(EXPORT_CSV){
		$fp = fopen ($file,"w+");
		if(!fwrite($fp,$csv)){
		    $str .= '<p>Nějaký problém s uložením csv</p>';
		}
	    }

	    
	    
	    
	 $str .= '</tbody></table>';    
	 }

     }
?>
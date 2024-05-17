<?php
	    $str = '';
	    $event_order = isset($_GET['event_order']) ? $_GET['event_order'] : 1; 
	    $time_order = isset($_GET['time_order']) ? $_GET['time_order'] : $this->MaxTimeOrder(); 
	    $time_order = isset($_GET['time_order']) ? $_GET['time_order'] : 1; 
	    $gender = isset($_GET['gender']) ? $_GET['gender'] : 'all'; 
	    //$colspan = 4 + $this->time_count;
	    $colspan = 4;
	    if(isset($_GET['time_order'])) $time_order = $_GET['time_order'];
	    if(isset($_GET['id_event'])) $id_event = $_GET['id_event'];
	    $gender_array = Array('M' => 'Muži','Z' => 'Ženy');
	    if($gender == 'all'){
		$str = '<h4 class="headline-results">'.$this->race_name.$this->event_name.', výsledky Muži/Ženy</h4>';
		$str .= '<table  id="table2excel" class="table table-striped table-bordered table-hover table_vysledky">';
		$k = 1; 
		foreach($gender_array as $key => $gender){
		    // 18.6.15 $sql1 = "SELECT $this->sqlvysledky.ids,$this->sqlvysledky.ids_alias,MAX($this->sqlvysledky.race_time) AS cilovy_cas,CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,osoby.psc AS stat,$this->sqlkategorie.kod_k AS nazev_kategorie,tymy.nazev_tymu FROM $this->sqlvysledky,osoby,$this->sqlzavod,$this->sqlkategorie,tymy WHERE race_time > '0' AND $this->sqlvysledky.time_order = :time_order AND $this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie AND $this->sqlkategorie.poradi_podzavodu = :id_event AND osoby.pohlavi = :gender AND $this->sqlzavod.ids = $this->sqlvysledky.ids AND $this->sqlzavod.ido = osoby.ido AND tymy.id_tymu = $this->sqlzavod.id_tymu AND false_time IS NULL GROUP BY ids ORDER BY cilovy_cas ASC".$this->rows_limit;
		   
		    $sql1 = "SELECT $this->sqlvysledky.ids,$this->sqlvysledky.cip,$this->sqlvysledky.ids_alias,MAX($this->sqlvysledky.race_time) AS cilovy_cas,CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,osoby.psc AS stat,$this->sqlkategorie.kod_k AS nazev_kategorie,tymy.nazev_tymu FROM $this->sqlvysledky,osoby,$this->sqlzavod,$this->sqlkategorie,tymy WHERE race_time > '0' AND $this->sqlvysledky.time_order = :time_order AND $this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie AND $this->sqlkategorie.poradi_podzavodu = :id_event AND osoby.pohlavi = :gender AND $this->sqlzavod.cip = $this->sqlvysledky.cip AND $this->sqlzavod.ido = osoby.ido AND tymy.id_tymu = $this->sqlzavod.id_tymu AND $this->sqlvysledky.false_time IS NULL AND $this->sqlvysledky.lap_only IS NULL GROUP BY ids ORDER BY cilovy_cas ASC".$this->rows_limit;
		    
		    $sth1 =  $this->db->prepare($sql1);
		    $sth1->execute(Array(':time_order' => $time_order,':gender' => $key,':id_event' => $event_order));
		    if($sth1->rowCount()){
			$class = $k == 1 ? $class = 'nadpis nopadding' : 'nadpis';
			$str .= '<tr><td class="'.$class.'" colspan="3">'.$gender.'</td></tr>';
			$str .= '<tr class="header"><th class="text-center">#</th><th class="text-center">St.č</th><th>Jméno</th><th class="text-center">Ročník</th><th>Tým/Bydliště</th><th class="text-center">Stát</th><th class="text-center">Kat</th>';
			//$str .= $this->TableHeader($time_order,$event_order,$this->cislo_kategorie).'</tr>';
                        $str .= $this->TableHeaderExtend($this->time_order,$this->event_order,$this->cislo_kategorie);
                        $poradi = 1;
			while($data1 = $sth1->fetchObject()){
			    $str .= '<tr><td class="text-center">'.$poradi.'</td><td class="text-center">'.$data1->ids_alias.'</td><td>'.$data1->jmeno.'</td><td class="text-center">'.$data1->rocnik.'</td><td>'.$this->NahrazkaPomlcky($data1->nazev_tymu).'</td><td class="text-center">'.$data1->stat.'</td><td class="text-center">'.$data1->nazev_kategorie.'</td>';
			    //18.6.15 $sql2 = "SELECT * FROM $this->sqlvysledky WHERE ids = :ids  AND false_time IS NULL ORDER BY race_time ASC LIMIT 0,$time_order";
			    $sql2 = "SELECT * FROM $this->sqlvysledky WHERE cip = :cip  AND false_time IS NULL AND lap_only IS NULL ORDER BY race_time ASC LIMIT 0,$time_order";
			    $sth2 = $this->db->prepare($sql2);
			    $sth2->execute(Array(':cip' => $data1->cip));
			    $i = 1;
			    $missing_time = false;
			    while($val2 = $sth2->fetchObject()){
				if($time_order == 1){
				    $str .= '<td class="text-center">'.$val2->lap_time.'</td>';
				    $str .= ($val2->distance_gender != '00:00:00.00') ?  ('<td class="text-center">'.$val2->distance_gender.'</td>') : ('<td class="text-center">&nbsp;</td>');
				}
				else{
				    if($i <= $time_order){
					if($val2->lap_time != '00:00:00.00' AND $missing_time == false){
					    $str .= '<td class="text-center">'.$val2->lap_time.'</td>';
					}
					else{
					    $str .= '<td class="text-center">&nbsp;</td>';
					    $missing_time = true; //byl nalezen nulový čas a tím pádem se vy výsledcích objeví pouze výsledný čas
					}
                                        $str .= '<td class="text-center"><i>'.$val2->rank_gender_lap.'</i></td>';			
                                        //if($i > 1 && $i < $this->time_order){
					  //  $str .= '<td class="text-center"><i>'.$val2->rank_gender.'</i></td>';
					//}
                                        
				    }
				    if($i == $time_order){
					$str .= ($val2->race_time != '00:00:00.00') ? ('<td class="text-center">'.$val2->race_time.'</td>') : ('<td class="text-center">&nbsp;</td>');
					$str .= ($val2->distance_gender != '00:00:00.00') ?  ('<td class="text-center">'.$val2->distance_gender.'</td>') : ('<td class="text-center">&nbsp;</td>');
				    }
				}
				$i++;
			    }
			    $poradi++;
			}
		    }
		    $str .= $this->DNFGender(1,$key);
		    $str .= '</tr>';
		    $k++;
		}
		$str .= '</table>';
	    }
	    else{
		$sql1 = "SELECT $this->sqlvysledky.ids,$this->sqlvysledky.cip,$this->sqlvysledky.ids_alias,MAX($this->sqlvysledky.race_time) AS cilovy_cas,CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,osoby.psc AS stat,$this->sqlkategorie.kod_k AS nazev_kategorie,tymy.nazev_tymu FROM $this->sqlvysledky,osoby,$this->sqlzavod,$this->sqlkategorie,tymy WHERE race_time > '0' AND $this->sqlvysledky.time_order = :time_order AND $this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie AND $this->sqlkategorie.poradi_podzavodu = :id_event AND osoby.pohlavi = :gender AND $this->sqlzavod.ids = $this->sqlvysledky.ids AND $this->sqlzavod.ido = osoby.ido AND tymy.id_tymu = $this->sqlzavod.id_tymu AND false_time IS NULL GROUP BY ids ORDER BY cilovy_cas ASC".$this->rows_limit;
		    $sth1 =  $this->db->prepare($sql1);
		    $sth1->execute(Array(':time_order' => $time_order,':gender' => $gender,':id_event' => $event_order));
		    if($sth1->rowCount()){
			$str = '<h4 class="headline-results">'.$this->race_name.$this->event_name.', kategorie '.$gender_array[$gender].'</h4>';
			$str .= '<table  id="table2excel" class="table table-striped table-bordered table-hover table_vysledky">';
			$str .= '<thead><tr class="header"><th class="text-center">#</th><th class="text-center">St.č</th><th>Jméno</th><th class="text-center">Ročník</th><th>Tým/Bydliště</th><th class="text-center">Stát</th><th class="text-center">Kat</th>';
			//$str .= $this->TableHeader($time_order,$event_order,$this->cislo_kategorie).'</tr></thead><tbody>';
                        $str .= $this->TableHeaderExtend($this->time_order,$this->event_order,$this->cislo_kategorie);
			$poradi = 1;
			while($data1 = $sth1->fetchObject()){
			    $str .= '<tr><td class="text-center">'.$poradi.'</td><td class="text-center">'.$data1->ids_alias.'</td><td>'.$data1->jmeno.'</td><td class="text-center">'.$data1->rocnik.'</td><td>'.$this->NahrazkaPomlcky($data1->nazev_tymu).'</td><td class="text-center">'.$data1->stat.'</td><td class="text-center">'.$data1->nazev_kategorie.'</td>';
			    $sql2 = "SELECT * FROM $this->sqlvysledky WHERE cip = '$data1->cip'  AND false_time IS NULL AND lap_only IS NULL ORDER BY race_time ASC LIMIT 0,$time_order";
			    $sth2 = $this->db->prepare($sql2);
			    $sth2->execute(Array(':cip' => $data1->ids));
			    $i = 1;
			    $missing_time = false;
			    while($val2 = $sth2->fetchObject()){
				if($time_order == 1){
				    $str .= '<td class="text-center">'.$val2->lap_time.'</td>';
				    $str .= ($val2->distance_gender != '00:00:00.00') ?  ('<td class="text-center">'.$val2->distance_gender.'</td>') : ('<td class="text-center">&nbsp;</td>');
				}
				else{
				    if($i <= $time_order){
					if($val2->lap_time != '00:00:00.00' AND $missing_time == false){
					    $str .= '<td class="text-center">'.$val2->lap_time.'</td>';
					}
					else{
					    $str .= '<td class="text-center">&nbsp;</td>';
					    $missing_time = true; //byl nalezen nulový čas a tím pádem se vy výsledcích objeví pouze výsledný čas
					}
                                        $str .= '<td class="text-center"><i>'.$val2->rank_gender_lap.'</i></td>';			
                                        //if($i > 1 && $i < $this->time_order){
                                          //  $str .= '<td class="text-center"><i>'.$val2->rank_gender.'</i></td>';
                                        //}
				    }
				    if($i == $time_order){
					$str .= ($val2->race_time != '00:00:00.00') ? ('<td class="text-center">'.$val2->race_time.'</td>') : ('<td class="text-center">&nbsp;</td>');
					$str .= ($val2->distance_gender != '00:00:00.00') ?  ('<td class="text-center">'.$val2->distance_gender.'</td>') : ('<td class="text-center">&nbsp;</td>');
				    }
				}
				$i++;
			    }
			    $poradi++;
			}
			$str .= $this->DNFGender(1,$gender);
			$str .= '</tr>';
		    }
		    else{
			$str .= '<p>Žádný výsledek</p>';
		    }
		$str .= '</tbody></table>';
	    }
	    
            
            
            $change_control_file_info = $this->ChangeControlFileInfo();
	    $fcdata['last_modified'] = $change_control_file_info['last_modified'];
	    $fcdata['change_control_file'] = $change_control_file_info['change_control_file'];
	    $fcdata['results'] = $str;
	    echo json_encode($fcdata);

	    

?>
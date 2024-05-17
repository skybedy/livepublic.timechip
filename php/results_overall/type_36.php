<?php
	/* Liptov Ride jednotlivci 
         uplne stejne jak type_1, jen je tam pridany sloupec pro penalizaci */

	    if($this->laps_only){
		require_once 'type_1_laps_only.php';
	    }
	    else{
		if($this->chip_time){
		    $sql1 = "SELECT $this->sqlzavod.ids,$this->sqlzavod.cip,$this->sqlzavod.ids_alias,MAX($this->sqlvysledky.race_time) AS cilovy_cas,MAX($this->sqlvysledky.race_time_sec) AS cilovy_cas_sec,$this->sqlvysledky.race_time_sec,$this->sqlvysledky.rank_category,$this->sqlvysledky.rank_gender,$this->sqlvysledky.chip_time,CONCAT_WS(' ',$this->sqlosoby.prijmeni,$this->sqlosoby.jmeno) AS jmeno,$this->sqlosoby.rocnik,$this->sqlosoby.psc AS stat,$this->sqlosoby.pohlavi,$this->sqlkategorie.kod_k AS nazev_kategorie,tymy.nazev_tymu FROM $this->sqlvysledky,$this->sqlosoby,$this->sqlzavod,$this->sqlkategorie,tymy WHERE race_time > 0 AND $this->sqlvysledky.time_order = $this->time_order AND $this->sqlkategorie.id_zavodu = $this->race_id AND $this->sqlkategorie.poradi_podzavodu = $this->event_order AND $this->sqlzavod.cip = $this->sqlvysledky.cip AND $this->sqlzavod.ido = $this->sqlosoby.ido AND $this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie$this->cislo_kategorie AND tymy.id_tymu = $this->sqlzavod.prislusnost AND $this->sqlvysledky.false_time IS NULL AND $this->sqlvysledky.lap_only IS NULL GROUP BY $this->sqlvysledky.ids ORDER BY cilovy_cas_sec ASC";
		}
		else{
		    $sql1 = "SELECT $this->sqlzavod.ids,$this->sqlzavod.penalizace,$this->sqlzavod.cip,$this->sqlzavod.ids_alias,MAX($this->sqlvysledky.race_time) AS cilovy_cas,MAX($this->sqlvysledky.race_time_sec) AS cilovy_cas_sec,$this->sqlvysledky.race_time_sec,$this->sqlvysledky.rank_category,$this->sqlvysledky.rank_gender,CONCAT_WS(' ',$this->sqlosoby.prijmeni,$this->sqlosoby.jmeno) AS jmeno,$this->sqlosoby.rocnik,$this->sqlosoby.psc AS stat,$this->sqlosoby.pohlavi,$this->sqlkategorie.kod_k AS nazev_kategorie,tymy.nazev_tymu FROM $this->sqlvysledky,$this->sqlosoby,$this->sqlzavod,$this->sqlkategorie,tymy WHERE race_time > 0 AND $this->sqlvysledky.time_order = $this->time_order AND $this->sqlkategorie.id_zavodu = $this->race_id AND $this->sqlkategorie.poradi_podzavodu = $this->event_order AND $this->sqlzavod.cip = $this->sqlvysledky.cip AND $this->sqlzavod.ido = $this->sqlosoby.ido AND $this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie$this->cislo_kategorie AND tymy.id_tymu = $this->sqlzavod.prislusnost AND $this->sqlvysledky.false_time IS NULL AND $this->sqlvysledky.lap_only IS NULL GROUP BY $this->sqlvysledky.ids ORDER BY cilovy_cas_sec ASC,ids DESC";
		}


		//$str .= $sql1."<br />";
		$sth1 =  $this->db->prepare($sql1);
		$sth1->execute(Array(':time_order' => $this->time_order,':race_id' => $this->race_id,':event_order' => $this->event_order));
		if($sth1->rowCount()){
		    $str .= '<h4 class="headline-results">'.$this->race_name.', výsledky bez rozdílu kategorií</h4>';
		    $str .= '<table class="table table-striped table-bordered table-hover">';
		    $str .= '<thead><tr class="header"><th class="text-center">Poř</th><th class="text-center">St.č</th><th>Jméno</th><th class="text-center">Ročník</th><th>Tým/Bydliště</th><th class="text-center">Stát</th>';
                    $str .= '<th class="text-center">Kat</th> <th class="text-center">Poř</th> <th class="text-center">M/Z</th> <th class="text-center">Poř</th><th class="text-center">Pen</th>';
		    
                    
                    $str .= ($this->chip_time == true) ? ('<th class="text-center">ČipČas</th>') : (""); 

		    $str .= $this->TableHeaderExtend($this->time_order,$this->event_order,$this->cislo_kategorie);

		    $str .= '</tr></thead><tbody>';
		    $poradi = 1;
		    while($data1 = $sth1->fetchObject()){//cyklus, v kterém se vyberou všecky časy konkrétního závodníka
			    if($poradi == 1) $best_time = $data1->race_time_sec;
			    $distance_time = $this->DynamicDistances($poradi,$data1->race_time_sec,$best_time);
			    $str .= '<tr><td class="text-center">'.$poradi.'</td><td class="text-center">'.$data1->ids_alias.'</td><td>'.$data1->jmeno.'</td><td class="text-center">'.$data1->rocnik.'</td><td>'.$this->NahrazkaPomlcky($data1->nazev_tymu).'</td><td class="text-center">'.$data1->stat.'</td>';
                            $str .= '<td class="text-center">'.$data1->nazev_kategorie.'</td><td class="text-center">'.$data1->rank_category.'</td><td class="text-center">'.$data1->pohlavi.'</td><td class="text-center">'.$data1->rank_gender.'</td><td class="text-center">'.$data1->penalizace.'</td>';
			    //$str .= '<tr><td class="text-center">'.$poradi.'</td><td class="text-center">'.$data1->ids_alias.'</td><td>'.$data1->jmeno.'</td><td class="text-center">'.$data1->rocnik.'</td><td>'.$data1->nazev_tymu.'</td><td class="text-center">'.$data1->stat.'</td>';
			    $str .= ($this->chip_time == true) ? (' <td class="text-center">'.$data1->chip_time.'</td>') : (""); 
			    $sql2 = "SELECT * FROM $this->sqlvysledky WHERE cip = :cip AND false_time IS NULL AND lap_only IS NULL ORDER BY race_time ASC LIMIT 0,$this->time_order";
			    $sth2 = $this->db->prepare($sql2);
			    $sth2->execute(Array(':cip' => $data1->cip));
			    $i = 1;
			    $missing_time = false; //nastavení proměnné pro konntrolu, jestli má závodník všecky časy 
			    while($val2 = $sth2->fetchObject()){
				if($this->time_order == 1){//pokud je to první čas 
				    $str .= '<td class="text-center">'.$val2->lap_time.'</td>';
				    if($this->cislo_kategorie == '_2'){ //pouze pokud se jedná o paralelní kategorie
					$str .= ($distance_time !== '00:00:00.00') ?  ('<td class="text-center">'.$distance_time.'</td>') : ('<td class="text-center">&nbsp;</td>');
				    }
				    else{  //normal
					$str .= ($val2->distance_overall != '00:00:00.00') ?  ('<td class="text-center">'.$val2->distance_overall.'</td>') : ('<td class="text-center">&nbsp;</td>');
				    }
				}
				else{//pokud je to jiný než první čas
				    if($i <= $this->time_order){ 
					if($val2->lap_time != '00:00:00.00' AND $missing_time == false){
					    $str .= '<td class="text-center">'.$val2->lap_time.'</td>';
					}
					else{
					   $str .= '<td class="text-center">&nbsp;</td>';
					   $missing_time = true; //byl nalezen nulový čas a tím pádem se vy výsledcích objeví pouze výsledný čas
					}
					$str .= '<td class="text-center"><i>'.$val2->rank_overall_lap.'</i></td>';
					//if($i > 1 && $i < $this->time_order){
					  //  $str .= '<td class="text-center"><i>'.$val2->rank_overall.'</i></td>';
					//}

				    }
				    if($i == $this->time_order){ // toto je poslední čas a tím pádem se tady vloží celkový čas a odstup
					$str .= ($val2->race_time != '00:00:00.00') ? ('<td class="text-center">'.$val2->race_time.'</td>') : ('<td class="text-center">&nbsp;</td>');
					if($this->cislo_kategorie == '_2'){ //pouze pokud se jedná o paralelní kategorie
					    $str .= ($distance_time !== '00:00:00.00') ?  ('<td class="text-center">'.$distance_time.'</td>') : ('<td class="text-center">&nbsp;</td>');
					}
					else{  //normal
					    $str .= ($val2->distance_overall != '00:00:00.00') ?  ('<td class="text-center">'.$val2->distance_overall.'</td>') : ('<td class="text-center">&nbsp;</td>');
					}
				    }
				}
				$i++;
			    }
			    $str .= '</tr>';
			    $poradi++;
		    }
		    $str .= $this->DNFOverall(36);
		    $str .= '</tbody></table>';
		}
		
	    }



	    

?>
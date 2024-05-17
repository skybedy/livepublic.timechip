<?php
	      
           //czech downhill tour 




	    if($this->laps_only){
		require_once 'type_1_laps_only.php';
	    }
	    else{
		$sql0 = "SELECT COUNT($this->sqlvysledky.id) AS pocet FROM $this->sqlvysledky,$this->sqlzavod WHERE race_time > 0 AND $this->sqlvysledky.time_order = $this->time_order AND $this->sqlvysledky.false_time IS NULL AND $this->sqlvysledky.lap_only IS NULL AND $this->sqlvysledky.ids = $this->sqlzavod.ids AND $this->sqlzavod.poradi_podzavodu = $this->event_order";
		$sth0 = $this->db->prepare($sql0);
		$sth0->execute();
		$dbdata0 = $sth0->fetchObject();
                $pagination_pocet = ceil($dbdata0->pocet / 100);
		
                $new_limit_rows = "";
		if($dbdata0->pocet > 100){
                    if(isset($_GET['limit_od'])){
                        $new_limit_rows = " LIMIT {$_GET['limit_od']},100";
                    }
                    else{
                        $new_limit_rows = " LIMIT 0,100";
                    }
		}
		
		
                $sql1 = "SELECT $this->sqlvysledky.ids,$this->sqlvysledky.ids_alias,$this->sqlvysledky.cip,MAX($this->sqlvysledky.race_time) AS cilovy_cas,$this->sqlvysledky.rank_category FROM $this->sqlvysledky,$this->sqlzavod WHERE race_time > 0 AND $this->sqlvysledky.time_order = :time_order AND $this->sqlvysledky.false_time IS NULL AND $this->sqlvysledky.lap_only IS NULL AND $this->sqlvysledky.ids = $this->sqlzavod.ids AND $this->sqlvysledky.poradi_podzavodu = :event_order AND $this->sqlvysledky.poradi_podzavodu = $this->sqlzavod.poradi_podzavodu  GROUP BY $this->sqlvysledky.ids ORDER BY cilovy_cas ASC";
		$sth1 =  $this->db->prepare($sql1);
		$sth1->execute(Array(':time_order' => $this->time_order,':event_order' => $this->event_order));
		if($sth1->rowCount()){
		    $str .= '<h4 class="headline-results">'.$this->race_name.$this->event_name.', výsledky bez rozdílu kategorií</h4>';

                    if($dbdata0->pocet > 100){
                        $str .= '<nav class="text-center">';
                        $str .= '<ul id="strankovani" class="pagination">';
                        for($i = 1;$i <= $pagination_pocet;$i++){
                            $pracovni_soucin = $i * 100;
                            $do = $pracovni_soucin;
                            if($i == $pagination_pocet){
                                if($pracovni_soucin > $dbdata0->pocet){
                                    $zbytek = $dbdata0->pocet % 100;
                                    $do = $pracovni_soucin - 100 + $zbytek;
                                }
                            }
                            //$str .= '<li><a class="strankovani" href="#">'.($pracovni_soucin - 99).'-'.($do).'</a></li>';
                        }
                        $str .= '</ul>';
                        $str .= '</nav>';
                    }

		    
		    $str .= '<table  id="table2excel" class="table table-striped table-bordered table-hover noborder table_vysledky">';
		    $str .= '<thead><tr class="header"><th class="text-center">Poř</th><th class="text-center">St.č</th><th class="text-left">Jméno</th><th class="text-center">Ročník</th><th class="text-left">Tým/Bydliště</th><th class="text-center">Stát</th><th class="text-center">Kat</th> <th class="text-center">Poř</th>';
		    $str .= $this->TableHeaderExtend($this->time_order,$this->event_order,$this->cislo_kategorie);
		    $str .= '</tr></thead><tbody>';
		    $poradi = 1;
		    if(isset($_GET['limit_od'])){
                        $poradi = 1 + $_GET['limit_od'];
                    }
                    else{
                        $poradi = 1;
                    }
                    while($data1 = $sth1->fetchObject()){//cyklus, v kterém se vyberou všecky časy konkrétního závodníka
			$sql3 = "SELECT CONCAT_WS(' ',$this->sqlosoby.prijmeni,$this->sqlosoby.jmeno) AS jmeno,$this->sqlosoby.rocnik,$this->sqlosoby.psc AS stat,$this->sqlosoby.pohlavi,$this->sqlkategorie.kod_k AS nazev_kategorie,tymy.nazev_tymu FROM $this->sqlzavod,$this->sqlosoby,$this->sqlkategorie,tymy WHERE $this->sqlzavod.ids = :ids AND $this->sqlzavod.ido = osoby.ido AND $this->sqlzavod.id_tymu = tymy.id_tymu AND $this->sqlzavod.id_kategorie = $this->sqlkategorie.id_kategorie";
			$sth3 = $this->db->prepare($sql3);
			$sth3->execute(Array(':ids' => $data1->ids));
			if($sth3->rowCount()){
			    $dbdata3 = $sth3->fetchObject();
			    if($this->cislo_kategorie == '_2'){ //pouze pokud se jedná o paralelní kategorie
				if($poradi == 1) $best_time = $data1->race_time_sec;
				$distance_time = $this->DynamicDistances($poradi,$data1->race_time_sec,$best_time);
			    }
			    $str .= '<tr><td class="text-center"><b>'.$poradi.'</b></td><td class="text-center">'.$data1->ids_alias.'</td><td>'.$dbdata3->jmeno.'</td><td class="text-center">'.$dbdata3->rocnik.'</td><td>'.$dbdata3->nazev_tymu.'</td><td class="text-center">'.$dbdata3->stat.'</td><td class="text-center">'.$dbdata3->nazev_kategorie.'</td><td class="text-center"><b>'.$data1->rank_category.'</b></td>';
			    $sql2 = "SELECT * FROM $this->sqlvysledky WHERE cip = :cip AND false_time IS NULL AND lap_only IS NULL AND $this->sqlvysledky.poradi_podzavodu = :event_order ORDER BY race_time_sec ASC LIMIT 0,$this->time_order";
			    $sth2 = $this->db->prepare($sql2);
			    $sth2->execute(Array(':cip' => $data1->cip,':event_order' => $this->event_order));
			    
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
					$str .= ($val2->race_time != '00:00:00.00') ? ('<td class="text-center">tu</td>') : ('<td class="text-center">&nbsp;</td>');
                                        $str .= ($val2->distance_overall != '00:00:00.00') ?  ('<td class="text-center">'.$val2->distance_overall.'</td>') : ('<td class="text-center">&nbsp;</td>');
				    }
				}
				$i++;
			    }
			    $str .= '</tr>';
			    $poradi++;

			}
		    }
		    $str .= $this->DNFOverall(1);
		    $str .= '</tbody></table>';
		    
                    if($dbdata0->pocet > 100){
                        $str .= '<nav class="text-center">';
                        $str .= '<ul id="strankovani" class="pagination">';
                        for($i = 1;$i <= $pagination_pocet;$i++){
                            $pracovni_soucin = $i * 100;
                            $do = $pracovni_soucin;
                            if($i == $pagination_pocet){
                                if($pracovni_soucin > $dbdata0->pocet){
                                    $zbytek = $dbdata0->pocet % 100;
                                    $do = $pracovni_soucin - 100 + $zbytek;
                                }
                            }
                           // $str .= '<li><a class="strankovani" href="#">'.($pracovni_soucin - 99).'-'.($do).'</a></li>';
                        }
                        $str .= '</ul>';
                        $str .= '</nav>';
                    }
		    
		    
		    
		    
		}
	    
	    }
?>
<?php

    if($this->laps_only){
	require_once 'type_34_laps_only.php';
    }
    else{
	$colspan = 4;
	//orlice 2015
	if($category_id == 'all'){
	    $str .= '<h4 class="headline-results">'.$this->race_name.', výsledky podle kategorí</h4>';
	    $sql = "SELECT id_kategorie,kod_k AS kod_kategorie,nazev_k AS nazev_kategorie,poradi_podzavodu FROM $this->sqlkategorie WHERE id_zavodu = :race_id AND poradi_podzavodu = :event_order ORDER BY poradi";
	    $sth = $this->db->prepare($sql);
	    $sth->execute(Array(':race_id' => $this->race_id,':event_order' => $this->event_order));
	    if($sth->rowCount()){
		$str .= '<table class="table table-bordered  table-hover orlice_cup noborder">';
		$k = 1;
		while($data = $sth->fetchObject()){
		    $sql1 = "SELECT "
                                . "tymy.nazev_tymu,"
                                . "$this->sqlvysledky.ids,"
                                . "SUM($this->sqlvysledky.lap_time_sec) AS finish_time_sec,"
                                . "SEC_TO_TIME(SUM($this->sqlvysledky.lap_time_sec)) AS finish_time,"
                                . "COUNT($this->sqlvysledky.id) AS pocet_kol, "
                                . "$this->sqlzavod.penalizace "
                            . "FROM $this->sqlvysledky,$this->sqlzavod,tymy "
                            . "WHERE "
                              . "race_time > '0' AND "
			      . "$this->sqlzavod.id_kategorie = :id_kategorie AND "
			      . "$this->sqlzavod.cip = $this->sqlvysledky.cip AND "
			      . "$this->sqlzavod.tym = tymy.id_tymu AND " 
                              . "$this->sqlvysledky.race_time > 0 AND "
                              . "$this->sqlvysledky.false_time IS NULL AND "
                              . "$this->sqlvysledky.lap_only IS NULL AND "
                              . "$this->sqlvysledky.time_order <= :time_order "
                            . "GROUP BY $this->sqlzavod.ids "
                            . "ORDER BY finish_time ASC".$this->rows_limit;
                    //echo $sql1."\n\n";

                    $sth1 =  $this->db->prepare($sql1);
		    $sth1->execute(Array(':time_order' => $this->time_order,':id_kategorie' => $data->id_kategorie));
		    if($sth1->rowCount()){
			$class = $k == 1 ? $class = 'nadpis nopadding' : 'nadpis';
			$str .= '<tr><td class="'.$class.'" colspan="3">'.$data->nazev_kategorie.'</td></tr>';
			$str .= '<tr class="header"><th rowspan="2" class="text-center">#</th><th rowspan="2" class="text-center">St.č</th><th rowspan="2" >Tým</th><th rowspan="2" class="text-center">Pen</th>';
			$str .= $this->TableHeaderTymyOrlice($this->time_order,$this->event_order);
			$str .= '</tr>';
			$xx = 1;
			$str .= '<tr>';
			while($xx <= $this->time_order){
			    $str .= '<th><div class="jmeno_ac">Jméno</div><div class="cas_ac">Čas</div><div class="poradi_ac">Poř</div></th>';
			    $xx++;
			}
			$str .= '</tr>'; 
			$poradi = 1;
			while($dbdata1 = $sth1->fetchObject()){
                            if($poradi == 1) $best_time = $dbdata1->finish_time_sec;
                            $distance_time = $this->DynamicDistances($poradi,$dbdata1->finish_time_sec,$best_time);
			    $sql3 = "SELECT CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,$this->sqlzavod.cip FROM osoby,$this->sqlzavod WHERE $this->sqlzavod.ids = :ids AND $this->sqlzavod.ido = osoby.ido ORDER BY $this->sqlzavod.id";
			    $sth3 = $this->db->prepare($sql3);
			    $sth3->execute(Array(':ids' => $dbdata1->ids));
			    $zavodnik_pole = Array();
			    while($dbdata3 = $sth3->fetchObject()){
				$zavodnik_pole[$dbdata3->cip] = $dbdata3;
			    }
			    $str .= '<tr class="orlice_cup_tr">';
			    $str .= '<td class="text-center">'.$poradi.'</td>';
			    $str .= '<td class="text-center">'.$dbdata1->ids.'</td>';
			    $str .= '<td>'.$dbdata1->nazev_tymu.'</td>';
			    $str .= '<td class="text-center">'.$dbdata1->penalizace.'</td>';
                            $sql2 = "SELECT * FROM $this->sqlvysledky WHERE ids = :ids AND false_time IS NULL AND lap_only IS NULL ORDER BY race_time ASC LIMIT 0,$this->time_order";
                            $sth2 = $this->db->prepare($sql2);
			    $sth2->execute(Array(':ids' => $dbdata1->ids));
			    $i = 1;
			    $missing_time = false; //nastavení proměnné pro konntrolu, jestli má závodník všecky časy 
			    while($val2 = $sth2->fetchObject()){
				if($this->time_order == 1){//pokud je to první čas
				    $str .= '<td><div class="jmeno_ac">'.$zavodnik_pole[$val2->cip]->jmeno.'</div> <div class="cas_ac">'.substr($val2->lap_time,1,-3).'</div> <div class="poradi_ac">'.$val2->rank_category_lap.'</div></td>';
                                    $str .= ($distance_time != '00:00:00.00') ?  ('<td class="text-center">'.$distance_time.'</td>') : ('<td class="text-center">-</td>');
				}
				else{//pokud je to jiný než první čas
				    if($i <= $this->time_order){ 
					if($val2->lap_time != '00:00:00.00' AND $missing_time == false){
					    $str .= '<td><div class="jmeno_ac">'.$zavodnik_pole[$val2->cip]->jmeno.'</div> <div class="cas_ac">'.substr($val2->lap_time,1,-3).'</div> <div class="poradi_ac">'.$val2->rank_category_lap.'</div></td>';
					}
					else{
					   $str .= '<td class="text-center">&nbsp;</td>';
					   $missing_time = true; //byl nalezen nulový čas a tím pádem se vy výsledcích objeví pouze výsledný čas
					}
				    }
				    if($i == $this->time_order){ // toto je poslední čas a tím pádem se tady vloží celkový čas a odstup
                                        $str .= '<td class="text-center">'.$dbdata1->finish_time.'</td>';
                                        $str .= ($distance_time != '00:00:00.00') ?  ('<td class="text-center">'.$distance_time.'</td>') : ('<td class="text-center">-</td>');
				    }
				}
				$i++;
			    }
			    $str .= '</tr>';
			$poradi++;
			}
		    }
		    $k++;
		}
		$str .= '</table>';
	    }
	}

	else{ // každá kategorie zvlášť
	    $sql = "SELECT nazev_k AS nazev_kategorie FROM $this->sqlkategorie WHERE id_zavodu = :race_id AND id_kategorie = :category_id";
	    $sth = $this->db->prepare($sql);
	    $sth->execute(Array(':race_id' => $this->race_id,':category_id' => $category_id));
	    if($sth->rowCount()){
		$dbdata = $sth->fetchObject();
		$str .= '<h4 class="headline-results">'.$this->race_name.', kategorie '.$dbdata->nazev_kategorie.'</h4>';
		$str .= '<table class="table table-bordered table-hover table-striped orlice_cup">';
		$k = 1;
		$sql1 = "SELECT "
                            . "tymy.nazev_tymu,"
                            . "$this->sqlvysledky.ids,"
                            . "SUM($this->sqlvysledky.lap_time_sec) AS finish_time_sec,"
                            . "SEC_TO_TIME(SUM($this->sqlvysledky.lap_time_sec)) AS finish_time,"
                            . "COUNT($this->sqlvysledky.id) AS pocet_kol, "
                            . "$this->sqlzavod.penalizace "
                        . "FROM $this->sqlvysledky,$this->sqlzavod,tymy "
                        . "WHERE "
                              . "race_time > '0' AND "
			      . "$this->sqlzavod.id_kategorie = :category_id AND "
			      . "$this->sqlzavod.cip = $this->sqlvysledky.cip AND "
			      . "$this->sqlzavod.tym = tymy.id_tymu AND " 
                              . "$this->sqlvysledky.race_time > 0 AND "
                              . "$this->sqlvysledky.false_time IS NULL AND "
                              . "$this->sqlvysledky.lap_only IS NULL AND "
                              . "$this->sqlvysledky.time_order <= :time_order " 
                            . "GROUP BY $this->sqlzavod.ids "
                            . "ORDER BY finish_time ASC".$this->rows_limit;
                    $sth1 =  $this->db->prepare($sql1);
		    $sth1->execute(Array(':time_order' => $this->time_order,':category_id' => $category_id));
		    if($sth1->rowCount()){
			$str .= '<thead><tr class="header"><th rowspan="2" class="text-center">#</th><th rowspan="2" class="text-center">St.č</th><th rowspan="2" >Tým</th><th rowspan="2" class="text-center">Pen</th>';
			$str .= $this->TableHeaderTymyOrlice($this->time_order,$this->event_order);
			$str .= '</tr>';
			$xx = 1;
			$str .= '<tr class=no_hover>';
			while($xx <= $this->time_order){
			    $str .= '<th><div class="jmeno_ac">Jméno</div><div class="cas_ac">Čas</div><div class="poradi_ac">Poř</div></th>';
			    $xx++;
			}
			$str .= '</tr>'; 
			$str .= '</thead><tbody>';
			$poradi = 1;
			while($dbdata1 = $sth1->fetchObject()){
                            if($poradi == 1) $best_time = $dbdata1->finish_time_sec;
                            $distance_time = $this->DynamicDistances($poradi,$dbdata1->finish_time_sec,$best_time);
                            
                            $sql3 = "SELECT CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,$this->sqlzavod.cip FROM osoby,$this->sqlzavod WHERE $this->sqlzavod.ids = :ids AND $this->sqlzavod.ido = osoby.ido ORDER BY $this->sqlzavod.id";
			    $sth3 = $this->db->prepare($sql3);
			    $sth3->execute(Array(':ids' => $dbdata1->ids));
			    $zavodnik_pole = Array();
			    while($dbdata3 = $sth3->fetchObject()){
				$zavodnik_pole[$dbdata3->cip] = $dbdata3;
			    }
			    $str .= '<tr class="orlice_cup_tr">';
			    $str .= '<td class="text-center">'.$poradi.'</td>';
			    $str .= '<td class="text-center">'.$dbdata1->ids.'</td>';
			    $str .= '<td>'.$dbdata1->nazev_tymu.'</td>';
			    $str .= '<td class="text-center">'.$dbdata1->penalizace.'</td>';
                            $sql2 = "SELECT * FROM $this->sqlvysledky WHERE ids = :ids AND false_time IS NULL AND lap_only IS NULL ORDER BY race_time ASC LIMIT 0,$this->time_order";
			    $sth2 = $this->db->prepare($sql2);
			    $sth2->execute(Array(':ids' => $dbdata1->ids));
			    $i = 1;
			    $missing_time = false; //nastavení proměnné pro konntrolu, jestli má závodník všecky časy 
			    while($val2 = $sth2->fetchObject()){
				if($this->time_order == 1){//pokud je to první čas 
				    //$str .= '<td><table class="inner_table_vysledky"><tr><td class="jmeno">'.$zavodnik_pole[$val2->cip]->jmeno.'</td><td class="rocnik">'.$zavodnik_pole[$val2->cip]->rocnik.'</td><td class="cas">'.$val2->lap_time.'</td><td class="poradi">'.$val2->rank_category_lap.'</td></tr></table></td>';
				    $str .= '<td><div class="jmeno_ac">'.$zavodnik_pole[$val2->cip]->jmeno.'</div>  <div class="cas_ac">'.substr($val2->lap_time,1,-3).'</div> <div class="poradi_ac">'.$val2->rank_category_lap.'</div></td>';
                                    $str .= ($distance_time != '00:00:00.00') ?  ('<td class="text-center">'.$distance_time.'</td>') : ('<td class="text-center">-</td>');
				}
				else{//pokud je to jiný než první čas
				    if($i <= $this->time_order){ 
					if($val2->lap_time != '00:00:00.00' AND $missing_time == false){
					    $str .= '<td><div class="jmeno_ac">'.$zavodnik_pole[$val2->cip]->jmeno.'</div> <div class="cas_ac">'.substr($val2->lap_time,1,-3).'</div> <div class="poradi_ac">'.$val2->rank_category_lap.'</div></td>';
					}
					else{
					   $str .= '<td class="text-center">&nbsp;</td>';
					   $missing_time = true; //byl nalezen nulový čas a tím pádem se vy výsledcích objeví pouze výsledný čas
					}
				    }
				    if($i == $this->time_order){ // toto je poslední čas a tím pádem se tady vloží celkový čas a odstup
                                        $str .= '<td class="text-center">'.$dbdata1->finish_time.'</td>';
                                        $str .= ($distance_time != '00:00:00.00') ?  ('<td class="text-center">'.$distance_time.'</td>') : ('<td class="text-center">-</td>');
				    }
				}
				$i++;
			    }
			    $str .= '</tr>';
			    $poradi++;
			}
		    }
		    else{
			$str .= '<p>Žádný výsledek</p>';
		    }
		$k++;
		$str .= '</tbody></table>';
	    }
	}
    }
?>



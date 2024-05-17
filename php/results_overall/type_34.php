<?php

      // Liptov Ride
    if($this->laps_only){
	require_once 'type_34_laps_only.php';
    }
    else{
	$sql1 = "SELECT "
                    . "tymy.nazev_tymu,"
                    . "$this->sqlvysledky.ids,"
                    . "$this->sqlvysledky.rank_category,"
                    . "$this->sqlkategorie.nazev_k AS nazev_kategorie,"
                    . "SUM($this->sqlvysledky.lap_time_sec) AS finish_time_sec,"
                    . "SEC_TO_TIME(SUM($this->sqlvysledky.lap_time_sec)) AS finish_time,"
                    . "COUNT($this->sqlvysledky.id) AS pocet_kol, "
                    . "$this->sqlzavod.penalizace "
                . "FROM $this->sqlvysledky,tymy,$this->sqlzavod,$this->sqlkategorie "
                . "WHERE "
                    . "$this->sqlkategorie.id_zavodu = $this->race_id AND "
                    . "$this->sqlkategorie.poradi_podzavodu = $this->event_order AND "
                    . "$this->sqlzavod.cip = $this->sqlvysledky.cip AND "
                    . "$this->sqlzavod.tym = tymy.id_tymu AND "
                    . "$this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie AND "
                    . "$this->sqlvysledky.race_time > 0 AND "
                    . "$this->sqlvysledky.false_time IS NULL AND "
                    . "$this->sqlvysledky.lap_only IS NULL AND "
                    . "$this->sqlvysledky.time_order <= $this->time_order "
                . "GROUP BY $this->sqlvysledky.ids "
                . "ORDER BY finish_time ASC".$this->rows_limit;
        //echo $sql1."\n";
	
        $sth1 =  $this->db->prepare($sql1);
	$sth1->execute(Array(':time_order' => $this->time_order,':race_id' => $this->race_id,':event_order' => $this->event_order));
	if($sth1->rowCount()){
	    $str .= '<h4 class="headline-results" style="text-align:center;font-size:14px">'.$this->race_name.' '.$this->race_year.', celkové výsledky</h4>';
	    $str .= '<table class="table table-striped table-hover table-bordered orlice_cup">';
	    
            $str .= '<thead><tr class="header"><th rowspan="2" class="text-center">#</th><th rowspan="2" class="text-center">St.č</th><th class="text-left" rowspan="2">Tým</th><th rowspan="2" class="text-center">Celkový<br>čas</th><th rowspan="2" class="text-center">Odstup</th><th rowspan="2" class="text-center">Pen</th>';
	    $str .= $this->TableHeaderTymyOrliceCelkove($this->time_order,$this->event_order);
	    $str .= '</tr>';
	    $xx = 1;
	    $str .= '<tr>';
	    while($xx <= $this->time_order){
		$str .= '<th class="text-left"><div style="float:left" class="jmeno_ac">Jméno</div><div class="cas_ac">Čas</div><div class="poradi_ac">Poř</div></th>';
		$xx++;
	    }
	    $str .= '</tr>'; 
	    $str .= '</thead><tbody>';
	    $poradi = 1;
	    while($dbdata1 = $sth1->fetchObject()){//cyklus, v kterém se vyberou všecky časy konkrétního týmu
                if($dbdata1->pocet_kol == $this->time_order){
                    if($poradi == 1) $best_time = $dbdata1->finish_time_sec;
                    $distance_time = $this->DynamicDistances($poradi,$dbdata1->finish_time_sec,$best_time);
                    $sql3 = "SELECT CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.prijmeni AS jmenox,osoby.rocnik,$this->sqlzavod.cip FROM osoby,$this->sqlzavod WHERE $this->sqlzavod.ids = :ids AND $this->sqlzavod.ido = osoby.ido ORDER BY $this->sqlzavod.id";
                    $sth3 = $this->db->prepare($sql3);
                    $sth3->execute(Array(':ids' => $dbdata1->ids));
                    $zavodnik_pole = Array();
                    while($dbdata3 = $sth3->fetchObject()){
                        $zavodnik_pole[$dbdata3->cip] = $dbdata3;
                    }
                    $str .= '<tr class="orlice_cup_tr">';
                    $str .= '<td class="text-center"><b>'.$poradi.'</b></td>';
                    $str .= '<td class="text-center">'.$dbdata1->ids.'</td>';
                    $str .= '<td style="width:10%"><b>'.$dbdata1->nazev_tymu.'</b></td>';
                    $str .= '<td class="text-center"><b>'.$dbdata1->finish_time.'</b></td>';
                    $str .= ($distance_time != '00:00:00.00') ?  ('<td class="text-center"><b>'.substr($distance_time,1,-3).'</b></td>') : ('<td class="text-center">...</td>');
                    
                    if($dbdata1->pocet_kol == 5){
                        $str .= '<td class="text-center">'.$dbdata1->penalizace.'</td>';
                    } 
                    else{
                        $str .= '<td class="text-center"></td>';
                    }

                    
                    
                    $sql2 = "SELECT * FROM $this->sqlvysledky WHERE ids = $dbdata1->ids AND false_time IS NULL AND lap_only IS NULL ORDER BY race_time ASC LIMIT 0,$this->time_order";
                    $sth2 = $this->db->prepare($sql2);
                    $sth2->execute(Array(':ids' => $dbdata1->ids));
                    $i = 1;
                    $missing_time = false; //nastavení proměnné pro kontrolu, jestli má závodník všecky časy 

                    
                    while($val2 = $sth2->fetchObject()){
                        if($this->time_order == 1){//pokud je to první čas 
                            $str .= '<td><div class="jmeno_ac">'.$zavodnik_pole[$val2->cip]->jmeno.'</div> <div class="cas_ac">'.$val2->lap_time.'</div><div class="poradi_ac">'.$val2->rank_overall_lap.'</div></td>';
                        }
                        else{//pokud je to jiný než první čas
                            if($i <= $this->time_order){ 
                                if($val2->lap_time != '00:00:00.00' AND $missing_time == false){
                                    $str .= '<td> <div class="jmeno_ac"><b>'.$zavodnik_pole[$val2->cip]->jmeno.'</b></div> <div class="cas_ac"><b>'.substr($val2->lap_time,1,-3).'</b></div><div class="poradi_ac">'.$val2->rank_overall_lap.'</div></td>';
                                    //$str .= '<td> <div class="jmeno_ac"><b>'.$zavodnik_pole[$val2->cip]->jmeno.'</b></div> <div class="cas_ac"><b>'.substr($val2->lap_time,1,-3).'</b></div></td>';
                                }
                                else{
                                   $str .= '<td class="text-center">&nbsp;</td>';
                                   $missing_time = true; //byl nalezen nulový čas a tím pádem se vy výsledcích objeví pouze výsledný čas
                                }
                            }
                        }
                        $i++;
                    }
                    
                    
                    
                    $str .= '</tr>';
                    $poradi++;
                
                    
                    
                    
                }
	    }
	    $str .= '</tbody></table>';
	}
    }
?>
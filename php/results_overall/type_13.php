<?php

      /* varianta Orlice Cup týmy 2015 */
    if($this->laps_only){
	require_once 'type_13_laps_only.php';
    }
    else{
	$sql1 = "SELECT tymy.nazev_tymu,$this->sqlvysledky.ids,$this->sqlvysledky.rank_category,MAX($this->sqlvysledky.race_time) AS cilovy_cas,$this->sqlkategorie.nazev_k AS nazev_kategorie,COUNT($this->sqlvysledky.id) AS pocet_casu FROM $this->sqlvysledky,tymy,$this->sqlzavod,$this->sqlkategorie WHERE race_time > 0 AND $this->sqlvysledky.time_order = :time_order AND $this->sqlkategorie.id_zavodu = :race_id AND $this->sqlkategorie.poradi_podzavodu = :event_order AND $this->sqlzavod.ids = $this->sqlvysledky.ids AND $this->sqlzavod.tym = tymy.id_tymu AND $this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie AND $this->sqlvysledky.false_time IS NULL AND $this->sqlvysledky.lap_only IS NULL GROUP BY $this->sqlvysledky.ids ORDER BY cilovy_cas ASC".$this->rows_limit;
	$sth1 =  $this->db->prepare($sql1);
	$sth1->execute(Array(':time_order' => $this->time_order,':race_id' => $this->race_id,':event_order' => $this->event_order));
	if($sth1->rowCount()){
	    $str .= '<h4 class="headline-results">'.$this->race_name.', výsledky bez rozdílu kategorií</h4>';
	    $str .= '<table class="table table-striped table-hover table-bordered orlice_cup">';
	   //$str .= '<thead><tr class="header"><th rowspan="2" class="text-center">#</th><th rowspan="2" class="text-center">St.č</th><th rowspan="2">Tým</th><th rowspan="2" colspan="2" class="text-center">Kat</th><th rowspan="2" class="text-center">Kol</th><th rowspan="2" class="text-center">Čas</th>';
	    $str .= '<thead><tr class="header"><th rowspan="2" class="text-center">#</th><th rowspan="2" class="text-center">St.č</th><th rowspan="2">Tým</th><th rowspan="2" colspan="2" class="text-center">Kat</th><th rowspan="2" class="text-center">Čas</th>';
	    $str .= $this->TableHeaderTymyOrliceCelkove($this->time_order,$this->event_order);
	    $str .= '</tr>';
	    $xx = 1;
	    $str .= '<tr>';
	    while($xx <= $this->time_order){
		$str .= '<th><div class="jmeno">Jméno</div><div class="rocnik">Ročník</div><div class="cas">Čas</div><div class="poradi">Poř</div></th>';
		$xx++;
	    }
	    $str .= '</tr>'; 
	    $str .= '</thead><tbody>';
	    $poradi = 1;
	    while($data1 = $sth1->fetchObject()){//cyklus, v kterém se vyberou všecky časy konkrétního týmu
		    $sql3 = "SELECT CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,$this->sqlzavod.cip FROM osoby,$this->sqlzavod WHERE $this->sqlzavod.ids = :ids AND $this->sqlzavod.ido = osoby.ido ORDER BY $this->sqlzavod.id";
		    $sth3 = $this->db->prepare($sql3);
		    $sth3->execute(Array(':ids' => $data1->ids));
		    $zavodnik_pole = Array();
		    while($dbdata3 = $sth3->fetchObject()){
			$zavodnik_pole[$dbdata3->cip] = $dbdata3;
		    }

		    $str .= '<tr class="orlice_cup_tr">';
		    $str .= '<td class="text-center"><b>'.$poradi.'</b></td>';
		    $str .= '<td class="text-center">'.$data1->ids.'</td>';
		    $str .= '<td><b>'.$data1->nazev_tymu.'</b></td>';
		    $str .= '<td class="text-center">'.$data1->rank_category.'</td>';
		    $str .= '<td class="text-center"><b>'.$data1->nazev_kategorie.'</b></td>';
		    //$str .= '<td class="text-center">'.$data1->pocet_casu.'</td>';
		    $str .= '<td class="text-center"><b>'.substr($data1->cilovy_cas,1,-3).'</b></td>';
		    $sql2 = "SELECT * FROM $this->sqlvysledky WHERE ids = :ids AND false_time IS NULL AND lap_only IS NULL ORDER BY race_time ASC LIMIT 0,$this->time_order";
		    $sth2 = $this->db->prepare($sql2);
		    $sth2->execute(Array(':ids' => $data1->ids));
		    $i = 1;
		    $missing_time = false; //nastavení proměnné pro konntrolu, jestli má závodník všecky časy 

		    while($val2 = $sth2->fetchObject()){
			if($this->time_order == 1){//pokud je to první čas 
			    $str .= '<td><div class="jmeno">'.$zavodnik_pole[$val2->cip]->jmeno.'</div> <div class="rocnik">'.$zavodnik_pole[$val2->cip]->rocnik.'</div> <div class="cas">'.$val2->lap_time.'</div><div class="poradi">'.$val2->rank_overall_lap.'</div></td>';
			    //$str .= ($val2->distance_overall != '00:00:00.00') ?  ('<td class="text-center">'.$val2->distance_overall.'</td>') : ('<td class="text-center">-</td>');
			}
			else{//pokud je to jiný než první čas
			    if($i <= $this->time_order){ 
				if($val2->lap_time != '00:00:00.00' AND $missing_time == false){
				    $str .= '<td> <div class="jmeno"><b>'.$zavodnik_pole[$val2->cip]->jmeno.'</b></div><div class="rocnik">'.$zavodnik_pole[$val2->cip]->rocnik.'</div> <div class="cas"><b>'.substr($val2->lap_time,1,-3).'</b></div><div class="poradi">'.$val2->rank_overall_lap.'</div></td>';
				}
				else{
				   $str .= '<td class="text-center">&nbsp;</td>';
				   $missing_time = true; //byl nalezen nulový čas a tím pádem se vy výsledcích objeví pouze výsledný čas
				}
			    }
			    if($i == $this->time_order){ // toto je poslední čas a tím pádem se tady vloží celkový čas a odstup
			       // $str .= ($val2->race_time != '00:00:00.00') ? ('<td class="text-center">'.$val2->race_time.'</td>') : ('<td class="text-center">&nbsp;</td>');
				//$str .= ($val2->distance_overall != '00:00:00.00') ?  ('<td class="text-center">'.$val2->distance_overall.'</td>') : ('<td class="text-center">-</td>');
			    }
			}
			$i++;
		    }
		    $str .= '</tr>';
		    $poradi++;
	    }
	    $str .= '</tbody></table>';
	}
    }
?>
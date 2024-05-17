<?php  
    /* 
     * MTB Enduro
     * Distance Overall se počítá dynamicky, protože autoreading zatím neumí korektně počítat odstupy při tomto typu výsledků, kdy celkovým časem není 
     * race_time nárůstem, ale pouze součet kol ze čtečky CIL
     */

    $hash_url = '#'.$this->race_year.'/'.$this->race_id.'/';
    $sql1 = "SELECT "
	      . "CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,"
	      . "osoby.rocnik,"
	      . "osoby.psc AS stat,"
	      . "tymy.nazev_tymu,"
	      . "$this->sqlkategorie.nazev_k AS nazev_kategorie, "
	      . "$this->sqlvysledky.ids,"
	      . "$this->sqlvysledky.cip,"
	      . "$this->sqlvysledky.rank_category,"
	      . "SUM($this->sqlvysledky.lap_time_sec) AS finish_time_sec,"
	      . "SEC_TO_TIME(SUM($this->sqlvysledky.lap_time_sec)) AS finish_time,"
	      . "COUNT($this->sqlvysledky.id) AS pocet_kol, "
	      . "$this->sqlzavod.penalizace "
	    . "FROM $this->sqlvysledky,osoby,tymy,$this->sqlzavod,$this->sqlkategorie "
	    . "WHERE "
	      . "race_time > 0 AND "
	      . "$this->sqlkategorie.poradi_podzavodu = :event_order AND "
	      . "$this->sqlzavod.ids = $this->sqlvysledky.ids AND "
	      . "$this->sqlzavod.tym = tymy.id_tymu AND "
	      . "$this->sqlzavod.ido = osoby.ido AND "
	      . "$this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie AND "
	      . "$this->sqlvysledky.false_time IS NULL AND "
	      . "$this->sqlvysledky.lap_only IS NULL AND "
	      . "$this->sqlvysledky.reader LIKE :reader AND "
	      . "$this->sqlvysledky.lap_count <= :time_order " //tohle by mělo zajistit, že se vybere SUM pouze z počtu kol daného požadivaným time_order
	    . "GROUP BY $this->sqlvysledky.ids "
	    . "ORDER BY finish_time_sec ASC";
	    //echo $sql1."\n";
    $sth1 =  $this->db->prepare($sql1);
    $sth1->execute(Array(':event_order' => $this->event_order,':reader' => 'CIL',':time_order' => $this->time_order));
    if($sth1->rowCount()){
	$str .= '<h4 class="headline-results">'.$this->race_name.', výsledky bez rozdílu kategorií</h4>';
	$str .= '<table class="table table-bordered table-hover table-striped table_vysledky">';
	$str .= '<thead><tr class="header"><th class="text-center">#</th><th class="text-center">St.č</th><th>Jméno</th><th class="text-center">Ročník</th><th>Název týmu</th><th class="text-center">Stát</th><th class="text-center">Kategorie</th><th class="text-center">Poř</th><th class="text-center">Pen</th>';
	$str .= $this->TableHeaderExtend($this->time_order,$this->event_order,$this->cislo_kategorie);
	$str .= '</tr></thead><tbody>';
	$poradi = 1;
	while($dbdata1 = $sth1->fetchObject()){ //začneme vypisovat první dotaz
	    if($dbdata1->pocet_kol == $this->time_order){
		if($poradi == 1) $best_time = $dbdata1->finish_time_sec;
		$distance_time = $this->DynamicDistances($poradi,$dbdata1->finish_time_sec,$best_time);
		$str .= '<tr><td class="text-center"><b>'.$poradi.'</b></td><td class="text-center">'.$dbdata1->ids.'</td><td>'.$dbdata1->jmeno.'</td><td class="text-center">'.$dbdata1->rocnik.'</td><td>'.$dbdata1->nazev_tymu.'</td><td class="text-center">'.$dbdata1->stat.'</td><td class="text-center">'.$dbdata1->nazev_kategorie.'</td><td class="text-center"><b>'.$dbdata1->rank_category.'</b></td><td class="text-center">'.$dbdata1->penalizace.'</td>';
		$sql2 = "SELECT race_time,lap_time,rank_overall_lap FROM $this->sqlvysledky "
			. "WHERE "
			    . "cip = :cip AND "
			    . "false_time IS NULL AND "
			    . "lap_only IS NULL AND "
			    . "reader = :reader "
			  . "ORDER BY race_time ASC "
			  . "LIMIT 0,$this->time_order";
		    // echo $sql2;
			$sth2 = $this->db->prepare($sql2);
			$sth2->execute(Array(':cip' => $dbdata1->cip,':reader' => 'CIL'));
			if($sth2->rowCount()){
			    $missing_time = false; 
			    $i = 1;
			    while($dbdata2 = $sth2->fetchOBject()){
				if($this->time_order == 1){//pokud je to první čas 
				    $str .= '<td class="text-center">'.$dbdata2->lap_time.'</td>';
				    $str .= ($distance_time != '00:00:00.00') ?  ('<td class="text-center">'.$distance_time.'</td>') : ('<td class="text-center">&nbsp;</td>');
				}
				else{//pokud je to jiný než první čas
				    if($i <= $this->time_order){ 
					if($dbdata2->lap_time != '00:00:00.00' AND $missing_time == false){
					    $str .= '<td class="text-center">'.$dbdata2->lap_time.'</td>';
					}
					else{
					    $str .= '<td class="text-center">&nbsp;</td>';
					    $missing_time = true; //byl nalezen nulový čas a tím pádem se vy výsledcích objeví pouze výsledný čas
					}
					$str .= '<td class="text-center"><i>'.$dbdata2->rank_overall_lap.'</i></td>';
				    }
				    if($i == $this->time_order){ // toto je poslední čas a tím pádem se tady vloží celkový čas a odstup
					$str .= ($dbdata2->race_time != '00:00:00.00') ? ('<td class="text-center">'.$dbdata1->finish_time.'</td>') : ('<td class="text-center">&nbsp;</td>');
					$str .= ($distance_time != '00:00:00.00') ?  ('<td class="text-center">'.$distance_time.'</td>') : ('<td class="text-center">&nbsp;</td>');
				    }
				}
			      $i++; 
			    }
			}
		    $poradi++;

	    }
	    
	}
	$str .= '</tbody></table>';
    }
?>
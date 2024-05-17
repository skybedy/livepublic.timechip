<?php
    /*
     *  MTB Enduro
     */
    $hash_url = '#'.$this->race_year.'/'.$this->race_id.'/';
    $colspan = 4;
    if($category_id == 'all'){
	$str .= '<h4 class="headline-results">'.$this->race_name.', výsledky podle kategorí</h4>';
	$sql = "SELECT id_kategorie,kod_k AS kod_kategorie,nazev_k AS nazev_kategorie,poradi_podzavodu FROM $this->sqlkategorie WHERE id_zavodu = :race_id ORDER BY poradi";
	$sth = $this->db->prepare($sql);
	$sth->execute(Array(':race_id' => $this->race_id));
	if($sth->rowCount()){
	    $str .= '<table  id="table2excel" class="table table-striped table-bordered table-hover noborder table_vysledky">';

	    $k = 1;
	    while($data = $sth->fetchObject()){
		$sql1 = "SELECT "
			    . "CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,"
			    . "osoby.rocnik,tymy.nazev_tymu,"
			    . "$this->sqlvysledky.ids,"
			    . "$this->sqlvysledky.cip,"
			    . "$this->sqlkategorie.nazev_k AS nazev_kategorie,"
			    . "SUM($this->sqlvysledky.lap_time_sec) AS finish_time_sec,"
			    . "SEC_TO_TIME(SUM($this->sqlvysledky.lap_time_sec)) AS finish_time,"
			    . "COUNT($this->sqlvysledky.id) AS pocet_kol, "
			    . "$this->sqlzavod.penalizace "
			. "FROM $this->sqlvysledky,$this->sqlzavod,$this->sqlkategorie,tymy,osoby "
			. "WHERE "
			    . "$this->sqlvysledky.race_time > '0' AND "
			    . "$this->sqlzavod.id_kategorie$this->cislo_kategorie = :id_kategorie AND "
			    . "$this->sqlzavod.id_kategorie$this->cislo_kategorie = $this->sqlkategorie.id_kategorie AND "
			    . "$this->sqlkategorie.poradi_podzavodu = :event_order AND "
			    . "$this->sqlzavod.cip = $this->sqlvysledky.cip AND "
			    . "$this->sqlzavod.id_tymu = tymy.id_tymu AND "
			    . "$this->sqlzavod.ido = osoby.ido AND "
			    . "$this->sqlvysledky.false_time IS NULL AND "
			    . "$this->sqlvysledky.lap_only IS NULL AND "
			    . "$this->sqlvysledky.reader LIKE :reader AND "
			    . "$this->sqlvysledky.lap_count <= :time_order " //tohle by mělo zajistit, že se vybere SUM pouze z počtu kol daného požadivaným time_order
			. "GROUP BY $this->sqlzavod.ids "
			. "ORDER BY pocet_kol DESC,finish_time ASC".$this->rows_limit;
		// echo $sql1."\n\n";
		$sth1 =  $this->db->prepare($sql1);
		$sth1->execute(Array(':id_kategorie' => $data->id_kategorie,':event_order' => $this->event_order,':reader' => 'CIL',':time_order' => $this->time_order));
		if($sth1->rowCount()){
		    $class = $k == 1 ? $class = 'nadpis nopadding' : 'nadpis';
		    $id = $k == 1 ? $id = ' id = "nopadding" ' : ''; //kvůli tomu, aby v prvním nadpisu nebyl padding, pokud se to udělá jen třídou, tak se to nepřepíše, protože i v původním předpisu je !mportant
		    $str .= '<tr><td '.$id.' class="'.$class.'" colspan="3">'.$data->nazev_kategorie.'</td></tr>';
		    
		    $str .= '<tr class="header"><th class="text-center">#</th><th class="text-center">St.č</th><th>Jméno</th><th>Tým/Bydliště</th><th class="text-center">Ročník</th><th class="text-center">Pen</th>';
		    $str .= $this->TableHeaderExtend($this->time_order,$this->event_order,$this->cislo_kategorie).'</tr>';

		    $poradi = 1;
		    while($dbdata1 = $sth1->fetchObject()){
			 if($dbdata1->pocet_kol == $this->time_order){
			    if($poradi == 1) $best_time = $dbdata1->finish_time_sec;
			    $distance_time = $this->DynamicDistances($poradi,$dbdata1->finish_time_sec,$best_time);
			    $str .= '<tr>';
			    $str .= '<td class="text-center">'.$poradi.'</td>';
			    $str .= '<td class="text-center">'.$dbdata1->ids.'</td>';
			    $str .= '<td>'.$dbdata1->jmeno.'</td>';
			    $str .= '<td>'.$dbdata1->nazev_tymu.'</td>';
			    $str .= '<td class="text-center">'.$dbdata1->rocnik.'</td>';
			    $str .= '<td class="text-center">'.$dbdata1->penalizace.'</td>';

			    $sql2 = "SELECT "
				. "race_time,"
				. "lap_time,"
				. "rank_category_lap "
			    . "FROM $this->sqlvysledky "
			    . "WHERE "
				. "cip = :cip AND "
				. "false_time IS NULL AND "
				. "lap_only IS NULL AND "
				. "reader = :reader "
			    . "ORDER BY race_time ASC "
			    . "LIMIT 0,$this->time_order";

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
					    $str .= '<td class="text-center"><i>'.$dbdata2->rank_category_lap.'</i></td>';
					}
					if($i == $this->time_order){ // toto je poslední čas a tím pádem se tady vloží celkový čas a odstup
					    $str .= ($dbdata2->race_time != '00:00:00.00') ? ('<td class="text-center">'.$dbdata1->finish_time.'</td>') : ('<td class="text-center">&nbsp;</td>');
					    $str .= ($distance_time != '00:00:00.00') ?  ('<td class="text-center">'.$distance_time.'</td>') : ('<td class="text-center">&nbsp;</td>');
					}
				    }
				  $i++; 
				}
			    }
			    $str .= '</tr>';
			    $poradi++;
			}
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
	    $str .= '<table class="table table-bordered table-hover table_vysledky">';
	    $k = 1;
	    $sql1 = "SELECT "
			. "CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,"
			. "osoby.rocnik,tymy.nazev_tymu,"
			. "$this->sqlvysledky.ids,"
			. "$this->sqlvysledky.cip,"
			. "$this->sqlkategorie.nazev_k AS nazev_kategorie, "
			. "SUM($this->sqlvysledky.lap_time_sec) AS finish_time_sec,"
			. "SEC_TO_TIME(SUM($this->sqlvysledky.lap_time_sec)) AS finish_time,"
			. "COUNT($this->sqlvysledky.id) AS pocet_kol,"
		        . "$this->sqlzavod.penalizace "
		    . "FROM $this->sqlvysledky,$this->sqlzavod,$this->sqlkategorie,tymy,osoby "
		    . "WHERE "
			. "$this->sqlvysledky.race_time > '0' AND "
			. "$this->sqlzavod.id_kategorie$this->cislo_kategorie = :category_id AND "
			. "$this->sqlzavod.id_kategorie$this->cislo_kategorie = $this->sqlkategorie.id_kategorie AND "
			. "$this->sqlkategorie.poradi_podzavodu = :event_order AND "
			. "$this->sqlzavod.cip = $this->sqlvysledky.cip AND "
			. "$this->sqlzavod.tym = tymy.id_tymu AND "
			. "$this->sqlzavod.ido = osoby.ido AND "
			. "$this->sqlvysledky.false_time IS NULL AND "
			. "$this->sqlvysledky.lap_only IS NULL AND "
			. "$this->sqlvysledky.reader LIKE :reader AND "
			. "$this->sqlvysledky.lap_count <= :time_order " //tohle by mělo zajistit, že se vybere SUM pouze z počtu kol daného požadivaným time_order
		    . "GROUP BY $this->sqlzavod.ids "
		    . "ORDER BY pocet_kol DESC,finish_time ASC";
	    $sth1 =  $this->db->prepare($sql1);
	    $sth1->execute(Array(':category_id' => $category_id,':event_order' => $this->event_order,':reader' => 'CIL',':time_order' => $this->time_order));
	    if($sth1->rowCount()){
		$str .= '<tr class="header"><th class="text-center">#</th><th class="text-center">St.č</th><th>Jméno</th><th>Tým/Bydliště</th><th class="text-center">Ročník</th><th class="text-center">Pen</th>';
		$str .= $this->TableHeaderExtend($this->time_order,$this->event_order,$this->cislo_kategorie).'</tr>';
		$poradi = 1;
		while($dbdata1 = $sth1->fetchObject()){
		    if($dbdata1->pocet_kol == $this->time_order){
			if($poradi == 1) $best_time = $dbdata1->finish_time_sec;
			$distance_time = $this->DynamicDistances($poradi,$dbdata1->finish_time_sec,$best_time);

			$str .= '<tr>';
			$str .= '<td class="text-center">'.$poradi.'</td>';
			$str .= '<td class="text-center">'.$dbdata1->ids.'</td>';
			$str .= '<td>'.$dbdata1->jmeno.'</td>';
			$str .= '<td>'.$dbdata1->nazev_tymu.'</td>';
			$str .= '<td class="text-center">'.$dbdata1->rocnik.'</td>';
			$str .= '<td class="text-center">'.$dbdata1->penalizace.'</td>';

			$sql2 = "SELECT "
				    . "race_time,"
				    . "lap_time,"
				    . "rank_category_lap "
				. "FROM $this->sqlvysledky "
				. "WHERE "
				    . "cip = :cip AND "
				    . "false_time IS NULL AND "
				    . "lap_only IS NULL AND "
				    . "reader = :reader "
				. "ORDER BY race_time ASC "
				. "LIMIT 0,$this->time_order";
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
					$str .= '<td class="text-center"><i>'.$dbdata2->rank_category_lap.'</i></td>';
				    }
				    if($i == $this->time_order){ // toto je poslední čas a tím pádem se tady vloží celkový čas a odstup
					$str .= ($dbdata2->race_time != '00:00:00.00') ? ('<td class="text-center">'.$dbdata1->finish_time.'</td>') : ('<td class="text-center">&nbsp;</td>');
					$str .= ($distance_time != '00:00:00.00') ?  ('<td class="text-center">'.$distance_time.'</td>') : ('<td class="text-center">&nbsp;</td>');
				    }
				}
			      $i++; 
			    }

			}
			$str .= '</tr>';
			$poradi++;
		    }
		}
	    }
	    else{
		$str .= '<p>Žádný výsledek</p>';
	    }

	    $k++;
	    $str .= '</tbody></table>';
	}
    }
?>
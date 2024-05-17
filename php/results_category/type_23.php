<?php
    $colspan = 4;
    $hash_url = '#'.$this->race_year.'/'.$this->race_id.'/';
    if($category_id == 'all'){
	$str .= '<h4 class="headline-results">'.$this->race_name.$this->event_name.', výsledky podle kategorí</h4>';
	$str .= '<h5 class="text-right"><i>#PM = pořadí mezičasu, #PP = pořadí průběžné</i></h5>';
	$sql = "SELECT id_kategorie,kod_k AS kod_kategorie,nazev_k AS nazev_kategorie,poradi_podzavodu FROM $this->sqlkategorie WHERE id_zavodu = :race_id AND poradi_podzavodu = $this->event_order ORDER BY poradi";
	$sth = $this->db->prepare($sql);
	$sth->execute(Array(':race_id' => $this->race_id));
	if($sth->rowCount()){
	    $str .= '<table  id="table2excel" class="table table-striped table-bordered table-hover noborder table_vysledky">';
	    $k = 1;
	    while($data = $sth->fetchObject()){
		$sql1 = "SELECT $this->sqlvysledky.ids,$this->sqlvysledky.ids_alias,$this->sqlvysledky.cip,MAX($this->sqlvysledky.race_time) AS cilovy_cas,$this->sqlvysledky.race_time_sec,CONCAT_WS(' ',$this->sqlosoby.prijmeni,$this->sqlosoby.jmeno) AS jmeno,$this->sqlosoby.rocnik,$this->sqlosoby.psc AS stat,$this->sqlkategorie.nazev_k AS nazev_kategorie,tymy.nazev_tymu FROM $this->sqlvysledky,$this->sqlosoby,$this->sqlzavod,$this->sqlkategorie,tymy "
			  . "WHERE race_time > '0' "
			  . "AND $this->sqlvysledky.time_order = '$this->time_order' " 
			  . "AND $this->sqlzavod.id_kategorie$this->cislo_kategorie = '$data->id_kategorie'  "
			  . "AND $this->sqlzavod.id_kategorie$this->cislo_kategorie = $this->sqlkategorie.id_kategorie "
			  . "AND $this->sqlkategorie.poradi_podzavodu = '$this->event_order' "
			  . "AND $this->sqlzavod.cip = $this->sqlvysledky.cip "
			  . "AND $this->sqlzavod.ido = $this->sqlosoby.ido "
			  . "AND tymy.id_tymu = $this->sqlzavod.prislusnost "
			  . "AND false_time IS NULL "
			  . "AND lap_only IS NULL "
			  . "GROUP BY cip "
			  . "ORDER BY cilovy_cas ASC ".$this->rows_limit;
		//echo $sql1."<br /><br />";
		$sth1 =  $this->db->prepare($sql1);
		$sth1->execute(Array(':cislo_casu' => $this->time_order,':kod_kategorie' => $data->kod_kategorie));
		if($sth1->rowCount()){
		    
		    $class = $k == 1 ? $class = 'nadpis nopading' : 'nadpis';
		    $id = $k == 1 ? $id = ' id = "nopadding" ' : ''; //kvůli tomu, aby v prvním nadpisu nebyl padding, pokud se to udělá jen třídou, tak se to nepřepíše, protože i v původním předpisu je !mportant
		    
		    
		    $str .= '<tr><td '.$id.' class="'.$class.'" colspan="3">'.$data->nazev_kategorie.'</td></tr>';
		    $str .= '<tr class="header"><th class="text-center">Poř</th><th class="text-center">St.č</th><th class="text-left">Jméno</th><th class="text-center">Ročník</th><th class="text-left">Tým/Bydliště</th><th class="text-center">Stát</th>';
		    $str .= $this->TableHeaderCasyNarustem($this->time_order,$this->event_order,$this->cislo_kategorie).'</tr>';
		    $poradi = 1;
		    while($data1 = $sth1->fetchObject()){
			if($this->cislo_kategorie == '_2'){ //pouze pokud se jedná o paralelní kategorie
			    if($poradi == 1) $best_time = $data1->race_time_sec;
			    $distance_time = $this->DynamicDistances($poradi,$data1->race_time_sec,$best_time);
			}
			$str .= '<tr><td class="text-center">'.$poradi.'</td><td class="text-center">'.$data1->ids_alias.'</td><td><a onclick="detail_cipu_lahofer('.$data1->cip.','.$this->race_id.','.$this->race_year.')" href="'.$hash_url.'vysledky">'.$data1->jmeno.'</a></td><td class="text-center">'.$data1->rocnik.'</td><td>'.$this->NahrazkaPomlcky($data1->nazev_tymu).'</td><td class="text-center">'.$data1->stat.'</td>';
			$sql2 = "SELECT * FROM $this->sqlvysledky WHERE cip = :cip AND false_time IS NULL AND lap_only IS NULL ORDER BY race_time LIMIT 0,$this->time_order";
			$sth2 = $this->db->prepare($sql2);
			$sth2->execute(Array(':cip' => $data1->cip));
			$i=1;
			$missing_time = false;
			while($val2 = $sth2->fetchObject()){
			    
			    if($this->time_order == 1){
				$str .= '<td class="text-center">'.$val2->race_time.'</td>';
				if($this->cislo_kategorie == '_2'){ //pouze pokud se jedná o paralelní kategorie
				    $str .= ($distance_time !== '00:00:00.00') ?  ('<td class="text-center">'.$distance_time.'</td>') : ('<td class="text-center">&nbsp;</td>');
				}
				else{  //normal
				    $str .= ($val2->distance_category != '00:00:00.00') ?  ('<td class="text-center">'.$val2->distance_category.'</td>') : ('<td class="text-center">&nbsp;</td>');
				}
			    }
			    
			    
			    else{
				
				if($i <= $this->time_order){
				    //if($val2->race_time != '00:00:00.00' AND $missing_time == false){
				    if($val2->race_time != '00:00:00.00'){
					$str .= '<td class="text-center">'.$val2->race_time.'</td>';
				    }
				    else{
					$str .= '<td class="text-center">&nbsp;</td>';
					$missing_time = true; //byl nalezen nulový čas a tím pádem se vy výsledcích objeví pouze výsledný čas
				    }
				    $str .= '<td class="text-center"><i>'.$val2->rank_category_lap.'</i></td>';
				    if($i > 1 && $i < $this->time_order){
					$str .= '<td class="text-center"><i>'.$val2->rank_category.'</i></td>';
				    }
				}
				
				
				if($i == $this->time_order){
				    if($this->cislo_kategorie == '_2'){ //pouze pokud se jedná o paralelní kategorie
					$str .= ($distance_time !== '00:00:00.00') ?  ('<td class="text-center">'.$distance_time.'</td>') : ('<td class="text-center">&nbsp;</td>');
				    }
				    else{  //normal
					$str .= ($val2->distance_category != '00:00:00.00') ?  ('<td class="text-center">'.$val2->distance_category.'</td>') : ('<td class="text-center">&nbsp;</td>');
				    }
				}
			    }
			    $i++;
			}
			$poradi++;
		    }
		}
		$k++;
		$str .= $this->DNFCAtegory(1,$data->id_kategorie);
		$str .= '</tr>';
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
	    $str .= '<h4 class="headline-results">'.$this->race_name.$this->event_name.', kategorie '.$dbdata->nazev_kategorie.'</h4>';
	    $str .= '<h5 class="text-right"><i>#PM = pořadí mezičasu, #PP = pořadí průběžné</i></h5>';
	    $str .= '<table  id="table2excel" class="table table-striped table-bordered table-hover table_vysledky">';
	    $k = 1;
	    $sql1 = "SELECT $this->sqlvysledky.ids,$this->sqlvysledky.ids_alias,$this->sqlvysledky.cip,MAX($this->sqlvysledky.race_time) AS cilovy_cas,$this->sqlvysledky.race_time_sec,CONCAT_WS(' ',$this->sqlosoby.prijmeni,$this->sqlosoby.jmeno) AS jmeno,$this->sqlosoby.rocnik,$this->sqlosoby.psc AS stat,$this->sqlkategorie.nazev_k AS nazev_kategorie,tymy.nazev_tymu FROM $this->sqlvysledky,$this->sqlosoby,$this->sqlzavod,$this->sqlkategorie,tymy "
			  . "WHERE race_time > '0' "
			  . "AND $this->sqlvysledky.time_order = :time_order " 
			  . "AND $this->sqlkategorie.id_kategorie = :category_id "
			  . "AND $this->sqlzavod.id_kategorie$this->cislo_kategorie = $this->sqlkategorie.id_kategorie "
			  . "AND $this->sqlzavod.cip = $this->sqlvysledky.cip "
			  . "AND $this->sqlzavod.ido = $this->sqlosoby.ido "
			  . "AND tymy.id_tymu = $this->sqlzavod.prislusnost "
			  . "AND false_time IS NULL "
			  . "AND lap_only IS NULL "
			  . "GROUP BY ids "
			  . "ORDER BY cilovy_cas ASC ".$this->rows_limit;
		$sth1 =  $this->db->prepare($sql1);
		$sth1->execute(Array(':time_order' => $this->time_order,':category_id' => $category_id));
		if($sth1->rowCount()){
		    $str .= '<thead><tr class="header"><th class="text-center">Poř</th><th class="text-center">St.č</th><th class="text-left">Jméno</th><th class="text-center">Ročník</th><th class="text-left">Tým/Bydliště</th><th class="text-center">Stát</th>';
		    $str .= $this->TableHeaderCasyNarustem($this->time_order,$this->event_order,$this->cislo_kategorie).'</tr></thead><tbody>';
		    $poradi = 1;
		    while($data1 = $sth1->fetchObject()){
			if($this->cislo_kategorie == '_2'){ //pouze pokud se jedná o paralelní kategorie
			    if($poradi == 1) $best_time = $data1->race_time_sec;
			    $distance_time = $this->DynamicDistances($poradi,$data1->race_time_sec,$best_time);
			}
			
			$str .= '<tr><td class="text-center">'.$poradi.'</td><td class="text-center">'.$data1->ids_alias.'</td><td><a onclick="detail_cipu_lahofer('.$data1->cip.','.$this->race_id.','.$this->race_year.')" href="'.$hash_url.'vysledky">'.$data1->jmeno.'</a></td><td class="text-center">'.$data1->rocnik.'</td><td>'.$this->NahrazkaPomlcky($data1->nazev_tymu).'</td><td class="text-center">'.$data1->stat.'</td>';
			$sql2 = "SELECT * FROM $this->sqlvysledky WHERE cip = :cip AND false_time IS NULL AND lap_only IS NULL ORDER BY race_time LIMIT 0,$this->time_order";
			$sth2 = $this->db->prepare($sql2);
			$sth2->execute(Array(':cip' => $data1->cip));
			$i=1;
			$missing_time = false;
			while($val2 = $sth2->fetchObject()){
			    
			    if($this->time_order == 1){
				$str .= '<td class="text-center">'.$val2->race_time.'</td>';
				if($this->cislo_kategorie == '_2'){ //pouze pokud se jedná o paralelní kategorie
				    $str .= ($distance_time !== '00:00:00.00') ?  ('<td class="text-center">'.$distance_time.'</td>') : ('<td class="text-center">&nbsp;</td>');
				}
				else{  //normal
				    $str .= ($val2->distance_category != '00:00:00.00') ?  ('<td class="text-center">'.$val2->distance_category.'</td>') : ('<td class="text-center">&nbsp;</td>');
				}
			    }
			    
			    else{
				if($i <= $this->time_order){
				    //if($val2->race_time != '00:00:00.00' AND $missing_time == false){
				    if($val2->race_time != '00:00:00.00'){
					$str .= '<td class="text-center">'.$val2->race_time.'</td>';
				    }
				    else{
					$str .= '<td class="text-center">&nbsp;</td>';
					$missing_time = true; //byl nalezen nulový čas a tím pádem se vy výsledcích objeví pouze výsledný čas
				    }
				    $str .= '<td class="text-center"><i>'.$val2->rank_category_lap.'</i></td>';
				    if($i > 1 && $i < $this->time_order){
					$str .= '<td class="text-center"><i>'.$val2->rank_category.'</i></td>';
				    }

				}
				if($i == $this->time_order){
				    if($this->cislo_kategorie == '_2'){ //pouze pokud se jedná o paralelní kategorie
					$str .= ($distance_time !== '00:00:00.00') ?  ('<td class="text-center">'.$distance_time.'</td>') : ('<td class="text-center">&nbsp;</td>');
				    }
				    else{  //normal
					$str .= ($val2->distance_category != '00:00:00.00') ?  ('<td class="text-center">'.$val2->distance_category.'</td>') : ('<td class="text-center">&nbsp;</td>');
				    }
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
	    $str .= $this->DNFCAtegory(1,$category_id);
	    $str .= '</tr>';
	    $str .= '</tbody></table>';
	}
    }
?>
<?php
    $colspan = 4;
    
    /*
     * Podle toho, jestli jde o jednotlivce nebo o tým se pracuje s atributem table-hover u tabulky.. 
     * pokud jde o tým tak "hoverování" vypadá spíš blbě
     */
    $table_hover = 'table-hover';
    if($this->racer_type == 2){
	$table_hover = '';
    }

    if($category_id == 'all'){
	$str .= '<h4 class="headline-results">'.$this->race_name.', výsledky podle kategorí</h4>';
	$sql = "SELECT id_kategorie,kod_k AS kod_kategorie,nazev_k AS nazev_kategorie,poradi_podzavodu FROM $this->sqlkategorie WHERE id_zavodu = :race_id ORDER BY id_kategorie";
	$sth = $this->db->prepare($sql);
	$sth->execute(Array(':race_id' => $this->race_id));
	if($sth->rowCount()){
	    $str .= '<table class="table table-bordered '.$table_hover.' noborder table_vysledky">';
	    $k = 1;
	    while($data = $sth->fetchObject()){
		$sql1 = "SELECT tymy.nazev_tymu,$this->sqlvysledky.ids,$this->sqlvysledky.ids_alias,$this->sqlvysledky.race_time_sec,MAX($this->sqlvysledky.race_time) AS cilovy_cas,$this->sqlkategorie.nazev_k AS nazev_kategorie FROM $this->sqlvysledky,$this->sqlzavod,$this->sqlkategorie,tymy "
			  . "WHERE race_time > '0' "
			  . "AND $this->sqlvysledky.time_order = :time_order " 
			  . "AND $this->sqlzavod.id_kategorie = :id_kategorie  "
			  . "AND $this->sqlzavod.id_kategorie = $this->sqlkategorie.id_kategorie "
			  . "AND $this->sqlkategorie.poradi_podzavodu = '$this->event_order' "
			  . "AND $this->sqlzavod.ids = $this->sqlvysledky.ids "
			  . "AND $this->sqlzavod.tym = tymy.id_tymu "
			  . "AND false_time IS NULL "
			  . "AND lap_only IS NULL "
			  . "GROUP BY $this->sqlzavod.ids "
			  . "ORDER BY cilovy_cas ASC".$this->rows_limit;
		//echo $sql1."\n\n";
		$sth1 =  $this->db->prepare($sql1);
		$sth1->execute(Array(':time_order' => $this->time_order,':id_kategorie' => $data->id_kategorie));
		if($sth1->rowCount()){
		    $class = $k == 1 ? $class = 'nadpis nopadding' : 'nadpis';
		    $str .= '<tr><td class="'.$class.'" colspan="3">'.$data->nazev_kategorie.'</td></tr>';
		    $str .= '<tr class="header"><th class="text-center">#</th><th class="text-center">St.č</th><th>Tým</th><th>Členové týmu</th><th class="text-center">Ročník</th>';
		    $str .= $this->TableHeader($this->time_order,$this->event_order,1).'</tr>';
		    $poradi = 1;
		    while($data1 = $sth1->fetchObject()){
			if($poradi == 1) $best_time = $data1->race_time_sec;
			$distance_time = $this->DynamicDistances($poradi,$data1->race_time_sec,$best_time);
			$sql3 = "SELECT CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik FROM osoby,$this->sqlzavod WHERE $this->sqlzavod.ids = :ids AND $this->sqlzavod.ido = osoby.ido ORDER BY $this->sqlzavod.id";
			$sth3 = $this->db->prepare($sql3);
			$sth3->execute(Array(':ids' => $data1->ids));
			$pocet_clenu = $sth3->rowCount(); //počet členů týmu, který potřebujeme na rowspan
			$z = 1;
			while($dbdata3 = $sth3->fetchObject()){
			    if($z == 1){
				$str .= '<tr>';
				$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$poradi.'</td>';
				$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$data1->ids_alias.'</td>';
				$str .= '<td rowspan="'.$pocet_clenu.'">'.$data1->nazev_tymu.'</td>';
				$str .= '<td>'.$dbdata3->jmeno.'</td>';
				$str .= '<td class="text-center">'.$dbdata3->rocnik.'</td>';
				$sql2 = "SELECT * FROM $this->sqlvysledky WHERE ids = '$data1->ids' AND false_time IS NULL AND lap_only IS NULL ORDER BY race_time LIMIT 0,$this->time_order";
				$sth2 = $this->db->prepare($sql2);
				$sth2->execute(Array(':ids' => $data1->ids));
				$i=1;
				$missing_time = false;
				while($val2 = $sth2->fetchObject()){
				    if($this->time_order == 1){
					$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$val2->lap_time.'</td>';
					$str .= ($distance_time != '00:00:00.00') ?  ('<td rowspan="'.$pocet_clenu.'" class="text-center">'.$distance_time.'</td>') : ('<td rowspan="'.$pocet_clenu.'" class="text-center">&nbsp;</td>');
				    }
				    else{
					if($i <= $this->time_order){
					    if($val2->lap_time != '00:00:00.00' AND $missing_time == false){
						$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$val2->lap_time.'</td>';
					    }
					    else{
						$str .= '<td class="text-center">&nbsp;</td>';
						$missing_time = true; //byl nalezen nulový čas a tím pádem se vy výsledcích objeví pouze výsledný čas
					    }
					}
					if($i == $this->time_order){
					    $str .= ($val2->race_time != '00:00:00.00') ? ('<td rowspan="'.$pocet_clenu.'" class="text-center">'.$val2->race_time.'</td>') : ('<td rowspan="'.$pocet_clenu.'" class="text-center">&nbsp;</td>');
					    $str .= ($distance_time != '00:00:00.00') ?  ('<td rowspan="'.$pocet_clenu.'" class="text-center">'.$distance_time.'</td>') : ('<td rowspan="'.$pocet_clenu.'" class="text-center">&nbsp;</td>');
					}
				    }
				    $i++;
				}
				
				$str .= '</tr>';
			    }
			    else{
				$str .= '<tr>';
				$str .= '<td>'.$dbdata3->jmeno.'</td>';
				$str .= '<td class="text-center">'.$dbdata3->rocnik.'</td>';
				$str .= '</tr>';
			    }
			    $z++;
			}
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
	    $str .= '<table class="table table-bordered '.$table_hover.' table_vysledky">';
	    $k = 1;
	    $sql1 = "SELECT tymy.nazev_tymu,$this->sqlvysledky.ids,$this->sqlvysledky.ids_alias,$this->sqlvysledky.race_time_sec,MAX($this->sqlvysledky.race_time) AS cilovy_cas,$this->sqlkategorie.nazev_k AS nazev_kategorie FROM $this->sqlvysledky,$this->sqlzavod,$this->sqlkategorie,tymy "
		      . "WHERE race_time > '0' "
		      . "AND $this->sqlvysledky.time_order = :time_order " 
		      . "AND $this->sqlzavod.id_kategorie = :category_id "
		      . "AND $this->sqlzavod.id_kategorie = $this->sqlkategorie.id_kategorie "
		      . "AND $this->sqlkategorie.poradi_podzavodu = '$this->event_order' "
		      . "AND $this->sqlzavod.ids = $this->sqlvysledky.ids "
		      . "AND $this->sqlzavod.tym = tymy.id_tymu "
		      . "AND false_time IS NULL "
		      . "AND lap_only IS NULL "
		      . "GROUP BY $this->sqlzavod.ids "
		      . "ORDER BY cilovy_cas ASC".$this->rows_limit;
		$sth1 =  $this->db->prepare($sql1);
		$sth1->execute(Array(':time_order' => $this->time_order,':category_id' => $category_id));
		if($sth1->rowCount()){
		    $str .= '<thead><tr class="header"><th class="text-center">#</th><th class="text-center">St.č</th><th>Název týmu</th><th>Členové týmu</th><th class="text-center">Ročník</th>';
		    $str .= $this->TableHeader($this->time_order,$this->event_order,1).'</tr>';
		    $poradi = 1;
		    while($data1 = $sth1->fetchObject()){
			if($poradi == 1) $best_time = $data1->race_time_sec;
			$distance_time = $this->DynamicDistances($poradi,$data1->race_time_sec,$best_time);
			$sql3 = "SELECT CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik FROM osoby,$this->sqlzavod WHERE $this->sqlzavod.ids = :ids AND $this->sqlzavod.ido = osoby.ido ORDER BY $this->sqlzavod.id";
			$sth3 = $this->db->prepare($sql3);
			$sth3->execute(Array(':ids' => $data1->ids));
			$pocet_clenu = $sth3->rowCount(); //počet členů týmu, který potřebujeme na rowspan
			$z = 1;
			while($dbdata3 = $sth3->fetchObject()){
			    if($z == 1){
				$str .= '<tr>';
				$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$poradi.'</td>';
				$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$data1->ids_alias.'</td>';
				$str .= '<td rowspan="'.$pocet_clenu.'">'.$data1->nazev_tymu.'</td>';
				$str .= '<td>'.$dbdata3->jmeno.'</td>';
				$str .= '<td class="text-center">'.$dbdata3->rocnik.'</td>';
				$sql2 = "SELECT * FROM $this->sqlvysledky WHERE ids = '$data1->ids' AND false_time IS NULL AND lap_only IS NULL ORDER BY race_time LIMIT 0,$this->time_order";
				$sth2 = $this->db->prepare($sql2);
				$sth2->execute(Array(':ids' => $data1->ids));
				$i=1;
				$missing_time = false;
				while($val2 = $sth2->fetchObject()){
				    if($this->time_order == 1){
					$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$val2->lap_time.'</td>';
					$str .= ($distance_time != '00:00:00.00') ?  ('<td rowspan="'.$pocet_clenu.'" class="text-center">'.$distance_time.'</td>') : ('<td rowspan="'.$pocet_clenu.'" class="text-center">&nbsp;</td>');
				    }
				    else{
					if($i <= $this->time_order){
					    if($val2->lap_time != '00:00:00.00' AND $missing_time == false){
						$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$val2->lap_time.'</td>';
					    }
					    else{
						$str .= '<td class="text-center">&nbsp;</td>';
						$missing_time = true; //byl nalezen nulový čas a tím pádem se vy výsledcích objeví pouze výsledný čas
					    }
					}
					if($i == $this->time_order){
					    $str .= ($val2->race_time != '00:00:00.00') ? ('<td rowspan="'.$pocet_clenu.'" class="text-center">'.$val2->race_time.'</td>') : ('<td rowspan="'.$pocet_clenu.'" class="text-center">&nbsp;</td>');
					    $str .= ($distance_time != '00:00:00.00') ?  ('<td rowspan="'.$pocet_clenu.'" class="text-center">'.$distance_time.'</td>') : ('<td rowspan="'.$pocet_clenu.'" class="text-center">&nbsp;</td>');
					}
				    }
				    $i++;
				}
				
				$str .= '</tr>';
			    }
			    else{
				$str .= '<tr>';
				$str .= '<td>'.$dbdata3->jmeno.'</td>';
				$str .= '<td class="text-center">'.$dbdata3->rocnik.'</td>';
				$str .= '</tr>';
			    }
			    $z++;
			}
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
?>
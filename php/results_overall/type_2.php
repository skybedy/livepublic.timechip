<?php
	    
    /*
     * Podle toho, jestli jde o jednotlivce nebo o tým se pracuje s atributem table-hover u tabulky.. 
     * pokud jde o tým tak "hoverování" vypadá spíš blbě
     */
    $table_hover = 'table-hover';
    if($this->racer_type == 2){
	$table_hover = '';
    }

    /* varianta týmy, každý člen svůj čip, použito Orlice Cup */
    $sql1 = "SELECT tymy.nazev_tymu,$this->sqlvysledky.ids,$this->sqlvysledky.ids_alias,MAX($this->sqlvysledky.race_time) AS cilovy_cas,$this->sqlvysledky.race_time_sec,$this->sqlkategorie.nazev_k AS nazev_kategorie FROM $this->sqlvysledky,tymy,$this->sqlzavod,$this->sqlkategorie WHERE race_time > 0 AND $this->sqlvysledky.time_order = :time_order AND $this->sqlkategorie.id_zavodu = :race_id AND $this->sqlkategorie.poradi_podzavodu = :event_order AND $this->sqlzavod.ids = $this->sqlvysledky.ids AND $this->sqlzavod.tym = tymy.id_tymu AND $this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie AND $this->sqlvysledky.false_time IS NULL AND $this->sqlvysledky.lap_only IS NULL GROUP BY $this->sqlvysledky.ids ORDER BY cilovy_cas ASC".$this->rows_limit;
    //echo $sql1;
    $sth1 =  $this->db->prepare($sql1);
    $sth1->execute(Array(':time_order' => $this->time_order,':race_id' => $this->race_id,':event_order' => $this->event_order));
    if($sth1->rowCount()){
	$str .= '<h4 class="headline-results">'.$this->race_name.', výsledky bez rozdílu kategorií</h4>';
	$str .= '<table class="table table-bordered '.$table_hover.' table_vysledky">';
	$str .= '<thead><tr class="header"><th class="text-center">#</th><th class="text-center">St.č</th><th>Tým</th><th class="text-center">Kategorie</th><th>Člen týmu</th><th class="text-center">Ročník</th>';
	$str .= $this->TableHeader($this->time_order,$this->event_order,1);
	$str .= '</tr></thead><tbody>';
	$poradi = 1;
	while($data1 = $sth1->fetchObject()){//cyklus, v kterém se vyberou všecky časy konkrétního týmu
	    if($poradi == 1) $best_time = $data1->race_time_sec;
	    $distance_time = $this->DynamicDistances($poradi,$data1->race_time_sec,$best_time);
	    $sql3 = "SELECT CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik FROM osoby,$this->sqlzavod WHERE $this->sqlzavod.ids = :ids AND $this->sqlzavod.ido = osoby.ido ORDER BY $this->sqlzavod.id";
		$sth3 = $this->db->prepare($sql3);
		$sth3->execute(Array(':ids' => $data1->ids));
		$pocet_clenu = $sth3->rowCount(); //počet členů týmu, který potřebujeme na rowspan
		$k = 1;
		while($dbdata3 = $sth3->fetchObject()){
		    if($k == 1){
			$str .= '<tr>';
			$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$poradi.'</td>';
			$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$data1->ids_alias.'</td>';
			$str .= '<td rowspan="'.$pocet_clenu.'">'.$data1->nazev_tymu.'</td>';
			$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$data1->nazev_kategorie.'</td>';
			$str .= '<td>'.$dbdata3->jmeno.'</td>';
			$str .= '<td class="text-center">'.$dbdata3->rocnik.'</td>';
			$sql2 = "SELECT * FROM $this->sqlvysledky WHERE ids = :ids AND false_time IS NULL AND lap_only IS NULL ORDER BY race_time ASC LIMIT 0,$this->time_order";
			$sth2 = $this->db->prepare($sql2);
			$sth2->execute(Array(':ids' => $data1->ids));
			$i = 1;
			$missing_time = false; //nastavení proměnné pro konntrolu, jestli má závodník všecky časy 
			while($val2 = $sth2->fetchObject()){
			    if($this->time_order == 1){//pokud je to první čas 
				$str .= '<td  rowspan="'.$pocet_clenu.'" class="text-center">'.$val2->lap_time.'</td>';
				$str .= ($distance_time != '00:00:00.00') ?  ('<td rowspan="'.$pocet_clenu.'" class="text-center">'.$distance_time.'</td>') : ('<td  rowspan="'.$pocet_clenu.'" class="text-center">&nbsp;</td>');
			    }
			    else{//pokud je to jiný než první čas
				if($i <= $this->time_order){ 
				    if($val2->lap_time != '00:00:00.00' AND $missing_time == false){
					$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$val2->lap_time.'</td>';
				    }
				    else{
				       $str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">&nbsp;</td>';
				       $missing_time = true; //byl nalezen nulový čas a tím pádem se vy výsledcích objeví pouze výsledný čas
				    }
				}
				if($i == $this->time_order){ // toto je poslední čas a tím pádem se tady vloží celkový čas a odstup
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

		    $k++;
		}
		$poradi++;
	}
	$str .= '</tbody></table>';
    }

?>
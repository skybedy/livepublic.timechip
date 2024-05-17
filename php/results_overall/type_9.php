<?php  
    /* varianta týmy, tři časy, vALACHIARUN vSETÍN
     * 
     * 
     *   
     */
    // v jednom dotazu se vyberou týmy, počet kol, celkový čas....
    //$sql1 = "SELECT tymy.nazev_tymu,$this->sqlvysledky.ids,$this->sqlvysledky.ids_alias,$this->sqlvysledky.race_time AS finish_time, $this->sqlvysledky.distance_overall,$this->sqlkategorie.nazev_k AS nazev_kategorie FROM $this->sqlvysledky,tymy,$this->sqlzavod,$this->sqlkategorie WHERE race_time > 0 AND $this->sqlkategorie.id_zavodu = '$this->race_id' AND $this->sqlkategorie.poradi_podzavodu = '$this->event_order' AND $this->sqlzavod.ids = $this->sqlvysledky.ids AND $this->sqlzavod.tym = tymy.id_tymu AND $this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie AND $this->sqlvysledky.false_time IS NULL AND $this->sqlvysledky.lap_only IS NULL GROUP BY $this->sqlvysledky.ids ORDER BY finish_time ASC";
    $sql1 = "SELECT tymy.nazev_tymu,$this->sqlvysledky.ids,$this->sqlvysledky.ids_alias,$this->sqlvysledky.race_time AS finish_time, $this->sqlvysledky.distance_overall,$this->sqlkategorie.nazev_k AS nazev_kategorie FROM $this->sqlvysledky,tymy,$this->sqlzavod,$this->sqlkategorie "
	      . "WHERE "
	      . "race_time > 0 AND "
	      . "$this->sqlzavod.poradi_podzavodu = $this->event_order AND "
	      . "$this->sqlzavod.ids = $this->sqlvysledky.ids AND "
	      . "$this->sqlzavod.tym = tymy.id_tymu AND "
	      . "$this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie AND "
	      . "$this->sqlvysledky.false_time IS NULL AND "
	      . "$this->sqlvysledky.time_order = '$this->time_order' AND "
	      . "$this->sqlvysledky.lap_only IS NULL "
	      . "GROUP BY $this->sqlvysledky.ids ORDER BY finish_time ASC";
    //echo $sql1;
    $sth1 =  $this->db->prepare($sql1);
    //$sth1->execute(Array(':race_id' => $this->race_id,':event_order' => $this->event_order));
    $sth1->execute(Array());
    if($sth1->rowCount()){
	$str .= '<h4 class="headline-results">'.$this->race_name.', výsledky bez rozdílu kategorií</h4>';
	$str .= '<table class="table table-bordered table-hover table_vysledky">';
	$str .= '<thead><tr class="header"><th class="text-center">#</th><th class="text-center">St.č</th><th>Název týmu</th><th>Kat</th><th>Členové týmu</th><th class="text-center">Ročník</th>';
	$str .= $this->TableHeaderValachiarun($this->time_order,$this->event_order,$this->cislo_kategorie).'</tr>';
	$str .= '</tr></thead><tbody>';
	$poradi = 1;
	while($data1 = $sth1->fetchObject()){ //začneme vypisovat první dotaz
	    // tady vybereme jednotlivé členy týmu
	    $sql3 = "SELECT CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik FROM osoby,$this->sqlzavod WHERE $this->sqlzavod.ids = :ids AND $this->sqlzavod.ido = osoby.ido ORDER BY $this->sqlzavod.id";
		$sth3 = $this->db->prepare($sql3);
		$sth3->execute(Array(':ids' => $data1->ids));
		$pocet_clenu = $sth3->rowCount(); //počet členů týmu, který potřebujeme na rowspan
		$pocet_clenu = 2;
		$z = 1;
		while($dbdata3 = $sth3->fetchObject()){
		    if($z == 1){
			$str .= '<tr>';
			$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$poradi.'</td>';
			$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$data1->ids_alias.'</td>';
			$str .= '<td rowspan="'.$pocet_clenu.'" class="align_left">'.$data1->nazev_tymu.'</td>';
			$str .= '<td rowspan="'.$pocet_clenu.'">'.$data1->nazev_kategorie.'</td>';
			$str .= '<td  class="align_left">'.$dbdata3->jmeno.'</td>';
			$str .= '<td class="text-center">'.$dbdata3->rocnik.'</td>';
			
			$sql4 = "SELECT * FROM $this->sqlvysledky WHERE ids = '$data1->ids' AND false_time IS NULL AND lap_only IS NULL ORDER BY race_time LIMIT 0,$this->time_order";
			$sth4 = $this->db->prepare($sql4);
			$sth4->execute();
			$i=1;
			$missing_time = false;
			while($val2 = $sth4->fetchObject()){
				    if($this->time_order == 1){
					$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$val2->race_time.'</td>';
					//$str .= ($val2->distance_category != '00:00:00.00') ?  ('<td rowspan="'.$pocet_clenu.'" class="text-center">'.$val2->distance_category.'</td>') : ('<td rowspan="'.$pocet_clenu.'" class="text-center">&nbsp;</td>');
				    }
				    else{
					if($i <= $this->time_order){
					    if($val2->lap_time != '00:00:00.00' AND $missing_time == false){
						$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$val2->race_time.'</td>';
					    }
					    else{
						$str .= '<td class="text-center">&nbsp;</td>';
						$missing_time = true; //byl nalezen nulový čas a tím pádem se vy výsledcích objeví pouze výsledný čas
					    }
					}
					if($i == $this->time_order){
					    //$str .= ($val2->distance_overall != '00:00:00.00') ?  ('<td rowspan="'.$pocet_clenu.'" class="text-center">'.$val2->distance_overall.'</td>') : ('<td rowspan="'.$pocet_clenu.'" class="text-center">&nbsp;</td>');
					}
				    }
				    $i++;
				}
				
				$str .= '</tr>';
			    }
			    else{
				$str .= '<tr>';
				$str .= '<td  class="align_left">'.$dbdata3->jmeno.'</td>';
				$str .= '<td class="text-center">'.$dbdata3->rocnik.'</td>';
				$str .= '</tr>';
			    }
			    $z++;
		}
		$poradi++;
	}
	$str .= '</tbody></table>';
    }
?>
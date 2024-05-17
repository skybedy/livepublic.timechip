<?php
	    /*  Orlice cup, vybírá se tady i jméno podzávodu, které se jinde nevybírá.. družstva jednočlenné i vícečlenné a každý má své číslo čipu
	     * v tabulce je jméno jednotlivce a pak název týmu a ještě jméno podzávodu
	     */
	    $autoreading_results = '<table class="table table-striped table-bordered table-hover"><thead><tr><th class="text-center">Poř.</th><th class="text-center">St.č</th><th class="text-center">Čip</th><th>Jméno a příjmení</th><th>Tým</th><th class="text-center">Závod</th><th class="text-center">Čas</th><th class="text-center">Odstup</th><th class="text-center">Kategorie</th><th class="text-center">Pořadí</th><th class="text-center">Odstup</th></tr></thead><tbody>';
	    /* pokud je v db alespoň jeden záznam */
	    if($this->MaxTimeOrder()){
		$max_time_order = $this->MaxTimeOrder();
		$best_time = $this->BestTime($max_time_order);
		$number_competitors_records = $this->NumberCompetitorRecords($max_time_order);
		$sql1 = "SELECT COUNT(ids) AS pocet_zaznamu FROM $this->sqlvysledky WHERE time_order = '$max_time_order' AND false_time IS NULL";
		$sth1 = $this->db->query($sql1);
		$data1 = $sth1->fetchObject();
		$sql2 = "SELECT $this->sqlvysledky.ids,$this->sqlvysledky.ids_alias,$this->sqlvysledky.cip,$this->sqlkategorie.nazev_k AS nazev_kategorie,tymy.nazev_tymu,CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,$this->sqlpodzavody.nazev AS nazev_podzavodu FROM $this->sqlvysledky,$this->sqlzavod,$this->sqlkategorie,tymy,osoby,$this->sqlpodzavody WHERE $this->sqlzavod.ido = osoby.ido AND $this->sqlvysledky.race_time = :best_time AND $this->sqlvysledky.time_order = :time_order AND $this->sqlzavod.poradi_podzavodu = $this->sqlpodzavody.poradi_podzavodu AND $this->sqlpodzavody.id_zavodu = :race_id AND $this->sqlzavod.cip = $this->sqlvysledky.cip AND $this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie AND tymy.id_tymu = $this->sqlzavod.tym AND $this->sqlvysledky.false_time IS NULL  GROUP BY $this->sqlzavod.ids ORDER BY $this->sqlvysledky.race_time LIMIT 0,1";
		$sth2 = $this->db->prepare($sql2);
		$sth2->execute(Array(':best_time' => $best_time,':time_order' => $max_time_order,':race_id' => $this->race_id));
		$data2 = $sth2->fetchObject();
		
		/**** dodělat podmínky, nebo spíš to udělat tak, ať se to vůbec nedostane do db ****/
		$autoreading_results .= '<tr><td class="text-center">1</td><td class="text-center">'.$data2->ids_alias.'</td><td class="text-center">'.$data2->cip.'</td><td>'.$data2->jmeno.'</td><td>'.$data2->nazev_tymu.'</td><td class="text-center">'.$data2->nazev_podzavodu.'</td><td class="text-center">'.$best_time.'</td><td class="text-center">-</td><td class="text-center">'.$data2->nazev_kategorie.'</td><td class="text-center">1</td><td class="text-center">-</td></tr>'; 
		
		
		$prvni_zaznam = $data1->pocet_zaznamu - 9;
		$sql3 = "SELECT $this->sqlvysledky.ids,$this->sqlvysledky.ids_alias,$this->sqlvysledky.cip,$this->sqlvysledky.race_time,$this->sqlvysledky.distance_overall,$this->sqlvysledky.distance_category,$this->sqlvysledky.distance_gender,$this->sqlvysledky.rank_category,$this->sqlvysledky.rank_gender,$this->sqlkategorie.nazev_k AS nazev_kategorie,tymy.nazev_tymu,CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,$this->sqlpodzavody.nazev AS nazev_podzavodu  FROM $this->sqlvysledky,$this->sqlzavod,$this->sqlkategorie,tymy,osoby,$this->sqlpodzavody WHERE $this->sqlzavod.ido = osoby.ido AND $this->sqlvysledky.time_order = :time_order AND $this->sqlvysledky.cip = $this->sqlzavod.cip AND $this->sqlzavod.poradi_podzavodu = $this->sqlpodzavody.poradi_podzavodu AND $this->sqlpodzavody.id_zavodu = :race_id AND $this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie AND tymy.id_tymu = $this->sqlzavod.tym AND $this->sqlvysledky.false_time IS NULL GROUP BY $this->sqlzavod.ids ORDER BY $this->sqlvysledky.race_time LIMIT ";
		$sql3 .= ($data1->pocet_zaznamu > 10) ? ("$prvni_zaznam,9") : ("1,9");
		$sth3 = $this->db->prepare($sql3);
		$sth3->execute(Array(':time_order' => $max_time_order,':race_id' => $this->race_id));
		if($sth3->rowCount()){
		    ($data1->pocet_zaznamu > 10) ? ($poradi = $prvni_zaznam + 1) : ($poradi = 2);
		    while($data3 = $sth3->fetchObject()){
			$autoreading_results .= '<tr><td class="text-center">'.$poradi.'</td><td class="text-center">'.$data3->ids_alias.'</td><td class="text-center">'.$data3->cip.'</td><td class="align_left">'.$data3->jmeno.'</td><td class="align_left">'.$data3->nazev_tymu.'</td><td class="text-center">'.$data3->nazev_podzavodu.'</td><td class="text-center">'.$data3->race_time.'</td><td class="text-center">'.$this->NullsReplacement($data3->distance_overall).'</td><td class="text-center">'.$data3->nazev_kategorie.'</td><td class="text-center">'.$data3->rank_category.'</td><td class="text-center">'.$this->NullsReplacement($data3->distance_category).'</td></tr>';
			$poradi++;
		    }
		}
	    
		$autoreading_results .= '</tbody></table>';
		
	    }
	    else{
		$autoreading_results .= '<p>Zatím není nahraný žádný čas</p>';
	    }

?>

	    

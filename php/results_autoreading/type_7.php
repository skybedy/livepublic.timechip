<?php
	    /*  bez nejlepšího času, prostě posledních 30 lidí tak jak se řadí do db  */
	    $autoreading_results = '';
	    /* pokud je v db alespoň jeden záznam */
	    if($this->MaxTimeOrder()){
		$autoreading_results .= '<table class="table table-striped table-bordered table-hover">';
		//$autoreading_results .= '<thead><tr><th class="text-center">Poř.</th><th class="text-center">St.č</th><th class="text-center">Čip</th><th>Jméno a příjmení</th><th class="text-center">Ročník</th><th>Tým/Bydliště</th><th class="text-center">Čas</th><th class="text-center">Odstup</th><th>Kategorie</th><th class="text-center">Pořadí</th><th class="text-center">Odstup</th></tr></thead>';
		$autoreading_results .= '<thead><tr><th class="text-center">St.č</th><th class="text-center">Čip</th><th>Jméno a příjmení</th><th class="text-center">Ročník</th><th>Tým/Bydliště</th><th class="text-center">Čas</th><th class="text-center">Odstup</th><th>Kategorie</th><th class="text-center">Pořadí</th><th class="text-center">Odstup</th></tr></thead>';
		$max_time_order = $this->MaxTimeOrder();
		$best_time = $this->BestTime($max_time_order);
		$number_competitors_records = $this->NumberCompetitorRecords($max_time_order);
		$sql1 = "SELECT COUNT(ids) AS pocet_zaznamu FROM $this->sqlvysledky WHERE time_order = '$max_time_order' AND false_time IS NULL";
		$sth1 = $this->db->query($sql1);
		$data1 = $sth1->fetchObject();
		$prvni_zaznam = $data1->pocet_zaznamu - 29;
		
		$sql3 = "SELECT $this->sqlvysledky.ids,$this->sqlvysledky.ids_alias,$this->sqlvysledky.cip,$this->sqlvysledky.race_time,$this->sqlvysledky.distance_overall,$this->sqlvysledky.distance_category,$this->sqlvysledky.distance_gender,$this->sqlvysledky.rank_category,$this->sqlvysledky.rank_gender,CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,osoby.psc AS stat,$this->sqlkategorie.nazev_k AS nazev_kategorie,tymy.nazev_tymu FROM $this->sqlvysledky,osoby,$this->sqlzavod,$this->sqlkategorie,tymy WHERE $this->sqlvysledky.time_order = '$max_time_order' AND $this->sqlvysledky.ids = $this->sqlzavod.ids AND $this->sqlzavod.ido = osoby.ido AND $this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie AND tymy.id_tymu = $this->sqlzavod.id_tymu AND $this->sqlvysledky.false_time IS NULL ORDER BY $this->sqlvysledky.id DESC LIMIT  ";
		$sql3 .= ($data1->pocet_zaznamu > 30) ? ("$prvni_zaznam,29") : ("0,30");
		//echo $sql3;
		$sth3 = $this->db->prepare($sql3);
		$sth3->execute(Array(':time_order' => $max_time_order));
		if($sth3->rowCount()){
		    while($data3 = $sth3->fetchObject()){
			//i s pohlavíma
			//$autoreading_results .= '<tr><td class="text-center">'.$poradi.'</td><td class="text-center">'.$data3->ids_alias.'</td><td class="text-center">'.$data3->cip.'</td><td class="align_left">'.$data3->jmeno.'</td><td class="text-center">'.$data3->rocnik.'</td><td class="align_left">'.$data3->nazev_tymu.'</td><td class="text-center">'.$data3->race_time.'</td><td class="text-center">'.$this->NullsReplacement($data3->distance_overall).'</td><td>'.$data3->nazev_kategorie.'</td><td class="text-center">'.$data3->rank_category.'</td><td class="text-center">'.$this->NullsReplacement($data3->distance_category).'</td><td class="text-center">'.$data3->rank_gender.'</td><td class="text-center">'.$this->NullsReplacement($data3->distance_gender).'</td></tr>';
			$autoreading_results .= '<tr><td class="text-center">'.$data3->ids_alias.'</td><td class="text-center">'.$data3->cip.'</td><td class="align_left">'.$data3->jmeno.'</td><td class="text-center">'.$data3->rocnik.'</td><td class="align_left">'.$data3->nazev_tymu.'</td><td class="text-center">'.$data3->race_time.'</td><td class="text-center">'.$this->NullsReplacement($data3->distance_overall).'</td><td>'.$data3->nazev_kategorie.'</td><td class="text-center">'.$data3->rank_category.'</td><td class="text-center">'.$this->NullsReplacement($data3->distance_category).'</td></tr>';
		    }
		}
		$autoreading_results .= '</table>';
	    }
	    else{
		$autoreading_results .= '<p>Zatím není nahraný žádný čas</p>';
	    }
	    
?>
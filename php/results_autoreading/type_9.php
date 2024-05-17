<?php
	    /*  bez nejlepšího času, posledních 5 lidí v db, řazení zhora a je tedy použit jednoduchý formát sql dotazu  */
	    $autoreading_results = '';
	    /* pokud je v db alespoň jeden záznam */
	    if($this->MaxTimeOrder()){
		
		$autoreading_results .= '<table class="table table-striped table-bordered table-hover stredni_radky">';
		$autoreading_results .= '<thead><tr><th rowspan="2" class="text-center">Poř</th><th rowspan="2" class="text-center">Stč</th><th rowspan="2">Jméno a příjmení</th><th rowspan="2" class="text-center">Roč</th><th rowspan="2">Tým/Bydliště</th><th rowspan="2" class="text-center">Čas</th><th class="text-center" rowspan="2" >Na prvního</th><th colspan="2" class="text-center j_k">Muži/Ženy</th><th colspan="3" class="text-center j_k">Kategorie</th></tr><tr><th class="text-center">Poř</th><th class="text-center">Na prvního</th><th class="text-center">Název</th><th class="text-center">Poř</th><th class="text-center">Na prvního</th></tr></thead>';
		$max_time_order = $this->MaxTimeOrder();
		$sql3 = "SELECT $this->sqlvysledky.ids,$this->sqlvysledky.ids_alias,$this->sqlvysledky.cip,$this->sqlvysledky.race_time,$this->sqlvysledky.distance_gender,$this->sqlvysledky.distance_overall,$this->sqlvysledky.distance_category,$this->sqlvysledky.rank_category,$this->sqlvysledky.rank_gender,$this->sqlvysledky.rank_overall,CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS cele_jmeno,osoby.prijmeni_bez_diakritiky AS prijmeni,osoby.rocnik,osoby.psc AS stat,$this->sqlkategorie.nazev_k AS nazev_kategorie,tymy.nazev_tymu FROM $this->sqlvysledky,osoby,$this->sqlzavod,$this->sqlkategorie,tymy WHERE "
			  . "$this->sqlvysledky.time_order = :time_order AND "
			  . "$this->sqlvysledky.ids = $this->sqlzavod.ids AND "
			  . "$this->sqlzavod.ido = osoby.ido AND "
			  . "$this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie AND "
			  . "tymy.id_tymu = $this->sqlzavod.id_tymu AND "
			  . "$this->sqlvysledky.false_time IS NULL "
			  . "ORDER BY $this->sqlvysledky.id DESC LIMIT 0,5";
		$sth3 = $this->db->prepare($sql3);
		$sth3->execute(Array(':time_order' => $max_time_order));
		if($sth3->rowCount()){
		    while($data3 = $sth3->fetchObject()){
			$autoreading_results .= '<tr><td class="text-center color_red">'.$data3->rank_overall.'</td><td class="text-center">'.$data3->ids_alias.'</td><td class="align_left">'.$data3->cele_jmeno.'</td><td class="text-center">'.$data3->rocnik.'</td><td class="align_left">'.$data3->nazev_tymu.'</td><td class="text-center">'.$data3->race_time.'</td><td class="text-center">'.$this->NullsReplacement($data3->distance_overall).'</td><td class="text-center color_red">'.$data3->rank_gender.'</td><td class="text-center">'.$this->NullsReplacement($data3->distance_gender).'</td><td class="text-center">'.$data3->nazev_kategorie.'</td><td class="text-center color_red">'.$data3->rank_category.'</td><td class="text-center">'.$this->NullsReplacement($data3->distance_category).'</td></tr>';
		    }
		}
		$autoreading_results .= '</table>';
	    }
	    else{
		$autoreading_results .= '<p>Zatím není nahraný žádný čas</p>';
	    }
	    
?>
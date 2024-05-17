<?php

      //	$disc = Array('BEH','PLV','BRU','CIL');
		//$disc = Array('BEH','MTB','CIL','N/A','N/A','N/A','N/A');
		//$disc = Array('MEZ','CIL','N/A','N/A','N/A','N/A');
                //$disc = Array('LAP','CIL','N/A','N/A','N/A'); 
		$disc = Array('CIL','N/A','N/A','N/A','N/A');
		//$disc = Array('BG1','MTB','MET');
		// $disc = Array('CIL','KOL','CIL'); 
		//$disc = Array('PLA','KOL','CIL','N/A','N/A','N/A','N/A');  
    //$disc = Array('1KL','2KL','N/A','N/A','N/A','N/A');     

     //$disc = Array('BEH','CIL');
  	//$disc = Array('MZC','CIL','N/A','N/A','N/A'); 
	//$disc = Array('BEH','MTB','CIL','N/A','N/A','N/A','N/A'); 
	//$disc = Array('LAP','LAP','CIL','N/A','N/A','N/A'); 

	    /*  bez nejlepšího času, prostě posledních 30 lidí tak jak se řadí do db  */
	    $autoreading_results = '';
	    $led_panel_str = '';
	    /* pokud je v db alespoň jeden záznam */
		$autoreading_results .= '<table class="table table-striped table-bordered table-hover autoreding_results stredni_radky">';

		//$autoreading_results .= '<thead><tr><th class="text-center">Poř.</th><th class="text-center">St.č</th><th class="text-center">Čip</th><th>Jméno a příjmení</th><th class="text-center">Ročník</th><th>Tým/Bydliště</th><th class="text-center">Čas</th><th class="text-center">Odstup</th><th>Kategorie</th><th class="text-center">Pořadí</th><th class="text-center">Odstup</th></tr></thead>';
		$autoreading_results .= '<thead><tr><th class="text-center">St.č</th><th class="text-center">Čip</th><th>Jméno a příjmení</th><th>Tým/Bydliště</th><th class="text-center">Trat</th><th class="text-center">Disc</th><th class="text-center">Čas</th><th class="text-center">Odstup</th><th class="text-center">#Cel</th><th>Kategorie</th><th class="text-center">Odstup</th><th class="text-center">#Kat</th></tr></thead>';
		$sql1 = "SELECT COUNT(ids) AS pocet_zaznamu FROM $this->sqlvysledky WHERE  false_time IS NULL";
		$sth1 = $this->db->query($sql1);
		$data1 = $sth1->fetchObject();
		$prvni_zaznam = $data1->pocet_zaznamu - 9;
		
		
		
		$sql3 = "SELECT $this->sqlvysledky.time_order,$this->sqlvysledky.ids,$this->sqlvysledky.ids_alias,$this->sqlvysledky.cip,$this->sqlvysledky.race_time,$this->sqlvysledky.distance_overall,$this->sqlvysledky.distance_category,$this->sqlvysledky.distance_gender,$this->sqlvysledky.rank_category,$this->sqlvysledky.rank_overall,$this->sqlvysledky.lap_only,CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,osoby.psc AS stat,osoby.prijmeni_bez_diakritiky AS prijmeni,$this->sqlkategorie.nazev_k AS nazev_kategorie,tymy.nazev_tymu,$this->sqlzavod.poradi_podzavodu FROM $this->sqlvysledky,osoby,$this->sqlzavod,$this->sqlkategorie,tymy WHERE "
			  . "$this->sqlvysledky.cip = $this->sqlzavod.cip AND "
			  . "$this->sqlzavod.ido = osoby.ido AND "
			  . "$this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie AND "
			  . "tymy.id_tymu = $this->sqlzavod.tym AND "
			  . "$this->sqlvysledky.false_time IS NULL "
			  . "ORDER BY $this->sqlvysledky.id DESC LIMIT 0,10";
		//$sql3 = "SELECT * FROM $this->sqlvysledky WHERE $this->sqlvysledky.false_time IS NULL ORDER BY $this->sqlvysledky.id DESC LIMIT 0,10";
		//echo $sql3;
		
		$sql3 = "SELECT $this->sqlvysledky.time_order,$this->sqlvysledky.ids,$this->sqlvysledky.ids_alias,$this->sqlvysledky.cip,$this->sqlvysledky.race_time,$this->sqlvysledky.distance_overall,$this->sqlvysledky.distance_category,$this->sqlvysledky.distance_gender,$this->sqlvysledky.rank_category,$this->sqlvysledky.rank_overall,$this->sqlvysledky.lap_only,CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,osoby.psc AS stat,osoby.prijmeni_bez_diakritiky AS prijmeni,$this->sqlkategorie.nazev_k AS nazev_kategorie,tymy.nazev_tymu,$this->sqlzavod.poradi_podzavodu,$this->sqlpodzavody.nazev FROM $this->sqlvysledky,osoby,$this->sqlzavod,$this->sqlkategorie,tymy,$this->sqlpodzavody WHERE "
			  . "$this->sqlvysledky.cip = $this->sqlzavod.cip AND "
			  . "$this->sqlzavod.ido = osoby.ido AND "
			  . "$this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie AND "
			  . "tymy.id_tymu = $this->sqlzavod.tym AND "
			  . "$this->sqlvysledky.false_time IS NULL AND "
			  . "$this->sqlpodzavody.poradi_podzavodu = $this->sqlkategorie.poradi_podzavodu AND "
			  . "$this->sqlpodzavody.id_zavodu = $this->race_id "
			  . "ORDER BY $this->sqlvysledky.id DESC LIMIT 0,10";
		//echo $sql3;


		$sth3 = $this->db->prepare($sql3);
		$sth3->execute();
		if($sth3->rowCount()){
		    while($data3 = $sth3->fetchObject()){
			/*
			if($data3->cip < 0){
			    $disc = Array('MEZ','CIL','N/A','N/A','N/A','N/A'); 
			}
			else{
			    $disc = Array('CIL','N/A','N/A','N/A','N/A');
			}
			*/
			
			/* jelyman
			if($data3->poradi_podzavodu ==  2){
			    $disc = Array('LAP','CIL','N/A','N/A','N/A','N/A'); 
			}
			else{
			    $disc = Array('CIL','N/A','N/A','N/A','N/A');
			}
			*/
			
			
			
			
			//i s pohlavíma
			//$autoreading_results .= '<tr><td class="text-center">'.$poradi.'</td><td class="text-center">'.$data3->ids_alias.'</td><td class="text-center">'.$data3->cip.'</td><td class="align_left">'.$data3->jmeno.'</td><td class="text-center">'.$data3->rocnik.'</td><td class="align_left">'.$data3->nazev_tymu.'</td><td class="text-center">'.$data3->race_time.'</td><td class="text-center">'.$this->NullsReplacement($data3->distance_overall).'</td><td>'.$data3->nazev_kategorie.'</td><td class="text-center">'.$data3->rank_category.'</td><td class="text-center">'.$this->NullsReplacement($data3->distance_category).'</td><td class="text-center">'.$data3->rank_gender.'</td><td class="text-center">'.$this->NullsReplacement($data3->distance_gender).'</td></tr>';
			if($data3->lap_only){
			    $autoreading_results .= '<tr class="vetsi_radky"><td class="text-center color_red">'.$data3->ids.'</td><td class="text-center color_red">'.$data3->cip.'</td><td class="align_left color_red">'.$data3->jmeno.'</td><td class="align_left color_red">'.$data3->nazev_tymu.'</td><td class="text-center color_red">MOD</td><td class="text-center color_red">'.$data3->race_time.'</td><td class="text-center color_red"></td><td class="text-center color_red"></td><td class="color_red">'.$data3->nazev_kategorie.'</td><td class="text-center color_red"></td><td class="text-center color_red"></td></tr>';
			}
			else{
			    $time_order = $data3->time_order;
			    $disciplina =  $disc[$time_order-1];
			    $autoreading_results .= '<tr class="vetsi_radky"><td class="text-center">'.$data3->ids.'</td><td class="text-center">'.$data3->cip.'</td><td class="align_left">'.$data3->jmeno.'</td><td class="align_left">'.$data3->nazev_tymu.'</td><td class="text-center">'.$data3->nazev.'</td><td class="text-center">'.$disciplina.'</td><td class="text-center">'.$data3->race_time.'</td><td class="text-center">'.$this->NullsReplacement($data3->distance_overall).'</td><td class="text-center">'.$data3->rank_overall.'</td><td>'.$data3->nazev_kategorie.'</td><td class="text-center">'.$this->NullsReplacement($data3->distance_category).'</td><td class="text-center">'.$data3->rank_category.'</td></tr>';
			}
		   }
		}
		$autoreading_results .= '</table>';
	    
?>
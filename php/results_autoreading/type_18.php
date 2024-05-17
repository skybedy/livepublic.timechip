<?php
	    /* 
	     * Hlučín plavání
	     */

	    
	    
	    $led_panel_str = '';
	    $autoreading_results = '<table class="table table-striped table-bordered table-hover">';
	    //$autoreading_results .= '<thead><tr class="vetsi_radky autoreading_header"><th class="text-center">St.č</th><th class="text-center">Čip</th><th>Jméno</th><th>Název týmu</th><th class="text-center">Kol</th><th class="text-center">Čas kola</th><th class="text-center">Celkový čas</th><th class="text-center>@Cel</th><th class="text-center>@Kat</th></tr></thead>';
		$autoreading_results .= '<thead><tr><th rowspan="2" class="text-center">Stč</th><th rowspan="2">Jméno a příjmení</th><th rowspan="2">Tým/Bydliště</th><th rowspan="2" class="text-center">Čas celkem</th><th class="text-center" rowspan="2" >Počet kol</th><th class="text-center"  rowspan="2">Čas kola</th><th colspan="2" class="text-center j_k">Celkem</th><th colspan="3" class="text-center j_k">Kategorie</th></tr><tr><th class="text-center">Závod</th><th class="text-center">Poř</th><th class="text-center">Název</th><th class="text-center">Poř</th></tr></thead>';

	    $autoreding_results = '<tbody>';
	    /* pokud je v db alespoň jeden záznam */
	    if($this->MaxTimeOrder()){
		$max_time_order = $this->MaxTimeOrder();
		// puvodně nachystano na variantu se statickym poradim podzavodu
		//$sql3 = "SELECT $this->sqlvysledky.ids,$this->sqlvysledky.cip,$this->sqlvysledky.lap_time,$this->sqlvysledky.race_time,$this->sqlvysledky.time_order AS pocet_kol FROM $this->sqlvysledky WHERE $this->sqlvysledky.false_time IS NULL AND $this->sqlvysledky.poradi_podzavodu = $poradi_podzavodu ORDER BY $this->sqlvysledky.id DESC LIMIT 0,5";
		$sql3 = "SELECT $this->sqlvysledky.ids,$this->sqlvysledky.cip,$this->sqlvysledky.lap_time,$this->sqlvysledky.race_time,$this->sqlvysledky.time_order AS pocet_kol,$this->sqlvysledky.rank_overall,$this->sqlvysledky.rank_category FROM $this->sqlvysledky WHERE $this->sqlvysledky.false_time IS NULL ORDER BY $this->sqlvysledky.id DESC LIMIT 0,10";
		$sth3 = $this->db->prepare($sql3);
		$sth3->execute();
		if($sth3->rowCount()){
		    while($data3 = $sth3->fetchObject()){
			$sql4 = "SELECT CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,tymy.nazev_tymu,$this->sqlpodzavody.nazev AS nazev_podzavodu,$this->sqlkategorie.nazev_k AS nazev_kategorie FROM $this->sqlzavod,tymy,osoby,$this->sqlpodzavody,$this->sqlkategorie WHERE $this->sqlzavod.cip = $data3->cip AND $this->sqlzavod.id_tymu = tymy.id_tymu AND $this->sqlzavod.ido = osoby.ido AND $this->sqlpodzavody.poradi_podzavodu = $this->sqlzavod.poradi_podzavodu AND  $this->sqlpodzavody.id_zavodu = $this->race_id AND $this->sqlzavod.id_kategorie = $this->sqlkategorie.id_kategorie LIMIT 0,1";
			$sth4 = $this->db->prepare($sql4);
			$sth4->execute(Array(':cip' => $data3->cip));
			if($sth4->rowCount()){
			    $data4 = $sth4->fetchObject();
			    $autoreading_results .= '<tr class="vetsi_radky"><td class="text-center">'.$data3->ids.'</td><td>'.$data4->jmeno.'</td><td>'.$data4->nazev_tymu.'</td><td class="text-center">'.$data3->race_time.'</td><td class="text-center color_red">'.$data3->pocet_kol.'</td><td style="color:blue" class="text-center">'.$data3->lap_time.'</td><td class="text-center">'.$data4->nazev_podzavodu.'</td><td class="text-center">'.$data3->rank_overall.'</td><td class="text-center">'.$data4->nazev_kategorie.'</td><td class="text-center">'.$data3->rank_category.'</td></tr>';
			}
		    }
		}
	    $autoreading_results .= '</tbody></table>';
		}
	    else{
		$autoreading_results .= '<p>Zatím není nahraný žádný čas</p>';
	    }

?>

	    

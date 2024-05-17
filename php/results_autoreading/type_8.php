<?php
	/*  ODŘIVOUS, KOMBINACE JEDNOTLIVCŮ A DVOJITÝCH MIXŮ, U KTERÝCH SE SČÍTALY ČASY
	 * TADY NEMÁ SMYSL  řešit pořadí, jde jen o seznam 
	 *   */
	$autoreading_results = '<table class="table table-striped table-bordered table-hover">';
	//$autoreading_results .= '<thead><tr><th class="text-center">Poř.</th><th class="text-center">St.č</th><th class="text-center">Čip</th><th>Tým</th><th class="text-center">Čas</th><th class="text-center">Odstup</th><th class="text-center">Kategorie</th><th class="text-center">Pořadí</th><th class="text-center">Odstup</th></tr></thead>';
	$autoreding_results = '<tbody>';
	/* pokud je v db alespoň jeden záznam */
	if($this->MaxTimeOrder()){
	    $max_time_order = $this->MaxTimeOrder();
	    $sql3 = "SELECT $this->sqlvysledky.ids,$this->sqlvysledky.ids_alias,$this->sqlvysledky.cip,$this->sqlvysledky.race_time FROM $this->sqlvysledky ORDER BY $this->sqlvysledky.day_time DESC LIMIT 0,30";
	    //echo $sql3;
	    $sth3 = $this->db->prepare($sql3);
	    $sth3->execute();
	    if($sth3->rowCount()){
		while($data3 = $sth3->fetchObject()){
		    $sql4 = "SELECT tymy.nazev_tymu,CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,$this->sqlkategorie.nazev_k as nazev_kategorie FROM $this->sqlzavod,tymy,$this->sqlkategorie,osoby WHERE $this->sqlzavod.ido = osoby.ido AND $this->sqlzavod.cip = '$data3->cip' AND $this->sqlzavod.tym = tymy.id_tymu AND $this->sqlzavod.id_kategorie = $this->sqlkategorie.id_kategorie LIMIT 0,1";
		    //echo $sql4;
		    $sth4 = $this->db->prepare($sql4);
		    $sth4->execute(Array(':cip' => $data3->cip));
		    if($sth4->rowCount()){
			$data4 = $sth4->fetchObject();
			$autoreading_results .= '<tr><td class="text-center">'.$data3->ids_alias.'</td><td class="text-center">'.$data3->cip.'</td><td class="align_left">'.$data4->jmeno.'</td><td class="align_left">'.$data4->nazev_tymu.'</td><td class="text-center">'.$data3->race_time.'</td><td class="text-center">'.$data4->nazev_kategorie.'</td></tr>';
		    }
		}
	    }
	    $autoreading_results .= '</tbody></table>';
	}
	else{
	    $autoreading_results .= '<p>Zatím není nahraný žádný čas</p>';
	}
?>

	    

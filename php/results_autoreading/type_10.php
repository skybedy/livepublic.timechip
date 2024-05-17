<?php
	    /*  Olešná týmy
	     * stejné jak 24 MTB, pouze tady není použito reader = CIL
	     */
	    $autoreading_results = '<table class="table table-striped table-bordered table-hover">';
	    $autoreading_results .= '<thead><tr class="vetsi_radky autoreading_header"><th class="text-center">St.č</th><th>Příjmení</th><th>Název týmu</th><th class="text-center">Kol</th><th class="text-center">Čas kola</th><th>Celkový čas</th></tr></thead>';
	    $autoreding_results = '<tbody>';
	    /* pokud je v db alespoň jeden záznam */
	    if($this->MaxTimeOrder()){
		$max_time_order = $this->MaxTimeOrder();
		//$sql3 = "SELECT $this->sqlvysledky.ids,$this->sqlvysledky.ids_alias,$this->sqlvysledky.cip,SEC_TO_TIME(SUM($this->sqlvysledky.lap_time_sec)) AS total_time,$this->sqlvysledky.lap_time,$this->sqlvysledky.race_time,$this->sqlvysledky.lap_count AS pocet_kol FROM $this->sqlvysledky,$this->sqlzavod WHERE $this->sqlvysledky.false_time IS NULL AND $this->sqlvysledky.reader = 'CIL' AND $this->sqlvysledky.cip = $this->sqlzavod.cip GROUP BY $this->sqlvysledky.lap_time ORDER BY $this->sqlvysledky.race_time DESC LIMIT 0,4";
		$sql3 = "SELECT $this->sqlvysledky.cip,$this->sqlvysledky.lap_time,$this->sqlvysledky.race_time,$this->sqlvysledky.time_order AS pocet_kol FROM $this->sqlvysledky WHERE $this->sqlvysledky.false_time IS NULL ORDER BY $this->sqlvysledky.race_time DESC LIMIT 0,8";
		$sth3 = $this->db->prepare($sql3);
		$sth3->execute();
		if($sth3->rowCount()){
		    while($data3 = $sth3->fetchObject()){
			$sql4 = "SELECT osoby.prijmeni AS jmeno,tymy.nazev_tymu FROM $this->sqlzavod,tymy,osoby WHERE $this->sqlzavod.cip = :cip AND $this->sqlzavod.tym = tymy.id_tymu AND $this->sqlzavod.ido = osoby.ido LIMIT 0,1";
			$sth4 = $this->db->prepare($sql4);
			$sth4->execute(Array(':cip' => $data3->cip));
			if($sth4->rowCount()){
			    $data4 = $sth4->fetchObject();
			    $autoreading_results .= '<tr class="vetsi_radky"><td class="text-center">'.$data3->cip.'</td><td class="color_red">'.$data4->jmeno.'</td><td>'.$data4->nazev_tymu.'</td><td class="text-center color_red">'.$data3->pocet_kol.'</td><td style="color:blue" class="text-center">'.$data3->lap_time.'</td><td class="text-center">'.$data3->race_time.'</td></tr>';
			}
		    }
		}
	    
		$autoreading_results .= '</tbody></table>';
		
	    }
	    else{
		$autoreading_results .= '<p>Zatím není nahraný žádný čas</p>';
	    }

?>

	    

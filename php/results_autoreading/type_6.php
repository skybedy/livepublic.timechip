<?php
	    /*  24 hodin START
	     */
	    $autoreading_results = '<table class="table table-striped table-bordered table-hover">';
	    $autoreading_results .= '<thead><tr autoreading_header"><th class="text-center">St.č</th><th>Jméno</th><th>Název týmu</th><th class="text-center">Kol</th><th class="text-center">Čas kola</th><th>Kategorie</th></tr></thead>';
	    $autoreding_results = '<tbody>';
	    /* pokud je v db alespoň jeden záznam */
	    if($this->MaxTimeOrder()){
		$max_time_order = $this->MaxTimeOrder();
		$sql3 = "SELECT $this->sqlvysledky.cip,$this->sqlvysledky.lap_time,$this->sqlvysledky.race_time,$this->sqlvysledky.lap_count AS pocet_kol FROM $this->sqlvysledky WHERE $this->sqlvysledky.false_time IS NULL AND $this->sqlvysledky.reader = 'START' ORDER BY $this->sqlvysledky.race_time DESC LIMIT 0,20";
		$sth3 = $this->db->prepare($sql3);
		$sth3->execute();
		if($sth3->rowCount()){
		    while($data3 = $sth3->fetchObject()){
			$sql4 = "SELECT osoby.prijmeni AS jmeno,tymy.nazev_tymu,$this->sqlkategorie.nazev_k as nazev_kategorie FROM $this->sqlzavod,tymy,$this->sqlkategorie,osoby WHERE $this->sqlzavod.cip = :cip AND $this->sqlzavod.tym = tymy.id_tymu AND $this->sqlzavod.ido = osoby.ido AND $this->sqlzavod.id_kategorie = $this->sqlkategorie.id_kategorie LIMIT 0,1";
			$sth4 = $this->db->prepare($sql4);
			$sth4->execute(Array(':cip' => $data3->cip));
			if($sth4->rowCount()){
			    $data4 = $sth4->fetchObject();
			    $autoreading_results .= '<tr><td class="text-center">'.$data3->cip.'</td><td>'.$data4->jmeno.'</td><td>'.$data4->nazev_tymu.'</td><td class="text-center color_red">'.$data3->pocet_kol.'</td><td style="color:blue" class="text-center">'.$data3->lap_time.'</td><td>'.$data4->nazev_kategorie.'</td></tr>';
			}
		    }
		}
	    
		$autoreading_results .= '</tbody></table>';
		
	    }
	    else{
		$autoreading_results .= '<p>Zatím není nahraný žádný čas</p>';
	    }

?>

	    

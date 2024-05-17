<?php
	    /*  Radegastova výzva,  týmy (tady tříčlenné), ale všichni mají stejný čip, to znamená, že v tabulce bude pouze název týmu, ale hlavně se tady počítají kola, kterých má každý jiný počet.. 
	     * bez podzávodu  !!
	     */
	    $autoreading_results = '<table class="table table-striped table-bordered table-hover vetsi_radky">';
	    $autoreading_results .= '<thead><tr><th class="text-center">St.č</th><th>Tým</th><th class="text-center">Kategorie</th><th class="text-center">Počet kol</th><th class="text-center">Čas kola</th><th class="text-center">Čas celkem</th></tr></thead>';
	    $autoreding_results = '<tbody>';
	    /* pokud je v db alespoň jeden záznam */
	    if($this->MaxTimeOrder()){
		$max_time_order = $this->MaxTimeOrder();
		$sql3 = "SELECT $this->sqlvysledky.ids,$this->sqlvysledky.ids_alias,$this->sqlvysledky.cip,$this->sqlvysledky.race_time,$this->sqlvysledky.lap_time,$this->sqlvysledky.lap_count AS pocet_kol FROM $this->sqlvysledky WHERE $this->sqlvysledky.false_time IS NULL ORDER BY $this->sqlvysledky.race_time DESC LIMIT 0,6";
		$sth3 = $this->db->prepare($sql3);
		$sth3->execute();
		if($sth3->rowCount()){
		    while($data3 = $sth3->fetchObject()){
			$sql4 = "SELECT tymy.nazev_tymu,$this->sqlkategorie.nazev_k as nazev_kategorie FROM $this->sqlzavod,tymy,$this->sqlkategorie WHERE $this->sqlzavod.cip = :cip AND $this->sqlzavod.tym = tymy.id_tymu AND $this->sqlzavod.id_kategorie = $this->sqlkategorie.id_kategorie LIMIT 0,1";
			$sth4 = $this->db->prepare($sql4);
			$sth4->execute(Array(':cip' => $data3->cip));
			if($sth4->rowCount()){
			    $data4 = $sth4->fetchObject();
			    $autoreading_results .= '<tr><td class="text-center">'.$data3->ids_alias.'</td><td class="align_left">'.$data4->nazev_tymu.'</td><td class="text-center">'.$data4->nazev_kategorie.'</td><td class="text-center color_red">'.$data3->pocet_kol.'</td><td class="text-center">'.$data3->lap_time.'</td><td class="text-center">'.$data3->race_time.'</td></tr>';
			}
		    }
		}
	    
		$autoreading_results .= '</tbody></table>';
		
	    }
	    else{
		$autoreading_results .= '<p>Zatím není nahraný žádný čas</p>';
	    }

?>

	    

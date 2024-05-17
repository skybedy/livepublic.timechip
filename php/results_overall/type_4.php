<?php  
    /* varianta týmy, Free Litoveslká jízda... nejprve lidi, pak město/tým
     * pouze jeden čas v cíli
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
	$str .= '<thead><tr class="header"><th class="text-center">#</th><th class="text-center">St.č</th><th>Členové týmu</th><th class="text-center">Ročník</th><th>Příslušnost</th><th class="text-center">Kategorie</th><th class="text-center">Celkový čas</th><th class="text-center">Odstup</th>';
	$str .= '</tr></thead><tbody>';
	$poradi = 1;
	while($data1 = $sth1->fetchObject()){ //začneme vypisovat první dotaz
	    // tady vybereme jednotlivé členy týmu
	    $sql3 = "SELECT CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik FROM osoby,$this->sqlzavod WHERE $this->sqlzavod.ids = :ids AND $this->sqlzavod.ido = osoby.ido ORDER BY $this->sqlzavod.id";
		$sth3 = $this->db->prepare($sql3);
		$sth3->execute(Array(':ids' => $data1->ids));
		$pocet_clenu = $sth3->rowCount(); //počet členů týmu, který potřebujeme na rowspan
		$k = 1;
		while($dbdata3 = $sth3->fetchObject()){
		    if($k == 1){
			$str .= '<tr>';
			$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$poradi.'</td>';
			$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$data1->ids_alias.'</td>';
			$str .= '<td>'.$dbdata3->jmeno.'</td>';
			$str .= '<td class="text-center">'.$dbdata3->rocnik.'</td>';
			$str .= '<td rowspan="'.$pocet_clenu.'">'.$data1->nazev_tymu.'</td>';
			$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$data1->nazev_kategorie.'</td>';
			$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$data1->finish_time.'</td>';
			$str .= ($data1->distance_overall != '00:00:00.00') ?  ('<td rowspan="'.$pocet_clenu.'" class="text-center">'.$data1->distance_overall.'</td>') : ('<td rowspan="'.$pocet_clenu.'" class="text-center">&nbsp;</td>');
			$str .= '</tr>';
		    }
		    else{
			$str .= '<tr>';
			$str .= '<td>'.$dbdata3->jmeno.'</td>';
			$str .= '<td class="text-center">'.$dbdata3->rocnik.'</td>';
			$str .= '</tr>';
		    }
		    $k++;
		}
		$poradi++;
	}
	$str .= '</tbody></table>';
    }
?>
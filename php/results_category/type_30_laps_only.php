<?php

  // to samé jak type_laps_only
    $colspan = 4;
    
    if($category_id == 'all'){
	$str .= '<h4 class="headline-results">'.$this->race_name.', výsledky podle kategorí</h4>';
	$sql = "SELECT id_kategorie,kod_k AS kod_kategorie,nazev_k AS nazev_kategorie,poradi_podzavodu FROM $this->sqlkategorie WHERE id_zavodu = :race_id AND poradi_podzavodu = :event_order ORDER BY poradi";
	$sth = $this->db->prepare($sql);
	$sth->execute(Array(':race_id' => $this->race_id,':event_order' => $this->event_order));
	if($sth->rowCount()){
	    $str .= '<table class="table table-bordered table-hover noborder orlice_cup">';
	    $k = 1;
	    while($data = $sth->fetchObject()){
		    $sql1 = "SELECT "
			. "CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,"
			. "osoby.rocnik,"
			. "osoby.psc AS stat,"
			. "$this->sqlzavod.ids,"
			. "tymy.nazev_tymu,"
			. "$this->sqlvysledky.lap_time,"
			. "$this->sqlvysledky.distance_category_lap "
			. "FROM $this->sqlvysledky,$this->sqlzavod,tymy,osoby "
		    . "WHERE race_time > 0 "
			. "AND $this->sqlvysledky.lap_count = :time_order " 
			. "AND $this->sqlzavod.id_kategorie = :id_kategorie  "
			. "AND $this->sqlzavod.cip = $this->sqlvysledky.cip "
			. "AND $this->sqlzavod.tym = tymy.id_tymu "
			. "AND $this->sqlzavod.ido = osoby.ido "
			. "AND false_time IS NULL "
			. "AND lap_only IS NULL "
		    . "ORDER BY lap_time ASC".$this->rows_limit;
		//echo $sql1."\n\n";
		$sth1 =  $this->db->prepare($sql1);
		$sth1->execute(Array(':time_order' => $this->time_order,':id_kategorie' => $data->id_kategorie));
		if($sth1->rowCount()){
		    $class = $k == 1 ? $class = 'nadpis nopadding' : 'nadpis';
		    $str .= '<tr><td class="'.$class.'" colspan="3">'.$data->nazev_kategorie.'</td></tr>';
		    
		    $str .= '<tr class="header"><th class="text-center">#</th><th class="text-center">St.č</th><th>Jméno</th><th class="text-center">Stát</th><th>Tým</th><th class="text-center">Čas</th><th class="text-center">Odstup</th></tr>';
		    $poradi = 1;
		    while($dbdata1 = $sth1->fetchObject()){
			$str .= '<tr class="orlice_cup_tr">';
			$str .= '<td class="text-center">'.$poradi.'</td>';
			$str .= '<td class="text-center">'.$dbdata1->ids.'</td>';
			$str .= '<td>'.$dbdata1->jmeno.'</td>';
			$str .= '<td class="text-center">'.$dbdata1->stat.'</b></td>';
			$str .= '<td>'.$dbdata1->nazev_tymu.'</td>';
			$str .= '<td class="text-center">'.substr($dbdata1->lap_time,1).'</td>';
			$str .= ($dbdata1->distance_category_lap != '00:00:00.00') ?  ('<td class="text-center">'.$dbdata1->distance_category_lap.'</td>') : ('<td class="text-center">&nbsp;</td>');
			$str .= '</tr>';
			$poradi++;
		    }
		}
		$k++;
	    }
	    $str .= '</table>';
	}
    }

    else{ // každá kategorie zvlášť
	$sql = "SELECT nazev_k AS nazev_kategorie FROM $this->sqlkategorie WHERE id_zavodu = :race_id AND id_kategorie = :category_id";
	$sth = $this->db->prepare($sql);
	$sth->execute(Array(':race_id' => $this->race_id,':category_id' => $category_id));
	if($sth->rowCount()){
	    $dbdata = $sth->fetchObject();
	    $str .= '<h4 class="headline-results">'.$this->race_name.', kategorie '.$dbdata->nazev_kategorie.'</h4>';
	    $str .= '<table class="table table-bordered table-striped table-hover orlice_cup">';
	    $sql1 = "SELECT "
		. "CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,"
		. "osoby.rocnik,"
		. "osoby.psc AS stat,"
		. "$this->sqlzavod.ids,"
		. "tymy.nazev_tymu,"
		. "$this->sqlvysledky.lap_time,"
		. "$this->sqlvysledky.distance_category_lap "
		. "FROM $this->sqlvysledky,$this->sqlzavod,tymy,osoby "
	    . "WHERE race_time > 0 "
		. "AND $this->sqlvysledky.lap_count = :time_order " 
		. "AND $this->sqlzavod.id_kategorie = :id_kategorie  "
		. "AND $this->sqlzavod.cip = $this->sqlvysledky.cip "
		. "AND $this->sqlzavod.tym = tymy.id_tymu "
		. "AND $this->sqlzavod.ido = osoby.ido "
		. "AND false_time IS NULL "
		. "AND lap_only IS NULL "
	    . "ORDER BY lap_time ASC".$this->rows_limit;
	    $sth1 =  $this->db->prepare($sql1);
		$sth1->execute(Array(':time_order' => $this->time_order,':id_kategorie' => $category_id));
		if($sth1->rowCount()){
		    $str .= '<thead><tr class="header"><th class="text-center">#</th><th class="text-center">St.č</th><th>Jméno</th><th class="text-center">Stát</th><th>Tým</th><th class="text-center">Čas</th><th class="text-center">Odstup</th></tr></thead><tbody>';
		    $poradi = 1;
		    while($dbdata1 = $sth1->fetchObject()){
			$str .= '<tr class="orlice_cup_tr">';
			$str .= '<td class="text-center">'.$poradi.'</td>';
			$str .= '<td class="text-center">'.$dbdata1->ids.'</td>';
			$str .= '<td>'.$dbdata1->jmeno.'</td>';
			$str .= '<td class="text-center">'.$dbdata1->stat.'</b></td>';
			$str .= '<td>'.$dbdata1->nazev_tymu.'</td>';
			$str .= '<td class="text-center">'.substr($dbdata1->lap_time,1).'</td>';
			$str .= ($dbdata1->distance_category_lap != '00:00:00.00') ?  ('<td class="text-center">'.$dbdata1->distance_category_lap.'</td>') : ('<td class="text-center">&nbsp;</td>');
			$str .= '</tr>';
			$poradi++;
		    }
		}
		else{
		    $str .= '<p>Žádný výsledek</p>';
		}
	    $str .= '</tbody></table>';
	}
    }

?>



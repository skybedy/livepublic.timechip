<?php

    	/*
	 * Varianta MADEJA
	 * Každý má více časů, každý startuje ve více podzávodech a každý v něm má jen jeden čas, přestože v db výsledky jich má několik
	 * 
	 */


    if($this->heat_id){
	$rozjizdky_string = "AND $this->sqlzavod.id_rozjizdky = $this->heat_id ";
    }
    else{
	$rozjizdky_string = '';
    }


    $colspan = 4;
    if($category_id == 'all'){
	$str = '';
	$str .= '<h4 class="headline-results">'.$this->race_name.', výsledky podle kategorí'.$this->event_name.'</h4>'; //bere se tam odněkud nějaká čárka, nevím odkud
	$sql = "SELECT id_kategorie,kod_k AS kod_kategorie,nazev_k AS nazev_kategorie,poradi_podzavodu FROM $this->sqlkategorie WHERE id_zavodu = :race_id AND poradi_podzavodu = :event_order ORDER BY poradi";
	$sth = $this->db->prepare($sql);
	$sth->execute(Array(':race_id' => $this->race_id,':event_order' => $this->event_order));
	if($sth->rowCount()){
	    $str .= '<table   id="table2excel" class="table table-striped table-bordered table-hover noborder">';
	    $k = 1;
	    while($data = $sth->fetchObject()){
		$sql1 = "SELECT $this->sqlvysledky.ids,$this->sqlvysledky.ids_alias,$this->sqlvysledky.race_time,$this->sqlvysledky.race_time_sec,$this->sqlvysledky.distance_category,$this->sqlvysledky.race_time_sec,CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,osoby.psc AS stat,$this->sqlkategorie.nazev_k AS nazev_kategorie,tymy.nazev_tymu FROM $this->sqlvysledky,osoby,$this->sqlzavod,$this->sqlkategorie,tymy "
			. "WHERE "
			. "race_time > '0' AND "
			. "false_time IS NULL AND "
			. "lap_only IS NULL AND "
			. "$this->sqlvysledky.poradi_podzavodu = :event_order AND "
			. "$this->sqlkategorie.poradi_podzavodu = :event_order AND "
			. "$this->sqlzavod.id_kategorie = :category_id AND "
			. "$this->sqlzavod.id_kategorie$this->cislo_kategorie = $this->sqlkategorie.id_kategorie AND "
			. "$this->sqlzavod.ids = $this->sqlvysledky.ids AND "
			. "$this->sqlzavod.ido = osoby.ido AND "
			. "tymy.id_tymu = $this->sqlzavod.prislusnost "
			. "$rozjizdky_string"
			. "ORDER BY $this->sqlvysledky.race_time ASC".$this->rows_limit;
		//echo $sql1."\n";
		$sth1 =  $this->db->prepare($sql1);
		$sth1->execute(Array(':event_order' => $this->event_order,':category_id' => $data->id_kategorie));
		$sth1->execute();
		if($sth1->rowCount()){
		    $class = $k == 1 ? $class = 'nadpiss nopadding' : 'nadpiss';
		    $str .= '<tr><td class="'.$class.'" colspan="8">'.$data->nazev_kategorie.'</td></tr>';
		    $str .= '<tr class="header"><th class="text-center">#</th><th class="text-center">St</th><th>Jméno</th><th class="text-center">Ročník</th><th>Tým/Bydliště</th><th class="text-center">Stát</th>';
		    $str .= $this->TableHeader($this->time_order,$this->event_order,$this->cislo_kategorie).'</tr>';
		    $poradi = 1;
		    while($data1 = $sth1->fetchObject()){
			if($poradi == 1) $best_time = $data1->race_time_sec;
			$distance_time = $this->DynamicDistances($poradi,$data1->race_time_sec,$best_time);
			$str .= '<tr><td class="text-center">'.$poradi.'</td><td class="text-center">'.$data1->ids_alias.'</td><td>'.$data1->jmeno.'</td><td class="text-center">'.$data1->rocnik.'</td><td>'.$data1->nazev_tymu.'</td><td class="text-center">'.$data1->stat.'</td>';
			$str .= '<td class="text-center">'.$data1->race_time.'</td>';
			$str .= ($distance_time != '00:00:00.00') ?  ('<td class="text-center">'.$distance_time.'</td>') : ('<td class="text-center">&nbsp;</td>');
			$str .= '</tr>';
			$poradi++;
		    }
		}
		$str .= $this->DNFCAtegory(1,$data->id_kategorie);
		$str .= '</tr>';
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
	    $str .= '<h4 class="headline-results">'.$this->race_name.', kategorie '.$dbdata->nazev_kategorie.$this->event_name.'</h4>';
	    $str .= '<table class="table table-striped table-bordered table-hover">';
	    $k = 1;
	    $sql1 = "SELECT $this->sqlvysledky.ids,$this->sqlvysledky.ids_alias,$this->sqlvysledky.race_time,$this->sqlvysledky.race_time_sec,$this->sqlvysledky.distance_category,$this->sqlvysledky.race_time_sec,CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,osoby.psc AS stat,$this->sqlkategorie.nazev_k AS nazev_kategorie,tymy.nazev_tymu FROM $this->sqlvysledky,osoby,$this->sqlzavod,$this->sqlkategorie,tymy "
		        . "WHERE "
			. "race_time > '0' AND "
			. "false_time IS NULL AND "
			. "lap_only IS NULL AND "
			. "$this->sqlvysledky.poradi_podzavodu = $this->event_order AND "
			. "$this->sqlkategorie.poradi_podzavodu = $this->event_order AND "
			. "$this->sqlzavod.id_kategorie = $category_id AND "
			. "$this->sqlzavod.id_kategorie$this->cislo_kategorie = $this->sqlkategorie.id_kategorie AND "
			. "$this->sqlzavod.ids = $this->sqlvysledky.ids AND "
			. "$this->sqlzavod.ido = osoby.ido AND "
			. "tymy.id_tymu = $this->sqlzavod.prislusnost "
			. "$rozjizdky_string"
			. "ORDER BY $this->sqlvysledky.race_time ASC".$this->rows_limit;
	    $sth1 =  $this->db->prepare($sql1);
	    $sth1->execute(Array(':event_order' => $this->event_order,':category_id' => $category_id));
	    if($sth1->rowCount()){
		$str .= '<thead><tr class="header"><th class="text-center">#</th><th class="text-center">St.č</th><th>Jméno</th><th class="text-center">Ročník</th><th>Tým/Bydliště</th><th class="text-center">Stát</th>';
		$str .= $this->TableHeader($this->time_order,$this->event_order,$this->cislo_kategorie).'</tr></thead><tbody>';
		$poradi = 1;
		while($data1 = $sth1->fetchObject()){
		    if($poradi == 1) $best_time = $data1->race_time_sec;
		    $distance_time = $this->DynamicDistances($poradi,$data1->race_time_sec,$best_time);
		    $str .= '<tr><td class="text-center">'.$poradi.'</td><td class="text-center">'.$data1->ids_alias.'</td><td>'.$data1->jmeno.'</td><td class="text-center">'.$data1->rocnik.'</td><td>'.$data1->nazev_tymu.'</td><td class="text-center">'.$data1->stat.'</td>';
		    $str .= '<td class="text-center">'.$data1->race_time.'</td>';
		    $str .= ($distance_time != '00:00:00.00') ?  ('<td class="text-center">'.$distance_time.'</td>') : ('<td class="text-center">&nbsp;</td>');
		    $str .= '</tr>';
		    $poradi++;
		}
	    }
	    else{
		$str .= '<p>Žádný výsledek</p>';
	    }

	    $k++;
	    $str .= $this->DNFCAtegory(1,$category_id);
	    $str .= '</tr>';
	    $str .= '</tbody></table>';
	}
    }
?>
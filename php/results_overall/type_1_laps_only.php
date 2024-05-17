<?php
	
    $sql1 = "SELECT "
	      . "$this->sqlvysledky.ids,"
	      . "$this->sqlvysledky.ids_alias,"
	      . "$this->sqlvysledky.cip,"
	      . "$this->sqlvysledky.lap_time,"
	      . "$this->sqlvysledky.rank_category_lap,"
	      . "$this->sqlvysledky.distance_overall_lap,"
	      . "$this->sqlvysledky.rank_gender_lap ,"
	      . "CONCAT_WS(' ',$this->sqlosoby.prijmeni,"
	      . "$this->sqlosoby.jmeno) AS jmeno,"
	      . "$this->sqlosoby.rocnik,"
	      . "$this->sqlosoby.psc AS stat,"
	      . "$this->sqlosoby.pohlavi,"
	      . "$this->sqlkategorie.kod_k AS nazev_kategorie,"
	      . "tymy.nazev_tymu " 
	      . "FROM $this->sqlvysledky,$this->sqlzavod,osoby,$this->sqlkategorie,tymy WHERE "
	      . "race_time > 0 AND "
	      . "$this->sqlvysledky.time_order = :time_order AND "
	      . "$this->sqlvysledky.false_time IS NULL AND "
	      . "$this->sqlvysledky.lap_only IS NULL AND "
	      . "$this->sqlvysledky.ids = $this->sqlzavod.ids AND "
	      . "$this->sqlzavod.poradi_podzavodu = :event_order AND "
	      . "$this->sqlzavod.ido = osoby.ido AND "
	      . "$this->sqlzavod.id_tymu = tymy.id_tymu AND "
	      . "$this->sqlzavod.id_kategorie = $this->sqlkategorie.id_kategorie "
	      . "ORDER BY lap_time ASC".$this->rows_limit;
    $sth1 =  $this->db->prepare($sql1);
    $sth1->execute(Array(':time_order' => $this->time_order,':event_order' => $this->event_order));
    if($sth1->rowCount()){
	$str .= '<h4 class="headline-results">'.$this->race_name.$this->event_name.', výsledky bez rozdílu kategorií</h4>';
	$str .= '<table  id="table2excel" class="table table-striped table-bordered table-hover">';
	$str .= '<thead><tr class="header"><th class="text-center">Poř</th><th class="text-center">St.č</th><th class="text-left">Jméno</th><th class="text-center">Ročník</th><th class="text-left">Tým/Bydliště</th><th class="text-center">Stát</th><th class="text-center">Kat</th> <th class="text-center">Poř</th><th class="text-center">M/Z</th><th class="text-center">Poř</th><th class="text-center">Čas</th><th class="text-center">Odstup</th>';
	$str .= '</tr></thead><tbody>';
	$poradi = 1;
	while($dbdata1 = $sth1->fetchObject()){//cyklus, v kterém se vyberou všecky časy konkrétního závodníka
	    $str .= '<tr><td class="text-center"><b>'.$poradi.'</b></td><td class="text-center">'.$dbdata1->ids.'</td><td>'.$dbdata1->jmeno.'</td><td class="text-center">'.$dbdata1->rocnik.'</td><td>'.$dbdata1->nazev_tymu.'</td><td class="text-center">'.$dbdata1->stat.'</td><td class="text-center">'.$dbdata1->nazev_kategorie.'</td><td class="text-center"><b>'.$dbdata1->rank_category_lap.'</b></td><td class="text-center">'.$dbdata1->pohlavi.'</td><td class="text-center"><b>'.$dbdata1->rank_gender_lap.'</b></td><td class="text-center">'.$dbdata1->lap_time.'</td>';
	    $str .= ($dbdata1->distance_overall_lap != '00:00:00.00') ?  ('<td class="text-center">'.$dbdata1->distance_overall_lap.'</td>') : ('<td class="text-center">&nbsp;</td>');
	    $str .= '</tr>';
	    $poradi++;
	}
	$str .= '</tbody></table>';
    }
	    
?>
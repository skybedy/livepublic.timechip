<?php

      /* varianta Orlice Cup týmy 2015 */
	    
    $sql1 = "SELECT "
	      . "CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,"
	      . "osoby.rocnik,"
	      . "osoby.psc AS stat,"
	      . "$this->sqlzavod.cip,"
	      . "tymy.nazev_tymu,"
	      . "$this->sqlvysledky.ids,"
	      . "$this->sqlvysledky.rank_category_lap,"
	      . "$this->sqlvysledky.distance_overall_lap,"
	      . "$this->sqlvysledky.lap_time,"
	      . "$this->sqlkategorie.nazev_k AS nazev_kategorie"
	    . " FROM $this->sqlvysledky,tymy,$this->sqlzavod,$this->sqlkategorie,osoby "
	    . "WHERE "
	      . "race_time > 0 AND "
	      . "$this->sqlvysledky.time_order = :time_order AND "
	      . "$this->sqlkategorie.poradi_podzavodu = :event_order AND "
	      . "$this->sqlzavod.cip = $this->sqlvysledky.cip AND "
	      . "$this->sqlzavod.tym = tymy.id_tymu AND "
	      . "$this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie AND "
	      . "$this->sqlvysledky.false_time IS NULL AND "
	      . "$this->sqlvysledky.lap_only IS NULL AND "
	      . "$this->sqlzavod.ido = osoby.ido "
	    . "ORDER BY lap_time ASC".$this->rows_limit;
    $sth1 =  $this->db->prepare($sql1);
    $sth1->execute(Array(':time_order' => $this->time_order,':event_order' => $this->event_order));
    if($sth1->rowCount()){
	$str .= '<h4 class="headline-results">'.$this->race_name.', výsledky bez rozdílu kategorií</h4>';
	$str .= '<table class="table table-striped table-hover table-bordered table_vysledky orlice_cup">';
	$str .= '<thead><tr class="header"><th class="text-center">#</th><th class="text-center">St.č</th><th>Jméno</th><th class="text-center">Ročník</th><th class="text-center">Stát</th><th>Tým</th><th class="text-center">Kat</th><th  class="text-center">Poř</th><th class="text-center">Čas</th><th class="text-center">Odstup</th>';
	$str .= '</tr>';
	$str .= '</thead><tbody>';
	$poradi = 1;
	while($dbdata1 = $sth1->fetchObject()){//cyklus, v kterém se vyberou všecky časy konkrétního týmu
	    $str .= '<tr>';
	    $str .= '<td class="text-center">'.$poradi.'</td>';
	    $str .= '<td class="text-center">'.$dbdata1->ids.'</td>';
	    $str .= '<td>'.$dbdata1->jmeno.'</td>';
	    $str .= '<td class="text-center">'.$dbdata1->rocnik.'</td>';
	    $str .= '<td class="text-center">'.$dbdata1->stat.'</td>';
	    $str .= '<td>'.$dbdata1->nazev_tymu.'</td>';
	    $str .= '<td class="text-center">'.$dbdata1->nazev_kategorie.'</td>';
	    $str .= '<td class="text-center">'.$dbdata1->rank_category_lap.'</td>';
	    $str .= '<td class="text-center">'.substr($dbdata1->lap_time,1).'</td>';
	    $str .= ($dbdata1->distance_overall_lap != '00:00:00.00') ?  ('<td class="text-center">'.$dbdata1->distance_overall_lap.'</td>') : ('<td class="text-center">&nbsp;</td>');
	    $str .= '</tr>';
	    $poradi++;
	}
	$str .= '</tbody></table>';
    }

?>
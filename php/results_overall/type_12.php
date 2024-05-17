<?php
	/*
	 * Varianta MADEJA
	 * Každý má více časů, každý startuje ve více podzávodech a každý v něm má jen jeden čas, přestože v db výsledky jich má několik
	 * Zde jsou použity dynamické odstupy, neboť při tomto způsobu závodu nefungovalo korektně přepočítávání odstupů
	 * 
	 */


	 if($this->heat_id){
	     $rozjizdky_string = "AND $this->sqlzavod.id_rozjizdky = $this->heat_id ";
	 }
	 else{
	     $rozjizdky_string = '';
	 }



	$sql1 = "SELECT $this->sqlvysledky.ids,$this->sqlvysledky.ids_alias,$this->sqlvysledky.race_time,$this->sqlvysledky.race_time_sec,$this->sqlvysledky.distance_overall,CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,osoby.psc AS stat,$this->sqlkategorie.nazev_k AS nazev_kategorie,tymy.nazev_tymu FROM $this->sqlvysledky,osoby,$this->sqlzavod,$this->sqlkategorie,tymy "
		. "WHERE "
		. "$this->sqlvysledky.race_time > '0' AND "
		. "$this->sqlvysledky.false_time IS NULL AND "
		. "$this->sqlvysledky.lap_only IS NULL AND "
		. "$this->sqlvysledky.poradi_podzavodu = '$this->event_order' AND "
		. "$this->sqlvysledky.poradi_podzavodu = $this->sqlzavod.poradi_podzavodu AND "
		. "$this->sqlvysledky.ids = $this->sqlzavod.ids AND "
		. "$this->sqlzavod.ido = osoby.ido AND "
		. "$this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie AND "
		. "tymy.id_tymu = $this->sqlzavod.prislusnost "
		  . "$rozjizdky_string"
		. "ORDER BY race_time ASC";
	//echo $sql1;
	$sth1 =  $this->db->prepare($sql1);
	$sth1->execute(Array(':event_order' => $this->event_order));
	//$sth1->execute();
	if($sth1->rowCount()){
	    $str .= '<h4 class="headline-results">'.$this->race_name.', výsledky bez rozdílu kategorií</h4>';
	    $str .= '<table class="table table-striped table-bordered table-hover">';
	    $str .= '<thead><tr class="header"><th class="text-center">#</th><th class="text-center">St.č</th><th>Jméno</th><th class="text-center">Ročník</th><th>Tým/Bydliště</th><th class="text-center">Stát</th><th>Kategorie</th>';
	    $str .= $this->TableHeader($this->time_order,$this->event_order,1);
	    $str .= '</tr></thead><tbody>';
	    $poradi = 1;
	    while($data1 = $sth1->fetchObject()){//cyklus, v kterém se vyberou všecky časy konkrétního závodníka
		if($poradi == 1) $best_time = $data1->race_time_sec;
		$distance_time = $this->DynamicDistances($poradi,$data1->race_time_sec,$best_time);
		$str .= '<tr><td class="text-center">'.$poradi.'</td><td class="text-center">'.$data1->ids_alias.'</td><td>'.$data1->jmeno.'</td><td class="text-center">'.$data1->rocnik.'</td><td>'.$data1->nazev_tymu.'</td><td class="text-center">'.$data1->stat.'</td><td>'.$data1->nazev_kategorie.'</td>';
		$str .= '<td class="text-center">'.$data1->race_time.'</td>';
		$str .= ($distance_time != '00:00:00.00') ?  ('<td class="text-center">'.$distance_time.'</td>') : ('<td class="text-center">&nbsp;</td>');
		$str .= '</tr>';
		$poradi++;
	    }
	    $str .= '</tbody></table>';
	}
?>
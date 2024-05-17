<?php
    /* 
     * Celkový čas a celkový počet kol bez pořadí podzávodu narozdíl od hlučína
     */
    // 
    $hash_url = '#'.$this->race_year.'/'.$this->race_id.'/';
    $sql1 = "SELECT CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,osoby.psc AS stat,osoby.pohlavi,tymy.nazev_tymu,$this->sqlvysledky.ids,$this->sqlvysledky.cip,MAX($this->sqlvysledky.race_time) AS finish_time,COUNT($this->sqlvysledky.id) AS pocet_kol,$this->sqlkategorie.nazev_k AS nazev_kategorie,$this->sqlkategorie.kod_k AS kod_kategorie FROM $this->sqlvysledky,osoby,tymy,$this->sqlzavod,$this->sqlkategorie WHERE $this->sqlzavod.poradi_podzavodu = :event_order AND race_time > 0 AND $this->sqlzavod.ids = $this->sqlvysledky.ids AND $this->sqlzavod.id_tymu = tymy.id_tymu AND $this->sqlzavod.ido = osoby.ido AND $this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie AND $this->sqlvysledky.false_time IS NULL AND $this->sqlvysledky.lap_only IS NULL GROUP BY $this->sqlvysledky.ids ORDER BY pocet_kol DESC,finish_time ASC ";
    $sth1 =  $this->db->prepare($sql1);
    $sth1->execute(Array(':event_order' => $this->event_order));
    if($sth1->rowCount()){
	$str .= '<h4 class="headline-results">'.$this->race_name.', Overall Results</h4>';
	$str .= '<table  id="table2excel" class="table table-bordered table-hover table-striped table_vysledky">';
	$str .= '<thead><tr class="header"><th class="text-center">Pos</th><th class="text-center">Bib</th><th>Name</th><th class="text-center">Birth Year</th><th class="text-center">Country</th><th class="text-center">Laps</th><th class="text-center">Cat</th><th class="text-center">C/Pos</th><th class="text-center">Gen</th><th class="text-center">G/Pos</th><th class="text-center">Time</th><th class="text-center">Distance</th>';
	$str .= '</tr></thead><tbody>';
	$poradi = 1;
	
	while($dbdata1 = $sth1->fetchObject()){ //začneme vypisovat první dotaz
	    if($poradi == 1) $max_pocet_kol = $dbdata1->pocet_kol;// nejvyšší počet kol pro počítání odstupů
	    ($dbdata1->pohlavi == "M") ? ($pohlavi = "M") : ($pohlavi = "W");
	    //tady musí být i pořadí podzávodu
	    $sql2 = "SELECT $this->sqlvysledky.distance_overall,$this->sqlvysledky.rank_category,$this->sqlvysledky.rank_gender FROM $this->sqlvysledky WHERE $this->sqlvysledky.ids = :ids AND time_order = :max_lap_count"; //vezmeme odstupy, v tomto případě ve zvláštním dotaze
	    $sth2 = $this->db->prepare($sql2);
	    $sth2->execute(Array(':ids' => $dbdata1->ids,':max_lap_count' => $max_pocet_kol));
	    if($sth2->rowCount()){ //pokud je někdo se stejným, tzn. nejvyšším počttem kol, použijeme časový odstup
		$dbdata2 = $sth2->fetchObject();
		($dbdata2->distance_overall != '00:00:00.00') ? ($distance_overall = $dbdata2->distance_overall) : ($distance_overall = '-'); //pokud nemá odstup, tak je první a bude tam pomlčka
	    }
	    else{ // pokud ne, spočítáme odstup v kolech
		 $distance_overall = $dbdata1->pocet_kol - $max_pocet_kol; 
		if($distance_overall == -1){
		    $kola = 'kolo';
		}
		elseif(($distance_overall < -1 AND $distance_overall > -5) OR $distance_overall > -1){
		    $kola = 'kola';
		}
		else{
		    $kola = 'kol';
		}
		$distance_overall = $distance_overall.' '.$kola;
	    }

		$str .= '<tr id="'.$dbdata1->cip.'">';
		$str .= '<td class="text-center">'.$poradi.'</td>';
		$str .= '<td class="text-center">'.$dbdata1->ids.'</td>';
		$str .= '<td><a onclick="detail_cipu_eng('.$dbdata1->cip.','.$this->race_id.','.$this->race_year.')" href="'.$hash_url.'vysledky">'.$dbdata1->jmeno.'</a></td>';
		$str .= '<td class="text-center">'.$dbdata1->rocnik.'</td>';
		$str .= '<td class="text-center">'.$dbdata1->nazev_tymu.'</td>';
		$str .= '<td class="text-center">'.$dbdata1->pocet_kol.'</td>';
		$str .= '<td class="text-center">'.$dbdata1->kod_kategorie.'</td>';
		$str .= '<td class="text-center">'.$dbdata2->rank_category.'</td>';
		$str .= '<td class="text-center">'.$pohlavi.'</td>';
		$str .= '<td class="text-center">'.$dbdata2->rank_gender.'</td>';
		$str .= '<td class="text-center">'.$dbdata1->finish_time.'</td>';
		$str .= '<td class="text-center">'.$distance_overall.'</td>';
		$str .= '</tr>';
		$poradi++;
	}
	$str .= $this->DNFOverall(19);
	$str .= '</tbody></table>';
    }
?>
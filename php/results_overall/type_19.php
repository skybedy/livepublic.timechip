<?php  
    /* 
     * Hlučín, plavání, celkový čas a celkový počet kol
     */
    // 
    $hash_url = '#'.$this->race_year.'/'.$this->race_id.'/';
    //tady musí být i pořadí podzávodu
    $sql1 = "SELECT CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,osoby.psc AS stat,tymy.nazev_tymu,$this->sqlvysledky.ids,$this->sqlvysledky.cip,MAX($this->sqlvysledky.race_time) AS finish_time,COUNT($this->sqlvysledky.id) AS pocet_kol,MAX($this->sqlvysledky.time_order) AS time_order, $this->sqlkategorie.nazev_k AS nazev_kategorie FROM $this->sqlvysledky,osoby,tymy,$this->sqlzavod,$this->sqlkategorie WHERE race_time > 0 AND $this->sqlvysledky.poradi_podzavodu = :event_order AND $this->sqlvysledky.poradi_podzavodu = $this->sqlzavod.poradi_podzavodu AND $this->sqlzavod.ids = $this->sqlvysledky.ids AND $this->sqlzavod.tym = tymy.id_tymu AND $this->sqlzavod.ido = osoby.ido AND $this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie AND $this->sqlvysledky.false_time IS NULL AND $this->sqlvysledky.lap_only IS NULL GROUP BY $this->sqlvysledky.ids ORDER BY pocet_kol DESC,finish_time ASC";
    $sth1 =  $this->db->prepare($sql1);
    $sth1->execute(Array(':event_order' => $this->event_order));
    if($sth1->rowCount()){
	$str .= '<h4 class="headline-results">'.$this->race_name.', výsledky bez rozdílu kategorií</h4>';
	$str .= '<table  id="table2excel" class="table table-bordered table-hover table-striped table_vysledky">';
	$str .= '<thead><tr class="header"><th class="text-center">#</th><th class="text-center">St.č</th><th>Jméno</th><th class="text-center">Ročník</th><th>Název týmu</th><th class="text-center">Stát</th><th>Kategorie</th><th class="text-center">Kola</th><th class="text-center">Celkový čas</th><th class="text-center">Odstup</th>';
	$str .= '</tr></thead><tbody>';
	$poradi = 1;
	
	while($dbdata1 = $sth1->fetchObject()){ //začneme vypisovat první dotaz
	    if($poradi == 1) $max_pocet_kol = $dbdata1->pocet_kol;// nejvyšší počet kol pro počítání odstupů
	    //tady musí být i pořadí podzávodu
	    $sql2 = "SELECT $this->sqlvysledky.distance_overall FROM $this->sqlvysledky WHERE $this->sqlvysledky.ids = :ids AND time_order = :max_lap_count AND poradi_podzavodu = :poradi_podzavodu"; //vezmeme odstupy, v tomto případě ve zvláštním dotaze
	    $sth2 = $this->db->prepare($sql2);
	    $sth2->execute(Array(':ids' => $dbdata1->ids,':max_lap_count' => $max_pocet_kol,':poradi_podzavodu' => $this->event_order));
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
		$str .= '<td><a onclick="detail_cipu_plavani('.$dbdata1->cip.','.$this->race_id.','.$this->race_year.','.$this->event_order.')" href="'.$hash_url.'vysledky">'.$dbdata1->jmeno.'</a></td>';
		$str .= '<td class="text-center">'.$dbdata1->rocnik.'</td>';
		$str .= '<td>'.$dbdata1->nazev_tymu.'</td>';
		$str .= '<td class="text-center">'.$dbdata1->stat.'</td>';
		$str .= '<td>'.$dbdata1->nazev_kategorie.'</td>';
		$str .= '<td class="text-center">'.$dbdata1->pocet_kol.'</td>';
		$str .= '<td class="text-center">'.$dbdata1->finish_time.'</td>';
		$str .= '<td class="text-center">'.$distance_overall.'</td>';
		$str .= '</tr>';
		$poradi++;
	}
	$str .= $this->DNFOverall(19);
	$str .= '</tbody></table>';
    }
?>
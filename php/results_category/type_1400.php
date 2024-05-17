<?php
    /*
     * tohle je spiciálka pouze na Enduro Gun Race Šiklův mlýn, pouze 4 časy
     * MariaDB někdy blbě počítala SEC_TO_TIME(SUM(lap_time_sec)), takže je to nahrazeno pouze SUM(lap_time_sec) a převod na vteřiny je dělám tady PHP metodou SecToTime()
     * 
     */
    
$hash_url = '#'.$this->race_year.'/'.$this->race_id.'/';
    $colspan = 4;
    $str = '';

    
   if($category_id == 'all'){ // 1.if
	$str .= '<h4 class="headline-results cc">'.$this->race_name.', výsledky podle kategorí</h4>';
	$sql = "SELECT id_kategorie,kod_k AS kod_kategorie,nazev_k AS nazev_kategorie,poradi_podzavodu FROM $this->sqlkategorie WHERE id_zavodu = :race_id  ORDER BY poradi";
	$sth = $this->db->prepare($sql);
	$sth->execute(Array(':race_id' => $this->race_id));
	if($sth->rowCount()){ //2.if
	    $str .= '<table class="table table-bordered table-stripd table-hover noborder table_vysledky"  id="table2excel">';
	    $k = 1;
	    while($dbdata1 = $sth->fetchObject()){ //1.cyklus
		$sql2 = "SELECT "
		. "$this->sqlvysledky.ids,"
		. "$this->sqlvysledky.cip,"
		. "COUNT($this->sqlvysledky.id) AS pocet_kol,"
		. "MAX($this->sqlvysledky.time_order) AS time_order ,"
		. "MIN($this->sqlvysledky.lap_time) AS best_lap_time,"
		. "SUM($this->sqlvysledky.lap_time_sec) AS total_lap_time_sec,"
		. "(SUM($this->sqlvysledky.lap_time_sec ) + $this->sqlzavod.penalizace) AS finish_time "
 
		."FROM $this->sqlvysledky,$this->sqlzavod,$this->sqlkategorie WHERE "
		. "race_time > 0 AND "
		. "$this->sqlvysledky.time_order > 1 AND "
		. "$this->sqlzavod.cip = $this->sqlvysledky.cip AND "
		. "$this->sqlzavod.priznak IS NULL AND "
		. "$this->sqlkategorie.id_kategorie =  $dbdata1->id_kategorie AND "
		. "$this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie AND "
		. "$this->sqlkategorie.poradi_podzavodu = $this->event_order AND "
		. "$this->sqlvysledky.false_time IS NULL AND "
		. "$this->sqlvysledky.lap_only IS NULL AND "
		. "$this->sqlvysledky.best_lap > 0 "
		. "GROUP BY $this->sqlvysledky.cip "
		. "ORDER BY pocet_kol DESC,finish_time ASC".$this->rows_limit;
		//echo $sql2."\n";
		
		

		$sth2 =  $this->db->prepare($sql2);
		$sth2->execute(Array(':id_kategorie' => $dbdata1->id_kategorie,':event_order' => $this->event_order));
		if($sth2->rowCount()){ //3.if
		    $class = $k == 1 ? $class = 'nadpis nopadding' : 'nadpis';
		    $str .= '<tr><td class="'.$class.'" colspan="3">'.$dbdata1->nazev_kategorie.'</td></tr>';
		    $str .= '<tr class="header"><th class="text-center">#</th><th class="text-center">St.č</th><th class="text-left">Jméno</th><th class="text-center">Ročník</th><th class="text-center">Tým</th><th class="text-center">Stát</th><th class="text-center">Čas</th><th class="text-center">Kola</th><th class="text-center">Odstup</th><th class="text-center">Km/h</th><th class="text-center">Nejlepší čas</th><th class="text-center">Penalizace</th></tr>';
		    $poradi = 1;
		    while($dbdata2 = $sth2->fetchObject()){ //2.cyklus
			$celkova_vzdalenost = $dbdata2->pocet_kol * $this->delka_kola;
			if($poradi == 1) $max_pocet_kol = $dbdata2->pocet_kol + 1;// nejvyšší počet kol pro počítání odstupů
			if($poradi == 1) $best_time = $dbdata2->finish_time;
			$distance_time = $this->DynamicDistancesTisiciny($poradi,$dbdata2->finish_time,$best_time);
			if($dbdata2->pocet_kol == ($max_pocet_kol - 1)){
			    if($distance_time == '00:00:00.000'){
				$distance_category = "-";
			    }
			    else{
				$distance_category = $distance_time; 
			    }
			}
			else{
			    $str.= $dbdata2->pocet_kol;
			    $str .= $max_pocet_kol;
			    $distance_category = $dbdata2->pocet_kol - $max_pocet_kol + 1; 
			    if($distance_category == -1){
				$kola = 'kolo';
			    }
			    elseif(($distance_category < -1 AND $distance_category > -5) OR $distance_category > -1){
				$kola = 'kola';
			    }
			    else{
				$kola = 'kol';
			    }
			    $distance_category = $distance_category.' '.$kola;
			    
		    }
			
			$sql3 = "SELECT CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.psc AS stat,osoby.rocnik,tymy.nazev_tymu,$this->sqlkategorie.nazev_k AS nazev_kategorie,SEC_TO_TIME($this->sqlzavod.penalizace) AS penalizace "
			. "FROM osoby,$this->sqlzavod,tymy,$this->sqlkategorie WHERE "
			. "$this->sqlzavod.ids = :ids AND "
			. "$this->sqlzavod.ido = osoby.ido AND "
			. "$this->sqlzavod.id_tymu = tymy.id_tymu AND "
			. "$this->sqlzavod.id_kategorie = $this->sqlkategorie.id_kategorie";
			$sth3 = $this->db->prepare($sql3);
			$sth3->execute(Array(':ids' => $dbdata2->ids));
			if($sth3->rowCount()){
			    $dbdata3 = $sth3->fetchObject();
			    $str .= '<tr id="'.$dbdata2->cip.'">';
			    $str .= '<td class="text-center">'.$poradi.'</td>';
			    $str .= '<td class="text-center">'.$dbdata2->ids.'</td>';
			    //$str .= '<td><a onclick="detail_cipu_cc('.$dbdata2->cip.','.$this->race_id.','.$this->race_year.','.$this->id_etapy.')" href="'.$hash_url.'vysledky">'.$dbdata3->jmeno.'</a></td>';
			    $str .= '<td>'.$dbdata3->jmeno.'</td>';
			    $str .= '<td class="text-center">'.$dbdata3->rocnik.'</td>';
			    $str .= ($dbdata3->nazev_tymu == 'Bez týmu') ? ('<td>&nbsp;</td>') : ('<td class="text-center">'.$this->NahrazkaPomlcky($dbdata3->nazev_tymu).'</td>');
			    $str .= '<td class="text-center">'.$dbdata3->stat.'</td>';
			    $str .= '<td class="text-center">'.$this->SecToTime($dbdata2->finish_time).'</td>';
			    $str .= '<td class="text-center">'.$dbdata2->pocet_kol.'</td>';
			    $str .= '<td class="text-center">'.$distance_category.'</td>';
			    $str .= '<td  class="text-center">'.round($celkova_vzdalenost / $dbdata2->total_lap_time_sec * 3600,1).'</td>';
			    $str .= '<td class="text-center">'.substr($dbdata2->best_lap_time,1).'</td>';
			    if($dbdata3->penalizace == '00:00:00'){
				$str .= '<td class="text-center">&nbsp;</td>';
			    }
			    else{
				$str .= '<td class="text-center">'.substr($dbdata3->penalizace,3).'</td>';
			    }

			    $str .= '</tr>';
		      }
		    $poradi++;
		    } //2.cyklus
		} // 3.if
		
		$sql4 = "SELECT "
		. "$this->sqlvysledky.ids,"
		. "$this->sqlvysledky.cip,"
		. "COUNT($this->sqlvysledky.id) AS pocet_kol,"
		. "MAX($this->sqlvysledky.race_time) AS finish_time,"
		. "MAX($this->sqlvysledky.time_order) AS time_order ,"
		. "MIN($this->sqlvysledky.lap_time) AS best_lap_time,"
		. "(SUM($this->sqlvysledky.lap_time_sec ) + $this->sqlzavod.penalizace) AS finish_time "
		."FROM $this->sqlvysledky,$this->sqlzavod,$this->sqlkategorie WHERE "
		. "race_time > 0 AND "
		. "time_order > 1 AND "
		. "$this->sqlzavod.cip = $this->sqlvysledky.cip AND "
		. "$this->sqlzavod.priznak IS NOT NULL AND "
		. "$this->sqlvysledky.id_etapy = $this->sqlzavod.id_etapy AND "
		. "$this->sqlvysledky.id_etapy = :id_etapy AND "
		. "$this->sqlkategorie.id_kategorie =  :id_kategorie AND "
		. "$this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie AND "
		. "$this->sqlkategorie.poradi_podzavodu = :event_order AND "
		. "$this->sqlvysledky.false_time IS NULL AND "
		. "$this->sqlvysledky.lap_only IS NULL "
		. "GROUP BY $this->sqlvysledky.cip "
		. "ORDER BY $this->sqlzavod.priznak ASC,pocet_kol DESC,finish_time ASC".$this->rows_limit;
		$sth4 =  $this->db->prepare($sql4);
		$sth4->execute(Array(':id_etapy' => $this->id_etapy,':id_kategorie' => $dbdata1->id_kategorie,':event_order' => $this->event_order));
		if($sth4->rowCount()){ //3.if
		    while($dbdata4 = $sth4->fetchObject()){ //2.cyklus
			$celkova_vzdalenost = $dbdata4->pocet_kol * $this->delka_kola;
			$sql5 = "SELECT CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.psc AS stat,osoby.rocnik,tymy.nazev_tymu,$this->sqlkategorie.nazev_k AS nazev_kategorie,znacky_motocyklu.nazev_motocyklu,$this->sqlzavod.penalizace,$this->sqlzavod.priznak "
			. "FROM osoby,$this->sqlzavod,tymy,$this->sqlkategorie,znacky_motocyklu WHERE "
			. "$this->sqlzavod.ids = :ids AND "
			. "$this->sqlzavod.id_etapy = :id_etapy AND "
			. "$this->sqlzavod.ido = osoby.ido AND "
			. "$this->sqlzavod.id_tymu = tymy.id_tymu AND "
			. "$this->sqlzavod.id_kategorie = $this->sqlkategorie.id_kategorie AND "
			. "$this->sqlzavod.id_motocyklu = znacky_motocyklu.id_motocyklu";
			$sth5 = $this->db->prepare($sql5);
			$sth5->execute(Array(':ids' => $dbdata4->ids,':id_etapy' => $this->id_etapy));
			if($sth5->rowCount()){
			    $dbdata5 = $sth5->fetchObject();
			    $str .= '<tr id="'.$dbdata4->cip.'">';
			    $str .= '<td class="text-center">-</td>';
			    $str .= '<td class="text-center">'.$dbdata4->ids.'</td>';
			    $str .= '<td><a onclick="detail_cipu_cc('.$dbdata4->cip.','.$this->race_id.','.$this->race_year.','.$this->id_etapy.')" href="'.$hash_url.'vysledky">'.$dbdata5->jmeno.'</a></td>';
			    $str .= '<td class="text-center">'.$dbdata3->rocnik.'</td>';
			    $str .= ($dbdata5->nazev_tymu == 'Bez týmu') ? ('<td>&nbsp;</td>') : ('<td class="text-center">'.$this->NahrazkaPomlcky($dbdata5->nazev_tymu).'</td>');
			    $str .= '<td class="text-center">'.$dbdata5->stat.'</td>';
			    $str .= '<td class="text-center">'.$dbdata5->nazev_motocyklu.'</td>';
			    $str .= '<td class="text-center">'.substr($dbdata4->finish_time,1).'</td>';
			    $str .= '<td class="text-center">'.$dbdata4->pocet_kol.'</td>';
			    $str .= '<td class="text-center">-</td>';
			    $str .= '<td  class="text-center">'.round($celkova_vzdalenost / $dbdata4->total_lap_time_sec * 3600,1).'</td>';
			    $str .= '<td class="text-center">'.substr($dbdata4->best_lap_time,1).'</td>';
			    $str .= '<td class="text-center">'.$dbdata5->penalizace.'</td>';
			    $str .= '<td class="text-center">'.$dbdata5->priznak.'</td>';
			    $str .= '</tr>';
		      }
		    } //2.cyklus
		
		}
		$k++;
	    } //1.cyklus
	    $str .= '</table>';
	} //2.if
    } // 1.if
    else{ //hlavní else
	
	    $sql1 = "SELECT nazev_k AS nazev_kategorie FROM $this->sqlkategorie WHERE id_zavodu = :race_id AND id_kategorie = :category_id";
	    $sth1 = $this->db->prepare($sql1);
	    $sth1->execute(Array(':race_id' => $this->race_id,':category_id' => $category_id));
	    if($sth1->rowCount()){
		$dbdata1 = $sth1->fetchObject();
		$str .= '<h4 class="headline-results cc">'.$this->race_name.', kategorie '.$dbdata1->nazev_kategorie.'</h4>';
		$str .= '<table class="table table-bordered table-hover table-stripe table_vysledky" id="table2excel">';
		$str .= '<thead><tr class="header"><th class="text-center">#</th><th class="text-center">St.č</th><th class="text-left">Jméno</th><th class="text-center">Ročník</th><th class="text-center">Tým</th><th class="text-center">Stát</th><th class="text-center">Čas</th><th class="text-center">Kolo</th><th class="text-center">Odstup</th><th class="text-center">Km/h</th><th class="text-center">Nejlepší čas</th><th class="text-center">Penalizace</th>';
		$str .= '</tr></thead><tbody>';

		$sql2 = "SELECT "
		. "$this->sqlvysledky.ids,"
		. "$this->sqlvysledky.cip,"
		. "COUNT($this->sqlvysledky.id) AS pocet_kol,"
		. "MAX($this->sqlvysledky.time_order) AS time_order ,"
		. "MIN($this->sqlvysledky.lap_time) AS best_lap_time,"
		. "SUM($this->sqlvysledky.lap_time_sec) AS total_lap_time_sec,"
//		. "SEC_TO_TIME(SUM($this->sqlvysledky.lap_time_sec)) AS finish_time "
		. "(SUM($this->sqlvysledky.lap_time_sec ) + $this->sqlzavod.penalizace) AS finish_time "
		."FROM $this->sqlvysledky,$this->sqlzavod,$this->sqlkategorie WHERE "
		. "race_time > 0 AND "
		. "time_order > 1 AND "
		. "$this->sqlzavod.cip = $this->sqlvysledky.cip AND "
		. "$this->sqlzavod.priznak IS NULL AND "
		. "$this->sqlkategorie.id_kategorie =  $category_id AND "
		. "$this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie AND "
		. "$this->sqlkategorie.poradi_podzavodu = $this->event_order AND "
		. "$this->sqlvysledky.false_time IS NULL AND "
		. "$this->sqlvysledky.lap_only IS NULL AND "
		. "$this->sqlvysledky.best_lap > 0 "
		. "GROUP BY $this->sqlvysledky.cip "
		. "ORDER BY pocet_kol DESC,finish_time ASC".$this->rows_limit;
		//$str .= $sql2;
		
		
		
		
		
		
		$sth2 =  $this->db->prepare($sql2);
		$sth2->execute(Array(':category_id' => $category_id,':event_order' => $this->event_order));
		if($sth2->rowCount()){ 
		    $poradi = 1;
		    while($dbdata2 = $sth2->fetchObject()){ //2.cyklus
			$celkova_vzdalenost = $dbdata2->pocet_kol * $this->delka_kola;
			    if($poradi == 1) $max_pocet_kol = $dbdata2->pocet_kol + 1;// nejvyšší počet kol pro počítání odstupů
			    if($poradi == 1) $best_time = $dbdata2->finish_time;
			    $distance_time = $this->DynamicDistancesTisiciny($poradi,$dbdata2->finish_time,$best_time);
			    if($dbdata2->pocet_kol == ($max_pocet_kol - 1)){
				if($distance_time == '00:00:00.000'){
				    $distance_category = "-";
				}
				else{
				    $distance_category = $distance_time; 
				}
			    }
			    else{
				$str.= $dbdata2->pocet_kol;
				$str .= $max_pocet_kol;
				$distance_category = $dbdata2->pocet_kol - $max_pocet_kol + 1; 
				if($distance_category == -1){
				    $kola = 'kolo';
				}
				elseif(($distance_category < -1 AND $distance_category > -5) OR $distance_category > -1){
				    $kola = 'kola';
				}
				else{
				    $kola = 'kol';
				}
				$distance_category = $distance_category.' '.$kola;
			}

			$sql3 = "SELECT CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.psc AS stat,osoby.rocnik,tymy.nazev_tymu,$this->sqlkategorie.nazev_k AS nazev_kategorie,SEC_TO_TIME($this->sqlzavod.penalizace) AS penalizace "
			. "FROM osoby,$this->sqlzavod,tymy,$this->sqlkategorie WHERE "
			. "$this->sqlzavod.ids = :ids AND "
			. "$this->sqlzavod.ido = osoby.ido AND "
			. "$this->sqlzavod.id_tymu = tymy.id_tymu AND "
			. "$this->sqlzavod.id_kategorie = $this->sqlkategorie.id_kategorie";
			$sth3 = $this->db->prepare($sql3);
			$sth3->execute(Array(':ids' => $dbdata2->ids));
			if($sth3->rowCount()){
			    $dbdata3 = $sth3->fetchObject();
			    $str .= '<tr id="'.$dbdata2->cip.'">';
			    $str .= '<td class="text-center">'.$poradi.'</td>';
			    $str .= '<td class="text-center">'.$dbdata2->ids.'</td>';
			    //$str .= '<td><a onclick="detail_cipu_cc('.$dbdata2->cip.','.$this->race_id.','.$this->race_year.','.$this->id_etapy.')" href="'.$hash_url.'vysledky">'.$dbdata3->jmeno.'</a></td>';
			    $str .= '<td>'.$dbdata3->jmeno.'</td>';
			    $str .= '<td class="text-center">'.$dbdata3->rocnik.'</td>';
			    $str .= ($dbdata3->nazev_tymu == 'Bez týmu') ? ('<td>&nbsp;</td>') : ('<td class="text-center">'.$this->NahrazkaPomlcky($dbdata3->nazev_tymu).'</td>');
			    $str .= '<td class="text-center">'.$dbdata3->stat.'</td>';
			    $str .= '<td class="text-center">'.substr($this->SecToTime($dbdata2->finish_time),1).'</td>';
			    //$str .= '<td class="text-center">'.substr($dbdata2->finish_time,0,-3).'</td>';
			    $str .= '<td class="text-center">'.$dbdata2->pocet_kol.'</td>';
			    
			    $str .= '<td class="text-center">'.$distance_category.'</td>';
			    
			    $str .= '<td  class="text-center">'.round($celkova_vzdalenost / $dbdata2->total_lap_time_sec * 3600,1).'</td>';
			    $str .= '<td class="text-center">'.substr($dbdata2->best_lap_time,1).'</td>';
			    if($dbdata3->penalizace == '00:00:00'){
				$str .= '<td class="text-center">&nbsp;</td>';
			    }
			    else{
				$str .= '<td class="text-center">'.substr($dbdata3->penalizace,3).'</td>';
			    }
			    $str .= '</tr>';
		      }
		    $poradi++;
		    } //2.cyklus
		}


			    
			    
			    
			    
			    
			
			
		
		
		
		
		$sql4 = "SELECT "
		. "$this->sqlvysledky.ids,"
		. "$this->sqlvysledky.cip,"
		. "COUNT($this->sqlvysledky.id) AS pocet_kol,"
		. "MAX($this->sqlvysledky.race_time) AS finish_time,"
		. "MAX($this->sqlvysledky.time_order) AS time_order ,"
		. "MIN($this->sqlvysledky.lap_time) AS best_lap_time,"
		. "SUM($this->sqlvysledky.lap_time_sec) AS total_lap_time_sec "
		."FROM $this->sqlvysledky,$this->sqlzavod,$this->sqlkategorie WHERE "
		. "race_time > 0 AND "
		. "time_order > 1 AND "
		. "$this->sqlzavod.cip = $this->sqlvysledky.cip AND "
		. "$this->sqlzavod.priznak IS NOT NULL AND "
		. "$this->sqlvysledky.id_etapy = :id_etapy AND "
		. "$this->sqlvysledky.id_etapy = $this->sqlzavod.id_etapy AND "
		. "$this->sqlkategorie.id_kategorie =  :category_id AND "
		. "$this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie AND "
		. "$this->sqlkategorie.poradi_podzavodu = :event_order AND "
		. "$this->sqlvysledky.false_time IS NULL AND "
		. "$this->sqlvysledky.lap_only IS NULL "
		. "GROUP BY $this->sqlvysledky.cip "
		. "ORDER BY $this->sqlzavod.priznak ASC,pocet_kol DESC,finish_time ASC".$this->rows_limit;
		//$str .= $sql4;

		$sth4 =  $this->db->prepare($sql4);
		$sth4->execute(Array(':id_etapy' => $this->id_etapy,':category_id' => $category_id,':event_order' => $this->event_order));
		if($sth4->rowCount()){ 
		    $poradi = 1;
		    while($dbdata4 = $sth4->fetchObject()){ //2.cyklus
			   $celkova_vzdalenost = $dbdata4->pocet_kol * $this->delka_kola;
			    if($poradi == 1) $max_pocet_kol = $dbdata4->pocet_kol;// nejvyšší počet kol pro počítání odstupů
			    //if($poradi == 1) $max_time_order = $dbdata1->time_order;// nejvyšší počet časů pro počítání odstupů
			    
			
			$sql5 = "SELECT CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.psc AS stat,osoby.rocnik,tymy.nazev_tymu,$this->sqlkategorie.nazev_k AS nazev_kategorie,znacky_motocyklu.nazev_motocyklu,$this->sqlzavod.penalizace,$this->sqlzavod.priznak,$this->sqlzavod.bodovani "
			. "FROM osoby,$this->sqlzavod,tymy,$this->sqlkategorie,znacky_motocyklu WHERE "
			. "$this->sqlzavod.ids = :ids AND "
			. "$this->sqlzavod.id_etapy = :id_etapy AND "
			. "$this->sqlzavod.ido = osoby.ido AND "
			. "$this->sqlzavod.id_tymu = tymy.id_tymu AND "
			. "$this->sqlzavod.id_kategorie = $this->sqlkategorie.id_kategorie AND "
			. "$this->sqlzavod.id_motocyklu = znacky_motocyklu.id_motocyklu";
			$sth5 = $this->db->prepare($sql5);
			$sth5->execute(Array(':ids' => $dbdata4->ids,':id_etapy' => $this->id_etapy));
			if($sth5->rowCount()){
			    $dbdata5 = $sth5->fetchObject();
			    $str .= '<tr id="'.$dbdata4->cip.'">';
			    $str .= '<td class="text-center">-</td>';
			    $str .= '<td class="text-center">'.$dbdata4->ids.'</td>';
			    $str .= '<td><a onclick="detail_cipu_cc('.$dbdata4->cip.','.$this->race_id.','.$this->race_year.','.$this->id_etapy.')" href="'.$hash_url.'vysledky">'.$dbdata5->jmeno.'</a></td>';
			    $str .= '<td class="text-center">'.$dbdata5->rocnik.'</td>';
			    $str .= ($dbdata5->nazev_tymu == 'Bez týmu') ? ('<td>&nbsp;</td>') : ('<td class="text-center">'.$this->NahrazkaPomlcky($dbdata5->nazev_tymu).'</td>');
			    $str .= '<td class="text-center">'.$dbdata5->stat.'</td>';
			    $str .= '<td class="text-center">'.$dbdata5->nazev_motocyklu.'</td>';
			    $str .= '<td class="text-center">'.substr($dbdata4->finish_time,1).'</td>';
			    $str .= '<td class="text-center">'.$dbdata4->pocet_kol.'</td>';
			    $str .= '<td class="text-center">-</td>';
			    $str .= '<td  class="text-center">'.round($celkova_vzdalenost / $dbdata4->total_lap_time_sec * 3600,1).'</td>';
			    $str .= '<td class="text-center">'.substr($dbdata4->best_lap_time,1).'</td>';
			    $str .= '<td class="text-center">'.$dbdata5->penalizace.'</td>';
			    $str .= '<td class="text-center">'.$dbdata5->priznak.'</td>';
			    $str .= '</tr>';
		      }
		    $poradi++;
		    } //2.cyklus
		}
	    } 
	
	    $str .= '</tbody></table>';

	
	
	
	
	
	        $sql1 = false;
		$sql2 = false;
		$sql3 = false;
		$dbdata1 = false;
		$dbdata2 = false;
		$sth1 = false;
		$sth2 = false;

		$sql1 = "SELECT "
			    . "$this->sqlvysledky.ids,"
			    . "$this->sqlvysledky.cip,"
			    . "COUNT($this->sqlvysledky.id) AS pocet_kol,"
			    . "(SUM($this->sqlvysledky.lap_time_sec ) + $this->sqlzavod.penalizace) AS finish_time, "
			    . "MIN($this->sqlvysledky.lap_time) AS best_lap_time,"
			    . "CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno "
			    . "FROM $this->sqlvysledky,$this->sqlzavod,$this->sqlkategorie,osoby WHERE "
			    . "race_time > 0 AND "
			    . "time_order > 1 AND "
			    . "$this->sqlzavod.cip = $this->sqlvysledky.cip AND "
			    . "$this->sqlzavod.ido = osoby.ido AND "
			    . "$this->sqlkategorie.id_kategorie = $category_id AND "
			    . "$this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie AND "
			    . "$this->sqlkategorie.poradi_podzavodu = $this->event_order AND "
			    . "$this->sqlvysledky.false_time IS NULL AND "
			    . "$this->sqlvysledky.lap_only IS NULL AND "
			    . "best_lap > 0 "
			    . "GROUP BY $this->sqlvysledky.cip "
			    . "ORDER BY pocet_kol DESC,finish_time ASC";

	       // $str .= $sql1;

		$sth1 =  $this->db->prepare($sql1);
		$sth1->execute(Array(':event_order' => $this->event_order,':category_id' => $category_id));
		if($sth1->rowCount()){
		    $str .= '<table class="table table-bordered table-hover table-striped table_vysledky" style="margin-top:100px">';
		    $i = 1;
		    while($dbdata1 = $sth1->fetchObject()){
			if($i == 1){
			    $sql11 = "SELECT MAX(time_order) as max_pocet_kol FROM $this->sqlvysledky WHERE false_time IS NULL";
			    $sth11 = $this->db->prepare($sql11);
			    $sth11->execute();
			    $dbdata11 = $sth11->fetchObject();
			    $max_pocet_kol = $dbdata11->max_pocet_kol;
			    $str .= '<thead><tr class="header"><th class="text-center">#</th><th class="text-left">Jméno</th>';
			    for($k=1;$k<$max_pocet_kol;$k++){
				$str .= '<th class="text-center">'.$k.'.kolo</th>';
			    }
			    $str .= '</tr></thead><tbody>';
			}
			$sql2 = "SELECT $this->sqlvysledky.lap_time,$this->sqlvysledky.best_lap FROM $this->sqlvysledky WHERE cip = :cip AND false_time IS NULL AND lap_only IS NULL AND time_order > 1 ORDER by time_order ASC";
			$sth2 = $this->db->prepare($sql2);
			$sth2->execute(Array(':cip' =>  $dbdata1->cip));
			if($sth2->rowCount()){
			    $dbdata2 = $sth2->fetchAll();
			    $str .= '<tr>';
			    $str .= '<td class="text-center">'.$i.'</td><td>'.$dbdata1->jmeno.'</td>';
			    $k = 1;
			    for($k = 0;$k <= $max_pocet_kol - 2;$k++){
				if(isset($dbdata2[$k]['lap_time'])){
				    if($dbdata2[$k]['best_lap']){
					if($dbdata2[$k]['lap_time'] == $dbdata1->best_lap_time){
					     $str .= '<td class="text-center"><b><span style="color:red;text-decoration:underline">'.substr($dbdata2[$k]['lap_time'],3).'</span></b></td>';
					}
					else{
					    $str .= '<td class="text-center"><b><span style="color:red">'.substr($dbdata2[$k]['lap_time'],3).'</span></b></td>';

					}
				    }
				    else{
					$str .= '<td class="text-center">'.substr($dbdata2[$k]['lap_time'],3).'</td>';
				    }
				}
				else{
				    $str .= '<td class="text-center">-</td>';
				}
			    }
			    
			    $str .= '</tr>';
			    
			    

			}
		     $i++;  
		    }
		    $str .= '</tbody></table>';
		}
	    } //hlavní else
	    
	    
    
?>



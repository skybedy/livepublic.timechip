<?php  
    /* 
     * CROSS Country hobby jednotlivci.. nejsou motorky ani body
     */
    
    $hash_url = '#'.$this->race_year.'/'.$this->race_id.'/';
    $str = '';
   
    $sql1 = "SELECT "
		    . "$this->sqlvysledky.ids,"
		    . "$this->sqlvysledky.cip,"
		    . "COUNT($this->sqlvysledky.id) AS pocet_kol,"
		    . "MAX($this->sqlvysledky.race_time) AS finish_time,"
		    . "MAX($this->sqlvysledky.time_order) AS time_order ,"
		    . "MIN($this->sqlvysledky.lap_time) AS best_lap_time,"
		    . "SUM($this->sqlvysledky.lap_time_sec) AS total_lap_time_sec "
		    ."FROM $this->sqlvysledky,$this->sqlzavod,$this->sqlkategorie WHERE "
		    . "race_time > 0 AND "
		    . "$this->sqlzavod.cip = $this->sqlvysledky.cip AND "
		    . "$this->sqlvysledky.id_etapy = :id_etapy AND "
		    . "$this->sqlvysledky.id_etapy = $this->sqlzavod.id_etapy AND "
		    . "$this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie AND "
		    . "$this->sqlkategorie.poradi_podzavodu = :event_order AND "
		    . "$this->sqlvysledky.false_time IS NULL AND "
		    . "$this->sqlvysledky.lap_only IS NULL "
		    . "GROUP BY $this->sqlvysledky.cip "
		    . "ORDER BY pocet_kol DESC,finish_time ASC";
    $sth1 =  $this->db->prepare($sql1);
    $sth1->execute(Array(':event_order' => $this->event_order,':id_etapy' => $this->id_etapy));
    if($sth1->rowCount()){
	$str .= '<h4 class="headline-results">'.$this->race_name.', výsledky bez rozdílu kategorií</h4>';
	$str .= '<table class="table table-bordered table-hover table_vysledky">';
	$str .= '<thead><tr class="header"><th class="text-center">#</th><th class="text-center">St.č</th><th>Jméno</th><th class="text-center">Tým</th><th class="text-center">Stát</th><th>Kategorie</th><th class="text-center">Čas</th><th class="text-center">Kolo</th><th class="text-center">Odstup</th><th class="text-center">Km/h</th><th class="text-center">Nejlepší čas</th>';
	$str .= '</tr></thead><tbody>';
	$poradi = 1;
	while($dbdata1 = $sth1->fetchObject()){
	    $celkova_vzdalenost = $dbdata1->pocet_kol * $this->delka_kola;
	    
	    if($poradi == 1) $max_pocet_kol = $dbdata1->pocet_kol;// nejvyšší počet kol pro počítání odstupů
	   
	    
	//if($poradi == 1) $max_time_order = $dbdata1->time_order;// nejvyšší počet časů pro počítání odstupů
	    $sql3 = "SELECT $this->sqlvysledky.distance_overall FROM $this->sqlvysledky WHERE $this->sqlvysledky.ids = :ids AND time_order = :max_pocet_kol AND id_etapy = :id_etapy"; //vezmeme odstupy, v tomto případě ve zvláštním dotaze
	    $sth3 = $this->db->prepare($sql3);
	    $sth3->execute(Array(':ids' => $dbdata1->ids,':max_pocet_kol' => $max_pocet_kol,':id_etapy' => $this->id_etapy));
	    
	    if($sth3->rowCount()){ //pokud je někdo se stejným, tzn. nejvyšším počttem kol, použijeme časový odstup
		$dbdata3 = $sth3->fetchObject();
		($dbdata3->distance_overall != '00:00:00.00') ? ($distance_overall = $dbdata3->distance_overall) : ($distance_overall = '-'); //pokud nemá odstup, tak je první a bude tam pomlčka
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

	    $sql2 = "SELECT CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.psc AS stat,tymy.nazev_tymu,$this->sqlkategorie.nazev_k AS nazev_kategorie "
		      . "FROM osoby,$this->sqlzavod,tymy,$this->sqlkategorie WHERE "
		      . "$this->sqlzavod.ids = :ids AND "
		      . "$this->sqlzavod.id_etapy = :id_etapy AND "
		      . "$this->sqlzavod.ido = osoby.ido AND "
		      . "$this->sqlzavod.id_tymu = tymy.id_tymu AND "
		      . "$this->sqlzavod.id_kategorie = $this->sqlkategorie.id_kategorie";
	    $sth2 = $this->db->prepare($sql2);
	    $sth2->execute(Array(':ids' => $dbdata1->ids,':id_etapy' => $this->id_etapy));
	    if($sth2->rowCount()){
		$dbdata2 = $sth2->fetchObject();
		$str .= '<tr id="'.$dbdata1->cip.'">';
		$str .= '<td class="text-center">'.$poradi.'</td>';
		$str .= '<td class="text-center">'.$dbdata1->ids.'</td>';
		$str .= '<td>'.$dbdata2->jmeno.'</td>';
		$str .= ($dbdata2->nazev_tymu == 'Bez týmu') ? ('<td>&nbsp;</td>') : ('<td class="text-center">'.$dbdata2->nazev_tymu.'</td>');
		$str .= '<td class="text-center">'.$dbdata2->stat.'</td>';
		$str .= '<td>'.$dbdata2->nazev_kategorie.'</td>';
		$str .= '<td class="text-center">'.$dbdata1->finish_time.'</td>';
		$str .= '<td class="text-center">'.$dbdata1->pocet_kol.'</td>';
		$str .= '<td class="text-center">'.$distance_overall.'</td>';
		$str .= '<td class="text-center">'.round($celkova_vzdalenost / $dbdata1->total_lap_time_sec * 3600,1).'</td>';
		$str .= '<td class="text-center">'.$dbdata1->best_lap_time.'</td>';
		$str .= '<td class="text-center"></td>';
		$str .= '</tr>';
	    }
	    $poradi++;
	 }
	 
	$str .= '</tbody></table>';
    }
    
    
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
	        . "MAX($this->sqlvysledky.race_time) AS finish_time,"
	        . "CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno "
		. "FROM $this->sqlvysledky,$this->sqlzavod,$this->sqlkategorie,osoby WHERE "
		. "race_time > 0 AND "
		. "$this->sqlzavod.cip = $this->sqlvysledky.cip AND "
	        . "$this->sqlzavod.ido = osoby.ido AND "
		. "$this->sqlvysledky.id_etapy = :id_etapy AND "
		. "$this->sqlvysledky.id_etapy = $this->sqlzavod.id_etapy AND "
		. "$this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie AND "
		. "$this->sqlkategorie.poradi_podzavodu = :event_order AND "
		. "$this->sqlvysledky.false_time IS NULL AND "
		. "$this->sqlvysledky.lap_only IS NULL "
		. "GROUP BY $this->sqlvysledky.cip "
		. "ORDER BY pocet_kol DESC,finish_time ASC";
		
   // echo $sql1;
    
    $sth1 =  $this->db->prepare($sql1);
    $sth1->execute(Array(':event_order' => $this->event_order,':id_etapy' => $this->id_etapy));
    if($sth1->rowCount()){
	$str .= '<table class="table table-bordered table-hover table_vysledky" style="margin-top:100px">';
	$i = 1;
	while($dbdata1 = $sth1->fetchObject()){
	    if($i == 1){
		$max_pocet_kol = $dbdata1->pocet_kol;
		$str .= '<thead><tr class="header"><th class="text-center">#</th><th>Jméno</th>';
		for($k=1;$k<=$max_pocet_kol;$k++){
		    $str .= '<th class="text-center">'.$k.'.kolo</th>';
		}
		$str .= '</tr></thead><tbody>';
	    }
	    $sql2 = "SELECT $this->sqlvysledky.lap_time FROM $this->sqlvysledky WHERE cip = :cip AND id_etapy = :id_etapy ORDER by time_order ASC";
	    $sth2 = $this->db->prepare($sql2);
	    $sth2->execute(Array(':cip' =>  $dbdata1->cip,':id_etapy' => $this->id_etapy));
	    if($sth2->rowCount()){
		$str .= '<tr>';
		$str .= '<td class="text-center">'.$dbdata1->ids.'</td><td>'.$dbdata1->jmeno.'</td>';
		while($dbdata2 = $sth2->fetchObject()){
		    $str .= '<td class="text-center">'.$dbdata2->lap_time.'</td>';
		}
		$str .= '</tr>';
	    }
	
	 $i++;   
	}
	$str .= '</tbody></table>';
    }

 ?>
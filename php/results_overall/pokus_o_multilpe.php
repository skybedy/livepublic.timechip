<?php  
    /* 
     * CROSS Country
     * 
     * dole pokud o multiple statement, vzdal jsem to, protože v druhém cyklu se nedaly získat data z prvního, už nebyl čas nad tím bádat
     * 
     */
    // v jednom dotazu se vyberou týmy, počet kol
    $hash_url = '#'.$this->race_year.'/'.$this->race_id.'/';
    $str = '';
   
    /*
    $sql1 = "SELECT CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,"
	      . "$this->sqlvysledky.ids,"
	      . "$this->sqlvysledky.cip,"
	      . "tymy.nazev_tymu,"
	      
	      . "MAX($this->sqlvysledky.race_time) AS finish_time,"
	      
	      . "COUNT($this->sqlvysledky.id) AS pocet_kol,"
	      
	      . "MAX($this->sqlvysledky.time_order) AS time_order, "
	      
	      . "$this->sqlkategorie.nazev_k AS nazev_kategorie "
	      .
	      "FROM osoby,tymy,$this->sqlvysledky,$this->sqlzavod,$this->sqlkategorie WHERE "
	      
	      . "race_time > 0 AND "
	      
	      
	      . "$this->sqlzavod.ids = $this->sqlvysledky.ids AND "
	      . "$this->sqlzavod.ido = osoby.ido AND "
	      . "$this->sqlzavod.id_tymu = tymy.id_tymu AND "
	      
	      . "$this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie AND "
	      . "$this->sqlkategorie.poradi_podzavodu = $this->event_order AND "
	      
	      . "$this->sqlvysledky.false_time IS NULL AND "
	      . "$this->sqlvysledky.lap_only IS NULL "
	      . "GROUP BY $this->sqlvysledky.ids "
	      . "ORDER BY pocet_kol DESC,finish_time ASC";
    
    */
    
    
	  $sql1 = "SELECT "
		    . "$this->sqlvysledky.ids,"
		    . "$this->sqlvysledky.cip,"
		    . "COUNT($this->sqlvysledky.id) AS pocet_kol,"
		    . "MAX($this->sqlvysledky.race_time) AS finish_time,"
		    . "MAX($this->sqlvysledky.time_order) AS time_order "
		    ."FROM $this->sqlvysledky,$this->sqlzavod,$this->sqlkategorie WHERE "
		    . "race_time > 0 AND "
		    . "$this->sqlzavod.cip = $this->sqlvysledky.cip AND "
		    . "$this->sqlvysledky.id_etapy = 2 AND "
		    . "$this->sqlvysledky.id_etapy = $this->sqlzavod.id_etapy AND "
		    . "$this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie AND "
		    . "$this->sqlkategorie.poradi_podzavodu = $this->event_order AND "
		    . "$this->sqlvysledky.false_time IS NULL AND "
		    . "$this->sqlvysledky.lap_only IS NULL "
		    . "GROUP BY $this->sqlvysledky.cip "
		    . "ORDER BY pocet_kol DESC,finish_time ASC";
    $sth1 =  $this->db->prepare($sql1);
    $sth1->execute(Array(':event_order' => $this->event_order));
    if($sth1->rowCount()){
	$str .= '<h4 class="headline-results">'.$this->race_name.', výsledky bez rozdílu kategorií</h4>';
	$str .= '<table class="table table-bordered table-hover table_vysledky">';
	$str .= '<thead><tr class="header"><th class="text-center">#</th><th class="text-center">St.č</th><th>Jméno</th><th>Tým</th><th class="text-center">Stát</th><th>Kategorie</th><th class="text-center">Značka</th><th class="text-center">Čas</th><th class="text-center">Kolo</th><th class="text-center">Cílový čas</th><th class="text-center">Odstup</th>';
	$str .= '</tr></thead><tbody>';
	$poradi = 1;
	$sql2 = '';
	while($dbdata1 = $sth1->fetchObject()){
	    $sql2 .= "SELECT CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.psc AS stat,tymy.nazev_tymu,$this->sqlkategorie.nazev_k AS nazev_kategorie,znacky_motocyklu.nazev_motocyklu "
		      . "FROM osoby,$this->sqlzavod,tymy,$this->sqlkategorie,znacky_motocyklu WHERE "
		      . "$this->sqlzavod.ids = $dbdata1->ids AND "
		      . "$this->sqlzavod.id_etapy = 2 AND "
		      . "$this->sqlzavod.ido = osoby.ido AND "
		      . "$this->sqlzavod.id_tymu = tymy.id_tymu AND "
		      . "$this->sqlzavod.id_kategorie = $this->sqlkategorie.id_kategorie AND "
		      . "$this->sqlzavod.id_motocyklu = znacky_motocyklu.id_motocyklu;";
	    
	}   
	$sth2 = $this->db->prepare($sql2);
	$sth2->execute();
	    
	    //$sth2 = $this->db->query($sql2);
	    //if($sth2->rowCount()){
		$i = 0;
		while($dbdata2 = $sth2->fetchObject()){
		    $str .= '<tr id="'.$dbdata1->cip.'">';
		    
		    
		
		   
		$str .= '</tr>';
		   $sth2->nextRowset();
		}

		
		
		

		    
	    //}
		
		
		/*
		$str .= '<tr id="'.$dbdata1->cip.'">';
		    $str .= '<td class="text-center">'.$poradi.'</td>';
		    $str .= '<td class="text-center">'.$dbdata1->ids.'</td>';
		    $str .= '<td>'.$dbdata2->jmeno.'</td>';
		    $str .= ($dbdata2->nazev_tymu == 'Bez týmu') ? ('<td>&nbsp;</td>') : ('<td>'.$dbdata2->nazev_tymu.'</td>');
		    $str .= '<td class="text-center">'.$dbdata2->stat.'</td>';
		    $str .= '<td>'.$dbdata2->nazev_kategorie.'</td>';
		    $str .= '<td class="text-center">'.$dbdata2->nazev_motocyklu.'</td>';
		    $str .= '<td class="text-center">'.$dbdata1->finish_time.'</td>';
		    $str .= '<td class="text-center">'.$dbdata1->pocet_kol.'</td>';
		    
		    
		
		   
		$str .= '</tr>';
		 * */
		 
	    //}
	    $poradi++;
	 //}
	 
	$str .= '</tbody></table>';

	 
	 
    }
    
    
    /*
    
    if($sth1->rowCount()){
	$str .= '<h4 class="headline-results">'.$this->race_name.', výsledky bez rozdílu kategorií</h4>';
	$str .= '<table class="table table-bordered table-hover table_vysledky">';
	$str .= '<thead><tr class="header"><th class="text-center">#</th><th class="text-center">St.č</th><th>Jméno</th><th class="text-center">Ročník</th><th>Název týmu</th><th>Kategorie</th><th class="text-center">Počet kol</th><th class="text-center">Počet km</th><th class="text-center">Poslední start</th><th class="text-center">Cílový čas</th><th class="text-center">Odstup</th>';
	$str .= '</tr></thead><tbody>';
	$poradi = 1;
	
	while($data1 = $sth1->fetchObject()){ //začneme vypisovat první dotaz
	    if($poradi == 1) $max_pocet_kol = $data1->pocet_kol;// nejvyšší počet kol pro počítání odstupů
	    if($poradi == 1) $max_time_order = $data1->time_order;// nejvyšší počet časů pro počítání odstupů
	    //$sql2 = "SELECT $this->sqlvysledky.distance_overall FROM $this->sqlvysledky WHERE $this->sqlvysledky.ids = :ids AND time_order = :max_time_order"; //vezmeme odstupy, v tomto případě ve zvláštním dotaze
	    $sql2 = "SELECT $this->sqlvysledky.distance_overall FROM $this->sqlvysledky WHERE $this->sqlvysledky.ids = '$data1->ids' AND lap_count = '$max_pocet_kol'"; //vezmeme odstupy, v tomto případě ve zvláštním dotaze
	    $sth2 = $this->db->prepare($sql2);
	    //$sth2->execute(Array(':ids' => $data1->ids,':max_time_order' => $max_time_order));
	    $sth2->execute();
	    if($sth2->rowCount()){ //pokud je někdo se stejným, tzn. nejvyšším počttem kol, použijeme časový odstup
		$dbdata2 = $sth2->fetchObject();
		($dbdata2->distance_overall != '00:00:00.00') ? ($distance_overall = $dbdata2->distance_overall) : ($distance_overall = '-'); //pokud nemá odstup, tak je první a bude tam pomlčka
	    }
	    else{ // pokud ne, spočítáme odstup v kolech
		 $distance_overall = $data1->pocet_kol - $max_pocet_kol; 
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
	    // tady vybereme jednotlivé členy týmu
	    $sql3 = "SELECT CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,$this->sqlzavod.ids_alias,$this->sqlzavod.cip FROM osoby,$this->sqlzavod WHERE $this->sqlzavod.ids = :ids AND $this->sqlzavod.ido = osoby.ido ORDER BY $this->sqlzavod.id";
	    $sth3 = $this->db->prepare($sql3);
	    $sth3->execute(Array(':ids' => $data1->ids));
	    $sql4 = "SELECT $this->sqlvysledky.day_time AS last_time FROM $this->sqlvysledky WHERE $this->sqlvysledky.ids = :ids AND $this->sqlvysledky.reader LIKE :reader  ORDER BY race_time DESC LIMIT 0,1";
	    $sth4 = $this->db->prepare($sql4);
	    $sth4->execute(Array(':ids' => $data1->ids,':reader' => 'START'));
	    if($sth4->rowCount()){
		$data4 = $sth4->fetchObject();
	    }

		
		    while($dbdata3 = $sth3->fetchObject()){
			$str .= '<tr id="'.$data1->cip.'">';
			$str .= '<td class="text-center">'.$poradi.'</td>';
			$str .= '<td class="text-center">'.$dbdata3->ids_alias.'</td>';
			$str .= '<td><a onclick="detail_cipu('.$data1->cip.','.$this->race_id.','.$this->race_year.')" href="'.$hash_url.'vysledky">'.$dbdata3->jmeno.'</a></td>';
			$str .= '<td class="text-center">'.$dbdata3->rocnik.'</td>';
			//$str .= '<td>'.$data1->nazev_tymu.'</td>';
			$str .= '<td>'.$data1->nazev_kategorie.'</td>';
			$str .= '<td class="text-center">'.$data1->pocet_kol.'</td>';
			$str .= '<td class="text-center">'.($data1->pocet_kol * $this->delka_kola ).'</td>';
			//$str .= '<td class="text-center">'.$data4->last_time.'</td>';
			$str .= '<td class="text-center">'.$data1->finish_time.'</td>';
			$str .= '<td class="text-center">'.$distance_overall.'</td>';
			$str .= '</tr>';
		}
		$poradi++;
	}
	$str .= '</tbody></table>';
    }
    
    
    */
    
    
?>
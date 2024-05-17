<?php
    /*
     * cross country hobby týmy
     */
    $hash_url = '#'.$this->race_year.'/'.$this->race_id.'/';
    $colspan = 4;
    $str = '';
    
    
    if($category_id == 'all'){ // 1.if
	$str .= '<h4 class="headline-results">'.$this->race_name.', výsledky podle kategorí</h4>';
	$sql = "SELECT id_kategorie,kod_k AS kod_kategorie,nazev_k AS nazev_kategorie,poradi_podzavodu FROM $this->sqlkategorie WHERE id_zavodu = :race_id  ORDER BY id_kategorie";
	$sth = $this->db->prepare($sql);
	$sth->execute(Array(':race_id' => $this->race_id));
	if($sth->rowCount()){ //2.if
	    $str .= '<table class="table table-bordered noborder table_vysledky">';
	    $k = 1;
	    while($dbdata1 = $sth->fetchObject()){ //1.cyklus
		$sql2 = "SELECT "
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
		. "$this->sqlkategorie.id_kategorie =  :id_kategorie AND "
		. "$this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie AND "
		. "$this->sqlkategorie.poradi_podzavodu = :event_order AND "
		. "$this->sqlvysledky.false_time IS NULL AND "
		. "$this->sqlvysledky.lap_only IS NULL "
		. "GROUP BY $this->sqlvysledky.ids "
		. "ORDER BY pocet_kol DESC,finish_time ASC";
		//$str .= $sql2.'<br />';
		$sth2 =  $this->db->prepare($sql2);
		$sth2->execute(Array(':id_etapy' => $this->id_etapy,':id_kategorie' => $dbdata1->id_kategorie,':event_order' => $this->event_order));
		
		
		
		if($sth2->rowCount()){ //3.if
		    $class = $k == 1 ? $class = 'nadpis nopadding' : 'nadpis';
		    $str .= '<tr><td class="'.$class.'" colspan="3">'.$dbdata1->nazev_kategorie.'</td></tr>';
		    $str .= '<tr class="header"><th class="text-center">#</th><th class="text-center">St.č</th><th>Jezdci</th><th class="text-center">Ročník</th><th class="text-center">Čip</th><th class="text-center">Čas</th><th class="text-center">Kola</th><th class="text-center">Odstup</th><th class="text-center">Km/h</th><th class="text-center">Nejlepší čas</th></tr>';
		    $poradi = 1;
		    while($dbdata2 = $sth2->fetchObject()){ //2.cyklus
			   $celkova_vzdalenost = $dbdata2->pocet_kol * $this->delka_kola;
			    if($poradi == 1) $max_pocet_kol = $dbdata2->pocet_kol;// nejvyšší počet kol pro počítání odstupů
			    //if($poradi == 1) $max_time_order = $dbdata1->time_order;// nejvyšší počet časů pro počítání odstupů
			    $sql4 = "SELECT $this->sqlvysledky.distance_category FROM $this->sqlvysledky WHERE $this->sqlvysledky.ids = :ids AND time_order = :max_pocet_kol AND id_etapy = :id_etapy"; //vezmeme odstupy, v tomto případě ve zvláštním dotaze
			    $sth4 = $this->db->prepare($sql4);
			    $sth4->execute(Array(':ids' => $dbdata2->ids,':max_pocet_kol' => $max_pocet_kol,':id_etapy' => $this->id_etapy));
			    if($sth4->rowCount()){ //pokud je někdo se stejným, tzn. nejvyšším počttem kol, použijeme časový odstup
				$dbdata4 = $sth4->fetchObject();
				($dbdata4->distance_category != '00:00:00.00') ? ($distance_category = $dbdata4->distance_category) : ($distance_category = '-'); //pokud nemá odstup, tak je první a bude tam pomlčka
			    }
			    else{ // pokud ne, spočítáme odstup v kolech
				$distance_category = $dbdata2->pocet_kol - $max_pocet_kol; 
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
			    
			    
			    
			    
			    /*
			     *	tady musíme vybírat na základě ids, protože čip z týmu má každá jiný a proto musíme přidat podmínku event_order, kterou bereme ze selectu z 
			     *  z výberu podávodu z lišty, aby to nevzalo ids i z jednotlivců v případě, že v jednotlivcích někdo se stejným ids je
			    */
			    $sql3 = "SELECT CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.psc AS stat,osoby.rocnik,$this->sqlzavod.cip "
				      . "FROM osoby,$this->sqlzavod WHERE "
				      . "$this->sqlzavod.ids = :ids AND "
				      . "$this->sqlzavod.id_etapy = :id_etapy AND "
				      . "$this->sqlzavod.poradi_podzavodu = :event_order AND "
				      . "$this->sqlzavod.ido = osoby.ido";
			    $sth3 = $this->db->prepare($sql3);
			    $sth3->execute(Array(':ids' => $dbdata2->ids,':id_etapy' => $this->id_etapy,':event_order' => $this->event_order));
			    if($sth3->rowCount()){
				$pocet_clenu = $sth3->rowCount();
				$i = 1;
				while($dbdata3 = $sth3->fetchObject()){
				    if($i == 1){
					$str .= '<tr>';
					$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$poradi.'</td>';
					$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$dbdata2->ids.'</td>';
					$str .= '<td>'.$dbdata3->jmeno.'</td>';
					$str .= '<td class="text-center">'.$dbdata3->rocnik.'</td>';
					$str .= '<td class="text-center">'.$dbdata3->cip.'</td>';
					$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$dbdata2->finish_time.'</td>';
					$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$dbdata2->pocet_kol.'</td>';
					$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$distance_category.'</td>';
					$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.round($celkova_vzdalenost / $dbdata2->total_lap_time_sec * 3600,1).'</td>';
					$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$dbdata2->best_lap_time.'</td>';

					$str .= '</tr>';
				    }
				    else{
					$str .= '<tr>';
					$str .= '<td>'.$dbdata3->jmeno.'</td>';
					$str .= '<td class="text-center">'.$dbdata3->rocnik.'</td>';
					$str .= '<td class="text-center">'.$dbdata3->cip.'</td>';
					$str .= '</tr>';
				    }
				    $i++;

				}
			    }
			    $poradi++;
		    } //2.cyklus
		} // 3.if
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
		$str .= '<h4 class="headline-results">'.$this->race_name.', kategorie '.$dbdata1->nazev_kategorie.'</h4>';
		$str .= '<table class="table table-bordered table_vysledky">';
		$str .= '<thead><tr class="header"><th class="text-center">#</th><th class="text-center">St.č</th><th>Jezdci</th><th class="text-center">Ročník</th><th class="text-center">Čip</th><th class="text-center">Čas</th><th class="text-center">Kolo</th><th class="text-center">Odstup</th><th class="text-center">Km/h</th><th class="text-center">Nejlepší čas</th>';
		$str .= '</tr></thead><tbody>';

		$sql2 = "SELECT "
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
		. "$this->sqlkategorie.id_kategorie =  :category_id AND "
		. "$this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie AND "
		. "$this->sqlkategorie.poradi_podzavodu = :event_order AND "
		. "$this->sqlvysledky.false_time IS NULL AND "
		. "$this->sqlvysledky.lap_only IS NULL "
		. "GROUP BY $this->sqlvysledky.ids "
		. "ORDER BY pocet_kol DESC,finish_time ASC";
		$sth2 =  $this->db->prepare($sql2);
		$sth2->execute(Array(':id_etapy' => $this->id_etapy,':category_id' => $category_id,':event_order' => $this->event_order));
		if($sth2->rowCount()){ 
		    $poradi = 1;
		    while($dbdata2 = $sth2->fetchObject()){ //2.cyklus
			   $celkova_vzdalenost = $dbdata2->pocet_kol * $this->delka_kola;
			    if($poradi == 1) $max_pocet_kol = $dbdata2->pocet_kol;// nejvyšší počet kol pro počítání odstupů
			    //if($poradi == 1) $max_time_order = $dbdata1->time_order;// nejvyšší počet časů pro počítání odstupů
			    $sql4 = "SELECT $this->sqlvysledky.distance_category FROM $this->sqlvysledky WHERE $this->sqlvysledky.ids = :ids AND time_order = :max_pocet_kol AND id_etapy = :id_etapy"; //vezmeme odstupy, v tomto případě ve zvláštním dotaze
			    $sth4 = $this->db->prepare($sql4);
			    $sth4->execute(Array(':ids' => $dbdata2->ids,':max_pocet_kol' => $max_pocet_kol,':id_etapy' => $this->id_etapy));
			    if($sth4->rowCount()){ //pokud je někdo se stejným, tzn. nejvyšším počttem kol, použijeme časový odstup
				$dbdata4 = $sth4->fetchObject();
				($dbdata4->distance_category != '00:00:00.00') ? ($distance_category = $dbdata4->distance_category) : ($distance_category = '-'); //pokud nemá odstup, tak je první a bude tam pomlčka
			    }
			    else{ // pokud ne, spočítáme odstup v kolech
				$distance_category = $dbdata2->pocet_kol - $max_pocet_kol; 
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
			    
			    /*
			     *	tady musíme vybírat na základě ids, protože čip z týmu má každá jiný a proto musíme přidat podmínku event_order, kterou bereme ze selectu z 
			     * z výberu podávodu z lišty, aby to nevzalo ids i z jednotlivců v případě, že v jednotlivcích někdo se stejným ids je
			    */
			    $sql3 = "SELECT CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.psc AS stat,osoby.rocnik,$this->sqlzavod.cip "
				      . "FROM osoby,$this->sqlzavod WHERE "
				      . "$this->sqlzavod.ids = :ids AND "
				      . "$this->sqlzavod.id_etapy = :id_etapy AND "
				      . "$this->sqlzavod.poradi_podzavodu = :event_order AND "
				      . "$this->sqlzavod.ido = osoby.ido";
			   // echo $sql3;
			    $sth3 = $this->db->prepare($sql3);
			    $sth3->execute(Array(':ids' => $dbdata2->ids,':id_etapy' => $this->id_etapy,':event_order' => $this->event_order));
			    if($sth3->rowCount()){
				$pocet_clenu = $sth3->rowCount();
				$i = 1;
				while($dbdata3 = $sth3->fetchObject()){
				    if($i == 1){
					$str .= '<tr>';
					$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$poradi.'</td>';
					$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$dbdata2->ids.'</td>';
					$str .= '<td>'.$dbdata3->jmeno.'</td>';
					$str .= '<td class="text-center">'.$dbdata3->rocnik.'</td>';
					$str .= '<td class="text-center">'.$dbdata3->cip.'</td>';
					$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$dbdata2->finish_time.'</td>';
					$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$dbdata2->pocet_kol.'</td>';
					$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$distance_category.'</td>';
					$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.round($celkova_vzdalenost / $dbdata2->total_lap_time_sec * 3600,1).'</td>';
					$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$dbdata2->best_lap_time.'</td>';

					$str .= '</tr>';
				    }
				    else{
					$str .= '<tr>';
					$str .= '<td>'.$dbdata3->jmeno.'</td>';
					$str .= '<td class="text-center">'.$dbdata3->rocnik.'</td>';
					$str .= '<td class="text-center">'.$dbdata3->cip.'</td>';
					$str .= '</tr>';
				    }
				    $i++;

				}
			    }
		    $poradi++;
		    } //2.cyklus
		}
	    } 
 
	} //hlavní else
    
 ?>
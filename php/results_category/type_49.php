<?php
    /*
     * cross country Open
     * v  hledání distancí je tady použito time_order a ne lap_count, které jsem tam odněkud zkopíroval z jinho type asi
     */
    $hash_url = '#'.$this->race_year.'/'.$this->race_id.'/';
    $colspan = 4;
    $str = '';
       $body = Array(25,22,20,18,16,15,14,13,12,11,10,9,8,7,6,5,4,3,2,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,11,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1);
   
    
   if($category_id == 'all'){ // 1.if
	$str .= '<h4 class="headline-results cc">'.$this->race_name.', výsledky podle kategorí</h4>';
	$str .= '<div class="datum_nadpis" style="padding-top:-19px;text-align:center;font-size:12px;"><b>'.$this->datum_zavodu.'</b></div>';
	$sql = "SELECT id_kategorie,kod_k AS kod_kategorie,nazev_k AS nazev_kategorie,poradi_podzavodu FROM $this->sqlkategorie WHERE id_zavodu = :race_id  ORDER BY poradi";
	$sth = $this->db->prepare($sql);
	$sth->execute(Array(':race_id' => $this->race_id));
	if($sth->rowCount()){ //2.if
	    $str .= '<table class="table table-bordered table-stripd table-hover noborder table_vysledky"  id="table2excel">';
	    $k = 1;
	    while($dbdata1 = $sth->fetchObject()){ //1.cyklus
		$sql2 = "SELECT "
		. "$this->sqlvysledky.ids,"
		. "$this->sqlvysledky.ids_alias,"
		. "$this->sqlvysledky.cip,"
		. "COUNT($this->sqlvysledky.id) AS pocet_kol,"
		. "MAX($this->sqlvysledky.race_time) AS finish_time,"
		. "MAX($this->sqlvysledky.time_order) AS time_order ,"
		. "MIN($this->sqlvysledky.lap_time) AS best_lap_time,"
		. "SUM($this->sqlvysledky.lap_time_sec) AS total_lap_time_sec "
		."FROM $this->sqlvysledky,$this->sqlzavod,$this->sqlkategorie WHERE "
		. "race_time > 0 AND "
		. "$this->sqlvysledky.time_order > 1 AND "
		. "$this->sqlzavod.cip = $this->sqlvysledky.cip AND "
		. "$this->sqlzavod.priznak IS NULL AND "
		. "$this->sqlkategorie.id_kategorie =  :id_kategorie AND "
		. "$this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie AND "
		. "$this->sqlkategorie.poradi_podzavodu = :event_order AND "
		. "$this->sqlvysledky.false_time IS NULL AND "
		. "$this->sqlvysledky.lap_only IS NULL "
		. "GROUP BY $this->sqlvysledky.cip "
		. "ORDER BY pocet_kol DESC,finish_time ASC".$this->rows_limit;
		//$str .= $sql2.'<br />';
		$sth2 =  $this->db->prepare($sql2);
		$sth2->execute(Array(':id_kategorie' => $dbdata1->id_kategorie,':event_order' => $this->event_order));
		if($sth2->rowCount()){ //3.if
		    $class = $k == 1 ? $class = 'nadpis nopadding' : 'nadpis';
		    $str .= '<tr><td class="'.$class.'" colspan="3">'.$dbdata1->nazev_kategorie.'</td></tr>';
		    $str .= '<tr class="header"><th class="text-center">#</th><th class="text-center">St.č</th><th class="text-left">Jméno</th><th>Kola</th><th class="text-center">Odstup</th></tr>';
		    $poradi = 1;
		    while($dbdata2 = $sth2->fetchObject()){ //2.cyklus
			    
			    $celkova_vzdalenost = $dbdata2->pocet_kol * $this->delka_kola;
			    if($poradi == 1) $max_pocet_kol = $dbdata2->pocet_kol + 1;// nejvyšší počet kol pro počítání odstupů
			    //echo $max_pocet_kol; 
			    //if($poradi == 1) $max_time_order = $dbdata1->time_order;// nejvyšší počet časů pro počítání odstupů
			    $sql4 = "SELECT $this->sqlvysledky.distance_category FROM $this->sqlvysledky WHERE $this->sqlvysledky.ids = :ids AND time_order = :max_pocet_kol"; //vezmeme odstupy, v tomto případě ve zvláštním dotaze
			    
			    $sth4 = $this->db->prepare($sql4);
			    $sth4->execute(Array(':ids' => $dbdata2->ids,':max_pocet_kol' => $max_pocet_kol));
			    if($sth4->rowCount()){ //pokud je někdo se stejným, tzn. nejvyšším počttem kol, použijeme časový odstup
				$dbdata4 = $sth4->fetchObject();
				($dbdata4->distance_category != '00:00:00.000') ? ($distance_category = substr($dbdata4->distance_category,1)) : ($distance_category = '-'); //pokud nemá odstup, tak je první a bude tam pomlčka
			    }
			    else{ // pokud ne, spočítáme odstup v kolech
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
			
			$sql3 = "SELECT CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.psc AS stat,osoby.rocnik,tymy.nazev_tymu,$this->sqlkategorie.nazev_k AS nazev_kategorie,znacky_motocyklu.nazev_motocyklu,$this->sqlzavod.penalizace,$this->sqlzavod.bodovani "
			. "FROM osoby,$this->sqlzavod,tymy,$this->sqlkategorie,znacky_motocyklu WHERE "
			. "$this->sqlzavod.cip = :cip AND "
			. "$this->sqlzavod.ido = osoby.ido AND "
			. "$this->sqlzavod.id_tymu = tymy.id_tymu AND "
			. "$this->sqlzavod.id_kategorie = $this->sqlkategorie.id_kategorie AND "
			. "$this->sqlzavod.id_motocyklu = znacky_motocyklu.id_motocyklu";
			$sth3 = $this->db->prepare($sql3);
			$sth3->execute(Array(':cip' => $dbdata2->cip));
			if($sth3->rowCount()){
			    $dbdata3 = $sth3->fetchObject();
			    $str .= '<tr id="'.$dbdata2->cip.'">';
			    $str .= '<td class="text-center">'.$poradi.'</td>';
			    $str .= '<td class="text-center">'.$dbdata2->ids_alias.'</td>';
			    $str .= '<td><a onclick="detail_cipu_cc_bez_etap('.$dbdata2->cip.','.$this->race_id.','.$this->race_year.')" href="'.$hash_url.'vysledky">'.$dbdata3->jmeno.'</a></td>';
			    $str .= '<td class="text-center">'.$dbdata3->rocnik.'</td>';
			    $str .= ($dbdata3->nazev_tymu == 'Bez týmu') ? ('<td>&nbsp;</td>') : ('<td class="text-center">'.$this->NahrazkaPomlcky($dbdata3->nazev_tymu).'</td>');
			    $str .= '<td class="text-center">'.$dbdata3->stat.'</td>';
			    $str .= '<td class="text-center">'.$dbdata3->nazev_kategorie.'</td>';
			    $str .= '<td class="text-center">'.(($dbdata3->nazev_motocyklu != "Neznámá") ? ($dbdata3->nazev_motocyklu) : ("&nbsp;")).'</td>';
			    $str .= '<td class="text-center">'.substr($dbdata2->finish_time,1).'</td>';
			    $str .= '<td class="text-center">'.$dbdata2->pocet_kol.'</td>';
			    $str .= '<td class="text-center">'.$distance_category.'</td>';
			    $str .= '<td  class="text-center">'.round($celkova_vzdalenost / $dbdata2->total_lap_time_sec * 3600,1).'</td>';
			    $str .= '<td class="text-center">'.substr($dbdata2->best_lap_time,1).'</td>';
			    $str .= '<td class="text-center">'.$dbdata3->penalizace.'</td>';
			    if(!$dbdata3->bodovani){
				$str .= '<td class="text-center">'.$body[$poradi-1].'</td>';
			    }
			    else{
				$str .= '<td class="text-center">'.$dbdata3->bodovani.'</td>';
			    }
			    $str .= '</tr>';
		      }
		    $poradi++;
		    } //2.cyklus
		} // 3.if
		
		$sql4 = "SELECT "
		. "$this->sqlvysledky.ids,"
		. "$this->sqlvysledky.ids_alias,"
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
		. "$this->sqlkategorie.id_kategorie =  :id_kategorie AND "
		. "$this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie AND "
		. "$this->sqlkategorie.poradi_podzavodu = :event_order AND "
		. "$this->sqlvysledky.false_time IS NULL AND "
		. "$this->sqlvysledky.lap_only IS NULL "
		. "GROUP BY $this->sqlvysledky.cip "
		. "ORDER BY $this->sqlzavod.priznak DESC,pocet_kol DESC,finish_time ASC".$this->rows_limit;
		$sth4 =  $this->db->prepare($sql4);
		$sth4->execute(Array(':id_kategorie' => $dbdata1->id_kategorie,':event_order' => $this->event_order));
		if($sth4->rowCount()){ //3.if
		    while($dbdata4 = $sth4->fetchObject()){ //2.cyklus
			$celkova_vzdalenost = $dbdata4->pocet_kol * $this->delka_kola;
			$sql5 = "SELECT CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.psc AS stat,osoby.rocnik,tymy.nazev_tymu,$this->sqlkategorie.nazev_k AS nazev_kategorie,znacky_motocyklu.nazev_motocyklu,$this->sqlzavod.penalizace,$this->sqlzavod.priznak "
			. "FROM osoby,$this->sqlzavod,tymy,$this->sqlkategorie,znacky_motocyklu WHERE "
			. "$this->sqlzavod.ids = :ids AND "
			. "$this->sqlzavod.ido = osoby.ido AND "
			. "$this->sqlzavod.id_tymu = tymy.id_tymu AND "
			. "$this->sqlzavod.id_kategorie = $this->sqlkategorie.id_kategorie AND "
			. "$this->sqlzavod.id_motocyklu = znacky_motocyklu.id_motocyklu";
			$sth5 = $this->db->prepare($sql5);
			$sth5->execute(Array(':ids' => $dbdata4->ids));
			if($sth5->rowCount()){
			    $dbdata5 = $sth5->fetchObject();
			    $str .= '<tr id="'.$dbdata4->cip.'">';
			    $str .= '<td class="text-center">-</td>';
			    $str .= '<td class="text-center">'.$dbdata4->ids_alias.'</td>';
			    $str .= '<td><a onclick="detail_cipu_cc_bez_etap('.$dbdata4->cip.','.$this->race_id.','.$this->race_year.')" href="'.$hash_url.'vysledky">'.$dbdata5->jmeno.'</a></td>';
			    $str .= '<td class="text-center">'.$dbdata5->rocnik.'</td>';
			    $str .= ($dbdata5->nazev_tymu == 'Bez týmu') ? ('<td>&nbsp;</td>') : ('<td class="text-center">'.$this->NahrazkaPomlcky($dbdata5->nazev_tymu).'</td>');
			    $str .= '<td class="text-center">'.$dbdata5->stat.'</td>';
			    $str .= '<td class="text-center">'.$dbdata5->nazev_kategorie.'</td>';
			    $str .= '<td class="text-center">'.(($dbdata5->nazev_motocyklu != "Neznámá") ? ($dbdata5->nazev_motocyklu) : ("&nbsp;")).'</td>';
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
		       $str .= '<div style=height:2500px>';
                $str .= '<table class="table table-bordered table-hver table-striped table_vysledky_enduro" style="position:relative;">';
		
                //$str .= '<thead><tr class="header"><th class="text-center" style="idth:10%">#</th><th class="text-left">Jméno</th><th class="text-center" style="idth:15%">Kolo</th><th class="text-center" style="idth:15%">Odstup</th>';
		
                //$str .= '</tr></thead><tbody>';
                $str .= '<tbody>';

		$sql2 = "SELECT "
		. "$this->sqlvysledky.ids,"
		. "$this->sqlvysledky.ids_alias,"
		. "$this->sqlvysledky.cip,"
		. "COUNT($this->sqlvysledky.id) AS pocet_kol,"
		. "MAX($this->sqlvysledky.race_time) AS finish_time,"
		. "MAX($this->sqlvysledky.time_order) AS time_order ,"
		. "MIN($this->sqlvysledky.lap_time) AS best_lap_time,"
                . "MAX($this->sqlvysledky.lap_time) AS slowest_lap_time,"
		. "SUM($this->sqlvysledky.lap_time_sec) AS total_lap_time_sec "
		."FROM $this->sqlvysledky,$this->sqlzavod,$this->sqlkategorie WHERE "
		. "race_time > 0 AND "
		. "time_order > 1 AND "
		. "$this->sqlzavod.cip = $this->sqlvysledky.cip AND "
		. "$this->sqlzavod.priznak IS NULL AND "
		. "$this->sqlkategorie.id_kategorie =  :category_id AND "
		. "$this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie AND "
		. "$this->sqlkategorie.poradi_podzavodu = :event_order AND "
		. "$this->sqlvysledky.false_time IS NULL AND "
		. "$this->sqlvysledky.lap_only IS NULL "
		. "GROUP BY $this->sqlvysledky.cip "
		. "ORDER BY pocet_kol DESC,finish_time ASC".$this->rows_limit;
		//z$str .= $sql2;
		$sth2 =  $this->db->prepare($sql2);
		$sth2->execute(Array(':category_id' => $category_id,':event_order' => $this->event_order));
		if($sth2->rowCount()){ 
		    $poradi = 1;
		    while($dbdata2 = $sth2->fetchObject()){ //2.cyklus
			   $celkova_vzdalenost = $dbdata2->pocet_kol * $this->delka_kola;
			    if($poradi == 1) $max_pocet_kol = $dbdata2->pocet_kol + 1;// nejvyšší počet kol pro počítání odstupů
			    //if($poradi == 1) $max_time_order = $dbdata1->time_order;// nejvyšší počet časů pro počítání odstupů
			    $sql4 = "SELECT $this->sqlvysledky.distance_category FROM $this->sqlvysledky WHERE $this->sqlvysledky.ids = :ids AND time_order = :max_pocet_kol"; //vezmeme odstupy, v tomto případě ve zvláštním dotaze
			    $sth4 = $this->db->prepare($sql4);
			    $sth4->execute(Array(':ids' => $dbdata2->ids,':max_pocet_kol' => $max_pocet_kol));
			    if($sth4->rowCount()){ //pokud je někdo se stejným, tzn. nejvyšším počttem kol, použijeme časový odstup
				$dbdata4 = $sth4->fetchObject();
				($dbdata4->distance_category != '00:00:00.000' && $dbdata4->distance_category != '00:00:00.00') ? ($distance_category = substr($dbdata4->distance_category,3,-2)) : ($distance_category = '-'); //pokud nemá odstup, tak je první a bude tam pomlčka
			    }
			    else{ // pokud ne, spočítáme odstup v kolech
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
			
			$sql3 = "SELECT CONCAT_WS(' ',osoby.jmeno,osoby.prijmeni) AS jmeno "
			. "FROM osoby,$this->sqlzavod WHERE "
			. "$this->sqlzavod.ids = :ids AND "
			. "$this->sqlzavod.ido = osoby.ido";
			$sth3 = $this->db->prepare($sql3);
			$sth3->execute(Array(':ids' => $dbdata2->ids));
			if($sth3->rowCount()){
			    $dbdata3 = $sth3->fetchObject();
			    $str .= '<tr id="'.$dbdata2->cip.'" class="tr_class" style="position:relative">';
			    $str .= '<td class="text-center">'.$poradi.'</td>';
			    //$str .= '<td class="text-center">'.$dbdata2->ids_alias.'</td>';
			    //$str .= '<td><a onclick="detail_cipu_cc_bez_etap('.$dbdata2->cip.','.$this->race_id.','.$this->race_year.')" href="'.$hash_url.'vysledky">';
                            $str .= '<td>';
                            
                            $str .= "<div style=\"margin-right:5px;text-align:center;width:24px;padding:1px;font-size:10px;float:left;border:1px solid blue;border-radius:3px;background:yellow;color:blue\">$dbdata2->ids_alias</div>";
                            
                            $str .= '<div class="detail_cipu_class" id="div_'.$dbdata2->cip.'" style="position:absolute;left:0;display:none;width:100%;margin-top:44px"></div>';
                            
                            
                            
                            
                            //$str .= "<div>";
                            
                            $str .= "<span>$dbdata3->jmeno</span><br>";
                            $str .= "<span>Nejrychlejší kolo:&nbsp;&nbsp;&nbsp;".substr($dbdata2->best_lap_time,3,-4)."</span><br>";
                            $str .= "<span>Nejpomalejší kolo:&nbsp;".substr($dbdata2->slowest_lap_time,3,-4)."</span>";
                            
                       
                           // $str .= "<div style=\"margin:-5px 5px 0 1px;text-align:center;width:60px;padding:5px;font-size:30px;float:left;border:1px solid blue;border-radius:10px;background:yellow;color:blue\">$dbdata2->ids_alias</div></div>";
                                    
                            $str .= '</td>';
                            //$str .= '</a></td>';
			    //$str .= '<td class="text-center">'.substr($dbdata2->finish_time,1).'</td>';
			    $str .= '<td class="text-center">'.$dbdata2->pocet_kol.'</td>';
			    $str .= '<td class="text-center">'.$distance_category.'</td>';
			    $str .= '</tr>';
		      }
		    $poradi++;
		    } //2.cyklus
		}
		
		
		
		
	    } 
	
	    $str .= '</tbody></table></div>';

	
	
	
	
                
                         $str .= "<hr><div class=\"paticka_cams\">"
			  . "<p>Délka trati: $this->delka_kola Km</p>"
			  . "<table class=\"paticka_cams_table\">"
			  . "<tr><td colspan=\"3\">Časomíra: TimeChip</td></tr>"
			  . "<tr><td>Ředitel závodu: $this->reditel_zavodu</td><td>Výsledky podléhají schválení jury</td><td>Hlavní časoměřič: $this->casomeric</td></tr>"
			  . "<tr><td>Jury: $this->jury</td><td>Čas vyvěšení:</td><td>www.timechip.cz</td></tr>"
			  . "</table>"
			  . "</div>";
                
                
	    } //hlavní else
	    
	    
    
?>



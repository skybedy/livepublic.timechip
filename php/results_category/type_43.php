<?php
    /*
     * motorky Benešov
     * v  hledání distancí je tady použito time_order a ne lap_count, které jsem tam odněkud zkopíroval z jinho type asi
     */
    $hash_url = '#'.$this->race_year.'/'.$this->race_id.'/';
    $colspan = 4;
    $str = '';
   
    
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
		. "$this->sqlvysledky.cip,"
		. "COUNT($this->sqlvysledky.id) AS pocet_kol,"
		. "MIN($this->sqlvysledky.lap_time) AS best_lap_time,"
		. "MIN($this->sqlvysledky.lap_time_sec) AS best_lap_time_sec "
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
		. "ORDER BY best_lap_time_sec ASC";
		//echo $sql2.'<br />';
		
                $sth2 =  $this->db->prepare($sql2);
		$sth2->execute(Array(':id_kategorie' => $dbdata1->id_kategorie,':event_order' => $this->event_order));
		if($sth2->rowCount()){ //3.if
		    $class = $k == 1 ? $class = 'nadpis nopadding' : 'nadpis';
		    $str .= '<tr><td class="'.$class.'" colspan="3">'.$dbdata1->nazev_kategorie.'</td></tr>';
		    $str .= '<tr class="header"><th class="text-center">#</th><th class="text-center">St.č</th><th class="text-left">Jméno</th><th class="text-center">Ročník</th><th class="text-center">Nejrychlejší kolo</th><th class="text-center">Odstup</th></tr>';
		    $poradi = 1;
		    while($dbdata2 = $sth2->fetchObject()){ //2.cyklus
                        if($poradi == 1){
                            $best_lap_time_sec = $dbdata2->best_lap_time_sec;
                        }
                        $dynamic_distances = $this->DynamicDistancesTisiciny($poradi,$dbdata2->best_lap_time_sec,$best_lap_time_sec);
                        $sql3 = "SELECT CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.psc AS stat,osoby.rocnik,tymy.nazev_tymu,$this->sqlkategorie.nazev_k AS nazev_kategorie "
			. "FROM osoby,$this->sqlzavod,tymy,$this->sqlkategorie WHERE "
			. "$this->sqlzavod.ids = $dbdata2->ids AND "
			. "$this->sqlzavod.ido = osoby.ido AND "
			. "$this->sqlzavod.id_tymu = tymy.id_tymu AND "
			. "$this->sqlzavod.id_kategorie = $this->sqlkategorie.id_kategorie";
			//echo $sql3."\n";
			
			$sth3 = $this->db->prepare($sql3);
			$sth3->execute(Array(':ids' => $dbdata2->ids));
			if($sth3->rowCount()){
			    $dbdata3 = $sth3->fetchObject();
			    $str .= '<tr id="'.$dbdata2->cip.'">';
			    $str .= '<td class="text-center">'.$poradi.'</td>';
			    $str .= '<td class="text-center">'.$dbdata2->ids.'</td>';
			    $str .= '<td><a onclick="detail_cipu_cc_bez_etap('.$dbdata2->cip.','.$this->race_id.','.$this->race_year.')" href="'.$hash_url.'vysledky">'.$dbdata3->jmeno.'</a></td>';
			    $str .= '<td class="text-center">'.$dbdata3->rocnik.'</td>';
			    $str .= '<td class="text-center">'.substr($dbdata2->best_lap_time,3).'</td>';
                            $str .= ($dynamic_distances == '00:00:00.000' ) ? ('<td class="text-center"></td>') : ('<td class="text-center">'.substr($dynamic_distances,3).'</td>');
			    $str .= '</tr>';
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
		$str .= '<h4>'.$this->race_name.', kategorie '.$dbdata1->nazev_kategorie.'</h4>';
		$str .= '<table class="table table-bordered table-hover table-stripe table_vysledky" id="table2excel">';
		$str .= '<thead><tr class="header"><th class="text-center">#</th><th class="text-center">St.č</th><th class="text-left">Jméno</th><th class="text-center">Ročník</th><th class="text-center">Nejrychlejší kolo</th><th class="text-center">Odstup</th></tr></thead><tbody>';

		$sql2 = "SELECT "
		. "$this->sqlvysledky.ids,"
		. "$this->sqlvysledky.cip,"
		. "COUNT($this->sqlvysledky.id) AS pocet_kol,"
		. "MIN($this->sqlvysledky.lap_time) AS best_lap_time,"
		. "MIN($this->sqlvysledky.lap_time_sec) AS best_lap_time_sec "
                ."FROM $this->sqlvysledky,$this->sqlzavod,$this->sqlkategorie WHERE "
		. "race_time > 0 AND "
		. "time_order > 1 AND "
		. "$this->sqlzavod.cip = $this->sqlvysledky.cip AND "
		. "$this->sqlzavod.priznak IS NULL AND "
		. "$this->sqlkategorie.id_kategorie =  $category_id AND "
		. "$this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie AND "
		. "$this->sqlkategorie.poradi_podzavodu = $this->event_order AND "
		. "$this->sqlvysledky.false_time IS NULL AND "
		. "$this->sqlvysledky.lap_only IS NULL "
		. "GROUP BY $this->sqlvysledky.cip "
		. "ORDER BY best_lap_time_sec ASC";
		//echo $sql2;
		$sth2 =  $this->db->prepare($sql2);
		$sth2->execute(Array(':category_id' => $category_id,':event_order' => $this->event_order));
		if($sth2->rowCount()){ 
		    $poradi = 1;
		    while($dbdata2 = $sth2->fetchObject()){ //2.cyklus
                        if($poradi == 1){
                            $best_lap_time_sec = $dbdata2->best_lap_time_sec;
                        }
                        $dynamic_distances = $this->DynamicDistancesTisiciny($poradi,$dbdata2->best_lap_time_sec,$best_lap_time_sec);
			    
			
			$sql3 = "SELECT CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.psc AS stat,osoby.rocnik,tymy.nazev_tymu,$this->sqlkategorie.nazev_k AS nazev_kategorie "
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
			    $str .= '<td><a onclick="detail_cipu_cc_bez_etap('.$dbdata2->cip.','.$this->race_id.','.$this->race_year.')" href="'.$hash_url.'vysledky">'.$dbdata3->jmeno.'</a></td>';
			    $str .= '<td class="text-center">'.$dbdata3->rocnik.'</td>';
			    $str .= '<td class="text-center">'.substr($dbdata2->best_lap_time,3).'</td>';
                            $str .= ($dynamic_distances == '00:00:00.000' ) ? ('<td class="text-center"></td>') : ('<td class="text-center">'.substr($dynamic_distances,3).'</td>');
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

		
                
                $sql = "SELECT MAX($this->sqlvysledky.time_order) AS max_time_order FROM $this->sqlzavod,$this->sqlvysledky,$this->sqlkategorie WHERE $this->sqlzavod.id_kategorie = $category_id AND $this->sqlzavod.id_kategorie = $this->sqlkategorie.id_kategorie AND $this->sqlzavod.ids = $this->sqlvysledky.ids";
                $sth = $this->db->prepare($sql);
                $sth->execute();
                $dbdata = $sth->fetchObject();
                
                
                
                $sql1 = "SELECT "
			    . "$this->sqlvysledky.ids,"
			    . "$this->sqlvysledky.cip,"
			    . "COUNT($this->sqlvysledky.id) AS pocet_kol,"
			    . "MAX($this->sqlvysledky.race_time) AS finish_time,"
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
			    . "$this->sqlvysledky.lap_only IS NULL "
			    . "GROUP BY $this->sqlvysledky.cip "
			    . "ORDER BY best_lap_time ASC";

	        //$str .= $sql1;

		$sth1 =  $this->db->prepare($sql1);
		$sth1->execute(Array(':event_order' => $this->event_order,':category_id' => $category_id));
		if($sth1->rowCount()){
                    $str .= "<br><br><h4>Rozpis kol, nejrychlejší je označeno červeně</h4>";
		    $str .= '<table class="table table-bordered table-hover table-striped table_vysledky">';
		    $i = 1;
		    while($dbdata1 = $sth1->fetchObject()){
			if($i == 1){
			    $max_pocet_kol = $dbdata->max_time_order - 1;
			    $str .= '<thead><tr class="header"><th class="text-center">#</th><th class="text-left">Jméno</th>';
			    for($k=1;$k<=$max_pocet_kol;$k++){
				$str .= '<th class="text-center">'.($k).'.kolo</th>';
			    }
			    $str .= '</tr></thead><tbody>';
			}
			$sql2 = "SELECT $this->sqlvysledky.lap_time FROM $this->sqlvysledky WHERE cip = :cip AND false_time IS NULL AND lap_only IS NULL AND time_order > 1 ORDER by time_order ASC";
			$sth2 = $this->db->prepare($sql2);
			$sth2->execute(Array(':cip' =>  $dbdata1->cip));
			if($sth2->rowCount()){
			    $str .= '<tr>';
			    $str .= '<td class="text-center">'.$dbdata1->ids.'</td><td>'.$dbdata1->jmeno.'</td>';
			    while($dbdata2 = $sth2->fetchObject()){
				if($dbdata1->best_lap_time == $dbdata2->lap_time){
				    $str .= '<td class="text-center"><b><span style="color:red">'.substr($dbdata2->lap_time,3).'</span></b></td>';

				}
				else{
				    $str .= '<td class="text-center">'.substr($dbdata2->lap_time,3).'</td>';
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



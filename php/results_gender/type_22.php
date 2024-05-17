<?php
	    $str = '';
	    $event_order = isset($_GET['event_order']) ? $_GET['event_order'] : 1; 
	    $time_order = isset($_GET['time_order']) ? $_GET['time_order'] : $this->MaxTimeOrder(); 
	    $time_order = isset($_GET['time_order']) ? $_GET['time_order'] : 1; 
	    $gender = isset($_GET['gender']) ? $_GET['gender'] : 'all'; 
	    $hash_url = '#'.$this->race_year.'/'.$this->race_id.'/';
	    //$colspan = 4 + $this->time_count;
	    $colspan = 4;
	    if(isset($_GET['time_order'])) $time_order = $_GET['time_order'];
	    if(isset($_GET['id_event'])) $id_event = $_GET['id_event'];
	    $gender_array = Array('M' => 'Muži','Z' => 'Ženy');
	    if($gender == 'all'){
		$str = '<h4 class="headline-results">'.$this->race_name.', výsledky Muži/Ženy</h4>';
		$str .= '<table class="table table-hover noborder">';
		$k = 1; 
		foreach($gender_array as $key => $gender){

		    $sql1 = "SELECT CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,osoby.psc AS stat,osoby.pohlavi,tymy.nazev_tymu,$this->sqlvysledky.ids,$this->sqlvysledky.ids_alias,$this->sqlvysledky.cip,MAX($this->sqlvysledky.race_time) AS finish_time,COUNT($this->sqlvysledky.id) AS pocet_kol,$this->sqlkategorie.nazev_k AS nazev_kategorie,$this->sqlkategorie.kod_k AS kod_kategorie FROM $this->sqlvysledky,osoby,tymy,$this->sqlzavod,$this->sqlkategorie WHERE "
			      . "$this->sqlzavod.poradi_podzavodu = :event_order AND "
			      . "race_time > 0 AND "
			      . "$this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie AND "
			      . "$this->sqlzavod.cip = $this->sqlvysledky.cip AND "
			      . "osoby.pohlavi = :gender AND "
			      
			      . "$this->sqlzavod.tym = tymy.id_tymu AND "
			      . "$this->sqlzavod.ido = osoby.ido AND "
			      . "$this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie AND "
			      . "$this->sqlvysledky.false_time IS NULL AND "
			      . "$this->sqlvysledky.lap_only IS NULL "
			      . "GROUP BY $this->sqlvysledky.ids ORDER BY pocet_kol DESC,finish_time ASC".$this->rows_limit;

		  ///  echo $sql1."\n";
		    
		    $sth1 =  $this->db->prepare($sql1);
		   // $sth1->execute(Array(':time_order' => $time_order,':gender' => $key,':id_event' => $event_order));
		    $sth1->execute(Array(':gender' => $key,':event_order' => $event_order));
		    if($sth1->rowCount()){
			$class = $k == 1 ? $class = 'nadpis nopadding' : 'nadpis';
			$str .= '<tr><td class="'.$class.'" colspan="3">'.$gender.'</td></tr>';
			$str .= '<tr class="header"><th class="text-center">#</th><th class="text-center">St.č</th><th>Jméno</th><th class="text-center">Ročník</th><th>Tým/Bydliště</th><th class="text-center">Stát</th><th class="text-center">Kola</th><th class="text-center">Čas</th><th class="text-center">Odstup</th>';
			$poradi = 1;
			while($dbdata1 = $sth1->fetchObject()){ //začneme vypisovat první dotaz
			    if($poradi == 1) $max_pocet_kol = $dbdata1->pocet_kol;// nejvyšší počet kol pro počítání odstupů
			    //tady musí být i pořadí podzávodu
			    $sql2 = "SELECT $this->sqlvysledky.distance_gender FROM $this->sqlvysledky WHERE $this->sqlvysledky.ids = :ids AND time_order = :max_lap_count"; //vezmeme odstupy, v tomto případě ve zvláštním dotaze
			    $sth2 = $this->db->prepare($sql2);
			    $sth2->execute(Array(':ids' => $dbdata1->ids,':max_lap_count' => $max_pocet_kol));
			    if($sth2->rowCount()){ //pokud je někdo se stejným, tzn. nejvyšším počttem kol, použijeme časový odstup
				$dbdata2 = $sth2->fetchObject();
				($dbdata2->distance_gender != '00:00:00.00') ? ($distance_gender = $dbdata2->distance_gender) : ($distance_gender = '-'); //pokud nemá odstup, tak je první a bude tam pomlčka
			    }
			    else{ // pokud ne, spočítáme odstup v kolech
				 $distance_gender = $dbdata1->pocet_kol - $max_pocet_kol; 
				if($distance_gender == -1){
				    $kola = 'kolo';
				}
				elseif(($distance_gender < -1 AND $distance_gender > -5) OR $distance_gender > -1){
				    $kola = 'kola';
				}
				else{
				    $kola = 'kol';
				}
				$distance_gender = $distance_gender.' '.$kola;
			    }

				$str .= '<tr id="'.$dbdata1->cip.'">';
				$str .= '<td class="text-center">'.$poradi.'</td>';
				$str .= '<td class="text-center">'.$dbdata1->ids_alias.'</td>';
				$str .= '<td><a onclick="detail_cipu_lahofer('.$dbdata1->cip.','.$this->race_id.','.$this->race_year.')" href="'.$hash_url.'vysledky">'.$dbdata1->jmeno.'</a></td>';
				$str .= '<td class="text-center">'.$dbdata1->rocnik.'</td>';
				$str .= '<td>'.$dbdata1->nazev_tymu.'</td>';
				$str .= '<td class="text-center">'.$dbdata1->stat.'</td>';
				$str .= '<td class="text-center">'.$dbdata1->pocet_kol.'</td>';
				$str .= '<td class="text-center">'.$dbdata1->finish_time.'</td>';
				$str .= '<td class="text-center">'.$distance_gender.'</td>';
				$str .= '</tr>';
				$poradi++;
			}
		    }
		    $str .= $this->DNFGender(1,$key);
		    $str .= '</tr>';
		    $k++;
		}
		$str .= '</table>';
	    }
	    else{
		    
		
		    $sql1 = "SELECT CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,osoby.psc AS stat,osoby.pohlavi,tymy.nazev_tymu,$this->sqlvysledky.ids,$this->sqlvysledky.ids_alias,$this->sqlvysledky.cip,MAX($this->sqlvysledky.race_time) AS finish_time,COUNT($this->sqlvysledky.id) AS pocet_kol,$this->sqlkategorie.nazev_k AS nazev_kategorie,$this->sqlkategorie.kod_k AS kod_kategorie FROM $this->sqlvysledky,osoby,tymy,$this->sqlzavod,$this->sqlkategorie WHERE "
			      . "$this->sqlzavod.poradi_podzavodu = :event_order AND "
			      . "race_time > 0 AND "
			      . "$this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie AND "
			      . "$this->sqlzavod.cip = $this->sqlvysledky.cip AND "
			      . "osoby.pohlavi = :gender AND "
			      
			      . "$this->sqlzavod.tym = tymy.id_tymu AND "
			      . "$this->sqlzavod.ido = osoby.ido AND "
			      . "$this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie AND "
			      . "$this->sqlvysledky.false_time IS NULL AND "
			      . "$this->sqlvysledky.lap_only IS NULL "
			      . "GROUP BY $this->sqlvysledky.ids ORDER BY pocet_kol DESC,finish_time ASC".$this->rows_limit;

		
		
		
		$sth1 =  $this->db->prepare($sql1);
		    $sth1->execute(Array(':gender' => $gender,':event_order' => $event_order));
		    if($sth1->rowCount()){
			$str = '<h4 class="headline-results">'.$this->race_name.', kategorie '.$gender_array[$gender].'</h4>';
			$str .= '<table class="table table-hover">';
			$str .= '<thead><tr class="header"><th class="text-center">#</th><th class="text-center">St.č</th><th>Jméno</th><th class="text-center">Ročník</th><th>Tým/Bydliště</th><th class="text-center">Stát</th><th class="text-center">Kat</th><th class="text-center">Čas</th><th class="text-center">Odstup</th>';
			$poradi = 1;
			while($dbdata1 = $sth1->fetchObject()){ //začneme vypisovat první dotaz
			    if($poradi == 1) $max_pocet_kol = $dbdata1->pocet_kol;// nejvyšší počet kol pro počítání odstupů
			    //tady musí být i pořadí podzávodu
			    $sql2 = "SELECT $this->sqlvysledky.distance_gender FROM $this->sqlvysledky WHERE $this->sqlvysledky.ids = :ids AND time_order = :max_lap_count"; //vezmeme odstupy, v tomto případě ve zvláštním dotaze
			    $sth2 = $this->db->prepare($sql2);
			    $sth2->execute(Array(':ids' => $dbdata1->ids,':max_lap_count' => $max_pocet_kol));
			    if($sth2->rowCount()){ //pokud je někdo se stejným, tzn. nejvyšším počttem kol, použijeme časový odstup
				$dbdata2 = $sth2->fetchObject();
				($dbdata2->distance_gender != '00:00:00.00') ? ($distance_gender = $dbdata2->distance_gender) : ($distance_gender = '-'); //pokud nemá odstup, tak je první a bude tam pomlčka
			    }
			    else{ // pokud ne, spočítáme odstup v kolech
				 $distance_gender = $dbdata1->pocet_kol - $max_pocet_kol; 
				if($distance_gender == -1){
				    $kola = 'kolo';
				}
				elseif(($distance_gender < -1 AND $distance_gender > -5) OR $distance_gender > -1){
				    $kola = 'kola';
				}
				else{
				    $kola = 'kol';
				}
				$distance_gender = $distance_gender.' '.$kola;
			    }

				$str .= '<tr id="'.$dbdata1->cip.'">';
				$str .= '<td class="text-center">'.$poradi.'</td>';
				$str .= '<td class="text-center">'.$dbdata1->ids_alias.'</td>';
				$str .= '<td><a onclick="detail_cipu_lahofer('.$dbdata1->cip.','.$this->race_id.','.$this->race_year.')" href="'.$hash_url.'vysledky">'.$dbdata1->jmeno.'</a></td>';
				$str .= '<td class="text-center">'.$dbdata1->rocnik.'</td>';
				$str .= '<td>'.$dbdata1->nazev_tymu.'</td>';
				$str .= '<td class="text-center">'.$dbdata1->stat.'</td>';
				$str .= '<td class="text-center">'.$dbdata1->pocet_kol.'</td>';
				$str .= '<td class="text-center">'.$dbdata1->finish_time.'</td>';
				$str .= '<td class="text-center">'.$distance_gender.'</td>';
				$str .= '</tr>';
				$poradi++;
			}
			$str .= $this->DNFGender(1,$gender);
			$str .= '</tr>';
		    }
		    else{
			$str .= '<p>Žádný výsledek</p>';
		    }
		$str .= '</tbody></table>';
	    }
	    $change_control_file_info = $this->ChangeControlFileInfo();
	    $fcdata['last_modified'] = $change_control_file_info['last_modified'];
	    $fcdata['change_control_file'] = $change_control_file_info['change_control_file'];
	    $fcdata['results'] = $str;
	    echo json_encode($fcdata);

	    

?>
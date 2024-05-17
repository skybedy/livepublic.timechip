<?php
    /*
     * 
     *  varianta štafety, Zátopek
     */
    $hash_url = '#'.$this->race_year.'/'.$this->race_id.'/';
    $colspan = 4;
    if($category_id == 'all'){
	$str .= '<h4 class="headline-results">'.$this->race_name.', výsledky podle kategorí</h4>';
	$sql = "SELECT id_kategorie,kod_k AS kod_kategorie,nazev_k AS nazev_kategorie,poradi_podzavodu FROM $this->sqlkategorie WHERE id_zavodu = :race_id ORDER BY id_kategorie";
	$sth = $this->db->prepare($sql);
	$sth->execute(Array(':race_id' => $this->race_id));
	if($sth->rowCount()){
	    $str .= '<table class="table table-bordered table-hover noborder table_vysledky">';
	    $k = 1;
	    while($data = $sth->fetchObject()){
		$sql1 = "SELECT tymy.nazev_tymu,$this->sqlvysledky.ids,$this->sqlvysledky.ids_alias,MAX($this->sqlvysledky.race_time) AS finish_time,MAX($this->sqlvysledky.race_time_sec) AS finish_time_sec,$this->sqlkategorie.nazev_k AS nazev_kategorie FROM $this->sqlvysledky,$this->sqlzavod,$this->sqlkategorie,tymy "
			  . "WHERE $this->sqlvysledky.race_time > '0' "
			  . "AND $this->sqlzavod.id_kategorie = :id_kategorie  "
			  . "AND $this->sqlzavod.id_kategorie = $this->sqlkategorie.id_kategorie "
			  . "AND $this->sqlkategorie.poradi_podzavodu = :event_order "
			  . "AND $this->sqlzavod.cip = $this->sqlvysledky.cip "
			  . "AND $this->sqlzavod.tym = tymy.id_tymu "
			  . "AND $this->sqlvysledky.false_time IS NULL "
			  . "AND $this->sqlvysledky.lap_only IS NULL "
			  . "GROUP BY $this->sqlzavod.ids "
			  . "ORDER BY finish_time_sec ASC";
		$sth1 =  $this->db->prepare($sql1);
		$sth1->execute(Array(':id_kategorie' => $data->id_kategorie,':event_order' => $this->event_order));
		if($sth1->rowCount()){
		    $class = $k == 1 ? $class = 'nadpis nopadding' : 'nadpis';
		    $str .= '<tr><td class="'.$class.'" colspan="3">'.$data->nazev_kategorie.'</td></tr>';
		    $str .= '<tr class="header"><th class="text-center">#</th><th class="text-center">St.č</th><th>Štafeta</th><th class="text-center">Celkový čas</th><th class="text-center">Odstup</th></tr>';
		    $poradi = 1;
		    while($data1 = $sth1->fetchObject()){
			$sql3 = "SELECT CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,$this->sqlzavod.cip,$this->sqlvysledky.lap_time,$this->sqlvysledky.rank_category_lap FROM osoby,$this->sqlzavod,$this->sqlvysledky WHERE $this->sqlzavod.ids = :ids AND $this->sqlzavod.ido = osoby.ido AND $this->sqlvysledky.cip = $this->sqlzavod.cip AND $this->sqlvysledky.false_time IS NULL AND $this->sqlvysledky.lap_only IS NULL ORDER BY $this->sqlvysledky.time_order ASC";
			$sth3 = $this->db->prepare($sql3);
			$sth3->execute(Array(':ids' => $data1->ids));
			$pocet_clenu = $sth3->rowCount(); //počet členů týmu, který potřebujeme na rowspan

			$z = 1;
			while($dbdata3 = $sth3->fetchObject()){
			    
                            $sql2 = "SELECT $this->sqlvysledky.distance_category FROM $this->sqlvysledky WHERE $this->sqlvysledky.ids = :ids AND time_order = :time_count"; //vezmeme odstupy, v tomto případě ve zvláštním dotaze
                            
                            $sth2 = $this->db->prepare($sql2);
			    $sth2->execute(Array(':ids' => $data1->ids,':time_count' => $this->time_count));
                            $dbdata2 = $sth2->fetchObject();
			    ($dbdata2->distance_category != '00:00:00.00') ? ($distance_category = $dbdata2->distance_category) : ($distance_category = '-'); //pokud nemá odstup, tak je první a bude tam pomlčka

			    if($z == 1){
				$str .= '<tr id="'.$data1->ids_alias.'">';
				$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$poradi.'</td>';
				$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.($data1->ids-1000).'</td>';
				$str .= '<td rowspan="'.$pocet_clenu.'">'.$data1->nazev_tymu.'</td>';
			//	$str .= '<td>'.$dbdata3->jmeno.'</td>';
				//$str .= '<td class="text-center">'.$dbdata3->lap_time.'</td>';
				//$str .= '<td class="text-center">'.$dbdata3->rank_category_lap.'</td>';
				//$str .= '<td class="text-center">'.$dbdata3->cip.'</td>';
				$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$data1->finish_time.'</td>';
				$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$distance_category.'</td>';
				$str .= '</tr>';
			    }
			    else{
				$str .= '<tr>';
				$str .= '<td>'.$dbdata3->jmeno.'</td>';
				$str .= '<td class="text-center">'.$dbdata3->lap_time.'</td>';
				$str .= '<td class="text-center">'.$dbdata3->rank_category_lap.'</td>';
				$str .= '<td class="text-center">'.$dbdata3->cip.'</td>';
				$str .= '</tr>';
			    }
			    $z++;
			}
			$poradi++;
		    }
		}
		$k++;
	    }
	    $str .= '</table>';
	}
    }
    
   else{ // každá kategorie zvlášť
	$sql = "SELECT nazev_k AS nazev_kategorie FROM $this->sqlkategorie WHERE id_zavodu = :race_id AND id_kategorie = :category_id";
	$sth = $this->db->prepare($sql);
	$sth->execute(Array(':race_id' => $this->race_id,':category_id' => $category_id));
	if($sth->rowCount()){
	    $dbdata = $sth->fetchObject();
	    $str .= '<h4 class="headline-results">'.$this->race_name.', kategorie '.$dbdata->nazev_kategorie.'</h4>';
	    $str .= '<table class="table table-bordered table-hover table_vysledky">';
	    $k = 1;
	    $sql1 = "SELECT tymy.nazev_tymu,$this->sqlvysledky.ids,$this->sqlvysledky.ids_alias,MAX($this->sqlvysledky.race_time) AS finish_time,MAX($this->sqlvysledky.race_time_sec) AS finish_time_sec,$this->sqlkategorie.nazev_k AS nazev_kategorie FROM $this->sqlvysledky,$this->sqlzavod,$this->sqlkategorie,tymy "
		    . "WHERE race_time > '0' "
		    . "AND $this->sqlzavod.id_kategorie = :category_id  "
		    . "AND $this->sqlzavod.id_kategorie = $this->sqlkategorie.id_kategorie "
		    . "AND $this->sqlkategorie.poradi_podzavodu = :event_order "
		    . "AND $this->sqlzavod.cip = $this->sqlvysledky.cip "
		    . "AND $this->sqlzavod.tym = tymy.id_tymu "
		    . "AND $this->sqlvysledky.false_time IS NULL "
		    . "AND $this->sqlvysledky.lap_only IS NULL "
		    . "GROUP BY $this->sqlzavod.ids "
		    . "ORDER BY finish_time_sec ASC";
	    $sth1 =  $this->db->prepare($sql1);
	    $sth1->execute(Array(':category_id' => $category_id,':event_order' => $this->event_order));
	    if($sth1->rowCount()){
		$str .= '<tr class="header"><th class="text-center">#</th><th class="text-center">Stč</th><th>Štafeta</th><th class="text-center">Celkový čas</th><th class="text-center">Odstup</th></tr>';
		
                //$str .= $this->TableHeader($this->time_order,$this->event_order,$this->cislo_kategorie).'</tr>';
		$poradi = 1;
		while($data1 = $sth1->fetchObject()){
		    $sql3 = "SELECT CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,$this->sqlzavod.cip,$this->sqlvysledky.lap_time,$this->sqlvysledky.rank_category_lap FROM osoby,$this->sqlzavod,$this->sqlvysledky WHERE $this->sqlzavod.ids = :ids AND $this->sqlzavod.ido = osoby.ido AND $this->sqlvysledky.cip = $this->sqlzavod.cip AND $this->sqlvysledky.false_time IS NULL AND $this->sqlvysledky.lap_only IS NULL ORDER BY $this->sqlvysledky.time_order ASC";
		    $sth3 = $this->db->prepare($sql3);
		    $sth3->execute(Array(':ids' => $data1->ids));
		    $pocet_clenu = $sth3->rowCount(); //počet členů týmu, který potřebujeme na rowspan
		    $z = 1;
		    while($dbdata3 = $sth3->fetchObject()){
                        $sql2 = "SELECT $this->sqlvysledky.distance_category FROM $this->sqlvysledky WHERE $this->sqlvysledky.ids = :ids AND time_order = :time_count"; //vezmeme odstupy, v tomto případě ve zvláštním dotaze
                        //echo $sql2."\n";
                        $sth2 = $this->db->prepare($sql2);
                        $sth2->execute(Array(":ids" => $data1->ids,":time_count" =>$this->time_count));
                        $dbdata2 = $sth2->fetchObject();
                        ($dbdata2->distance_category != '00:00:00.00') ? ($distance_category = $dbdata2->distance_category) : ($distance_category = '-'); //pokud nemá odstup, tak je první a bude tam pomlčka
                    

			    if($z == 1){
				$str .= '<tr>';
				$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$poradi.'</td>';
				$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.($data1->ids - 1000).'</td>';
				$str .= '<td rowspan="'.$pocet_clenu.'">'.$data1->nazev_tymu.'</td>';
				//$str .= '<td>'.$dbdata3->jmeno.'</td>';
				//$str .= '<td class="text-center">'.$dbdata3->lap_time.'</td>';
				//$str .= '<td class="text-center">'.$dbdata3->rank_category_lap.'</td>';
				//$str .= '<td class="text-center">'.$dbdata3->cip.'</td>';
				$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$data1->finish_time.'</td>';
				$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$distance_category.'</td>';
				$str .= '</tr>';
			    }
			    else{
				$str .= '<tr>';
				$str .= '<td>'.$dbdata3->jmeno.'</td>';
				$str .= '<td class="text-center">'.$dbdata3->lap_time.'</td>';
				$str .= '<td class="text-center">'.$dbdata3->rank_category_lap.'</td>';
				$str .= '<td class="text-center">'.$dbdata3->cip.'</td>';
				$str .= '</tr>';
			    }
			    $z++;
		    }
		    $poradi++;
		}
	    }
	    else{
		$str .= '<p>Žádný výsledek</p>';
	    }

		$k++;
	    $str .= '</tbody></table>';
	}
    }
?>
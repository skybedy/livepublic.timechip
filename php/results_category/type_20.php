<?php
    $pocet_lidi_na_stranku = 10000;


    if($this->laps_only){
    }
    else{
	$colspan = 4;
	if($category_id == 'all'){
	    
	}
	else{ // každá kategorie zvlášť
	    $sql = "SELECT nazev_k AS nazev_kategorie FROM $this->sqlkategorie WHERE id_zavodu = :race_id AND id_kategorie = :category_id";
	    $sth = $this->db->prepare($sql);
	    $sth->execute(Array(':race_id' => $this->race_id,':category_id' => $category_id));
	    if($sth->rowCount()){
                
                $sql0 = "SELECT COUNT($this->sqlvysledky.id) AS pocet FROM $this->sqlvysledky,$this->sqlzavod WHERE race_time > 0 AND $this->sqlvysledky.time_order = :time_order AND $this->sqlvysledky.false_time IS NULL AND $this->sqlvysledky.lap_only IS NULL AND $this->sqlvysledky.ids = $this->sqlzavod.ids AND $this->sqlzavod.poradi_podzavodu = :event_order AND $this->sqlvysledky.ids = $this->sqlzavod.ids AND $this->sqlzavod.id_kategorie = :category_id";
                $sth0 = $this->db->prepare($sql0);
                $sth0->execute(Array(":time_order" => $this->time_order,":event_order" => $this->event_order, ":category_id" => $category_id));
		$dbdata0 = $sth0->fetchObject();
                $pagination_pocet = ceil($dbdata0->pocet / $pocet_lidi_na_stranku);
                $new_limit_rows = "";
		if($dbdata0->pocet > $pocet_lidi_na_stranku){
                    if(isset($_GET['limit_od'])){
                        $new_limit_rows = " LIMIT {$_GET['limit_od']},$pocet_lidi_na_stranku";
                    }
                    else{
                        $new_limit_rows = " LIMIT 0,$pocet_lidi_na_stranku";
                    }
		}

                
                
                
                
                
                
                
                $sql1 = "SELECT "
                            . "$this->sqlvysledky.ids,"
                            . "SEC_TO_TIME(MAX($this->sqlvysledky.race_time_sec)) AS finish_time,"
                            . "COUNT($this->sqlvysledky.id) AS laps_count "
                        . "FROM $this->sqlvysledky,$this->sqlzavod "
                        . "WHERE "
                            . "$this->sqlvysledky.false_time IS NULL AND "
                            . "$this->sqlvysledky.ids = $this->sqlzavod.ids AND "
                            . "$this->sqlzavod.id_kategorie = $category_id "
                        . "GROUP BY $this->sqlvysledky.ids "
                        . "ORDER BY laps_count DESC,finish_time ASC".$new_limit_rows;

                //$str .= $sql1;
                
                
                
                
                
                $sth1 = $this->db->prepare($sql1);
		$sth1->execute(Array(":time_order" => $this->time_order,":event_order" => $this->event_order, ":category_id" => $category_id));
		$dbdata1 = $sth1->fetchObject();
                
                
                
                
                
                
                
                
                
                $pagination_pocet = ceil($dbdata0->pocet / 100);
                $new_limit_rows = "";
		if($dbdata0->pocet > 100){
                    if(isset($_GET['limit_od'])){
                        $new_limit_rows = " LIMIT {$_GET['limit_od']},100";
                    }
                    else{
                        $new_limit_rows = " LIMIT 0,100";
                    }
		}
		
		$dbdata = $sth->fetchObject();
		
                
                
                
                
                
                
                
                
                $str .= '<h4 class="headline-results">'.$this->race_name.$this->event_name.', kategorie '.$dbdata->nazev_kategorie.'</h4>';
                
                
                
                
                
                if($dbdata0->pocet > 100){
                    //$str .= $this->Strankovani($pagination_pocet,$dbdata0->pocet);
                }

		
                
                $str .= '<table class="table table-striped table-bordered table-hover noborder table_vysledky">';
		$k = 1;
		$sql1 = "SELECT $this->sqlvysledky.ids,$this->sqlvysledky.ids_alias,$this->sqlvysledky.cip,MAX($this->sqlvysledky.race_time) AS cilovy_cas,$this->sqlvysledky.race_time_sec,CONCAT_WS(' ',osoby_teribear.prijmeni,osoby_teribear.jmeno) AS jmeno,osoby_teribear.rocnik,osoby_teribear.psc AS stat,$this->sqlkategorie.nazev_k AS nazev_kategorie,tymy.nazev_tymu FROM $this->sqlvysledky,osoby_teribear,$this->sqlzavod,$this->sqlkategorie,tymy "
			      . "WHERE race_time > '0' "
			      . "AND $this->sqlvysledky.time_order = :time_order " 
			      . "AND $this->sqlkategorie.id_kategorie = :category_id "
			      . "AND $this->sqlzavod.id_kategorie$this->cislo_kategorie = $this->sqlkategorie.id_kategorie "
			      . "AND $this->sqlzavod.cip = $this->sqlvysledky.cip "
			      . "AND $this->sqlzavod.ido = osoby_teribear.ido "
			      . "AND tymy.id_tymu = $this->sqlzavod.prislusnost "
			      . "AND false_time IS NULL "
			      . "AND lap_only IS NULL "
			      . "GROUP BY ids "
			      . "ORDER BY cilovy_cas ASC".$new_limit_rows;
		    $sth1 =  $this->db->prepare($sql1);
		    $sth1->execute(Array(':time_order' => $this->time_order,':category_id' => $category_id));
		    if($sth1->rowCount()){
			$str .= '<thead><tr class="header"><th class="text-center">Poř</th><th class="text-center">St.č</th><th class="text-left">Jméno</th><th class="text-center">Ročník</th><th class="text-left">Tým/Bydliště</th><th class="text-center">Stát</th>';
			$str .= $this->TableHeader($this->time_order,$this->event_order,$this->cislo_kategorie).'</tr></thead><tbody>';
			$poradi = 1;
                        if(isset($_GET['limit_od'])){
                            $poradi = 1 + $_GET['limit_od'];
                        }
                        else{
                            $poradi = 1;
                        }

			while($data1 = $sth1->fetchObject()){
			    
			    $str .= '<tr><td class="text-center">'.$poradi.'</td><td class="text-center">'.$data1->ids_alias.'</td><td>'.$data1->jmeno.'</td><td class="text-center">'.$data1->rocnik.'</td><td>'.$this->NahrazkaPomlcky($data1->nazev_tymu).'</td><td class="text-center">'.$data1->stat.'</td>';

			    $str .= '</tr>';
			    $poradi++;
			}
		    }
		    else{
			$str .= '<p>Žádný výsledek</p>';
		    }

		$k++;
		$str .= $this->DNFCAtegory(1,$category_id);
		$str .= '</tr>';
		$str .= '</tbody></table>';
                if($dbdata0->pocet > 100){
                    //$str .= $this->Strankovani($pagination_pocet,$dbdata0->pocet);
                }

	    }
	}
    }
?>
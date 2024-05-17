<?php
        $pocet_lidi_na_stranku = 500;
        //$castka_na_kolo = 50;
        $castka_na_kolo = 20;
    

        $sql0 = "SELECT COUNT($this->sqlvysledky.id) AS pocet FROM $this->sqlvysledky,$this->sqlzavod WHERE race_time > 0 AND $this->sqlvysledky.time_order = :time_order AND $this->sqlvysledky.false_time IS NULL AND $this->sqlvysledky.lap_only IS NULL AND $this->sqlvysledky.ids = $this->sqlzavod.ids AND $this->sqlzavod.poradi_podzavodu = :event_order";
        $sth0 = $this->db->prepare($sql0);
        $sth0->execute(Array(":time_order" => $this->time_order,":event_order" => $this->event_order));
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



        $sql1 = "SELECT $this->sqlvysledky.ids,SEC_TO_TIME(MAX($this->sqlvysledky.race_time_sec)) AS finish_time,COUNT($this->sqlvysledky.id) AS laps_count FROM $this->sqlvysledky,$this->sqlzavod WHERE $this->sqlvysledky.false_time IS NULL AND $this->sqlvysledky.ids = $this->sqlzavod.ids GROUP BY $this->sqlvysledky.ids ORDER BY laps_count DESC,finish_time ASC".$new_limit_rows;
	//echo $sql1;
	$sth1 =  $this->db->prepare($sql1);
	$sth1->execute();
	if($sth1->rowCount()){
	    $str .= '<h4 class="headline-results">'.$this->race_name.$this->event_name.', výsledky bez rozdílu kategorií</h4>';
           
            /*
            if($dbdata0->pocet > $pocet_lidi_na_stranku){
                $str .= $this->Strankovani($pagination_pocet,$dbdata0->pocet,$pocet_lidi_na_stranku);
            }
            */
            
                    if($dbdata0->pocet > 500){
                        $str .= '<nav class="text-center">';
                        $str .= '<ul id="strankovani" class="pagination">';
                        for($i = 1;$i <= $pagination_pocet;$i++){
                            $pracovni_soucin = $i * 500;
                            $do = $pracovni_soucin;
                            if($i == $pagination_pocet){
                                if($pracovni_soucin > $dbdata0->pocet){
                                    $zbytek = $dbdata0->pocet % 100;
                                    $do = $pracovni_soucin - 500 + $zbytek;
                                }
                            }
                            $str .= '<li><a class="strankovani" href="#">'.($pracovni_soucin - 499).'-'.($do).'</a></li>';
                        }
                        $str .= '</ul>';
                        $str .= '</nav>';
                    }
            
            
            
            
            
            $str .= '<table class="table table-striped table-bordered table-hover noborder table_vysledky">';

            $str .= '<thead><tr class="header"><th class="text-center">Poř</th><th class="text-center">St.č</th><th class="text-left">Jméno</th><th class="text-center">Ročník</th><th class="text-center">Stát</th><th class="text-center">Kat</th><th class="text-center">Kol</th><th class="text-center">Vzdálenost</th><th class="text-center">Částka</th>';
	    $str .= '</tr></thead><tbody>';
            $poradi = 1;
            if(isset($_GET['limit_od'])){
                $poradi = 1 + $_GET['limit_od'];
            }
            else{
                $poradi = 1;
            }
	    while($dbdata1 = $sth1->fetchObject()){//cyklus, v kterém se vyberou všecky časy konkrétního závodníka
		$sql2 = "SELECT CONCAT_WS(' ',osoby_teribear.prijmeni,osoby_teribear.jmeno) AS jmeno,osoby_teribear.rocnik,osoby_teribear.psc AS stat,osoby_teribear.pohlavi,$this->sqlkategorie.nazev_k AS nazev_kategorie FROM $this->sqlzavod,osoby_teribear,$this->sqlkategorie WHERE $this->sqlzavod.ids = :ids AND $this->sqlzavod.ido = osoby.ido  AND $this->sqlzavod.id_kategorie = $this->sqlkategorie.id_kategorie";
		$sth2 = $this->db->prepare($sql2);
		$sth2->execute(Array(':ids' => $dbdata1->ids));
		if($sth2->rowCount()){
		    $dbdata2 = $sth2->fetchObject();
                    $vzdalenost = $dbdata1->laps_count * $this->delka_kola; 
                    $celkova_castka = $vzdalenost * $castka_na_kolo;
		    $str .= '<tr><td class="text-center"><b>'.$poradi.'</b></td><td class="text-center">'.$dbdata1->ids.'</td><td>'.$dbdata2->jmeno.'</td><td class="text-center">'.$dbdata2->rocnik.'</td><td class="text-center">'.$dbdata2->stat.'</td><td class="text-center">'.$dbdata2->nazev_kategorie.'</td><td class="text-center">'.$dbdata1->laps_count.'</td><td class="text-center">'.$vzdalenost.' Km</td><td class="text-center">'.$celkova_castka.' Kč</td>';
		    $str .= '</tr>';
		    $poradi++;

		}
	    }
	    $str .= '</tbody></table>';
            if($dbdata0->pocet > $pocet_lidi_na_stranku){
                if(isset($_GET['limit_od'])){
                    $new_limit_rows = " LIMIT {$_GET['limit_od']},$pocet_lidi_na_stranku";
                }
                else{
                    $new_limit_rows = " LIMIT 0,$pocet_lidi_na_stranku";
                }
            }

	}
?>
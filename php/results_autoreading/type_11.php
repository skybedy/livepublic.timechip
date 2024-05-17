<?php
	    /*  Valachaiarun Vsetín, kombinace týmů a jednotlivců
	     * narozdil od tymu na 24 hod, tady je použito ids a ne číslo čipu, to chce do budoucna nějak vymyslet, kdy čip a kdy ids
	     */
	   
	$prevodni_tabulka = Array(
				'ä'=>'a',
				'Ä'=>'A',
				'á'=>'a',
				'Á'=>'A',
				'à'=>'a',
				'À'=>'A',
				'ã'=>'a',
				'Ã'=>'A',
				'â'=>'a',
				'Â'=>'A',
				'č'=>'c',
				'Č'=>'C',
				'ć'=>'c',
				'Ć'=>'C',
				'ď'=>'d',
				'Ď'=>'D',
				'ě'=>'e',
				'Ě'=>'E',
				'é'=>'e',
				'É'=>'E',
				'ë'=>'e',
				'Ë'=>'E',
				'è'=>'e',
				'È'=>'E',
				'ê'=>'e',
				'Ê'=>'E',
				'í'=>'i',
				'Í'=>'I',
				'ï'=>'i',
				'Ï'=>'I',
				'ì'=>'i',
				'Ì'=>'I',
				'î'=>'i',
				'Î'=>'I',
				'ľ'=>'l',
				'Ľ'=>'L',
				'ĺ'=>'l',
				'Ĺ'=>'L',
				'ń'=>'n',
				'Ń'=>'N',
				'ň'=>'n',
				'Ň'=>'N',
				'ñ'=>'n',
				'Ñ'=>'N',
				'ó'=>'o',
				'Ó'=>'O',
				'ö'=>'o',
				'Ö'=>'O',
				'ô'=>'o',
				'Ô'=>'O',
				'ò'=>'o',
				'Ò'=>'O',
				'õ'=>'o',
				'Õ'=>'O',
				'ő'=>'o',
				'Ő'=>'O',
				'ř'=>'r',
				'Ř'=>'R',
				'ŕ'=>'r',
				'Ŕ'=>'R',
				'š'=>'s',
				'Š'=>'S',
				'ś'=>'s',
				'Ś'=>'S',
				'ť'=>'t',
				'Ť'=>'T',
				'ú'=>'u',
				'Ú'=>'U',
				'ů'=>'u',
				'Ů'=>'U',
				'ü'=>'u',
				'Ü'=>'U',
				'ù'=>'u',
				'Ù'=>'U',
				'ũ'=>'u',
				'Ũ'=>'U',
				'û'=>'u',
				'Û'=>'U',
				'ý'=>'y',
				'Ý'=>'Y',
				'ž'=>'z',
				'Ž'=>'Z',
				'ź'=>'z',
				'Ź'=>'Z'
	    );

	    $led_panel_str = '';
	    $autoreading_results = '<table class="table table-striped table-bordered table-hover stredni_radky">';
	    $autoreading_results .= '<thead><tr class="autoreading_header"><th class="text-center">St.č</th><th>Jméno</th><th>Název týmu</th><th>Kategorie</th><th class="text-center">Celkový čas</th></tr></thead>';
	    $autoreding_results = '<tbody>';
	    /* pokud je v db alespoň jeden záznam */
	    if($this->MaxTimeOrder()){
		$max_time_order = $this->MaxTimeOrder();
		//$sql3 = "SELECT $this->sqlvysledky.ids,$this->sqlvysledky.ids_alias,$this->sqlvysledky.cip,SEC_TO_TIME(SUM($this->sqlvysledky.lap_time_sec)) AS total_time,$this->sqlvysledky.lap_time,$this->sqlvysledky.race_time,$this->sqlvysledky.lap_count AS pocet_kol FROM $this->sqlvysledky,$this->sqlzavod WHERE $this->sqlvysledky.false_time IS NULL AND $this->sqlvysledky.reader = 'CIL' AND $this->sqlvysledky.cip = $this->sqlzavod.cip GROUP BY $this->sqlvysledky.lap_time ORDER BY $this->sqlvysledky.race_time DESC LIMIT 0,4";
		$sql3 = "SELECT $this->sqlvysledky.cip,$this->sqlvysledky.lap_time,$this->sqlvysledky.race_time,$this->sqlvysledky.lap_count AS pocet_kol FROM $this->sqlvysledky WHERE $this->sqlvysledky.false_time IS NULL ORDER BY $this->sqlvysledky.id DESC LIMIT 0,20";
		$sth3 = $this->db->prepare($sql3);
		$sth3->execute();
		if($sth3->rowCount()){
		    while($data3 = $sth3->fetchObject()){
			$sql4 = "SELECT $this->sqlzavod.ids_alias,$this->sqlzavod.ids,CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,tymy.nazev_tymu,$this->sqlkategorie.nazev_k AS nazev_kategorie FROM $this->sqlzavod,tymy,osoby,$this->sqlkategorie WHERE $this->sqlzavod.cip = :cip AND $this->sqlzavod.tym = tymy.id_tymu AND $this->sqlzavod.ido = osoby.ido AND $this->sqlzavod.id_kategorie = $this->sqlkategorie.id_kategorie LIMIT 0,1";
			//$sql4 = "SELECT $this->sqlzavod.ids_alias,$this->sqlzavod.ids,CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.prijmeni AS nazev_tymu FROM $this->sqlzavod,tymy,osoby WHERE $this->sqlzavod.cip = :cip AND $this->sqlzavod.tym = tymy.id_tymu AND $this->sqlzavod.ido = osoby.ido LIMIT 0,1";
			$sth4 = $this->db->prepare($sql4);
			$sth4->execute(Array(':cip' => $data3->cip));
			if($sth4->rowCount()){
			    
			    $data4 = $sth4->fetchObject();
			    $zavodnik = $data4->nazev_tymu; 
			    if($data4->ids < 2000){
				$zavodnik = $data4->jmeno;
			    }
			    
			    $autoreading_results .= '<tr><td class="text-center">'.$data4->ids.'</td><td>'.$data4->jmeno.'</td><td>'.$data4->nazev_tymu.'</td><td>'.$data4->nazev_kategorie.'</td><td class="text-center">'.$data3->race_time.'</td></tr>';
			    
			    //$nazev_tymu = strtr($data4->nazev_tymu,$prevodni_tabulka);
			    $nazev_tymu = strtr($zavodnik,$prevodni_tabulka);

			    $delka_nazvu_tymu = strlen($nazev_tymu);
			    if($delka_nazvu_tymu > 13){
				$nazev_tymu = substr($nazev_tymu,0,13);	
			    }
			
			    $delka_nazvu_tymu = strlen($nazev_tymu);
			    switch($delka_nazvu_tymu){
				case 13:
				    $mezera = ' ';
				break;
				case 12:
				    $mezera = '  ';
				break;
				case 11:
				    $mezera = '   ';
				break;
				case 10:
				    $mezera = '    ';
				break;
				case 9:
				    $mezera = '     ';
				break;
				case 8:
				    $mezera = '      ';
				break;
				case 7:
				    $mezera = '       ';
				break;
			
				case 6:
				    $mezera = '        ';
				break;
				case 5:
				    $mezera = '         ';
				break;
				case 4:
				    $mezera = '          ';
				break;
				case 3:
				    $mezera = '           ';
				break;
				case 2:
				    $mezera = '            ';
				break;
			    }
			    
			    $delka_ids = strlen($data4->ids_alias);
			    //$delka_ids = strlen($data3->cip);
			    
			    switch($delka_ids){
				case 1:
				    $ids_mezera = '     ';
				break;
				case 2:
				    $ids_mezera = '    ';
				break;
				case 3:
				    $ids_mezera = '   ';
				break;
				case 4:
				    $ids_mezera = '  ';
				break;
				default:
				    $ids_mezera = ' ';
			    }
			    $pocet_kol = $data3->pocet_kol;
			    $delka_pocet_kol = strlen($pocet_kol);
			    switch($delka_pocet_kol){
				case 1:
				    $pocet_kol_mezera = ' ';
				break;
				//case 2:
				    //$pocet_kol_mezera = ' ';
				//break;
				default:
				    $pocet_kol_mezera = '';
			    }
			    $led_panel_str .= $data4->ids.$ids_mezera.ucfirst($nazev_tymu).$mezera.$pocet_kol_mezera.$data3->race_time."\n";
			}
		    }
		}
	    $autoreading_results .= '</tbody></table>';
	    }
	    else{
		$autoreading_results .= '<p>Zatím není nahraný žádný čas</p>';
	    }

?>

	    

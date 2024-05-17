<?php
	    /*
	     * TODO
	     * když má jeden podzávod méně časů než druhý.... dodělat
	     */
	    
	    
	     /*
	      *  změněno z $this->sqlvysledky.ids_alias (ids) na $this->sqlzavod.ids_alias (ids) po Jesenické 70, protože asi nední důvod, aby se do výsledků dostávaly startovní čísla
	      * uvedené v tabulce 'výsledky', do které se může za určitých okolností dostat chybné číslo ids_alias, jak se právě stalo na Jesenické 70
	      * správně by se nejspíš mělo do výsledků dostávat číslo z tabulky "závod"
	      */
	    $sql1 = "SELECT $this->sqlzavod.ids,$this->sqlzavod.ids_alias,MAX($this->sqlvysledky.race_time) AS cilovy_cas,$this->sqlvysledky.race_time_sec,$this->sqlvysledky.rank_category,$this->sqlvysledky.rank_gender,CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,osoby.psc AS stat,osoby.pohlavi,$this->sqlkategorie.kod_k AS nazev_kategorie,tymy.nazev_tymu FROM $this->sqlvysledky,osoby,$this->sqlzavod,$this->sqlkategorie,tymy WHERE race_time > 0 AND $this->sqlvysledky.time_order = $this->time_order AND $this->sqlkategorie.id_zavodu = $this->race_id AND $this->sqlkategorie.poradi_podzavodu = $this->event_order AND $this->sqlzavod.ids = $this->sqlvysledky.ids AND $this->sqlzavod.ido = osoby.ido AND $this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie$this->cislo_kategorie AND tymy.id_tymu = $this->sqlzavod.prislusnost AND $this->sqlvysledky.false_time IS NULL AND $this->sqlvysledky.lap_only IS NULL GROUP BY $this->sqlvysledky.ids ORDER BY cilovy_cas ASC".$this->rows_limit;
	    //$sql1 = "SELECT $this->sqlvysledky.ids,$this->sqlvysledky.ids_alias,MAX($this->sqlvysledky.race_time) AS cilovy_cas,$this->sqlvysledky.race_time_sec,$this->sqlvysledky.rank_category,$this->sqlvysledky.rank_gender,CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,osoby.psc AS stat,osoby.pohlavi,$this->sqlkategorie.kod_k AS nazev_kategorie,tymy.nazev_tymu FROM $this->sqlvysledky,osoby,$this->sqlzavod,$this->sqlkategorie,tymy WHERE race_time > 0 AND $this->sqlvysledky.time_order = :time_order AND $this->sqlkategorie.id_zavodu = :race_id AND $this->sqlkategorie.poradi_podzavodu = :event_order AND $this->sqlzavod.ids = $this->sqlvysledky.ids AND $this->sqlzavod.ido = osoby.ido AND $this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie$this->cislo_kategorie AND tymy.id_tymu = $this->sqlzavod.prislusnost AND $this->sqlvysledky.false_time IS NULL AND $this->sqlvysledky.lap_only IS NULL GROUP BY $this->sqlvysledky.ids ORDER BY cilovy_cas ASC";
	    //$sql1 = "SELECT $this->sqlvysledky.ids,$this->sqlvysledky.ids_alias,MAX($this->sqlvysledky.race_time) AS cilovy_cas,CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,osoby.psc AS stat,$this->sqlkategorie.kod_k AS nazev_kategorie,tymy.nazev_tymu FROM $this->sqlvysledky,osoby,$this->sqlzavod,$this->sqlkategorie,tymy WHERE race_time > 0 AND $this->sqlvysledky.time_order = '$this->time_order' AND $this->sqlkategorie.id_zavodu = '$this->race_id' AND $this->sqlkategorie.poradi_podzavodu = '$this->event_order' AND $this->sqlzavod.ids = $this->sqlvysledky.ids AND $this->sqlzavod.ido = osoby.ido AND $this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie$this->cislo_kategorie AND tymy.id_tymu = $this->sqlzavod.prislusnost AND $this->sqlvysledky.false_time IS NULL AND $this->sqlvysledky.lap_only IS NULL GROUP BY $this->sqlvysledky.ids ORDER BY cilovy_cas ASC";
	    //$str .= $sql1."<br />";
	    $sth1 =  $this->db->prepare($sql1);
	    $sth1->execute(Array(':time_order' => $this->time_order,':race_id' => $this->race_id,':event_order' => $this->event_order));
	    if($sth1->rowCount()){
		$str .= '<h4 class="headline-results">'.$this->race_name.$this->event_name.', výsledky bez rozdílu kategorií</h4>';
		$str .= '<table class="table table-striped table-bordered table-hover">';
		//$str .= '<thead><tr class="header"><th class="text-center">Poř</th><th class="text-center">St.č</th><th class="text-left">Jméno</th><th class="text-center">Ročník</th><th class="text-left">Tým/Bydliště</th><th class="text-center">Stát</th><th class="text-center">Kat</th> <th class="text-center">Poř</th> <th class="text-center">Poh</th> <th class="text-center">Poř</th>';
		$str .= '<thead><tr class="header"><th class="text-center">Poř</th><th class="text-center">St.č</th><th class="text-left">Jméno</th><th class="text-center">Ročník</th><th class="text-left">Tým/Bydliště</th><th class="text-center">Stát</th><th class="text-center">Kat</th> <th class="text-center">Poř</th>';
		
		$str .= $this->TableHeaderExtend($this->time_order,$this->event_order,$this->cislo_kategorie);
		
		$str .= '</tr></thead><tbody>';
		$poradi = 1;
		while($data1 = $sth1->fetchObject()){//cyklus, v kterém se vyberou všecky časy konkrétního závodníka
			if($this->cislo_kategorie == '_2'){ //pouze pokud se jedná o paralelní kategorie
			    if($poradi == 1) $best_time = $data1->race_time_sec;
			    $distance_time = $this->DynamicDistances($poradi,$data1->race_time_sec,$best_time);
			}
			//$str .= '<tr><td class="text-center"><b>'.$poradi.'</b></td><td class="text-center">'.$data1->ids_alias.'</td><td>'.$data1->jmeno.'</td><td class="text-center">'.$data1->rocnik.'</td><td>'.$data1->nazev_tymu.'</td><td class="text-center">'.$data1->stat.'</td><td class="text-center">'.$data1->nazev_kategorie.'</td><td class="text-center"><b>'.$data1->rank_category.'</b></td><td class="text-center">'.$data1->pohlavi.'</td> <td class="text-center"><b>'.$data1->rank_gender.'</b></td>';
			$str .= '<tr><td class="text-center"><b>'.$poradi.'</b></td><td class="text-center">'.$data1->ids_alias.'</td><td>'.$data1->jmeno.'</td><td class="text-center">'.$data1->rocnik.'</td><td>'.$data1->nazev_tymu.'</td><td class="text-center">'.$data1->stat.'</td><td class="text-center">'.$data1->nazev_kategorie.'</td><td class="text-center"><b>'.$data1->rank_category.'</b></td>';
			$sql2 = "SELECT * FROM $this->sqlvysledky WHERE ids = :ids AND false_time IS NULL AND lap_only IS NULL ORDER BY race_time ASC LIMIT 0,$this->time_order";
			$sth2 = $this->db->prepare($sql2);
			$sth2->execute(Array(':ids' => $data1->ids));
			$i = 1;
			$missing_time = false; //nastavení proměnné pro konntrolu, jestli má závodník všecky časy 
			while($val2 = $sth2->fetchObject()){
			    if($this->time_order == 1){//pokud je to první čas 
				$str .= '<td class="text-center">'.$val2->lap_time.'</td>';
				if($this->cislo_kategorie == '_2'){ //pouze pokud se jedná o paralelní kategorie
				    $str .= ($distance_time !== '00:00:00.00') ?  ('<td class="text-center">'.$distance_time.'</td>') : ('<td class="text-center">&nbsp;</td>');
				}
				else{  //normal
				    $str .= ($val2->distance_overall != '00:00:00.00') ?  ('<td class="text-center">'.$val2->distance_overall.'</td>') : ('<td class="text-center">&nbsp;</td>');
				}
			    }
			    else{//pokud je to jiný než první čas
				if($i <= $this->time_order){ 
				    if($val2->lap_time != '00:00:00.00' AND $missing_time == false){
					$str .= '<td class="text-center">'.$val2->lap_time.'</td>';
				    }
				    else{
				       $str .= '<td class="text-center">&nbsp;</td>';
				       $missing_time = true; //byl nalezen nulový čas a tím pádem se vy výsledcích objeví pouze výsledný čas
				    }
				    $str .= '<td class="text-center"><i>'.$val2->rank_overall_lap.'</i></td>';
				    if($i > 1 && $i < $this->time_order){
					$str .= '<td class="text-center"><i>'.$val2->rank_overall.'</i></td>';
				    }

				}
				if($i == $this->time_order){ // toto je poslední čas a tím pádem se tady vloží celkový čas a odstup
				    $str .= ($val2->race_time != '00:00:00.00') ? ('<td class="text-center">'.$val2->race_time.'</td>') : ('<td class="text-center">&nbsp;</td>');
				    if($this->cislo_kategorie == '_2'){ //pouze pokud se jedná o paralelní kategorie
					$str .= ($distance_time !== '00:00:00.00') ?  ('<td class="text-center">'.$distance_time.'</td>') : ('<td class="text-center">&nbsp;</td>');
				    }
				    else{  //normal
					$str .= ($val2->distance_overall != '00:00:00.00') ?  ('<td class="text-center">'.$val2->distance_overall.'</td>') : ('<td class="text-center">&nbsp;</td>');
				    }
				}
			    }
			    $i++;
			}
			$str .= '</tr>';
			$poradi++;
		}
		$str .= $this->DNFOverall(1);
		$str .= '</tbody></table>';
	    }
	    

?>
<?php  
    /* sareza, ..
     * 
     */
   
    $str = '';
    $hash_url = '#'.$this->race_year.'/'.$this->race_id.'/';
    $sql1 = "SELECT CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,osoby.psc AS stat,tymy.nazev_tymu,COUNT($this->sqlvysledky.id) AS pocet_kol,MAX($this->sqlvysledky.race_time) AS finish_time,$this->sqlzavod.cip,$this->sqlzavod.ids_alias,$this->sqlkategorie.nazev_k AS nazev_kategorie FROM $this->sqlvysledky,$this->sqlzavod,osoby,$this->sqlkategorie,tymy "
	      . "WHERE "
	      . "$this->sqlvysledky.false_time IS NULL AND "
	      . "$this->sqlvysledky.race_time > '0' AND "
	      . "$this->sqlvysledky.cip = $this->sqlzavod.cip AND "
	      . "$this->sqlzavod.ido = osoby.ido AND "
	      . "$this->sqlzavod.poradi_podzavodu = :event_order AND "
	      . "$this->sqlzavod.id_kategorie = $this->sqlkategorie.id_kategorie AND "
	      . "$this->sqlzavod.id_tymu = tymy.id_tymu "
	      . "GROUP BY $this->sqlzavod.cip "
	      . " ORDER BY pocet_kol DESC,finish_time ASC";
    $sth1 =  $this->db->prepare($sql1);
    $sth1->execute(Array(':event_order' => $this->event_order));
    if($sth1->rowCount()){
	$str .= '<table class="table table-bordered table-hover table_vysledky">';
	$str .= '<thead><tr class="header"><th class="text-center">#</th><th class="text-center">St.č</th><th>Jméno</th><th class="text-center">Ročník</th><th>Tým/Bydliště</th><th>Kategorie</th><th class="text-center">Počet kol</th><th class="text-center">Celkový čas</th><th class="text-center">Odstup</th></tr></thead><tbody>';
	$poradi = 1;
	while($dbdata1 = $sth1->fetchObject()){
	    if($poradi == 1) $max_pocet_kol = $dbdata1->pocet_kol;// nejvyšší počet časů pro počítání odstupů
	    $sql2 = "SELECT $this->sqlvysledky.distance_overall FROM $this->sqlvysledky WHERE $this->sqlvysledky.cip = :cip AND time_order = :max_pocet_kol"; //vezmeme odstupy, v tomto případě ve zvláštním dotaze
	    $sth2 = $this->db->prepare($sql2);
	    $sth2->execute(Array(':cip' => $dbdata1->cip,':max_pocet_kol' => $max_pocet_kol));
	    if($sth2->rowCount()){ //pokud je někdo se stejným, tzn. nejvyšším počttem kol, použijeme časový odstup
		$dbdata2 = $sth2->fetchObject();
		($dbdata2->distance_overall != '00:00:00.00') ? ($distance_overall = $dbdata2->distance_overall) : ($distance_overall = '-'); //pokud nemá odstup, tak je první a bude tam pomlčka
	    }
	    else{ // pokud ne, spočítáme odstup v kolech
		 $distance_overall = $dbdata1->pocet_kol - $max_pocet_kol; 
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
	    $str .= '<tr id="'.$dbdata1->cip.'">';
	    $str .= '<td class="text-center">'.$poradi.'</td>';
	    $str .= '<td class="text-center">'.$dbdata1->ids_alias.'</td>';
	    $str .= '<td>'.$dbdata1->jmeno.'</td>';
	    $str .= '<td class="text-center">'.$dbdata1->rocnik.'</td>';
	    $str .= '<td>'.$dbdata1->nazev_tymu.'</td>';
	    $str .= '<td>'.$dbdata1->nazev_kategorie.'</td>';
	    $str .= '<td class="text-center">'.$dbdata1->pocet_kol.'</td>';
	    $str .= '<td class="text-center">'.$dbdata1->finish_time.'</td>';
	    $str .= ($distance_overall != '00:00:00.00') ?  ('<td class="text-center">'.$distance_overall.'</td>') : ('<td class="text-center">-</td>');
	    $str .= '</tr>';
	    $poradi++;
	}
	$str .= '</table>'; 
    }
?>
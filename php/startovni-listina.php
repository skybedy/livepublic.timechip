<?php
    require_once '../libs/settings.php';
    class StartovniListina extends Settings{
	private $sqlvysledky;
	private $sqlzavod;
	private $race_code;
	private $race_name;
	private $ruzny_pocet_casu;
	private $results_type;
	private $event_order;
	private $time_count;
	private $racer_type;
	private $team_racer_count;
	private $hash_url;
	private $cislo_kategorie;
	private $event_name;
        private $startovni_cas;
	

	public function __construct(){
	    parent::__construct();
	    $this->event_order = isset($_GET['event_order']) ? $_GET['event_order'] : 1; 
	    
	    
	    /* provizorka na VALACHIARUN */
	    if($this->event_order == 'all'){
		$sql1 = "SELECT $this->sqlzavody.kod_zavodu,$this->sqlzavody.delka_kola,$this->sqlzavody.nazev_zavodu,$this->sqlzavody.pocet_podzavodu,$this->sqlzavody.ruzny_pocet_casu,$this->sqlzavody.startovni_cas AS cas_startu_zavodu FROM $this->sqlzavody WHERE $this->sqlzavody.id_zavodu = :race_id";
		$sth1 =  $this->db->prepare($sql1);
		$sth1->execute(array(':race_id' => $this->race_id));
		$dbdata1 =  $sth1->fetchObject();
		$this->race_name = $dbdata1->nazev_zavodu;
		$this->event_count = $dbdata1->pocet_podzavodu;
		$this->race_code = $dbdata1->kod_zavodu;
		$this->sqlvysledky = 'vysledky_'.$this->race_code.'_'.$this->race_year.'_test';
		$this->sqlzavod = 'zavod_'.$this->race_code.'_'.$this->race_year;
		$this->ruzny_pocet_casu = $dbdata1->ruzny_pocet_casu;
		$this->hash_url = '#'.$this->race_year.'/'.$this->race_id.'/';
		$this->time_count = 1;
		$this->results_type = 1;
		$this->racer_type = 1;
		$this->team_racer_count = 1;
                $this->startovni_cas = $dbdata1->cas_startu_zavodu;
	    }
	    else{
	    
	    
		/* provizorka.. mělo by se to dát už do předchozíchi selectu ať se nemusí dělat další dotaz do db*/

		    $sql = "SELECT kategorie FROM $this->sqlpodzavody WHERE id_zavodu = '$this->race_id' AND poradi_podzavodu = '$this->event_order'";
		    $sth = $this->db->prepare($sql);;
		    $sth->execute();
		    $dbdata = $sth->fetchObject();
		    $cislo_kategorie = $dbdata->kategorie;
		    $this->cislo_kategorie = ($cislo_kategorie == 1) ? ('') : ('_2');
		    $sql1 = "SELECT $this->sqlzavody.kod_zavodu,$this->sqlzavody.delka_kola,$this->sqlzavody.nazev_zavodu,$this->sqlzavody.pocet_podzavodu,$this->sqlzavody.ruzny_pocet_casu,$this->sqlzavody.startovni_cas AS cas_startu_zavodu,$this->sqlpodzavody.* FROM $this->sqlzavody,$this->sqlpodzavody WHERE $this->sqlzavody.id_zavodu = :race_id AND $this->sqlpodzavody.id_zavodu = $this->sqlzavody.id_zavodu AND $this->sqlpodzavody.poradi_podzavodu = :event_order";
                    $sth1 =  $this->db->prepare($sql1);
		    $sth1->execute(array(':race_id' => $this->race_id,':event_order' => $this->event_order));
		    $dbdata1 =  $sth1->fetchObject();
		    $this->race_name = $dbdata1->nazev_zavodu;
		    $this->time_count = $dbdata1->pocet_casu;
		    $this->event_count = $dbdata1->pocet_podzavodu;
		    $this->event_name = $dbdata1->nazev;
		    $this->race_code = $dbdata1->kod_zavodu;
                    $this->startovni_cas = $dbdata1->cas_startu_zavodu;
		    $this->sqlvysledky = 'vysledky_'.$this->race_code.'_'.$this->race_year.'_test';
		    $this->sqlzavod = 'zavod_'.$this->race_code.'_'.$this->race_year;
		    $this->ruzny_pocet_casu = $dbdata1->ruzny_pocet_casu;
		    $this->results_type = $dbdata1->typ_vysledku;
		    $this->racer_type = $dbdata1->typ_zavodnika;
		    $this->team_racer_count = $dbdata1->pocet_clenu_tymu; 
		    $this->hash_url = '#'.$this->race_year.'/'.$this->race_id.'/';
	    }
	    
         if(isset($_GET['murinoha'])){
		if(method_exists(get_class(),$_GET['murinoha'])){
                    $nazev_funkce = $_GET['murinoha']; 
                    $this->$nazev_funkce();
		}
                else{
		  $this->index();  
		}
	    }
	    else{
		$this->index();
	    }	 }
	
	private function index(){
	    $fcdata = '';
	    $sql2 = "SELECT lista_dolni,lista_horni FROM reklamy_na_vysledky WHERE rok_zavodu = :race_year AND id_zavodu = :race_id";
	    $sth2 = $this->db->prepare($sql2);
	    $sth2->execute(Array(':race_year' => $this->race_year,':race_id' => $this->race_id));
	    $reklamy_lista_horni = false;
	    $reklamy_lista_dolni = false;
	    $dbdata2 = (object) array('lista_horni' => 0,'lista_dolni' => 0);
	    if($sth2->rowCount()){
		$dbdata2 = $sth2->fetchObject();
	    }

	    $fcdata .= '<div class="panel panel-default panel-collapse"><div class="panel-body">';
	    if($dbdata2->lista_horni){
		$fcdata .= '<div class="reklama-lista-horni"><img src="images/results/lista_horni_'.$this->race_code.'_'.$this->race_year.'.jpg" style="max-width:100%;" /></div>';
	    }
	    $fcdata .= '<div class="navbar navbar-raceadmin"><form id="starting_list_form" class="navbar-form navbar-left">';
	    /*
	     * nejprve se podíváme,jestli je více podzávodů, pokud jo, vypíšeme je, pokud ne, neuděláme nic
	     */
	   																																																				    
	    $sql1 = "SELECT * FROM $this->sqlpodzavody WHERE id_zavodu = :race_id ORDER BY poradi_podzavodu";
	    $sth1 = $this->db->prepare($sql1);
	    $sth1->execute(Array(':race_id' => $this->race_id));
	    if($sth1->rowCount()){
		$fcdata.= '<select id="event_list" class="form-control input-lg">';
		$i = 1;
		while($dbdata1 = $sth1->fetchObject()){
		    if($i == 1) $first_event_order = $dbdata1->poradi_podzavodu; //pořadí prvního podzávodu, které potřebuju v druhém selectu
		    $fcdata .= '<option value="'.$dbdata1->poradi_podzavodu.'">'.$dbdata1->nazev.'</option>';
		    $i++;
		}
		$fcdata .= '<option value="all">'.'All'.'</option>';
		$fcdata.= '</select>'."\r\n";
	    }
	    $fcdata .= $this->StartingListTypeSelect();
	    $fcdata .= '<span id="dynamic_select"></span>';
	    $fcdata .= '</form></div>';
	    $fcdata .= '<div id="result_table" class="table-responsive">';
	    $fcdata .= $this->StartingListOverall();
	    $fcdata .= '</div>';
	    if($dbdata2->lista_dolni){
		$fcdata .= '<div class="reklama-lista-dolni"><img src="images/results/lista_dolni_'.$this->race_code.'_'.$this->race_year.'.jpg" style="max-width:100%;" /></div>';
	    }
	    $fcdata .= '</div></div>';
	    echo json_encode($fcdata);
	}
	
	private function StartingListTypeSelect(){
	    $fcdata = '<select id="starting_list_type" class="form-control input-lg">';
	    $fcdata .= '<option value="StartingListOverall">Bez rozdílu kategorií</option>';
	    $fcdata .= '<option value="StartingListCategory">Podle kategorií</option>';
	    
	   // $fcdata .= '<option value="StartingListRozjizdka">Podle rozjížděk</option>';
	    //$fcdata .= '<option value="StartingListGender">Podle pohlaví</option>';
	    //$fcdata .= '<option value="StartingListBirthYear">Podle ročníků</option>';
	    $fcdata.= '</select>'."\r\n";
	    return $fcdata;
	}
	
	
	private function StartingListTeribear(){
	    $str = '';
		if($this->racer_type == 1){
		    //$sql1 = "SELECT $this->sqlzavod.ids_alias,$this->sqlzavod.cip,CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,osoby.psc AS stat,osoby.tricko, $this->sqlkategorie.nazev_k AS nazev_kategorie,pribehy_teribear.nazev_pribehu FROM osoby,$this->sqlzavod,$this->sqlkategorie,pribehy_teribear WHERE $this->sqlzavod.ido = osoby.ido AND $this->sqlkategorie.poradi_podzavodu = $this->event_order AND $this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie AND $this->sqlzavod.id_pribehu = pribehy_teribear.id_pribehu ORDER BY $this->sqlzavod.ids";
		    $sql1 = "SELECT $this->sqlzavod.ids_alias,$this->sqlzavod.cip,CONCAT_WS(' ',osoby_teribear.prijmeni,osoby_teribear.jmeno) AS jmeno,osoby_teribear.rocnik,osoby_teribear.psc AS stat,osoby_teribear.tricko, $this->sqlkategorie.nazev_k AS nazev_kategorie,tymy.nazev_tymu FROM osoby_teribear,$this->sqlzavod,$this->sqlkategorie,tymy WHERE $this->sqlzavod.ido = osoby_teribear.ido AND $this->sqlkategorie.poradi_podzavodu = $this->event_order AND $this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie AND $this->sqlzavod.tym_2 = tymy.id_tymu ORDER BY $this->sqlzavod.ids";
		    $sth1 = $this->db->prepare($sql1);
		    $sth1->execute(Array(':event_order' => $this->event_order));
		    if($sth1->rowCount()){
			$pocet = $sth1->rowCount();
			$str .= '<h4 style="float:left" class="headline-results">'.$this->race_name.', startovní listina bez rozdílu kategorií, '.$this->event_name.'</h4>';
			$str .= '<p style="text-align:right">Celkem '.$pocet.' závodníků.</p>';
			$str .= '<table id="startlist" class="table table-hover noborder">';
			$str .= '<tr class="header"><th class="text-center">St.č</th><th class="text-center">Čip</th><th>Jméno</th><th class="text-center">Ročník</th><th class="text-center">Stát</th><th>Kategorie</th><th>Tým</th></tr>';
			while($dbdata1 = $sth1->fetchObject()){
			   // $str .= '<tr><td class="text-center">'.$dbdata1->ids_alias.'</td><td class="text-center">'.$dbdata1->cip.'</td><td>'.$dbdata1->jmeno.'</td><td class="text-center">'.$dbdata1->rocnik.'</td><td class="text-center">'.$dbdata1->stat.'</td><td>'.$dbdata1->nazev_kategorie.'</td><td>'.$dbdata1->nazev_pribehu.'</td></tr>';
			    $str .= '<tr><td class="text-center">'.$dbdata1->ids_alias.'</td><td class="text-center">'.$dbdata1->cip.'</td><td>'.$dbdata1->jmeno.'</td><td class="text-center">'.$dbdata1->rocnik.'</td><td class="text-center">'.$dbdata1->stat.'</td><td>'.$dbdata1->nazev_kategorie.'</td><td>'.$dbdata1->nazev_tymu.'</td></tr>';
			}
			$str .= '</table>';
		    }
		    else{
			$str .= '<p>Startovní listina ještě není vytvořená</p>';
		    }
		}
		elseif($this->racer_type == 3){
		    $sql1 = "SELECT tymy.id_tymu,tymy.nazev_tymu,$this->sqlkategorie.nazev_k AS nazev_kategorie FROM tymy,$this->sqlzavod,$this->sqlkategorie WHERE $this->sqlkategorie.poradi_podzavodu = $this->event_order AND $this->sqlzavod.tym_2 = tymy.id_tymu AND $this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie_2 GROUP BY tymy.id_tymu ORDER BY tymy.nazev_tymu";
		    $sth1 =  $this->db->prepare($sql1);
		    $sth1->execute(Array(':event_order' => $this->event_order));
		    if($sth1->rowCount()){
			$str .= '<table class="table table-bordered table_vysledky">';
			$str .= '<thead><tr class="header"><th>Tým</th><th>Kategorie</th><th>Člen týmu</th><th class="text-center">Ročník</th><th class="text-center">St.č</th>';
			$str .= '</tr></thead><tbody>';
			while($data1 = $sth1->fetchObject()){
			    $sql2 = "SELECT CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,$this->sqlzavod.cip FROM osoby,$this->sqlzavod WHERE $this->sqlzavod.tym_2 = '$data1->id_tymu' AND $this->sqlzavod.ido = osoby.ido ORDER BY $this->sqlzavod.cip";
			    $sth2 = $this->db->prepare($sql2);
			    $sth2->execute();
			    if($sth2->rowCount()){
				$pocet_clenu = $sth2->rowCount();
				$k = 1;
				while($data2 = $sth2->fetchObject()){
				    if($k == 1){
					$str .= '<tr style="background:red">';
					$str .= '<td rowspan="'.$pocet_clenu.'">'.$data1->nazev_tymu.'</td>';
					$str .= '<td rowspan="'.$pocet_clenu.'">'.$data1->nazev_kategorie.'</td>';
					$str .= '<td>'.$data2->jmeno.'</td>';
					$str .= '<td class="text-center">'.$data2->rocnik.'</td>';
					$str .= '<td class="text-center">'.$data2->cip.'</td>';
					$str .= '</tr>';
				    }
				    else{
					$str .= '<tr>';
					$str .= '<td>'.$data2->jmeno.'</td>';
					$str .= '<td class="text-center">'.$data2->rocnik.'</td>';
					$str .= '<td class="text-center">'.$data2->cip.'</td>';
					$str .= '</tr>';
				    }
				    $k++;

				}
			    }

			}
			$str .= '</tbody></table>';
		    }

		}
	    
	    
	    
	    
	    if(isset($_GET['murinoha'])){ 
		echo $str;
	    }
	    else{
		return $str;
	    }

	}
	
	public function StartingListRozjizdka(){
	    $str = "";
	    $rok_zavodu = 2017;
	    $sql1 = "SELECT * FROM rozjizdky_$rok_zavodu,podzavody_$rok_zavodu WHERE rozjizdky_$rok_zavodu.id_zavodu = $this->race_id AND rozjizdky_$rok_zavodu.id_podzavodu = podzavody_$rok_zavodu.id_podzavodu AND podzavody_$rok_zavodu.poradi_podzavodu = {$_GET['event_order']} ORDER BY rozjizdky_$rok_zavodu.id_podzavodu ASC,rozjizdky_$rok_zavodu.poradi_rozjizdky ASC";
	    $sth1 = $this->db->prepare($sql1);
	    $sth1->execute();
	    if($sth1->rowCount()){
                $str .= '<table id="table2excel" class="table table-striped table-bordered table-hover noborder table_vysledky">';
		$k = 1;
		while($dbdata1 = $sth1->fetchObject()){
		    $sql2 = "SELECT $this->sqlzavod.ids_alias,CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,osoby.psc AS stat,$this->sqlkategorie.nazev_k AS nazev_kategorie,tymy.nazev_tymu FROM osoby,$this->sqlzavod,$this->sqlkategorie,tymy WHERE $this->sqlzavod.id_rozjizdky = $dbdata1->id_rozjizdky AND $this->sqlzavod.id_kategorie = $this->sqlkategorie.id_kategorie AND  $this->sqlzavod.ido = osoby.ido AND tymy.id_tymu = $this->sqlzavod.prislusnost ORDER BY $this->sqlzavod.ids ASC";
		    $sth2 = $this->db->prepare($sql2);
		    $sth2->execute();
		    if($sth2->rowCount()){
			$class = $k == 1 ? $class = 'nadpis nopadding' : 'nadpis';
			$str .= '<tr><td class="'.$class.'" colspan="5">'.$dbdata1->nazev_rozjizdky.'</td></tr>';
			$str .= '<tr class="header"><th class="text-center">St.číslo</th><th>Jméno</th><th class="text-center">Ročník</th><th>Tým/Bydliště</th><th class="text-center">Stát</th><th class="text-center">Kategorie</th></tr>';
			while($dbdata2 = $sth2->fetchObject()){
			    $str .= '<tr><td class="text-center">'.$dbdata2->ids_alias.'</td><td>'.$dbdata2->jmeno.'</td><td class="text-center">'.$dbdata2->rocnik.'</td><td>'.$dbdata2->nazev_tymu.'</td><td class="text-center">'.$dbdata2->stat.'</td><td class="text-center">'.$dbdata2->nazev_kategorie.'</td></tr>';

			}
		    }
		    $k++;
		}
		$str .= '</table>';
	    }
	    echo $str;
	}
	
	
	private function NazevTymu($tym){
	    $nazev_tymu = $tym;
	    //echo $tym."<br>";
	    if($tym == '-'){
		$nazev_tymu = '';
	    }
	    
	    return $nazev_tymu;
	}


	private function StartingListOverall(){
	    $str = '';
	    //if($this->race_id == 93 && $this->race_year == 2015){
	    //if($this->race_id == 45 && $this->race_year == 2016){
            if($this->race_id == 60 && $this->race_year == 2018){
		return $this->StartingListTeribear();
	    }
	    else{
		if($this->event_order == 'all'){
                    //echo "bla";
		$sql1 = "SELECT $this->sqlzavod.ids_alias,$this->sqlzavod.ids_alias,$this->sqlzavod.cip,CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,osoby.tricko,osoby.psc AS stat,osoby.tricko,osoby.mail,osoby.telefon,osoby.id_cts,osoby.prijmeni AS surname,osoby.jmeno AS firstname,osoby.dalsi_udaje_1,osoby.dalsi_udaje_2,$this->sqlkategorie.nazev_k AS nazev_kategorie,tymy.nazev_tymu FROM osoby,$this->sqlzavod,$this->sqlkategorie,tymy "
                        . "WHERE "
                        . "$this->sqlzavod.ido = osoby.ido AND "
                        . "$this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie$this->cislo_kategorie AND "
                        . "tymy.id_tymu = $this->sqlzavod.prislusnost " 
                        . "ORDER BY $this->sqlzavod.ids_alias ASC,$this->sqlzavod.cip ASC";
		$sth1 = $this->db->prepare($sql1);
		$sth1->execute();
		if($sth1->rowCount()){  
		    $pocet = $sth1->rowCount();
		    $str .= '<p style="text-align:right">Celkem '.$pocet.' závodníků.</p>';
		    $str .= '<table class="table table-striped table-bordered table-hover noborder table_vysledky">';
		    //$str .= '<thead><tr class="header"><th class="text-center">St.č</th><th class="text-center">Čip</th><th>Jméno</th><th class="text-center">Ročník</th><th>Tým/Bydliště</th><th class="text-center">Stát</th><th>Kategorie</th></tr></thead>';
		    //pro winter hei rum, pridan sloupec se stasrtovnim casem
                    $str .= '<thead><tr class="header"><th class="text-center">St.č</th><th class="text-center">Čip</th><th>Jméno</th><th class="text-center">Ročník</th><th>Tým/Bydliště</th><th class="text-center">Stát</th><th>Kategorie</th><th class="text-center">Startovní čas</th></tr></thead>';
		    while($dbdata1 = $sth1->fetchObject()){
			if($this->event_order == 1){
			    $str .= '<tr><td class="text-center">'.$dbdata1->ids_alias.'</td><td class="text-center">'.$dbdata1->cip.'</td><td>'.$dbdata1->jmeno.'</td><td class="text-center">'.$dbdata1->rocnik.'</td><td>'.$this->NazevTymu($dbdata1->nazev_tymu).'</td><td class="text-center">'.$dbdata1->stat.'</td><td>'.$dbdata1->nazev_kategorie.'</td></tr>';
			    
                            //winter hei run
                           // $str .= '<tr><td class="text-center">'.$dbdata1->ids_alias.'</td><td class="text-center">'.$dbdata1->cip.'</td><td>'.$dbdata1->jmeno.'</td><td class="text-center">'.$dbdata1->rocnik.'</td><td>'.$this->NazevTymu($dbdata1->nazev_tymu).'</td><td class="text-center">'.$dbdata1->stat.'</td><td>'.$dbdata1->nazev_kategorie.'</td><td class="text-center">'.$this->StartovniCasVeVlne($dbdata1->cip).'</td></tr>';

                            //$str .= '<tr><td class="text-center">'.$dbdata1->ids_alias.'</td><td class="text-center">'.$dbdata1->cip.'</td><td>'.$dbdata1->jmeno.'</td><td class="text-center">'.$dbdata1->rocnik.'</td><td>'.$this->NazevTymu($dbdata1->nazev_tymu).'</td><td class="text-center">'.$dbdata1->stat.'</td><td>'.$dbdata1->nazev_kategorie.'</td><td>'.$dbdata1->dalsi_udaje_1.'</td><td>'.$dbdata1->dalsi_udaje_2.'</td></tr>';
			}
			else{
			    //$str .= '<tr><td class="text-center">'.$dbdata1->ids_alias.'</td><td class="text-center">'.$dbdata1->cip.'</td><td>'.$dbdata1->jmeno.'</td><td class="text-center">'.$dbdata1->rocnik.'</td><td>'.$dbdata1->nazev_tymu.'</td><td class="text-center">'.$dbdata1->stat.'</td><td>'.$dbdata1->nazev_kategorie.'</td><td>'.$dbdata1->tricko.'</td></tr>';
			    //$str .= '<tr><td class="text-center">'.$dbdata1->ids_alias.'</td><td class="text-center">'.$dbdata1->cip.'</td><td>'.$dbdata1->jmeno.'</td><td class="text-center">'.$dbdata1->rocnik.'</td><td>'.$dbdata1->nazev_tymu.'</td><td class="text-center">'.$dbdata1->stat.'</td><td>'.$dbdata1->nazev_kategorie.'</td><td>'.$dbdata1->mail.'</td><td>'.$dbdata1->telefon.'</td></tr>';
			    $str .= '<tr><td class="text-center">'.$dbdata1->ids_alias.'</td><td class="text-center">'.$dbdata1->cip.'</td><td>'.$dbdata1->jmeno.'</td><td class="text-center">'.$dbdata1->rocnik.'</td><td>'.$this->NazevTymu($dbdata1->nazev_tymu).'</td><td class="text-center">'.$dbdata1->stat.'</td><td>'.$dbdata1->nazev_kategorie.'</td></tr>';
			    //$str .= '<tr><td class="text-center">'.$dbdata1->ids_alias.'</td><td class="text-center">'.$dbdata1->cip.'</td><td>'.$dbdata1->jmeno.'</td><td class="text-center">'.$dbdata1->rocnik.'</td><td>'.$this->NazevTymu($dbdata1->nazev_tymu).'</td><td class="text-center">'.$dbdata1->stat.'</td><td>'.$dbdata1->nazev_kategorie.'</td><td class="text-center">'.$this->StartovniCasVeVlne($dbdata1->cip).'</td></tr>';
                            //$str .= '<tr><td class="text-center">'.$dbdata1->ids_alias.'</td><td class="text-center">'.$dbdata1->cip.'</td><td>'.$dbdata1->jmeno.'</td><td>'.$dbdata1->nazev_kategorie.'</td><td>'.$dbdata1->telefon.'</td><td>'.$dbdata1->dalsi_udaje_1.'</td><td>'.$dbdata1->dalsi_udaje_2.'</td></tr>';

                        }
			}
		    $str .= '</table>';
		}
		else{
		    $str .= '<p>Startovní listina ještě není vytvořená</p>';
		}

	    }
	    else{
		if($this->racer_type == 1){
                    $sql1 = "SELECT $this->sqlzavod.ids,$this->sqlzavod.ids_alias,$this->sqlzavod.cip,CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,osoby.psc AS stat,osoby.tricko, $this->sqlkategorie.nazev_k AS nazev_kategorie,tymy.nazev_tymu FROM osoby,$this->sqlzavod,$this->sqlkategorie,tymy WHERE $this->sqlzavod.ido = osoby.ido AND $this->sqlkategorie.poradi_podzavodu = '$this->event_order' AND $this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie$this->cislo_kategorie AND tymy.id_tymu = $this->sqlzavod.prislusnost ORDER BY $this->sqlzavod.ids";


		    //$sql1 = "SELECT $this->sqlzavod.vlna,$this->sqlzavod.ids,$this->sqlzavod.ids_alias,$this->sqlzavod.cip,CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,osoby.psc AS stat,osoby.tricko, $this->sqlkategorie.nazev_k AS nazev_kategorie,tymy.nazev_tymu FROM osoby,$this->sqlzavod,$this->sqlkategorie,tymy WHERE $this->sqlzavod.ido = osoby.ido AND $this->sqlkategorie.poradi_podzavodu = '$this->event_order' AND $this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie$this->cislo_kategorie AND tymy.id_tymu = $this->sqlzavod.prislusnost ORDER BY $this->sqlzavod.ids";
//echo $sql1."\n";
//$sql1 = "SELECT $this->sqlzavod.ids_alias,$this->sqlzavod.cip,CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,osoby.psc AS stat,osoby.tricko, $this->sqlkategorie.nazev_k AS nazev_kategorie,tymy.nazev_tymu FROM osoby,$this->sqlzavod,$this->sqlkategorie,tymy WHERE $this->sqlzavod.ido = osoby.ido AND $this->sqlkategorie.poradi_podzavodu = '$this->event_order' AND $this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie$this->cislo_kategorie AND tymy.id_tymu = $this->sqlzavod.prislusnost ORDER BY jmeno";
		   //echo $sql1;
		    $sth1 = $this->db->prepare($sql1);
		    $sth1->execute(Array(':event_order' => $this->event_order));
		    if($sth1->rowCount()){
			$pocet = $sth1->rowCount();
			$str .= '<h4 style="float:left" class="headline-results">'.$this->race_name.', startovní listina bez rozdílu kategorií, '.$this->event_name.'</h4>';
			$str .= '<p style="text-align:right">Celkem '.$pocet.' závodníků.</p>';
			$str .= '<table class="table table-striped table-bordered table-hover noborder table_vysledky">';
			$str .= '<thead><tr class="header"><th class="text-center">St.č</th><th class="text-center">Čip</th><th>Jméno</th><th class="text-center">Ročník</th><th>Tým/Bydliště</th><th class="text-center">Stát</th><th>Kategorie</th></tr></thead>';
			//$str .= '<thead><tr class="header"><th class="text-center">St.č</th><th class="text-center">Alias</th><th class="text-center">Čip</th><th>Jméno</th><th class="text-center">Ročník</th><th>Tým/Bydliště</th><th class="text-center">Stát</th><th>Kategorie</th><th class="text-center">Startovní čas</th></tr></thead>';
			while($dbdata1 = $sth1->fetchObject()){
			    if($this->event_order == 1){
				$str .= '<tr><td class="text-center">'.$dbdata1->ids_alias.'</td><td class="text-center">'.$dbdata1->cip.'</td><td>'.$dbdata1->jmeno.'</td><td class="text-center">'.$dbdata1->rocnik.'</td><td>'.$this->NazevTymu($dbdata1->nazev_tymu).'</td><td class="text-center">'.$dbdata1->stat.'</td><td>'.$dbdata1->nazev_kategorie.'</td></tr>';
				//$str .= '<tr><td class="text-center">'.$dbdata1->ids.'</td><td class="text-center">'.$dbdata1->ids_alias.'</td><td class="text-center">'.$dbdata1->cip.'</td><td>'.$dbdata1->jmeno.'</td><td class="text-center">'.$dbdata1->rocnik.'</td><td>'.$this->NazevTymu($dbdata1->nazev_tymu).'</td><td class="text-center">'.$dbdata1->stat.'</td><td>'.$dbdata1->nazev_kategorie.'</td><td class="text-center">'.$dbdata1->vlna.'</td></tr>';
			    }
			    else{
				$str .= '<tr><td class="text-center">'.$dbdata1->ids_alias.'</td><td class="text-center">'.$dbdata1->cip.'</td><td>'.$dbdata1->jmeno.'</td><td class="text-center">'.$dbdata1->rocnik.'</td><td>'.$this->NazevTymu($dbdata1->nazev_tymu).'</td><td class="text-center">'.$dbdata1->stat.'</td><td>'.$dbdata1->nazev_kategorie.'</td></tr>';
				//$str .= '<tr><td class="text-center">'.$dbdata1->ids.'</td><td class="text-center">'.$dbdata1->ids_alias.'</td><td class="text-center">'.$dbdata1->cip.'</td><td>'.$dbdata1->jmeno.'</td><td class="text-center">'.$dbdata1->rocnik.'</td><td>'.$this->NazevTymu($dbdata1->nazev_tymu).'</td><td class="text-center">'.$dbdata1->stat.'</td><td>'.$dbdata1->nazev_kategorie.'</td><td>'.$dbdata1->tricko.'</td></tr>';
				//$str .= '<tr><td class="text-center">'.$dbdata1->ids.'</td><td class="text-center">'.$dbdata1->ids_alias.'</td><td class="text-center">'.$dbdata1->cip.'</td><td>'.$dbdata1->jmeno.'</td><td class="text-center">'.$dbdata1->rocnik.'</td><td>'.$this->NazevTymu($dbdata1->nazev_tymu).'</td><td class="text-center">'.$dbdata1->stat.'</td><td>'.$dbdata1->nazev_kategorie.'</td><td class="text-center">'.$this->StartovniCasVeVlne($dbdata1->cip).'</td></tr>';
			    }

			    }
			$str .= '</table>';
		    }
		    else{
			$str .= '<p>Startovní listina ještě není vytvořená</p>';
		    }
		}
		elseif($this->racer_type == 2){
                    $discipliny_ac = Array("Běh","Paragliding","MTB","Kajak"); 
		    $sql1 = "SELECT tymy.nazev_tymu, $this->sqlkategorie.nazev_k AS nazev_kategorie,$this->sqlzavod.ids_alias FROM tymy,$this->sqlzavod,$this->sqlkategorie WHERE $this->sqlkategorie.poradi_podzavodu = '$this->event_order' AND $this->sqlzavod.tym = tymy.id_tymu AND $this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie GROUP BY $this->sqlzavod.ids ORDER BY $this->sqlzavod.ids ASC";
		    $sth1 =  $this->db->prepare($sql1);
		    $sth1->execute(Array(':event_order' => $this->event_order));
		    if($sth1->rowCount()){
			$pocet = $sth1->rowCount();
			$str .= '<h4 style="float:left" class="headline-results">'.$this->race_name.', startovní listina bez rozdílu kategorií</h4>';
			$str .= '<p style="text-align:right">Celkem '.$pocet.' týmů.</p>';
			$str .= '<table class="table table_vysledky">';
			//$str .= '<thead><tr class="header"><th class="text-center">#</th><th>Tým</th><th>Kategorie</th><th>Člen týmu</th><th class="text-center">Ročník</th><th class="text-center">Čip</th>';
			$str .= '<thead><tr class="header"><th class="text-center">#</th><th>Tým</th><th>Kategorie</th><th>Člen týmu</th><th>Disciplína</th><th class="text-center">Čip</th>';
			$str .= '</tr></thead><tbody>';
			while($data1 = $sth1->fetchObject()){
			    $sql2 = "SELECT CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,$this->sqlzavod.cip FROM osoby,$this->sqlzavod WHERE $this->sqlzavod.ids_alias = :ids_alias AND $this->sqlzavod.ido = osoby.ido ORDER BY $this->sqlzavod.cip";
			    $sth2 = $this->db->prepare($sql2);
			    $sth2->execute(Array(':ids_alias' => $data1->ids_alias));
			    if($sth2->rowCount()){
				$pocet_clenu = $sth2->rowCount();
				$k = 1;
				while($data2 = $sth2->fetchObject()){
				    if($k == 1){
					$str .= '<tr>';
					$str .= '<td class="text-center" rowspan="'.$pocet_clenu.'">'.$data1->ids_alias.'</td>';
					$str .= '<td rowspan="'.$pocet_clenu.'">'.$data1->nazev_tymu.'</td>';
					$str .= '<td rowspan="'.$pocet_clenu.'">'.$data1->nazev_kategorie.'</td>';
					$str .= '<td>'.$data2->jmeno.'</td>';
					$str .= '<td class="text-center">'.$data2->rocnik.'</td>';
                                        //$str .= '<td class="text-left">'.$discipliny_ac[$k-1].'</td>';
					$str .= '<td class="text-center">'.$data2->cip.'</td>';
					$str .= '</tr>';
				    }
				    else{
					$str .= '<tr>';
					$str .= '<td>'.$data2->jmeno.'</td>';
					$str .= '<td class="text-center">'.$data2->rocnik.'</td>';
                                        //$str .= '<td class="text-left">'.$discipliny_ac[$k-1].'</td>';
					$str .= '<td class="text-center">'.$data2->cip.'</td>';
					$str .= '</tr>';
				    }
				    $k++;

				}
			    }

			}
			$str .= '</tbody></table>';
		    }
		}
		elseif($this->racer_type == 3){  // týmy,použito na Lahofer cup
		    $sql1 = "SELECT tymy.id_tymu,tymy.nazev_tymu,$this->sqlkategorie.nazev_k AS nazev_kategorie FROM tymy,$this->sqlzavod,$this->sqlkategorie WHERE $this->sqlkategorie.poradi_podzavodu = '$this->event_order' AND $this->sqlzavod.tym_2 = tymy.id_tymu AND $this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie_2 GROUP BY tymy.id_tymu ORDER BY tymy.nazev_tymu";
		    $sth1 =  $this->db->prepare($sql1);
		    $sth1->execute(Array(':event_order' => $this->event_order));
		    if($sth1->rowCount()){
                        $pocet_tymu = $sth1->rowCount();
                        $str .= "<p class=\"text-right\"><i>Celkem $pocet_tymu týmů</i></p>";
			$str .= '<table class="table tale-bordered table_vysledky">';
			$str .= '<thead><tr class="header"><th>Tým</th><th>Kategorie</th><th>Člen týmu</th><th class="text-center">Ročník</th><th class="text-center">St.č</th>';
			$str .= '</tr></thead><tbody>';
			while($data1 = $sth1->fetchObject()){
			    $sql2 = "SELECT CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,$this->sqlzavod.ids_alias FROM osoby,$this->sqlzavod WHERE $this->sqlzavod.tym_2 = '$data1->id_tymu' AND $this->sqlzavod.ido = osoby.ido ORDER BY $this->sqlzavod.cip";
			    $sth2 = $this->db->prepare($sql2);
			    $sth2->execute();
			    if($sth2->rowCount()){
				$pocet_clenu = $sth2->rowCount();
				$k = 1;
				while($data2 = $sth2->fetchObject()){
				    if($k == 1){
					$str .= '<tr>';
					$str .= '<td rowspan="'.$pocet_clenu.'">'.$data1->nazev_tymu.'</td>';
					$str .= '<td rowspan="'.$pocet_clenu.'">'.$data1->nazev_kategorie.'</td>';
					$str .= '<td>'.$data2->jmeno.'</td>';
					$str .= '<td class="text-center">'.$data2->rocnik.'</td>';
					$str .= '<td class="text-center">'.$data2->ids_alias.'</td>';
					$str .= '</tr>';
				    }
				    else{
					$str .= '<tr>';
					$str .= '<td>'.$data2->jmeno.'</td>';
					$str .= '<td class="text-center">'.$data2->rocnik.'</td>';
					$str .= '<td class="text-center">'.$data2->ids_alias.'</td>';
					$str .= '</tr>';
				    }
				    $k++;

				}
			    }

			}
			$str .= '</tbody></table>';
		    }
		}   
		elseif($this->racer_type == 4){
		    $sql1 = "SELECT $this->sqlzavod.ids,$this->sqlzavod.ids_alias FROM $this->sqlzavod WHERE poradi_podzavodu = $this->event_order GROUP BY ids ORDER BY ids";
		    $sth1 =  $this->db->prepare($sql1);
		    $sth1->execute();
		    if($sth1->rowCount()){
			$str .= '<table class="table table-bordered table-hover table_vysledky">';
			$str .= '<thead><tr class="header"><th class="text-center">St.č.</th><th>Tým</th><th class="text-center">Ročník</th><th class="text-center">Čip</th>';
			$str .= '</tr></thead><tbody>';
			while($data1 = $sth1->fetchObject()){
			    $sql2 = "SELECT CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,osoby.prijmeni,$this->sqlzavod.cip FROM osoby,$this->sqlzavod WHERE $this->sqlzavod.ids = $data1->ids AND $this->sqlzavod.ido = osoby.ido ORDER BY $this->sqlzavod.cip";
			    $sth2 = $this->db->prepare($sql2);
			    $sth2->execute();
			    if($sth2->rowCount()){
				$pocet_clenu = $sth2->rowCount();
				$k = 1;
				while($data2 = $sth2->fetchObject()){
				    if($k == 1){
					$str .= '<tr>';
					$str .= '<td class="text-center" rowspan="'.$pocet_clenu.'">'.$data1->ids_alias.'</td>';
					$str .= '<td>'.$data2->jmeno.'</td>';
					$str .= '<td class="text-center">'.$data2->rocnik.'</td>';
					$str .= '<td class="text-center">'.$data2->cip.'</td>';
					$str .= '</tr>';
				    }
				    else{
					$str .= '<tr>';
					$str .= '<td>'.$data2->jmeno.'</td>';
					$str .= '<td class="text-center">'.$data2->rocnik.'</td>';
					$str .= '<td class="text-center">'.$data2->cip.'</td>';
					$str .= '</tr>';
				    }
				    $k++;

				}
			    }

			}
			$str .= '</tbody></table>';
		    }
		}
		
		
		elseif($this->racer_type == 5){  // týmy,BEHEJ LESY
                    
                    
		    $sql1 = "SELECT tymy.id_tymu,tymy.nazev_tymu,$this->sqlkategorie.nazev_k AS nazev_kategorie FROM tymy,$this->sqlzavod,$this->sqlkategorie WHERE $this->sqlkategorie.poradi_podzavodu = '$this->event_order' AND $this->sqlzavod.tym_3 = tymy.id_tymu AND $this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie_3 GROUP BY tymy.id_tymu ORDER BY tymy.nazev_tymu";
		   //echo $sql1;
		    $sth1 =  $this->db->prepare($sql1);
		    $sth1->execute(Array(':event_order' => $this->event_order));
		    if($sth1->rowCount()){
			$str .= '<table class="table table-bordered table_vysledky">';
			$str .= '<thead><tr class="header"><th>Tým</th><th>Kategorie</th><th>Člen týmu</th><th class="text-center">Ročník</th><th class="text-center">St.č</th>';
			$str .= '</tr></thead><tbody>';
			while($data1 = $sth1->fetchObject()){
			    $sql2 = "SELECT CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,$this->sqlzavod.cip FROM osoby,$this->sqlzavod WHERE $this->sqlzavod.tym_3 = '$data1->id_tymu' AND $this->sqlzavod.ido = osoby.ido ORDER BY $this->sqlzavod.cip";
			    $sth2 = $this->db->prepare($sql2);
			    $sth2->execute();
			    if($sth2->rowCount()){
				$pocet_clenu = $sth2->rowCount();
				$k = 1;
				while($data2 = $sth2->fetchObject()){
				    if($k == 1){
					$str .= '<tr>';
					$str .= '<td rowspan="'.$pocet_clenu.'">'.$data1->nazev_tymu.'</td>';
					$str .= '<td rowspan="'.$pocet_clenu.'">'.$data1->nazev_kategorie.'</td>';
					$str .= '<td>'.$data2->jmeno.'</td>';
					$str .= '<td class="text-center">'.$data2->rocnik.'</td>';
					$str .= '<td class="text-center">'.$data2->cip.'</td>';
					$str .= '</tr>';
				    }
				    else{
					$str .= '<tr>';
					$str .= '<td>'.$data2->jmeno.'</td>';
					$str .= '<td class="text-center">'.$data2->rocnik.'</td>';
					$str .= '<td class="text-center">'.$data2->cip.'</td>';
					$str .= '</tr>';
				    }
				    $k++;

				}
			    }

			}
			$str .= '</tbody></table>';
		    }
		}   
                
                /*
                 * 7.2.18 vytvoreno pro startovku winter hei run, jsou tu mírné odlišnosti od typu 2, není to tu vybírané podle ids_alias, což kdoví
                 * jaký byl tehdy záměr, ale podle ids
                 * je tu taky přidán čas startu
                 */
                
                elseif($this->racer_type == 6){ 

		    $sql1 = "SELECT tymy.nazev_tymu, $this->sqlkategorie.nazev_k AS nazev_kategorie,$this->sqlzavod.ids,$this->sqlzavod.cip FROM tymy,$this->sqlzavod,$this->sqlkategorie WHERE $this->sqlkategorie.poradi_podzavodu = '$this->event_order' AND $this->sqlzavod.tym = tymy.id_tymu AND $this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie GROUP BY $this->sqlzavod.ids ORDER BY $this->sqlzavod.ids ASC";
		    $sth1 =  $this->db->prepare($sql1);
		    $sth1->execute(Array(':event_order' => $this->event_order));
		    if($sth1->rowCount()){
			$pocet = $sth1->rowCount();
			$str .= '<h4 style="float:left" class="headline-results">'.$this->race_name.', startovní listina bez rozdílu kategorií</h4>';
			$str .= '<p style="text-align:right">Celkem '.$pocet.' týmů.</p>';
			$str .= '<table class="table table_vysledky">';
			//$str .= '<thead><tr class="header"><th class="text-center">#</th><th>Tým</th><th>Kategorie</th><th>Člen týmu</th><th class="text-center">Ročník</th><th class="text-center">Čip</th>';
			$str .= '<thead><tr class="header"><th class="text-center">#</th><th>Tým</th><th>Kategorie</th><th>Člen týmu</th><th>Disciplína</th><th class="text-center">Čip</th><th class="text-center">Startovní čas</th>';
			$str .= '</tr></thead><tbody>';
			while($data1 = $sth1->fetchObject()){
			    $sql2 = "SELECT CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,$this->sqlzavod.cip,$this->sqlzavod.vlna FROM osoby,$this->sqlzavod WHERE $this->sqlzavod.ids = :ids AND $this->sqlzavod.ido = osoby.ido ORDER BY $this->sqlzavod.cip";
			    $sth2 = $this->db->prepare($sql2);
			    $sth2->execute(Array(':ids' => $data1->ids));
			    if($sth2->rowCount()){
				$pocet_clenu = $sth2->rowCount();
				$k = 1;
				while($data2 = $sth2->fetchObject()){
				    if($k == 1){
					$str .= '<tr>';
					$str .= '<td class="text-center" rowspan="'.$pocet_clenu.'">'.$data1->ids.'</td>';
					$str .= '<td rowspan="'.$pocet_clenu.'">'.$data1->nazev_tymu.'</td>';
					$str .= '<td rowspan="'.$pocet_clenu.'">'.$data1->nazev_kategorie.'</td>';
					$str .= '<td>'.$data2->jmeno.'</td>';
					$str .= '<td class="text-center">'.$data2->rocnik.'</td>';
					$str .= '<td class="text-center">'.$data2->cip.'</td>';
					$str .= '<td class="text-center">'.$data2->vlna.'</td>';
					$str .= '</tr>';
				    }
				    else{
					$str .= '<tr>';
					$str .= '<td>'.$data2->jmeno.'</td>';
					$str .= '<td class="text-center">'.$data2->rocnik.'</td>';
					$str .= '<td class="text-center">'.$data2->cip.'</td>';
                                        $str .= '<td class="text-center">'.$this->StartovniCasVeVlne($data2->cip).'</td>';

					$str .= '</tr>';
				    }
				    $k++;

				}
			    }

			}
			$str .= '</tbody></table>';
		    }
		}
                
                
                

		
		
		


	    }
	    
	    
		if(isset($_GET['murinoha'])){ 
		    echo $str;
		}
		else{
		    return $str;
		}
	    }
	}
        
        
        private function StartovniCasVeVlne($cip){
            //$sql1 = "SELECT LEFT(SEC_TO_TIME(casovka+$this->startovni_cas),8) AS startovni_cas_vlny FROM $this->sqlzavod WHERE cip = $cip";
            $sql1 = "SELECT SEC_TO_TIME(casovka+$this->startovni_cas) AS startovni_cas_vlny FROM $this->sqlzavod WHERE cip = :cip";
            $sth1 = $this->db->prepare($sql1);
            $sth1->execute(Array(':cip' => $cip));
            if($sth1->rowCount()){
                $dbdata1 = $sth1->fetch(PDO::FETCH_ASSOC);
                return $dbdata1['startovni_cas_vlny'];
            }
                    
        }
        
	
	
	private function StartingListCategory(){
	    $str = '';
	    $colspan = 5;
	    $category_id = isset($_GET['category_id']) ? $_GET['category_id'] : 'all'; 
	    if($category_id == 'all'){
		$sql = "SELECT id_kategorie,kod_k AS kod_kategorie,nazev_k AS nazev_kategorie,poradi_podzavodu FROM $this->sqlkategorie WHERE id_zavodu = :race_id AND poradi_podzavodu = :event_order AND neviditelna_kategorie = 0 ORDER BY poradi";
		$sth = $this->db->prepare($sql);
		$sth->execute(Array(':race_id' => $this->race_id,':event_order' => $this->event_order));
		if($sth->rowCount()){
		    if($this->racer_type == 1){ 
		    $str .= '<table class="table table-striped table-bordered table-hover noborder table_vysledky">';
		    $k = 1;
		    while($data = $sth->fetchObject()){
			$sql1 = "SELECT $this->sqlzavod.ids_alias,CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,osoby.psc AS stat,$this->sqlkategorie.nazev_k AS nazev_kategorie,tymy.nazev_tymu FROM osoby,$this->sqlzavod,$this->sqlkategorie,tymy WHERE $this->sqlzavod.id_kategorie$this->cislo_kategorie = :id_kategorie AND $this->sqlzavod.id_kategorie$this->cislo_kategorie = $this->sqlkategorie.id_kategorie AND $this->sqlzavod.ido = osoby.ido AND tymy.id_tymu = $this->sqlzavod.prislusnost ORDER BY $this->sqlzavod.ids ASC";
			$sth1 =  $this->db->prepare($sql1);
			$sth1->execute(Array(':id_kategorie' => $data->id_kategorie));
			if($sth1->rowCount()){
			$pocet = $sth1->rowCount();
			    //$class = $k == 1 ? $class = 'nadpiss nopadding' : 'nadpiss';
			    $class = ($k == 1) ? ($class = 'nadpis nopadding') : ('nadpis');
			    $id = $k == 1 ? $id = ' id = "nopadding" ' : '';
			    $str .= '<tr><td '.$id.' class="'.$class.'" colspan="'.$colspan.'">'.$data->nazev_kategorie.', celkem '.$pocet.' závodníků.</td></tr>';
			    $str .= '<tr class="header"><th class="text-center">St.číslo</th><th>Jméno</th><th class="text-center">Ročník</th><th>Tým/Bydliště</th><th class="text-center">Stát</th></tr>';
			    $poradi = 1;
			    while($data1 = $sth1->fetchObject()){
				$str .= '<tr><td class="text-center">'.$data1->ids_alias.'</td><td>'.$data1->jmeno.'</td><td class="text-center">'.$data1->rocnik.'</td><td>'.$this->NazevTymu($data1->nazev_tymu).'</td><td class="text-center">'.$data1->stat.'</td></tr>';
			    }
			}
			$k++;
		    }
		    $str .= '</table>';
		   }
		    if($this->racer_type == 2 || $this->racer_type == 4){ 
			$str .= '<table class="table table-bordered noborder table_vysledky">';
			$ii = 1;
			while($data = $sth->fetchObject()){
			    $sql1 = "SELECT tymy.nazev_tymu, $this->sqlkategorie.nazev_k AS nazev_kategorie,$this->sqlzavod.ids_alias FROM tymy,$this->sqlzavod,$this->sqlkategorie WHERE $this->sqlzavod.tym = tymy.id_tymu AND $this->sqlkategorie.id_kategorie = '$data->id_kategorie' AND $this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie GROUP BY $this->sqlzavod.ids ORDER BY $this->sqlzavod.ids ASC";
			    $sth1 =  $this->db->prepare($sql1);
			    $sth1->execute(Array(':event_order' => $this->event_order,':category_id' => $_GET['category_id']));
			    if($sth1->rowCount()){
				$class = $ii == 1 ? $class = 'nadpis nopadding' : 'nadpis';
				$str .= '<tr><td  style="border:none" class="'.$class.'" colspan="'.$colspan.'">'.$data->nazev_kategorie.'</td></tr>';
				$str .= '<tr class="header"><th class="text-center">St.číslo</th><th>Tým</th><th>Kategorie</th><th>Členové týmu</th><th class="text-center">Ročník</th><th class="text-center">Čip</th></tr>';
				while($data1 = $sth1->fetchObject()){
				    $class = $ii == 1 ? $class = 'nadpis nopadding' : 'nadpis';
				    //$str .= '<tr><td  style="border:none" class="'.$class.'" colspan="'.$colspan.'">'.$data->nazev_kategorie.'</td></tr>';
				    //$str .= '<tr class="header"><th class="text-center">St.číslo</th><th>Tým</th><th>Kategorie</th><th>Členové týmu</th><th class="text-center">Ročník</th><th class="text-center">St.č</th></tr>';
				    $sql2 = "SELECT CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,$this->sqlzavod.cip FROM osoby,$this->sqlzavod WHERE $this->sqlzavod.ids = '$data1->ids_alias' AND $this->sqlzavod.ido = osoby.ido ORDER BY $this->sqlzavod.cip";
				    $sth2 = $this->db->prepare($sql2);
				    $sth2->execute();
				    if($sth2->rowCount()){
					$pocet_clenu = $sth2->rowCount();
					$k = 1;
					while($data2 = $sth2->fetchObject()){
					    if($k == 1){
						$str .= '<tr>';
						$str .= '<td class="text-center" rowspan="'.$pocet_clenu.'">'.$data1->ids_alias.'</td>';
						$str .= '<td rowspan="'.$pocet_clenu.'">'.$this->NazevTymu($data1->nazev_tymu).'</td>';
						$str .= '<td rowspan="'.$pocet_clenu.'">'.$data1->nazev_kategorie.'</td>';
						$str .= '<td>'.$data2->jmeno.'</td>';
						$str .= '<td class="text-center">'.$data2->rocnik.'</td>';
						$str .= '<td class="text-center">'.$data2->cip.'</td>';
						$str .= '</tr>';
					    }
					    else{
						$str .= '<tr>';
						$str .= '<td>'.$data2->jmeno.'</td>';
						$str .= '<td class="text-center">'.$data2->rocnik.'</td>';
						$str .= '<td class="text-center">'.$data2->cip.'</td>';
						$str .= '</tr>';
					    }
					    $k++;

					}
				    }

				}
			    }
			    $ii++;
		   }
		   $str .= '</table>';
		   } 
		}
	    }
	    else{
		if($this->racer_type == 1){
		    $sql1 = "SELECT $this->sqlzavod.ids_alias,CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,osoby.psc AS stat,$this->sqlkategorie.nazev_k AS nazev_kategorie,tymy.nazev_tymu FROM osoby,$this->sqlzavod,$this->sqlkategorie,tymy WHERE $this->sqlzavod.id_kategorie$this->cislo_kategorie = :category_id AND $this->sqlzavod.id_kategorie$this->cislo_kategorie = $this->sqlkategorie.id_kategorie AND $this->sqlzavod.ido = osoby.ido AND tymy.id_tymu = $this->sqlzavod.prislusnost ORDER BY $this->sqlzavod.ids ASC";
		    $sth1 =  $this->db->prepare($sql1);
		    $sth1->execute(Array(':category_id' => $_GET['category_id']));
		    if($sth1->rowCount()){
			$str .= '<table class="table table-striped table-bordered table-hover noborder table_vysledky">';
			$str .= '<thead><tr class="header"><th class="text-center">St.číslo</th><th>Jméno</th><th class="text-center">Ročník</th><th>Tým/Bydliště</th><th class="text-center">Stát</th></tr></thead>';
			$poradi = 1;
			while($data1 = $sth1->fetchObject()){
			    $str .= '<tr><td class="text-center">'.$data1->ids_alias.'</td><td>'.$data1->jmeno.'</td><td class="text-center">'.$data1->rocnik.'</td><td>'.$this->NazevTymu($data1->nazev_tymu).'</td><td class="text-center">'.$data1->stat.'</td></tr>';
			}
			$str .= '</table>'; 
		    }
		}
		elseif($this->racer_type == 2 || $this->racer_type == 4){
		    $sql1 = "SELECT tymy.nazev_tymu, $this->sqlkategorie.nazev_k AS nazev_kategorie,$this->sqlzavod.ids_alias FROM tymy,$this->sqlzavod,$this->sqlkategorie WHERE $this->sqlzavod.tym = tymy.id_tymu AND $this->sqlzavod.id_kategorie = :category_id AND $this->sqlzavod.id_kategorie = $this->sqlkategorie.id_kategorie GROUP BY $this->sqlzavod.ids ORDER BY $this->sqlzavod.ids ASC";
		   //echo $sql1;
		    $sth1 =  $this->db->prepare($sql1);
		    $sth1->execute(Array(':category_id' => $_GET['category_id']));
		    if($sth1->rowCount()){
			$str .= '<table class="table table_vysledky">';
			$str .= '<thead><tr class="header"><th class="text-center">#</th><th>Tým</th><th>Kategorie</th><th>Člen týmu</th><th class="text-center">Ročník</th><th class="text-center">Čip</th>';
			$str .= '</tr></thead><tbody>';
			while($data1 = $sth1->fetchObject()){
			    $sql2 = "SELECT CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,$this->sqlzavod.cip FROM osoby,$this->sqlzavod WHERE $this->sqlzavod.ids = '$data1->ids_alias' AND $this->sqlzavod.ido = osoby.ido ORDER BY $this->sqlzavod.cip";
			    $sth2 = $this->db->prepare($sql2);
			    $sth2->execute();
			    if($sth2->rowCount()){
				$pocet_clenu = $sth2->rowCount();
				$k = 1;
				while($data2 = $sth2->fetchObject()){
				    if($k == 1){
					$str .= '<tr>';
					$str .= '<td class="text-center" rowspan="'.$pocet_clenu.'">'.$data1->ids_alias.'</td>';
					$str .= '<td rowspan="'.$pocet_clenu.'">'.$data1->nazev_tymu.'</td>';
					$str .= '<td rowspan="'.$pocet_clenu.'">'.$data1->nazev_kategorie.'</td>';
					$str .= '<td>'.$data2->jmeno.'</td>';
					$str .= '<td class="text-center">'.$data2->rocnik.'</td>';
					$str .= '<td class="text-center">'.$data2->cip.'</td>';
					$str .= '</tr>';
				    }
				    else{
					$str .= '<tr>';
					$str .= '<td>'.$data2->jmeno.'</td>';
					$str .= '<td class="text-center">'.$data2->rocnik.'</td>';
					$str .= '<td class="text-center">'.$data2->cip.'</td>';
					$str .= '</tr>';
				    }
				    $k++;

				}
			    }

			}
			$str .= '</tbody></table>';
		    }

		}
	    }
	    echo $str;
	}
	
	private function CategoryListSelect(){
	    $fcdata = '';
	    $sql1 = "SELECT id_kategorie,nazev_k FROM $this->sqlkategorie WHERE id_zavodu = :race_id AND poradi_podzavodu = :event_order AND neviditelna_kategorie = 0 ORDER BY poradi";
	    $sth1 = $this->db->prepare($sql1);
	    $sth1->execute(Array(':race_id' => $this->race_id,':event_order' => $_GET['event_order']));
	    if($sth1->rowCount()){
		$fcdata.= '<select id="category_list" class="form-control input-lg">';
		if($sth1->rowCount() > 0) $fcdata .= '<option value="all">All</option>';
		while($dbdata1 = $sth1->fetchObject()){
		    $fcdata .= '<option value="'.$dbdata1->id_kategorie.'">'.$dbdata1->nazev_k.'</option>';
		}
		$fcdata.= '</select>'."\r\n";
	    }
	    echo $fcdata;
	}
    }

New StartovniListina();
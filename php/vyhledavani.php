<?php
    require_once '../libs/settings.php';
    
    class Vyhledavani extends Settings{
	
	private $race_name;
	private $race_code;
	private $sqlvysledky;
	private $sqlzavod;
	private $delka_kola;

	
	public function __construct(){
	    parent::__construct();
	    $sql1 = "SELECT $this->sqlzavody.kod_zavodu,$this->sqlzavody.delka_kola,$this->sqlzavody.nazev_zavodu FROM $this->sqlzavody WHERE $this->sqlzavody.id_zavodu = :race_id";
	    $sth1 = $this->db->prepare($sql1);
	    $sth1->execute(Array(':race_id' => $this->race_id));
	    if($sth1->rowCount()){
		$dbdata1 = $sth1->fetchObject();
		$this->race_name = $dbdata1->nazev_zavodu;
		$this->race_code = $dbdata1->kod_zavodu;
		$this->sqlvysledky = 'vysledky_'.$this->race_code.'_'.$this->race_year.'_test';
		$this->sqlzavod = 'zavod_'.$this->race_code.'_'.$this->race_year;
		$this->delka_kola = $dbdata1->delka_kola;
	    }
	    parent::__construct();
	    if(isset($_GET['murinoha'])){
		if(method_exists(get_class(),$_GET['murinoha'])){
		    $this->$_GET['murinoha']();
		}
	    }
	    else{
		$this->index();
	    }
	}

	    
	private function index(){
	    $fcdata = '<div class="panel panel-default panel-collapse">';
	    $fcdata .= '<div class="panel-body">';
	    $fcdata .= '<div class="row">
			<div class="col-lg-3">
		       <table id="table-keyboard" class="table table-default table-bordered table-keyboard">'
		      . '<tr>'
		      . '<td colspan="3" id="result_search_ids" class="result_search_ids"><span></span><div id="vycpavka">&nbsp;</div></td>'
		      . '</tr>'
		      . '<tr>'
		      . '<td class="keyboard_number">1</td>'
		      . '<td class="keyboard_number">2</td>'
		      . '<td class="keyboard_number">3</td>'
		      . '</tr>'
		      . '<tr>'
		      . '<td class="keyboard_number">4</td>'
		      . '<td class="keyboard_number">5</td>'
		      . '<td class="keyboard_number">6</td>'
		      . '</tr>'
		      . '<tr>'
		      . '<td class="keyboard_number">7</td>'
		      . '<td class="keyboard_number">8</td>'
		      . '<td class="keyboard_number">9</td>'
		      . '</tr>'
		      . '<tr>'
		      . '<td></td>'
		      . '<td class="keyboard_number">0</td>'
		      . '<td></td>'
		      . '</tr>'
			. '<tr>'
		      . '<td colspan="3" id="result_search_clear" class="result_search_clear">Smazat</td>'
		      . '</tr>'
			. '<tr>'
		      . '<td colspan="3" id="result_search_submit" class="result_search_submit">Vyhledat</td>'
		      . '</tr>'
		      . '</table>'
		      . '</div>';
		$fcdata .= '<div class="col-lg-9"><div id="result_table"></div></div></div>';	
	    
	    
	    $fcdata .= '</div>';
	    $fcdata .= '</div>';
	    echo json_encode($fcdata);
	}
	
	
	function ResultsSearch(){
	    $str = '';
	    $str2 = '';
	    /*
	     * velmi provizorní varianta na Radegastovu výzvu, nebvyl ani čas, ani vůle
	     */
	    if($this->race_id == 10){
		$sql1 = "SELECT tymy.nazev_tymu,"
		      . "$this->sqlvysledky.ids,"
		      . "MAX($this->sqlvysledky.race_time) AS total_time,"
		      . "MAX($this->sqlvysledky.lap_count) AS lap_count,"
		      . "$this->sqlkategorie.nazev_k AS nazev_kategorie "
		      . "FROM $this->sqlvysledky,tymy,$this->sqlzavod,$this->sqlkategorie "
		      . "WHERE "
		      . "race_time > 0 AND "
		      . "$this->sqlzavod.cip = $this->sqlvysledky.cip AND "
		      . "$this->sqlzavod.cip = {$_GET['search_val']} AND "
		      . "$this->sqlzavod.tym = tymy.id_tymu AND "
		      . "$this->sqlzavod.id_kategorie = $this->sqlzavod.id_kategorie AND "
		      . "$this->sqlvysledky.false_time IS NULL AND "
		      . "$this->sqlvysledky.lap_only IS NULL";
		      $sth1 = $this->db->prepare($sql1);
		   // $sth1->execute(Array(':ids' => $_GET['search_val'],':race_id' => $this->race_id));
		
		$sth1->execute();
		$dbdata1 = $sth1->fetchObject();
		if($dbdata1->lap_count > 0){
		    $str .= '<div class="row">'
			. '<div class="col-lg-12">'
			. '<div class="jumbotron">'
			. '<h1>'.$dbdata1->nazev_tymu.'</h1>'
			. '<table class="table" style="width:auto;font-size:18px">'
			. '<tr><td>Startovní číslo:</td><td class="text-right">'.$_GET['search_val'].'</td></tr>'
			. '<tr><td>Kategorie:</td><td class="text-right">'.$dbdata1->nazev_kategorie.'</td></tr>';
		    $sql2 = "SELECT CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno FROM osoby,$this->sqlzavod WHERE $this->sqlzavod.ids =  {$_GET['search_val']} AND osoby.ido = $this->sqlzavod.ido ORDER BY $this->sqlzavod.id";
		    $sth2 = $this->db->prepare($sql2);
		    $sth2->execute();
		    if($sth2->rowCount()){
			$str .= '<tr><td>Členové:</td><td>';
			$i = 1;
			while($dbdata2 = $sth2->fetchObject()){
			    $carka = '';
			    if($i > 1){
				$carka = ', ';
			    }
			    $str.= $carka.$dbdata2->jmeno;
			    $i++;
			}
			$str .= '<td></tr>';
		    }
		    $str .=  '<tr><td>Počet kol:</td><td class="text-right">'.$dbdata1->lap_count.'</td></tr>';
		    $str .=  '<tr><td>Celkový čas:</td><td class="text-right">'.substr($dbdata1->total_time,0,-3).'</td></tr>';
		    $celkova_vzdalenost = $dbdata1->lap_count * $this->delka_kola;
		    $str .=  '<tr><td>Celková vzdálenost:</td><td class="text-right">'.$celkova_vzdalenost.'</td></tr>';
		    $str .= '</table>';
		    $str .= '</div>';
		    $str .= '</div>';
		    $str .= '</div>';
		}
		
		$sql1 = "SELECT $this->sqlvysledky.* FROM $this->sqlvysledky "
		. "WHERE "
		. "$this->sqlvysledky.false_time IS NULL AND "
		. "$this->sqlvysledky.cip = :cip "
		. "ORDER BY race_time";
		$sth1 = $this->db->prepare($sql1);
		$sth1->execute(Array(':cip' => $_GET['search_val']));
		$posledni_kolo = false;
		if($sth1->rowCount()){
		    $row_count = $sth1->rowCount();
		    $str .= '<table class="table table-bordered table-hover  table-striped table_vysledky text-center" style="font-size:18px">';
		    $str .= '<thead><tr class="header"><th class="text-center">Kolo</th><th class="text-center">Čas kola</th><th class="text-center">Čas doběhu</th><th class="text-center">Čas závodu</th><th class="text-center">Km</th></tr></thead><tbody>';
		   $poradi = 1;
	
		    while($dbdata1 = $sth1->fetchObject()){
			 $str .= '<tr>';
			 $str .= '<td>'.$dbdata1->lap_count.'</td>';
			 $str .= '<td>'.$dbdata1->lap_time.'</td>';
			 $str .= '<td>'.$dbdata1->day_time.'</td>';
			 $str .= '<td>'.$dbdata1->race_time.'</td>';
			 $str .= '<td>'.($this->delka_kola * $dbdata1->lap_count).'</td>';
			 $str .= '</tr>';
			 $poradi++;
		     }
		}
	    }
	    
            
            
           //jen TeriBear Boleslav 
            elseif($this->race_id == 75 AND $this->race_year == 2016 OR $this->race_id == 45 AND $this->race_year == 2016){
                       $sql1 = "SELECT COUNT($this->sqlvysledky.id) AS pocet_kol,COUNT($this->sqlvysledky.id) * $this->delka_kola AS pocet_km,$this->sqlzavod.cip,{$_GET['search_val']} AS hledane_cislo,$this->sqlzavod.ids_alias,CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,osoby.psc AS stat,$this->sqlkategorie.nazev_k AS nazev_kategorie,tymy.nazev_tymu "
                            . "FROM osoby,$this->sqlzavod,$this->sqlkategorie,tymy,$this->sqlvysledky "
                            . "WHERE "
                                . "$this->sqlzavod.cip = {$_GET['search_val']} AND "
                                . "$this->sqlzavod.ido = osoby.ido AND "
                                . "$this->sqlzavod.prislusnost = tymy.id_tymu AND "
                                . "$this->sqlzavod.id_kategorie = $this->sqlkategorie.id_kategorie AND "
                                . "$this->sqlvysledky.cip = $this->sqlzavod.cip AND "
                                . "$this->sqlvysledky.false_time IS NULL";
                                
                
                        $sth1 = $this->db->prepare($sql1);
                        $sth1->execute(Array(':ids' => $_GET['search_val'],':race_id' => $this->race_id));
                        if($sth1->rowCount()){
                            $dbdata1 = $sth1->fetchObject();
                            echo json_encode($dbdata1);
                        }
                        
                        
                
            }
            
            
            
            
            else{
		
                
                $sql1 = "SELECT COUNT($this->sqlvysledky.id) AS pocet_casu,$this->sqlzavod.cip,$this->sqlzavod.ids_alias,CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,osoby.psc AS stat,$this->sqlkategorie.nazev_k AS nazev_kategorie,tymy.nazev_tymu,$this->sqlpodzavody.pocet_casu AS time_count "
		      . "FROM osoby,$this->sqlzavod,$this->sqlkategorie,tymy,$this->sqlvysledky,$this->sqlpodzavody "
		      . "WHERE "
		      . "$this->sqlzavod.ids = :ids AND "
		      //. "$this->sqlzavod.cip = :ids AND "
		      . "$this->sqlzavod.poradi_podzavodu = $this->sqlpodzavody.poradi_podzavodu AND "
		      . "$this->sqlpodzavody.id_zavodu = :race_id AND "
		      . "$this->sqlzavod.ido = osoby.ido AND "
		      . "$this->sqlzavod.prislusnost = tymy.id_tymu AND "
		      . "$this->sqlzavod.id_kategorie = $this->sqlkategorie.id_kategorie AND "
		      . "$this->sqlvysledky.ids = $this->sqlzavod.ids AND "
		      . "$this->sqlvysledky.false_time IS NULL";
		$sth1 = $this->db->prepare($sql1);
		$sth1->execute(Array(':ids' => $_GET['search_val'],':race_id' => $this->race_id));
		$dbdata1 = $sth1->fetchObject();
		if($dbdata1->pocet_casu > 0){
		    $str .= '<div class="row">'
				. '<div class="col-lg-12">'
				. '<div class="jumbotron">'
				. '<h1>'.$dbdata1->jmeno.'</h1>'
				. '<table class="table" style="width:auto;font-size:18px">'
				. '<tr><td>Startovní číslo:</td><td class="text-right">'.$_GET['search_val'].'</td></tr>'
			        . '<tr><td>Tým/Bydliště:</td><td class="text-right">'.$dbdata1->nazev_tymu.'</td></tr>'
				. '<tr><td>Ročník:</td><td class="text-right">'.$dbdata1->rocnik.'</td></tr>'
				. '<tr><td>Kategorie:</td><td class="text-right">'.$dbdata1->nazev_kategorie.'</td></tr>';
		    if($dbdata1->pocet_casu < $dbdata1->time_count){
			$str .= '</table';

		    }
		    else{
			$sql3 = "SELECT * FROM $this->sqlvysledky WHERE cip = $dbdata1->cip AND time_order = $dbdata1->time_count";
			$sth3 = $this->db->prepare($sql3);
			$sth3->execute();
			if($sth3->rowCount()){
			    $dbdata3 = $sth3->fetchObject();
			    $str .= '<tr><td>Celkový čas:</td><td class="text-right">'.$dbdata3->race_time.'</td></tr>'
			      . '<tr><td>Celkové pořadí:</td><td class="text-right">'.$dbdata3->rank_overall.'</td></tr>'
			      . '<tr><td>Pořadí v kategorii:</td><td class="text-right">'.$dbdata3->rank_category.'</td></tr>'
			      . '<tr><td>Pořadí Muži/Ženy:</td><td class="text-right">'.$dbdata3->rank_gender.'</td></tr>'
			      . '</table>'
			      . '</div>'
			      . '</div>'
			      . '</div>';
			}
		    }

		    //tady by to měl být diferencováno  
		    $sql4 = "SELECT nazev_discipliny FROM $this->sqldiscipliny WHERE id_zavodu = :race_id AND poradi_podzavodu = 1 ORDER BY poradi_discipliny";
		    //$str .= $sql3;
		    $sth4 = $this->db->prepare($sql4);
		    $sth4->execute(Array(':race_id' => $this->race_id));
		    if($sth4->rowCount()){
			$dbdata4 = $sth4->fetchAll();
		    }

                     // zakomentovano na lednici
		    $sql5 = "SELECT * FROM $this->sqlvysledky WHERE cip = :cip AND false_time IS NULL ORDER BY time_order";
		    //echo $sql5;
		    $sth5 = $this->db->prepare($sql5);
		    $sth5->execute(Array(':cip' => $dbdata1->cip));
		    if($sth5->rowCount()){
			$i = 1;
			$str .= '<div class="row">';
			while($dbdata5 = $sth5->fetchObject()){
			    if(isset($dbdata4[$i-1]['nazev_discipliny'])){
                               // zakomentovano ne behej lesy kerlstejn
                                
                                
				$str .= '<div class="col-lg-3">'
				      . '<div class="panel panel-default">'
					. '<div class="panel-heading">'.$dbdata4[$i-1]['nazev_discipliny'].'</div>'
					    . '<div class="panel-body">'
						. '<table class="table">'
						    . '<tr><td>Čas '.$dbdata4[$i-1]['nazev_discipliny'].'</td><td class="text-right">'.$dbdata5->lap_time.'</td></tr>'
						    . '<tr><td>Celkové pořadí v '.$dbdata4[$i-1]['nazev_discipliny'].'</td><td class="text-right">'.$dbdata5->rank_overall_lap.'</td></tr>'
						    . '<tr><td>Pořadí v kategorii v '.$dbdata4[$i-1]['nazev_discipliny'].'</td><td class="text-right">'.$dbdata5->rank_category_lap.'</td></tr>'
						    . '<tr><td>Ztráta celkově v '.$dbdata4[$i-1]['nazev_discipliny'].'</td><td class="text-right">'.$this->NahradaNulovehoCasu($dbdata5->distance_overall_lap).'</td></tr>'
						    . '<tr><td>Ztráta v kategorii v '.$dbdata4[$i-1]['nazev_discipliny'].'</td><td class="text-right">'.$this->NahradaNulovehoCasu($dbdata5->distance_category_lap).'</td></tr>'
						    . '<tr><td>Čas po '.$dbdata4[$i-1]['nazev_discipliny'].'</td><td class="text-right">'.$dbdata5->race_time.'</td></tr>'
						    . '<tr><td>Pořadí po '.$dbdata4[$i-1]['nazev_discipliny'].'</td><td class="text-right">'.$dbdata5->rank_overall.'</td></tr>'
						    . '<tr><td>Pořadí v kategorii po '.$dbdata4[$i-1]['nazev_discipliny'].'</td><td class="text-right">'.$dbdata5->rank_category.'</td></tr>'
						    . '<tr><td>Ztráta celkově po '.$dbdata4[$i-1]['nazev_discipliny'].'</td><td class="text-right">'.$this->NahradaNulovehoCasu($dbdata5->distance_overall).'</td></tr>'
						    . '<tr><td>Ztráta v kategorii po '.$dbdata4[$i-1]['nazev_discipliny'].'</td><td class="text-right">'.$this->NahradaNulovehoCasu($dbdata5->distance_category).'</td></tr>'
						. '</table>'
					    . '</div>'
					. '</div>'
				      . '</div>';
			    $i++;
			    }
			}
			$str .= '</div>';
		}
                
                
	    }
	else{
	    $str .= '<p>Žádný výsledek</p>';
	}

	    }
	    
	    
	echo $str;

    }
    
	private function NahradaNulovehoCasu($cas){
	    $str = $cas;
	    if($cas == '00:00:00.00'){
		$str = '-';
	    }
	    return $str;
	}
    }
    
    New Vyhledavani();
?>
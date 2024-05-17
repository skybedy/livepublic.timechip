<?php
    require_once '../libs/settings.php';
    class DetailIds extends Settings{
	private $sqlvysledky;
	private $sqlzavod;
	private $race_code;
	private $race_name;
	private $ruzny_pocet_casu;
	private $results_type;
	private $event_order;
	private $time_count;
	private $time_order;
	private $racer_type;
	private $team_racer_count;
	

	public function __construct(){
	    parent::__construct();
	    $this->event_order = isset($_GET['event_order']) ? $_GET['event_order'] : 1; 
	    $sql1 = "SELECT $this->sqlzavody.kod_zavodu,$this->sqlzavody.nazev_zavodu,$this->sqlzavody.pocet_podzavodu,$this->sqlzavody.ruzny_pocet_casu,$this->sqlpodzavody.* FROM $this->sqlzavody,$this->sqlpodzavody WHERE $this->sqlzavody.id_zavodu = :race_id AND $this->sqlpodzavody.id_zavodu = $this->sqlzavody.id_zavodu AND $this->sqlpodzavody.poradi_podzavodu = :event_order";
	    $sth1 =  $this->db->prepare($sql1);
	    $sth1->execute(array(':race_id' => $this->race_id,':event_order' => $this->event_order));
	    $dbdata1 =  $sth1->fetchObject();
	    $this->race_name = $dbdata1->nazev_zavodu;
	    $this->time_count = $dbdata1->pocet_casu;
	    $this->event_count = $dbdata1->pocet_podzavodu;
	    $this->race_code = $dbdata1->kod_zavodu;
	    $this->sqlvysledky = 'vysledky_'.$this->race_code.'_'.$this->race_year.'_test';
	    $this->sqlzavod = 'zavod_'.$this->race_code.'_'.$this->race_year;
	    $this->ruzny_pocet_casu = $dbdata1->ruzny_pocet_casu;
	    $this->results_type = $dbdata1->typ_vysledku;
	    $this->racer_type = $dbdata1->typ_zavodnika;
	    $this->team_racer_count = $dbdata1->pocet_clenu_tymu; 
	    $this->time_order = isset($_GET['time_order']) ? $_GET['time_order'] : $this->MaxTimeOrder(); 
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
	    echo json_encode($fcdata);
	}
	
	
}

$neco = New DetailIds();
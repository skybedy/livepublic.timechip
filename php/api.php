<?php
    require_once '../libs/settings.php';
    class Vysledky extends Settings{
	private $sqlvysledky;
	private $sqlzavod;
	private $race_code;
	private $race_name;
	

	public function __construct(){
	    parent::__construct();
	    $this->event_order = isset($_GET['event_order']) ? $_GET['event_order'] : 1; 
	    
	    $sql1 = "SELECT $this->sqlzavody.kod_zavodu,$this->sqlzavody.delka_kola,$this->sqlzavody.nazev_zavodu,$this->sqlzavody.pocet_podzavodu,$this->sqlzavody.etapy,$this->sqlzavody.ruzny_pocet_casu,$this->sqlpodzavody.*,reklamy_na_vysledky.* FROM $this->sqlzavody,$this->sqlpodzavody,reklamy_na_vysledky WHERE $this->sqlzavody.id_zavodu = :race_id AND $this->sqlpodzavody.id_zavodu = $this->sqlzavody.id_zavodu AND $this->sqlpodzavody.poradi_podzavodu = :event_order";
	    $sth1 =  $this->db->prepare($sql1);
	    $sth1->execute(array(':race_id' => $this->race_id,':event_order' => $this->event_order));
	    $dbdata1 =  $sth1->fetchObject();
	    $this->race_name = $dbdata1->nazev_zavodu;
	    $this->time_count = $dbdata1->pocet_casu;
	    $this->event_count = $dbdata1->pocet_podzavodu;
	    $this->race_code = $dbdata1->kod_zavodu;
	    $this->sqlvysledky = 'vysledky_'.$this->race_code.'_'.$this->race_year.'_test';
	    $this->sqlzavod = 'zavod_'.$this->race_code.'_'.$this->race_year;
	    
	    $this->hash_url = '#'.$this->race_year.'/'.$this->race_id.'/';
	    $this->delka_kola = $dbdata1->delka_kola; 
	    $this->etapy = $dbdata1->etapy; 
	    
	    if(isset($_GET['murinoha'])){
		if(method_exists(get_class(),$_GET['murinoha'])){
		    $this->$_GET['murinoha']();
		}
	    }
	    else{
		$this->index();
	    }
	 }
	 
	 
	 
	 public function Result(){
	     
	 
	     
	     
	     
	 }
	
	


	
	


}

$neco = New Vysledky();
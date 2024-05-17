<?php
    require_once '../libs/settings.php';
    class PraveTed extends Settings{
	private $sqlvysledky;
	private $sqlzavod;
	private $race_code;
	private $race_name;
	private $hash_url;
	private $delka_kola;
	private $autoreading_output;
	

	public function __construct(){
	    parent::__construct();
	    $sql1 = "SELECT $this->sqlzavody.kod_zavodu,$this->sqlzavody.delka_kola,$this->sqlzavody.nazev_zavodu,$this->sqlzavody.autoreading_output FROM $this->sqlzavody WHERE $this->sqlzavody.id_zavodu = :race_id";
	    $sth1 =  $this->db->prepare($sql1);
	    $sth1->execute(array(':race_id' => $this->race_id));
	    $dbdata1 =  $sth1->fetchObject();
	    $this->race_code = $dbdata1->kod_zavodu;
	    $this->sqlvysledky = 'vysledky_'.$this->race_code.'_'.$this->race_year.'_test';
	    $this->sqlzavod = 'zavod_'.$this->race_code.'_'.$this->race_year;
	    //$this->time_order = isset($_GET['time_order']) ? $_GET['time_order'] : $this->MaxTimeOrder(); 
	    $this->hash_url = '#'.$this->race_year.'/'.$this->race_id.'/';
	    $this->delka_kola = $dbdata1->delka_kola; 
	    $this->autoreading_output = $dbdata1->autoreading_output;

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
	    $fcdata = '';
	    $fcdata = '<div class="panel panel-default panel-collapse">'
			. '<div class="panel-body">'
			    . '<div id="panel_content"></div>'
			. '</div>'
		      . '</div>';
	    echo json_encode($fcdata);
	}
	
	private function ResultsAutoreading(){
	    $str = Array();
	    $change_control_file_info = $this->ChangeControlFileInfo();
	    require_once 'results_autoreading/type_'.$this->autoreading_output.'.php';

	    $str['results'] = $autoreading_results;
	    $str['change_control_file'] = $change_control_file_info['change_control_file'];
	    $str['last_modified'] = $change_control_file_info['last_modified'];
	    echo json_encode($str);
	}

	
	private function ChangeControlFileInfo(){
	    $str = Array();
	    $hlavicky = (get_headers(CHANGE_CONTROL_FILE_URL_PATH));
	    $x = explode("Last-Modified: ",$hlavicky[3]);
	    $str['change_control_file'] = CHANGE_CONTROL_FILE_URL_PATH;
	    $str['last_modified'] = strtotime($x[1]);
	    return $str;
	}
	
	private function MaxTimeOrder() {  //tady se musí dodělat podzávody
	    $sql1 = "SELECT MAX(time_order) AS max_time_order FROM $this->sqlvysledky";
	    $sth1 = $this->db->query($sql1);
	    $data1 = $sth1->fetchObject();
	    return $data1->max_time_order;
	}
    
    private function BestTime($time_order){
	    $sql1 = "SELECT MIN($this->sqlvysledky.race_time) AS best_time FROM $this->sqlvysledky WHERE time_order = :time_order AND false_time IS NULL";
	    $sth1 = $this->db->prepare($sql1);
	    $sth1->execute(Array(':time_order' => $time_order));
	    $dbdata1 = $sth1->fetchObject();
	    return $dbdata1->best_time;
	}
    private function NullsReplacement($time){
	    ($time == '00:00:00.00') ? ($fcdata = '-') : ($fcdata = $time);
	    return $fcdata;
	}
}
New PraveTed();
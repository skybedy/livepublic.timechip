<?php
    require_once '../libs/settings.php';
    class Komponenty extends Settings{
	
	public function __construct(){
	    parent::__construct();
	    if(isset($_GET['murinoha'])){
		if(method_exists(get_class(),$_GET['murinoha'])){
		    $this->$_GET['murinoha'];
		}
		else{
		  $this->index();  
		}
	    }
	    else{
		$this->index();
	    }	
	}
	
	private function index(){
	    $fcdata = '';
	    $sql1 = "SELECT * FROM $this->sqlzavody WHERE id_zavodu = '{$this->race_id}'";
	    $sth1 = $this->db->prepare($sql1);
	    $sth1->execute();
	    $fcdata['navbar_menu'] = $this->navbar_menu();
	    //$fcdata['basic_race_info'] = $sth1->fetchObject(); 
	    $fcdata['race_select'] = $this->race_select(); 
	    $fcdata['race_year'] = $this->race_year(); 
	    echo json_encode($fcdata);
	}

	
	
	private function race_select(){
	    $fcdata = '<option value="" selected disabled>Vyběr závodu</option>';  
	    $sql1 = "SELECT id_zavodu, nazev_zavodu FROM $this->sqlzavody WHERE online_results > 0 ORDER BY datum_zavodu";
	    $sth1 = $this->db->prepare($sql1);
	    $sth1->execute();
	    while($db_data = $sth1->fetchObject()){
		$fcdata .= '<option value="'.$db_data->id_zavodu.'"';
		//$fcdata .= ($db_data->id_zavodu == $this->race_id) ? (' selected = "selected"') : ('');
		$fcdata .= '>'.$db_data->nazev_zavodu.'</option>';
	    }
	    return $fcdata;  
	}  
	
	private function race_year(){
	   $fcdata = '';
	   $i = $this->first_year;
	   while($i <= $this->current_year){
	       $fcdata .= '<option value="'.$i.'"';
	       $fcdata .= ($i == $this->race_year) ? (' selected = "selected"') : ('');
	       $fcdata .= '>'.$i.'</option>';
	       $i++;
	   }
	   return $fcdata;
	}
	
	
	private function navbar_menu(){
	    $fcdata = '';
	    $hash_url = '#'.$this->race_year.'/'.$this->race_id.'/';
	    $fcdata .= '<li class="nav navbar-nav"><a class="menu" href="'.$hash_url.'startovni-listina">Startovka</a></li>';
	    //$fcdata .= '<li class="nav navbar-nav"><a class="menu" href="'.$hash_url.'prave-ted">PRÁVĚ TĚĎ</a></li>';
	    $fcdata .= '<li class="nav navbar-nav"><a class="menu" href="'.$hash_url.'vysledky">Výsledky</a></li>';
	  //$fcdata .= '<li class="nav navbar-nav"><a class="menu" href="'.$hash_url.'vyhledavani">Vyhledávání</a></li>';
	    return $fcdata;
	}
}
New Komponenty();
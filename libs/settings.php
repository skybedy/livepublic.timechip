<?php
    session_start();
    class Database extends PDO{
	public function __construct($DB_TYPE,$DB_HOST,$DB_NAME,$DB_USER,$DB_PASS) {
	    parent::__construct($DB_TYPE.':host='.$DB_HOST.';dbname='.$DB_NAME,$DB_USER, $DB_PASS);
	 }
     }

    class Settings {
	public $db;
	public $race_year;
	public $race_id;
	public $first_year = 2016;
	public $current_year;
	public $sqlzavody;
	public $sqlpodzavody;
	public $sqlkategorie;
	public $sqlsourcefile;
	public $sqlmenuvysledky;
	public $sqlrozjizdky;
	public $sqlosoby;
	public $sqldnf;
	
	
	public function __construct() {
	    require_once '../config.php';
	    $this->current_year = date("Y");
	    isset($_GET['race_year']) ? $this->race_year = $_GET['race_year'] : $this->race_year = $this->current_year;
	    isset($_GET['race_id']) ? $this->race_id = $_GET['race_id'] : $this->race_id = NULL;
	    $this->db = New Database(DB_TYPE,DB_HOST,DB_NAME,DB_USER,DB_PASS);
	    $this->db->query('SET NAMES utf8mb4');
	    $this->sqlzavody = 'zavody_'.$this->race_year;
	    $this->sqlpodzavody = 'podzavody_'.$this->race_year;
	    $this->sqlrozjizdky = 'rozjizdky_'.$this->race_year;
	    $this->sqlkategorie = 'kategorie_'.$this->race_year;
	    $this->sqlsourcefile = 'sourcefile_'.$this->race_year;
	    $this->sqlmenuvysledky = 'menu_vysledky_'.$this->race_year;
	    $this->sqldiscipliny = 'discipliny_'.$this->race_year.'_test';
	    $this->sqlosoby = 'osoby';
	    $this->sqldnf = 'dnf_'.$this->race_year;
	}
     }
     
 ?>    
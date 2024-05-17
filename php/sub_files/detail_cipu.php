
<?php 
	require_once '../../config.php';
	class Database extends PDO{
	public function __construct($DB_TYPE,$DB_HOST,$DB_NAME,$DB_USER,$DB_PASS) {
	    parent::__construct($DB_TYPE.':host='.$DB_HOST.';dbname='.$DB_NAME,$DB_USER, $DB_PASS);
	 }
     }

    class DetailCipu {
	private $db;
	private $race_year;
	private $race_id;
	private $sqlzavody;
	private $sqlkategorie;
	private $race_code;
	private $sqlzavod;
	private $delka_kola;
	
	
	public function __construct() {
	    $this->race_year = $_GET['race_year'];
	    $this->race_id = $_GET['race_id'];
	    $this->db = New Database(DB_TYPE,DB_HOST,DB_NAME,DB_USER,DB_PASS);
	    $this->db->query('SET NAMES utf8');
	    $this->sqlzavody = 'zavody_'.$this->race_year;
	    $this->sqlkategorie = 'kategorie_'.$this->race_year;
	    $sql = "SELECT *,SEC_TO_TIME(startovni_cas) AS start_time FROM $this->sqlzavody WHERE id_zavodu = '$this->race_id'";
	    $sth = $this->db->query($sql);
	    $sth->execute();
	    $dbdata = $sth->fetchObject();
	    $this->delka_kola = $dbdata->delka_kola;
	    $this->race_code = $dbdata->kod_zavodu;
	    $this->sqlvysledky = 'vysledky_'.$this->race_code.'_'.$this->race_year.'_test';
	    $this->sqlzavod = 'zavod_'.$this->race_code.'_'.$this->race_year;
	    $str = '';
	    $sql2 = "SELECT CONCAT_WS(' ',osoby.jmeno,osoby.prijmeni) AS jmeno,nazev_tymu,MAX($this->sqlvysledky.race_time) AS total_time,COUNT($this->sqlvysledky.id) AS lap_count,SEC_TO_TIME(SUM($this->sqlvysledky.lap_time_sec)) AS total_lap_time,SUM($this->sqlvysledky.lap_time_sec) AS total_lap_time_sec,"
		      . "SEC_TO_TIME(MAX($this->sqlvysledky.race_time_sec) - SUM($this->sqlvysledky.lap_time_sec)) AS total_depo_time FROM $this->sqlzavod,osoby,tymy,$this->sqlvysledky WHERE $this->sqlzavod.cip = :cip AND $this->sqlzavod.ido = osoby.ido AND $this->sqlzavod.tym = tymy.id_tymu"
		      . " AND $this->sqlzavod.cip = $this->sqlvysledky.cip AND $this->sqlzavod.ids = $this->sqlvysledky.ids AND $this->sqlvysledky.reader LIKE 'CIL' AND false_time IS NULL";
	    //echo $sql2;
	    $sth2 = $this->db->prepare($sql2);
	    $sth2->execute(Array(':cip' => $_GET['cip']));
	    $dbdata2 = $sth2->fetchObject();
?>	    
   <!DOCTYPE html>
	<html lang="cs" dir="ltr">
	<head>
	    
	    <title><?php echo $_GET['cip'].' - '.$dbdata2->jmeno.', '.$dbdata2->nazev_tymu ?></title>
	    <meta charset="UTF-8" />
	    <meta http-equiv="refresh" content="60">
	    <link rel="stylesheet" href="../../css/bootstrap.min.css" media="screen" />
	    <link rel="stylesheet" href="../../css/default2.css" media="screen" />
	    <link rel="stylesheet"  href="../css/print.css" media="print" />
	    <script type="text/javascript" src="../../js/jquery-1.11.0.min.js"></script>
	    <script type="text/javascript" src="../../js/bootstrap.min.js"></script>
	 </head>
	<body style="padding-top: 0">
	<div id="content" class="container-fluid">
	    
    <?php	    
	    
    
            $cip = $_GET['cip'];

	    $str .= '<h3>'.$cip.' | '.$dbdata2->jmeno.' | '.$dbdata2->nazev_tymu.'</h3>';
	    if($dbdata2->lap_count > 0){
		$celkova_vzdalenost = $dbdata2->lap_count * $this->delka_kola;
		$str .= '<p>Počet kol: <span class="color_red">'.$dbdata2->lap_count.'</span><br />';
		$str .= 'Celková vzdálenost: <span class="color_red">'.$celkova_vzdalenost.' km</span><br />';
		$str .= 'Průměrná rychlost: <span class="color_red">'.round($celkova_vzdalenost / $dbdata2->total_lap_time_sec * 3600,1).' km/h</span><br />';
		$str .= 'Celkový čas na trati: <span class="color_red">'.$dbdata2->total_lap_time.'</span><br />';
		$str .= 'Celkový čas v depu: <span class="color_red">'.$dbdata2->total_depo_time.'</span><br />';
		$str .= 'Celkový čas závodu: <span class="color_red">'.$dbdata2->total_time.'</span>';
		$str .= '</p><br />';
	    }
	    
	    
	    
	    $sql1 = "SELECT $this->sqlvysledky.* FROM $this->sqlvysledky,$this->sqlzavod WHERE $this->sqlvysledky.false_time IS NULL AND $this->sqlvysledky.cip = :cip AND $this->sqlvysledky.cip = $this->sqlzavod.cip ORDER BY race_time";
			  //echo $sql1;
	    $sth1 = $this->db->prepare($sql1);
	    $sth1->execute(Array(':cip' => $_GET['cip']));
	    if($sth1->rowCount()){
		$row_count = $sth1->rowCount();
		$str .= '<table class="table table-bordered table-hover table-striped table_vysledky text-center detail_ids">';
		$str .= '<thead><tr class="header"><th class="text-center">Kolo</th><th class="text-center">Čas startu</th><th class="text-center">Čas dojezdu</th><th class="text-center">Čas kola</th><th class="text-center">Čas závodu</th><th class="text-center">Km</th><th class="text-center">Km/h</th></tr></thead><tbody>';
	       $poradi = 1;
	       $posledni_kolo = false;
		while($dbdata1 = $sth1->fetchObject()){
		    if($dbdata1->lap_time_sec > 0){
			$prumerna_rychlost = round($this->delka_kola/$dbdata1->lap_time_sec*3600,1);
		    }
			if($dbdata1->reader == 'START'){
			    $day_time = $dbdata1->day_time;
			}
			else{
			    if($poradi == 1) $day_time = $dbdata->start_time.'.00';
			    $str .= '<tr>';
			    $str .= '<td>'.$dbdata1->lap_count.'</td>';
			    $str .= '<td>'.$day_time.'</td>';
			    $str .= '<td>'.$dbdata1->day_time.'</td>';
			    $str .= '<td>'.$dbdata1->lap_time.'</td>';
			    $str .= '<td>'.$dbdata1->race_time.'</td>';
			    $str .= '<td>'.($this->delka_kola * $dbdata1->lap_count).'</td>';
			    $str .= '<td>'.$prumerna_rychlost.'</td>';
			    $str .= '</tr>';
			    $posledni_kolo = $dbdata1->lap_count;
			}
			if($poradi == $row_count){
			    if($dbdata1->reader == 'START'){
				$str .= '<tr>';
				$str .= '<td>'.($posledni_kolo + 1).'</td>';
				$str .= '<td>'.$dbdata1->day_time.'</td>';
				$str .= '<td></td>';
				$str .= '<td></td>';
				$str .= '<td></td>';
				$str .= '<td></td>';
				$str .= '<td></td>';
				$str .= '</tr>';
			    }
			}
		    $poradi++;
		}
		$str .= '</tbody></table>';
	    }
	    echo $str;
	    
	 }
     }
     New DetailCipu();

?>
    
 </div><!-- /.container -->
</body>
</html>	

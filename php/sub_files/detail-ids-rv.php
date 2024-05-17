<?php	
    require_once '../../config.php';
	class Database extends PDO{
	public function __construct($DB_TYPE,$DB_HOST,$DB_NAME,$DB_USER,$DB_PASS) {
	    parent::__construct($DB_TYPE.':host='.$DB_HOST.';dbname='.$DB_NAME,$DB_USER, $DB_PASS);
	 }
     }

    class DetailIds {
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
	    $sql = "SELECT * FROM $this->sqlzavody WHERE id_zavodu = '$this->race_id'";
	    $sth = $this->db->query($sql);
	    $sth->execute();
	    $dbdata = $sth->fetchObject();
	    $this->delka_kola = $dbdata->delka_kola;
	    $this->race_code = $dbdata->kod_zavodu;
	    $this->sqlvysledky = 'vysledky_'.$this->race_code.'_'.$this->race_year.'_test';
	    $this->sqlzavod = 'zavod_'.$this->race_code.'_'.$this->race_year;
            $sql3 = "SELECT SEC_TO_TIME(($this->sqlkategorie.odpocet_casu * 60) + $dbdata->startovni_cas) AS start_time FROM $this->sqlkategorie,$this->sqlzavod WHERE $this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie AND $this->sqlzavod.ids = :ids";
            $sth3 = $this->db->prepare($sql3);
            $sth3->execute(Array(':ids' => $_GET['ids']));
            $dbdata3 = $sth3->fetchObject();
            
            
	    $str = '';
	    $sql2 = "SELECT tymy.nazev_tymu,"
                        . "$this->sqlvysledky.ids_alias,"
                        . "MAX($this->sqlvysledky.race_time) AS total_time,"
                        . "MAX($this->sqlvysledky.lap_count) AS lap_count,"
                        . "SEC_TO_TIME(SUM($this->sqlvysledky.lap_time_sec)) AS total_lap_time,"
                        . "SUM($this->sqlvysledky.lap_time_sec) AS total_lap_time_sec "
                        . "FROM $this->sqlvysledky,tymy,$this->sqlzavod "
		      . "WHERE "
		      . "race_time > 0 AND "
		      . "$this->sqlzavod.cip = $this->sqlvysledky.cip AND "
		      . "$this->sqlzavod.ids = :ids AND "
		      . "$this->sqlzavod.tym = tymy.id_tymu AND "
		      . "$this->sqlvysledky.false_time IS NULL AND "
		      . "$this->sqlvysledky.lap_only IS NULL";
	    $sth2 =  $this->db->prepare($sql2);
	    $sth2->execute(Array(':ids' => $_GET['ids']));
	    $dbdata2 = $sth2->fetchObject();
?>
<!DOCTYPE html>
<html lang="cs" dir="ltr">
<head>
    <title><?php echo $_GET['ids'].' | '.$dbdata2->nazev_tymu ?></title>
    <meta charset="UTF-8" />
    <meta http-equiv="refresh" content="60">
    <link rel="stylesheet" href="../../css/bootstrap.min.css" media="screen" />
    <link rel="stylesheet" href="../../css/default2.css" media="screen" />
    <link rel="stylesheet" href="../../css/jquery-ui.css" media="screen" />
    <link rel="stylesheet"  href="../css/print.css" media="print" />
    <script type="text/javascript" src="../../js/jquery-1.11.0.min.js"></script>
    <script type="text/javascript" src="../../js/jquery-ui.custom.min.js"></script>
    <script type="text/javascript" src="../../js/default.js"></script>
    <script type="text/javascript" src="../../js/bootstrap.min.js"></script>
 </head>
<body style="padding-top: 0">
<div id="content" class="container-fluid">
<?php 
	    
    $celkova_vzdalenost = $dbdata2->lap_count * $this->delka_kola;
    $str .= '<h3>'.$_GET['ids'].' | '.$dbdata2->nazev_tymu.'</h3>';
    $str .= '<p>Počet kol: <span class="color_red">'.$dbdata2->lap_count.'</span><br />';
    $str .= 'Celková vzdálenost: <span class="color_red">'.$celkova_vzdalenost.' km</span><br />';
    $str .= 'Průměrná rychlost: <span class="color_red">'.round($celkova_vzdalenost / $dbdata2->total_lap_time_sec * 3600,1).' km/h</span><br />';
    $str .= 'Celkový čas: <span class="color_red">'.$dbdata2->total_time.'</span>';
    $str .= '</p><br />';
    $sql1 = "SELECT "
                . "$this->sqlvysledky.lap_count,$this->sqlvysledky.lap_time,$this->sqlvysledky.lap_count,$this->sqlvysledky.cip,$this->sqlvysledky.day_time_sec,$this->sqlvysledky.race_time,$this->sqlvysledky.day_time,$this->sqlvysledky.lap_time_sec,"
                . "CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno "
            . "FROM $this->sqlvysledky,$this->sqlzavod,osoby "
            . "WHERE "
                    . "$this->sqlvysledky.false_time IS NULL AND "
                    . "$this->sqlvysledky.ids = :ids AND "
                    . "$this->sqlvysledky.cip = $this->sqlzavod.cip AND "
                    . "$this->sqlzavod.ido = osoby.ido AND "
                    . "MOD($this->sqlvysledky.lap_count,1) = 0 "
            . "ORDER BY race_time";
    $sth1 = $this->db->prepare($sql1);
    $sth1->execute(Array(':ids' => $_GET['ids']));
    $posledni_kolo = false;
    if($sth1->rowCount()){
	$row_count = $sth1->rowCount();
	$str .= '<table class="table table-bordered table-hover  table-striped table_vysledky text-center detail_ids" style="font-size:14px">';
	$str .= '<thead><tr class="header"><th class="text-center">Kolo</th><th>Jméno</th><th class="text-center">Čip</th><th class="text-center">Čas úseku</th><th class="text-center">Čas závodu</th><th class="text-center">Denní čas</th><th class="text-center">Km</th></tr></thead><tbody>';
       $poradi = 1;
	while($dbdata1 = $sth1->fetchObject()){
            $str .= '<tr>';
            $str .= '<td>' . $dbdata1->lap_count . '</td>';
            $str .= '<td style="padding-left:0" class="text-left"><a href="#" style="text-decoration:none" onclick="detail_cipu_rv('.$dbdata1->cip.','.$this->race_id.','.$this->race_year.')">'.$dbdata1->jmeno.'</a></td>';
            $str .= '<td>' . $dbdata1->cip . '</td>';
            if ($poradi == 1){
                $str .= '<td>' . $dbdata1->lap_time . '</td>';
            }
            else{
                $str .= '<td>' . $this->DynamicLaps($dbdata1->day_time_sec,$time_before) . '</td>';
            }
            $str .= '<td>' . $dbdata1->race_time . '</td>';
            $str .= '<td>' . $dbdata1->day_time . '</td>';
            $str .= '<td>' . ($this->delka_kola * $dbdata1->lap_count) . '</td>';
            //$str .= '<td>' . round($this->delka_kola/$dbdata1->lap_time_sec*3600,1) . '</td>';
            $str .= '</tr>';
            $time_before = $dbdata1->day_time_sec;
            $poradi++;
	}
	$str .= '</tbody></table>';

    }
    echo $str;
	    
    }
    
    
    private function DynamicLaps($time_current,$time_before){
        $lap_distance = round($time_current - $time_before,2); //musíme zaokrouhlovat, bo jinak to PHP při určitých hodnotách počítá blbě
        $pole_casu = explode(".",$lap_distance);

        if(isset($pole_casu[1])){
            //echo $pole_casu[1]."\n";
            if(strlen($pole_casu[1]) < 3){
                $distance_time = gmdate("H:i:s",$pole_casu[0]).'.'.$pole_casu[1].'0';
            }
            else{
                $distance_time = gmdate("H:i:s",$pole_casu[0]).'.'.$pole_casu[1];
            }
        }
        else{
             $distance_time = gmdate("H:i:s",$pole_casu[0]).'.00';
        }
        return $distance_time;
    }
    
}


New DetailIds();

?>
    
<script>
function detail_cipu_rv(cip,race_id,race_year) {
    window.open("detail-cipu-rv.php?cip="+cip+"&race_id="+race_id+"&race_year="+race_year);
}

</script>    
    
</div><!-- /.container -->
</body>
</html>	

<?php	
    require_once '../../config.php';
	class Database extends PDO{
	public function __construct($DB_TYPE,$DB_HOST,$DB_NAME,$DB_USER,$DB_PASS) {
	    parent::__construct($DB_TYPE.':host='.$DB_HOST.';dbname='.$DB_NAME,$DB_USER, $DB_PASS);
	 }
     }

    class DetailTymu {
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
            $sql = "SELECT * FROM $this->sqlzavody WHERE id_zavodu = $this->race_id";
            $sth = $this->db->query($sql);
	    $sth->execute();
	    $dbdata = $sth->fetchObject();
	    $this->race_code = $dbdata->kod_zavodu;
	    $this->sqlvysledky = 'vysledky_'.$this->race_code.'_'.$this->race_year.'_test';
	    $this->sqlzavod = 'zavod_'.$this->race_code.'_'.$this->race_year;
	    $str = '';
            $sql2 = "SELECT SEC_TO_TIME(SUM(race_time_sec)) as finish_time FROM (SELECT $this->sqlvysledky.race_time_sec FROM $this->sqlvysledky WHERE time_order = :time_order AND $this->sqlvysledky.ids = :ids AND false_time IS NULL ORDER BY $this->sqlvysledky.race_time_sec LIMIT 0,{$_GET['pocet_clenu']}) AS subquery";
            $sth2 =  $this->db->prepare($sql2);
	    $sth2->execute(Array(':ids' => $_GET['ids'],':time_order' => $_GET['time_order']));
	    $dbdata2 = $sth2->fetchObject();
            $sql2a = "SELECT nazev_tymu FROM tymy,$this->sqlzavod WHERE $this->sqlzavod.tym = tymy.id_tymu AND $this->sqlzavod.ids = :ids";
            $sth2a = $this->db->prepare($sql2a);
            $sth2a->execute(Array(':ids' => $_GET['ids']));
            $dbdata2a = $sth2a->fetchObject();
            
            $tym = 'Tým '.$dbdata2a->nazev_tymu;
            ?>
<!DOCTYPE html>
<html lang="cs" dir="ltr">
<head>
    <title><?php echo $tym ?></title>
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
	    
    $str .= '<h3>'.$tym.', celkový čas <span class="color_red">'.$dbdata2->finish_time.'</span></h3>';
    $str .= '<br />';
   
    $sql1 = "SELECT $this->sqlvysledky.race_time,$this->sqlvysledky.rank_overall,$this->sqlzavod.ids_alias,CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno FROM $this->sqlvysledky,$this->sqlzavod,osoby WHERE "
                          . "$this->sqlvysledky.false_time IS NULL AND $this->sqlvysledky.ids = :ids AND $this->sqlvysledky.cip = $this->sqlzavod.cip AND $this->sqlzavod.ido = osoby.ido "
			  . "ORDER BY race_time_sec ASC";
   
    
    
    $sth1 = $this->db->prepare($sql1);
    $sth1->execute(Array(':ids' => $_GET['ids']));
    $posledni_kolo = false;
    if($sth1->rowCount()){
	$row_count = $sth1->rowCount();
	$str .= '<table class="table table-bordered table-hover  table-striped table_vysledky text-center detail_ids" style="font-size:14px">';
	$str .= '<thead><tr class="header"><th class="text-center">Poř</th><th class="text-center">St.č</th><th>Jméno</th><th class="text-center">Čas</th></tr></thead><tbody>';
       $poradi = 1;
	while($dbdata1 = $sth1->fetchObject()){
            $str .= '<tr>';
            $str .= '<td>'.$dbdata1->rank_overall.'</td>';
            $str .= '<td>'.$dbdata1->ids_alias.'</td>';
            $str .= '<td style="padding-left:0" class="text-left">'.$dbdata1->jmeno.'</td>';
            $str .= '<td>'.$dbdata1->race_time.'</td>';
            $str .= '</tr>';
	    if($poradi == $_GET['pocet_clenu']){
                $str .= '<tr><td colspan="4" style="background:red"></td></tr>';
            }
	    $poradi++;
	}
	$str .= '</tbody></table>';

    }
    echo $str;
	    
    }
}
New DetailTymu();

?>
    
    
</div><!-- /.container -->
</body>
</html>	

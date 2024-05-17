<!DOCTYPE html>
<html lang="cs" dir="ltr">
<head>
    <title>TimeChip Raceadmin</title>
    <meta charset="UTF-8" />
    <meta http-equiv="refresh" content="60">
    <link rel="stylesheet" href="../../css/bootstrap.min.css" media="screen" />
    <link rel="stylesheet" href="../../css/default2.css" media="screen" />
    <link rel="stylesheet" href="../../css/jquery-ui.css" media="screen" />
    <link rel="stylesheet"  href="../../css/print.css" media="print" />
    <script type="text/javascript" src="../../js/jquery-1.11.0.min.js"></script>
    <script type="text/javascript" src="../../js/jquery-ui.custom.min.js"></script>
    <script type="text/javascript" src="../../js/default.js"></script>
    <script type="text/javascript" src="../../js/bootstrap.min.js"></script>
 </head>
<body style="padding-top:0">
<div id="content" class="container-fluid">
<?php 
	require_once '../../config.php';
	class Database extends PDO{
	public function __construct($DB_TYPE,$DB_HOST,$DB_NAME,$DB_USER,$DB_PASS) {
	    parent::__construct($DB_TYPE.':host='.$DB_HOST.';dbname='.$DB_NAME,$DB_USER, $DB_PASS);
	 }
     }

    class DetailTymuTeribear {
	private $db;
	private $race_year;
	private $race_id;
	private $sqlzavody;
	private $sqlkategorie;
	private $race_code;
	private $sqlzavod;
	private $id_tymu;
        private $delka_kola;
        private $castka_za_kolo = 20;
	
	
	public function __construct() {
	    $str = '';
	    $this->race_year = $_GET['race_year'];
	    $this->race_id = $_GET['race_id'];
	    $this->id_tymu = $_GET['id_tymu'];
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
	    $str = '';
	    $sql1 = "SELECT nazev_tymu FROM tymy WHERE id_tymu = $this->id_tymu";   
	    $sth1 = $this->db->prepare($sql1);
	    $sth1->execute();
	    if($sth1->rowCount()){
		$dbdata1 = $sth1->fetchObject();
		$nazev_tymu = $dbdata1->nazev_tymu;
	    }
	    
	    $sql2  = "SELECT $this->sqlvysledky.ids,SEC_TO_TIME(MAX($this->sqlvysledky.race_time_sec)) AS finish_time,COUNT($this->sqlvysledky.id) AS laps_count FROM $this->sqlvysledky,$this->sqlzavod WHERE $this->sqlvysledky.false_time IS NULL AND $this->sqlvysledky.ids = $this->sqlzavod.ids AND $this->sqlvysledky.id_tymu = $this->id_tymu GROUP BY $this->sqlvysledky.ids ORDER BY laps_count DESC,finish_time ASC";
	    //echo $sql2;
      $sth2 = $this->db->prepare($sql2);
	    $sth2->execute();
	    
	    if($sth2->rowCount()){
		
    $str .= '<h3>'.$nazev_tymu.', individuální výsledky</h3>';
		$str .= '<div id="results_table" class="table-responsive">';
                $str .= '<table class="table table-striped table-bordered table-hover noborder table_vysledky">';
		$str .= '<thead><tr class="header"><th class="text-center">Poř</th><th class="text-center">St.č</th><th class="text-left">Jméno</th><th class="text-center">Ročník</th><th class="text-center">Stát</th><th class="text-center">Kat</th><th class="text-center">Kol</th><th class="text-center">Vzdálenost</th><th class="text-center">Částka</th>';
		$str .= '</tr></thead><tbody>';
		$poradi = 1;
		while($dbdata2 = $sth2->fetchObject()){//cyklus, v kterém se vyberou všecky časy konkrétního závodníka
		    $sql3 = "SELECT CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,osoby.psc AS stat,osoby.pohlavi,$this->sqlkategorie.kod_k AS nazev_kategorie FROM $this->sqlzavod,osoby,$this->sqlkategorie WHERE $this->sqlzavod.ids = $dbdata2->ids AND $this->sqlzavod.ido = osoby.ido  AND $this->sqlzavod.id_kategorie = $this->sqlkategorie.id_kategorie";
		    //echo $sql3;
                    $sth3 = $this->db->prepare($sql3);
		    $sth3->execute(Array(':ids' => $dbdata2->ids));
		    if($sth3->rowCount()){
                        $vzdalenost = $dbdata2->laps_count * $this->delka_kola;
                        $castka_celkem = $vzdalenost * $this->castka_za_kolo;
			$dbdata3 = $sth3->fetchObject();
			$str .= '<tr><td class="text-center"><b>'.$poradi.'</b></td><td class="text-center">'.$dbdata2->ids.'</td><td>'.$dbdata3->jmeno.'</td><td class="text-center">'.$dbdata3->rocnik.'</td><td class="text-center">'.$dbdata3->stat.'</td><td class="text-center">'.$dbdata3->nazev_kategorie.'</td><td class="text-center">'.$dbdata2->laps_count.'</td><td class="text-center">'.$vzdalenost.' Km</td><td class="text-center">'.$castka_celkem.' Kč</td>';
			$str .= '</tr>';
			$poradi++;

		    }
		}
		$str .= '</tbody></table></div>';
	    }
	    echo $str;
	}
     }
     New DetailTymuTeribear();

?>
    
    
    
</div><!-- /.container -->
</body>
</html>	

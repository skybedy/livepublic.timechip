<?php
    require_once '../libs/settings.php';
    class Vysledky extends Settings{
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
	private $hash_url;
	private $delka_kola;
	private $cislo_kategorie;
	private $heat_id;
	private $etapy;
	private $id_etapy;
	private $rows_limit;
	private $rows_limit_number;
	private $event_name;
	private $start_time;
	private $chip_time;
	private $laps_only;
	private $podle_disciplin;
	private $datum_zavodu;
	private $typ_zavodu;
	private $reditel_zavodu;
	private $casomeric;
	private $jury;
	private $event_name_bez_carky;
	private $misto_zavodu;
        private $pocet_ubranych_znaku_zepredu = 0;
        private $vlny;
 //       private $pocet_ubranych_znaku_zepredu = 3;

	public function __construct(){
	    parent::__construct();
	    $this->event_order = isset($_GET['event_order']) ? $_GET['event_order'] : 1; 

	   /* provizorka.. mělo by se to dát už do předchozíchi selectu ať se nemusí dělat další dotaz do db*/

	    $sql = "SELECT kategorie FROM $this->sqlpodzavody WHERE id_zavodu = '$this->race_id' AND poradi_podzavodu = '$this->event_order'";
	   // echo $sql;
	    $sth = $this->db->prepare($sql);
	    $sth->execute();
	    $dbdata = $sth->fetchObject();
	    $cislo_kategorie = $dbdata->kategorie;
	    $this->cislo_kategorie = ($cislo_kategorie == 1) ? ('') : ('_2');
		    
	    $sql1 = "SELECT $this->sqlzavody.kod_zavodu,$this->sqlzavody.typ_zavodu,$this->sqlzavody.misto_zavodu,$this->sqlzavody.typ_zavodu,DATE_FORMAT(datum_zavodu,'%e.%c.%Y') AS datum_zavodu,$this->sqlzavody.delka_kola,$this->sqlzavody.startovni_cas AS start_time,$this->sqlzavody.nazev_zavodu,$this->sqlzavody.pocet_podzavodu,$this->sqlzavody.etapy,$this->sqlzavody.ruzny_pocet_casu,$this->sqlzavody.reditel_zavodu,$this->sqlzavody.casomeric,$this->sqlzavody.jury,$this->sqlpodzavody.*,reklamy_na_vysledky.* FROM $this->sqlzavody,$this->sqlpodzavody,reklamy_na_vysledky WHERE $this->sqlzavody.id_zavodu = :race_id AND $this->sqlpodzavody.id_zavodu = $this->sqlzavody.id_zavodu AND $this->sqlpodzavody.poradi_podzavodu = :event_order";
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
	    $this->start_time = $dbdata1->start_time;
	    $this->datum_zavodu = $dbdata1->datum_zavodu;
	    $this->misto_zavodu = $dbdata1->misto_zavodu;
	    $this->reditel_zavodu = $dbdata1->reditel_zavodu;
	    $this->casomeric = $dbdata1->casomeric;
	    $this->jury = $dbdata1->jury;
            $this->vlny = $dbdata1->vlny;
	    $this->typ_zavodu = $dbdata1->typ_zavodu;
	    $this->team_racer_count = $dbdata1->pocet_clenu_tymu; //zatím necháno jen kvůli zpětné kompabilitě, jestli to náhodou ještě nějaký typ výsledků nepoužívá.. jinak už se to řeší dynamicky počítaným počtem členů týmu přímo v type_ (15.6.16, to není pravda, používá se to u počítání týmů v type 8 například)
	    //$this->time_order = isset($_GET['time_order']) ? $_GET['time_order'] : $this->MaxTimeOrder(); 
	   //$this->time_order = isset($_GET['time_order']) ? $_GET['time_order'] : $this->time_count; //kvůli odřivousouvi a mixům
	    $this->laps_only = false;
	    if(isset($_GET['laps_only'])){
		$this->laps_only = true;
	    }
	    $this->podle_disciplin = false;
	    
	    if(isset($_GET['time_order'])){
		if(isset($_GET['select_type'])){
		    if($_GET['select_type']){
			$this->time_order = $this->time_count;
		    }
		}
		
		//děláno kvůli pdf u silesky, jinak to nefungovalo, je to třeba prozkoumat
		if($_GET['time_order']){
		    if($this->ruzny_pocet_casu){
			//pokud se jedná o změnu podzávodu
			if($_GET['event_list'] === "true"){
			    $this->time_order = $this->time_count;
			    //echo "true $this->time_order";
			}
			else{
			    $this->time_order = $_GET['time_order'];
			    if($this->time_count < $_GET['time_order']){ //pokud je v podzávodě menší počet časů než jde v $_GET což se stane díky javascriptovému snímání
				$this->time_order = $this->time_count;
			    }
			    //echo "false $this->time_order";
			}
		    }
		    else{
			$this->time_order = $_GET['time_order'];
		    }
		    
		    
		}
		else{
		    $this->time_order = $this->time_count; 
		}
	    }
	    else{
		$this->time_order = $this->time_count;
	    }
	    /*
	     * kvůli nadpisům ve výsledcích
	     * pokud je více než jeden podzávod, tak se k nadpisu přopojí i název podzavodu a nemusí se pak vepisovat do názvu kategorií (třeba 55km, 90km, atd) použito zatím jen u typu 1
	     */
	    $this->event_name = '';
	    if($this->event_count > 1){
		$this->event_name = ', '.$dbdata1->nazev;
		$this->event_name_bez_carky = $dbdata1->nazev;
	    }

	    
	    
	    
	    
	    $this->hash_url = '#'.$this->race_year.'/'.$this->race_id.'/';
	    $this->delka_kola = $dbdata1->delka_kola; 
	    $this->etapy = $dbdata1->etapy; 
	    if(isset($_GET['heat_id'])){
		$this->heat_id = $_GET['heat_id'];
	    }
	    
	    if($dbdata1->etapy){  
		if(isset($_GET['id_etapy'])){
		    $this->id_etapy = $_GET['id_etapy'];
		}
		else{
		    $sql2 = "SELECT id_etapy FROM etapy WHERE id_zavodu = :race_id AND rok_zavodu = :race_year ORDER BY poradi_etapy ASC LIMIT 0,1";
		    $sth2 = $this->db->prepare($sql2);
		    $sth2->execute(Array(':race_id' => $this->race_id,':race_year' => $this->race_year));
		    if($sth2->rowCount()){
			$dbdata2 = $sth2->fetchObject();
			$this->id_etapy = $dbdata2->id_etapy;
		    }
		}
	    }
	    
	    if(isset($_GET['rows_limit'])){
		if($_GET['rows_limit']){
		    $this->rows_limit = ' LIMIT 0,'.$_GET['rows_limit'];
		     $this->rows_limit_number = $_GET['rows_limit']; //kvůli výsledkům č.8
		}
	    }
	    
	    
            if($this->race_year == 2014 AND $this->race_id == 36){
		$this->sqlosoby = 'osoby_bbl';
	    }


           if(isset($_GET['murinoha'])){
		if(method_exists(get_class(),$_GET['murinoha'])){
                    $nazev_funkce = $_GET['murinoha']; 
                    $this->$nazev_funkce();
		}
                else{
		  $this->index();  
		}
	    }
	    else{
		$this->index();
	    }
	 }
	
	
	 
	private function LapsListSelect(){
	    $fcdata = '';
	    
	    $event_order = isset($_GET['event_order']) ? $_GET['event_order'] : 1; 
	    if(!$this->ruzny_pocet_casu){
		$event_order = 1;
	    }
	    
	    //$time_order = isset($_GET['time_order']) ? $_GET['time_order'] : $this->MaxTimeOrder(); 
	    $time_order = $this->time_order;
	    //echo $time_order;
	    
	    $sql1 = "SELECT nazev_discipliny,poradi_discipliny FROM $this->sqldiscipliny WHERE id_zavodu = :race_id AND poradi_podzavodu = :poradi_podzavodu ORDER BY poradi_discipliny";
	    $sth1 = $this->db->prepare($sql1);
	    $sth1->execute(Array(':race_id' => $this->race_id, ':poradi_podzavodu' => $event_order));
	    if($sth1->rowCount() > 1){
		$fcdata .= '<select id="lap_name" class="form-control input-lg">';
		//$fcdata .= '<option>*** Výběr času ***</option>';
		$i = 1;
		while($data1 = $sth1->fetchObject()){
		    $fcdata .= '<option';
		    if($i == $time_order) $fcdata .= ' selected="selected"';
		    $fcdata .= ' value="'.$data1->poradi_discipliny.'">'.$data1->nazev_discipliny.'</option>';
		    $i++;
		}
		$fcdata .= '</select>';
	    }
	    if(isset($_GET['murinoha'])){
		echo $fcdata;
	    }
	    else{
		return $fcdata;
	    }
	}

	 
	 
	 
	 
	 
	 
	 
	 private function index(){ 
	    $fcdata = '';
	    $sql2 = "SELECT lista_dolni,lista_horni FROM reklamy_na_vysledky WHERE rok_zavodu = :race_year AND id_zavodu = :race_id";
	    $sth2 = $this->db->prepare($sql2);
	    $sth2->execute(Array(':race_year' => $this->race_year,':race_id' => $this->race_id));
	    $reklamy_lista_horni = false;
	    $reklamy_lista_dolni = false;
	    $dbdata2 = (object) array('lista_horni' => 0,'lista_dolni' => 0);
	    if($sth2->rowCount()){
		$dbdata2 = $sth2->fetchObject();
	    }
	    if($this->race_id == 53 AND $this->race_year == 2014){
		$fcdata .= '<div style="text-align:center;margin:10px 0"><img src="images/free_litovel_1.jpg" /></div>';
	    }
	    
	    $fcdata .= '<div class="panel panel-default panel-collapse"><div class="panel-body" style="overflow:auto">';
	    if($dbdata2->lista_horni){
		$fcdata .= '<div class="reklama-lista-horni"><img src="images/results/lista_horni_'.$this->race_code.'_'.$this->race_year.'.jpg" style="max-width:100%;" /></div>';
	    }
      
     // $fcdata .= '<p style="color:red;font-weight:bold">V průbehu noci bude kvůli večernímu a nočnímu klidu vypnuto "pípání" na měřicím bodě za depem. Měření bude samozřejmě pokračovat dále, jen to bude bez zvuku.<br /> V případě jakéhokoliv podezření, že jste tam nebyli zaznamenáni, přijďte bez obav za námi do časoměřičského stanu</p>'; 
      
	    $fcdata .= '<div class="navbar navbar-raceadmin navbar-results"><form id="vysledky_form" class="navbar-form navbar-left">';
	    //nejprve se podíváme, jestli to je etapák, pokud jo, dáme select se etapama
	    if($this->etapy){
		$fcdata .= $this->EtapySelect();
	    }
	    
	    /*
	     * pak se podíváme,jestli je více podzávodů, pokud jo, vypíšeme je, pokud ne, neuděláme nic
	     */
	   																																																				    
	    //select s podzávodama
	    $sql1 = "SELECT * FROM $this->sqlpodzavody WHERE id_zavodu = :race_id ORDER BY poradi_podzavodu";
	    $sth1 = $this->db->prepare($sql1);
	    $sth1->execute(Array(':race_id' => $this->race_id));
	    if($sth1->rowCount()){
		$fcdata.= '<select id="event_list" class="form-control input-lg">';
		$i = 1;
		while($dbdata1 = $sth1->fetchObject()){
		    if($i == 1) $first_event_order = $dbdata1->poradi_podzavodu; //pořadí prvního podzávodu, které potřebuju v druhém selectu
		    $fcdata .= '<option value="'.$dbdata1->poradi_podzavodu.'">'.$dbdata1->nazev.'</option>';
		    $i++;
		}
		$fcdata.= '</select>'."\r\n";
	    }
	    
	    $fcdata .= '<span id="heat_list_select_wrapper">'.$this->HeatListSelect().'</span>';
	    $fcdata .= $this->ResultsTypeSelect();
	    $fcdata .= '<span id="laps_list_select_wrapper">'.$this->LapsListSelect().'</span>';
	    
	    if($this->podle_disciplin){
		if($this->time_count > 1){
		    $fcdata .= ' <label class="checkbox-inline"> <input type="checkbox" id="laps_only" name="laps_only"   /> Pořadí v disciplíně</label>';
		}
	    }
	    //$fcdata .= ' <button id="export_to_pdf" class="form-control">PDF</button>';
	    //$fcdata .= ' <button id="export_to_xls" class="form-control">XLS</button>';
	    
	    
	    //$fcdata .= ' <input id="rows_limit" class="form-control text-center" size="1" placeholder="Limit" />';
	    //$fcdata .= ' <input id="results_search" class="form-control text-center" size="1" placeholder="St.č" />';
	    ///$fcdata .= ' <button id="only_laps" class="form-control text-center">Laps only</button>';
	    $fcdata .= '</form></div>';
	   // $fcdata .= '<img id="spinner" src="./images/ajax-loader-big.gif" />';
	    $fcdata .= '<div id="result_table" class="table-responsive">'; 
	    $fcdata .= $this->ResultsOverall();  //nevím jak s tím teď o půlnici naložit, když celkové výsledky vůbec nejsou a měly by se spustit resilts category, které spuštěny jsou
	    $fcdata .= '</div>';
	    
	    if($dbdata2->lista_dolni){
		$fcdata .= '<div class="reklama-lista-dolni"><img src="images/results/lista_dolni_'.$this->race_code.'_'.$this->race_year.'.jpg" style="max-width:100%;" /></div>';
	    }
	     
	    
	    // patička jen pro CC
	    if($this->typ_zavodu == 25 || $this->typ_zavodu == 20){
                $cas_vytisteni = date("j.n.Y, H:i");
		$fcdata .= "<div class=\"paticka_cams\">"
			  . "<p>Délka trati: $this->delka_kola Km</p>"
			  . "<table class=\"paticka_cams_table\">"
			  . "<tr><td colspan=\"3\">Časomíra: TimeChip</td></tr>"
			  . "<tr><td>Ředitel závodu: $this->reditel_zavodu</td><td>Výsledky podléhají schválení jury</td><td>Hlavní časoměřič: $this->casomeric</td></tr>"
			  . "<tr><td>Jury: $this->jury</td><td>Čas vytištění: <span id=\"cas_vytisteni\">$cas_vytisteni</span></td><td>www.timechip.cz</td></tr>"
			  . "</table>"
			  . "</div>";
	    }
	    $fcdata .= '</div></div>';
	     echo json_encode($fcdata);
	}
	
	

	
	
	
	
	
	
	//POUZE NA CITY SPRINZ ZAKOMENTOVAN 1 .SELECT A RADEK 341 <span id="dynamic_select">'.$this->CategoryListSelect().'</span>';
	
	private function ResultsTypeSelect(){
	    //$fcdata = '<select id="results_type" class="form-control">';
            
            if($this->race_id == 25000){ //puvodne 25
                $fcdata = '<select style="display:none" id="results_type" class="form-control input-lg">';
                $sql1 = "SELECT * FROM $this->sqlmenuvysledky WHERE id_zavodu = :race_id";
                $sth1 = $this->db->prepare($sql1);
                $sth1->execute(Array(':race_id' => $this->race_id));
                if($sth1->rowCount()){
                    $dbdata1 = $sth1->fetchObject();
                    if($dbdata1->bez_kategorii) $fcdata .= '<option value="ResultsOverall">Bez rozdílu kategorií</option>';
                    if($dbdata1->podle_kategorii) $fcdata .= '<option value="ResultsCategory">Podle kategorií</option>';
                    if($dbdata1->podle_pohlavi) $fcdata .= '<option value="ResultsGender">Podle pohlaví</option>';
                    if($dbdata1->podle_rocniku) $fcdata .= '<option value="ResultsBirthYear">Podle ročníků</option>';
                    if($dbdata1->nejrychlejsi_kola) $fcdata .= '<option value="ResultsBestLaps">Nejrychlejší kola</option>';
                    if($dbdata1->k23) $fcdata .= '<option value="ResultsK23">Absolutní junior</option>';
                    if($dbdata1->pribehy) $fcdata .= '<option value="Pribehy">Témata Teribear</option>';
                    //if($dbdata1->led_bila) $fcdata .= '<option value="ResultsLedBila">LED Bílá</option>';

                    
                    
                    
                }
                $fcdata.= '</select>'."\r\n";
                if(isset($dbdata1)){ // pokud jsou nastaveny hodnoty v menu výsledky
                    if(!$dbdata1->bez_kategorii && $dbdata1->podle_kategorii ){ // pokud nejsou výsledky bez kategorií, tak se tam dá i select s výběrem kategorí, který se tam jinak nedá 
                       // $fcdata .= '<span id="dynamic_select">'.$this->CategoryListSelect().'</span>';
                    }
                    else{
                         $fcdata .= '<span id="dynamic_select"></span>'; //normální situace kdy jsou výsledky bez kategorií a tady přijde dynamicky vložit category list select teprve když se zvolí
                     }

                     if($dbdata1->podle_disciplin){
                        $this->podle_disciplin= true; 
                     }
                }
                else{
                    $fcdata .= 'Patrně není nastaveno Menu výsledky';
                }
                return $fcdata;
            }
            else{
                $fcdata = '<select  id="results_type" class="form-control input-lg">';
                $sql1 = "SELECT * FROM $this->sqlmenuvysledky WHERE id_zavodu = :race_id";
                $sth1 = $this->db->prepare($sql1);
                $sth1->execute(Array(':race_id' => $this->race_id));
                if($sth1->rowCount()){
                    $dbdata1 = $sth1->fetchObject();
                    if($dbdata1->bez_kategorii) $fcdata .= '<option value="ResultsOverall">Bez rozdílu kategorií</option>';
                    if($dbdata1->podle_kategorii) $fcdata .= '<option value="ResultsCategory">Podle kategorií</option>';
                    if($dbdata1->podle_pohlavi) $fcdata .= '<option value="ResultsGender">Podle pohlaví</option>';
                    if($dbdata1->podle_rocniku) $fcdata .= '<option value="ResultsBirthYear">Podle ročníků</option>';
                    if($dbdata1->nejrychlejsi_kola) $fcdata .= '<option value="ResultsBestLaps">Nejrychlejší kola</option>';
                    if($dbdata1->k23) $fcdata .= '<option value="ResultsK23">Absolutní junior</option>';
                    if($dbdata1->pribehy) $fcdata .= '<option value="Pribehy">Témata Teribear</option>';
                    //if($dbdata1->led_bila) $fcdata .= '<option value="ResultsLedBila">LED Bílá</option>';
                    
                                        
                    if($dbdata1->extra){
                        if($this->race_year == '2016' AND $this->race_id == '82'){
                             $fcdata .= '<option value="ResultsExtra">SILESIA Double</option>';
                        }
                        if($this->race_year == '2017' AND $this->race_id == '38'){
                             $fcdata .= '<option value="ResultsExtra">SILESIA Double</option>';
                        }
                        
                    }
                    
                    
                }
                $fcdata.= '</select>'."\r\n";
                if(isset($dbdata1)){ // pokud jsou nastaveny hodnoty v menu výsledky
                    if(!$dbdata1->bez_kategorii && $dbdata1->podle_kategorii ){ // pokud nejsou výsledky bez kategorií, tak se tam dá i select s výběrem kategorí, který se tam jinak nedá 
                        $fcdata .= '<span id="dynamic_select">'.$this->CategoryListSelect().'</span>';
                    }
                    else{
                         $fcdata .= '<span id="dynamic_select"></span>'; //normální situace kdy jsou výsledky bez kategorií a tady přijde dynamicky vložit category list select teprve když se zvolí
                     }

                     if($dbdata1->podle_disciplin){
                        $this->podle_disciplin= true; 
                     }
                }
                else{
                    $fcdata .= 'Patrně není nastaveno Menu výsledky';
                }
                return $fcdata;
            }
            
            
            
            
            
	}
	    
	private function CategoryListSelect(){
	    $fcdata = '';
	    $sql1 = "SELECT id_kategorie,nazev_k FROM $this->sqlkategorie WHERE id_zavodu = :race_id AND poradi_podzavodu = :event_order AND abstraktni_kategorie = :abstraktni_kategorie AND neviditelna_kategorie = :neviditelna_kategorie ORDER BY poradi";
	    $sth1 = $this->db->prepare($sql1);
	    $sth1->execute(Array(':race_id' => $this->race_id,':event_order' => $this->event_order,':abstraktni_kategorie' => 0,':neviditelna_kategorie' => 0));
	    if($sth1->rowCount()){
		$fcdata.= '<select id="category_list" class="form-control input-lg">';
		if($sth1->rowCount() > 0){
                    //$fcdata .= '<option value="all">All</option>';
                }
		while($dbdata1 = $sth1->fetchObject()){
		    $fcdata .= '<option value="'.$dbdata1->id_kategorie.'">'.$dbdata1->nazev_k.'</option>';
		}
		$fcdata.= '</select>'."\r\n";
	    }
	    if(isset($_GET['murinoha'])){
		echo $fcdata;
	    }
	    else{
		return $fcdata;
	    }
	}




	
	
	private function EtapySelect(){
	    $aktivni_etapa = 1;//provizorka, aby se nemuselo furt přepínat
	    $fcdata = '';
	    $this->datum_etapy = Array();
	    $sql1 = "SELECT *,DATE_FORMAT(datum_etapy,'%e.%c.%Y') AS datum_etapy FROM etapy WHERE id_zavodu = :race_id AND rok_zavodu = :rok_zavodu ORDER BY id_etapy ASC";
	    //echo $sql1;
	    $sth1 = $this->db->prepare($sql1);
	    $sth1->execute(Array(':race_id' => $this->race_id,':rok_zavodu' => $this->race_year));
	    if($sth1->rowCount()){
		$fcdata .= '<select id="id_etapy" name="id_etapy" class="form-control input-lg">';
		while($dbdata1 = $sth1->fetchObject()){
		    $this->datum_etapy[$dbdata1->id_etapy] = $dbdata1->datum_etapy;
		    $fcdata .= '<option value="'.$dbdata1->id_etapy.'"';
		    if($dbdata1->id_etapy == $aktivni_etapa){
			$fcdata .= ' selected';
		    }
		    $fcdata .= '>'.$dbdata1->nazev_etapy.'</option>';
		}
		$fcdata .= '</select> ';
	    }
	    return $fcdata;
	}
	
	
	
	
	  
	  private function HeatListSelect(){
	    //rozjíždky - pokud jsou definovány
	     $fcdata = '';
	    $sql2 = "SELECT * FROM $this->sqlrozjizdky WHERE id_zavodu = $this->race_id AND id_podzavodu = (SELECT id_podzavodu FROM $this->sqlpodzavody WHERE id_zavodu = $this->race_id AND poradi_podzavodu = $this->event_order) ORDER BY id_podzavodu,poradi_rozjizdky";
	   // echo $sql2;
	    $sth2 = $this->db->prepare($sql2);
	    $sth2->execute();
	    if($sth2->rowCount()){
		$fcdata.= '<select id="heat_list" class="form-control"><option value="">Celkově</option>';
		    while($dbdata2 = $sth2->fetchObject()){
			$fcdata .= '<option value="'.$dbdata2->id_rozjizdky.'">'.$dbdata2->nazev_rozjizdky.'</option>';
		    }
		$fcdata.= '</select>'."\r\n";
	    }
	    if(isset($_GET['murinoha'])){
		echo $fcdata;
	    }
	    else{
		return $fcdata;
	    }
	  }
	  
	

	
	private function ChangeControlFileInfo(){
	    $str = Array();
	    $hlavicky = (get_headers(CHANGE_CONTROL_FILE_URL_PATH));
           // print_r($hlavicky);
	    $x = explode("Last-Modified: ",$hlavicky[3]); //apache
           // $x = explode("Last-Modified: ",$hlavicky[5]); //nginx
            //echo $hlavicky[3];
	    $str['change_control_file'] = CHANGE_CONTROL_FILE_URL_PATH;
	    $str['last_modified'] = strtotime($x[1]);
	    return $str;
	}
	
	
	        
        private function DoubleCas($cas){
            $new_cas = $cas; 
            if(strlen($cas) == 10){
                $new_cas = $cas.'0';
            }
            return $new_cas;
        } 
        
        private function ResultsExtra(){

            //2015

            /*
            $race_id_1 = 54;
            $race_1 = 'silesia_merida_bike_maraton';
            */
            
            
            //2016
            //$race_id_1 = 6;
            //$race_1 = 'silesia_merida_bike_maraton';
            
            
            
            //2017
            $race_id_1 = 7;
            $race_1 = 'silesia_bike_maraton';
            
            
            $sqlzavod1 = 'zavod_'.$race_1.'_'.$this->race_year;
            $sqlvysledky1 = 'vysledky_'.$race_1.'_'.$this->race_year.'_test';
            $pocet_podzavodu = 5;

            
            $nazvy = Array(
                'kategorie_1' => Array(
                                    'nazev' => Array(
                                        'M' => 'SILESIA Double marathon 90 km + 23 km Muži',
                                        'Z' => 'SILESIA Double marathon 90 km + 23 km Ženy'
                                    ),
                                    'trasy' => Array(
                                        'trasa_1' => 'Kolo 90km',
                                        'trasa_2' => 'Běh 23km',
                                    )
                                ),
                'kategorie_2' => Array(
                                    'nazev' => Array(
                                        'M' => 'SILESIA Double marathon 55 km + 12 km - Muži',
                                        'Z' => 'SILESIA Double marathon 55 km + 12 km - Ženy'
                                    ),
                                    'trasy' => Array(
                                        'trasa_1' => 'Kolo 55km',
                                        'trasa_2' => 'Běh 12km',
                                    )
                                ),
                'kategorie_3' => Array(
                                    'nazev' => Array(
                                        'M' => 'SILESIA Double marathon kategorie H15 - dorostenci 15-16 let',
                                        'Z' => 'SILESIA Double marathon kategorie H15 - dorostenky 15-16 let'
                                    ),
                                    'trasy' => Array(
                                        'trasa_1' => 'Kolo 55km',
                                        'trasa_2' => 'Běh 6km',
                                    )
                                ),
                'kategorie_4' => Array(
                                    'nazev' => Array(
                                        'M' => 'SILESIA Double marathon kategorie H13 - žáci starší 13 - 14 let',
                                        'Z' => ''
                                    ),
                                    'trasy' => Array(
                                        'trasa_1' => 'Kolo 55km',
                                        'trasa_2' => 'Běh 4km',
                                    )
                                ),
                'kategorie_5' => Array(
                                    'nazev' => Array(
                                        'M' => '',
                                        'Z' => 'SILESIA Double marathon kategorie D13 - žačky starší 13 - 14 let'
                                    ),
                                    'trasy' => Array(
                                        'trasa_1' => 'Kolo 55km',
                                        'trasa_2' => 'Běh 3km',
                                    )
                                ),
                    
                    
            );
            
            

            //$str = '<h4 class="headline-results">'.$this->race_name.$this->event_name.', DOUBLE</h4>';
            $str = ""; 
            $str .= '<table  id="table2excel" class="table table-striped table-bordered table-hover noborder table_vysledky">';

            $fcdata = Array();
            
            $i = 1;
            for($i;$i<=$pocet_podzavodu;$i++){
                $podzavod = $i;
                $podzavod_zavod_1 = $podzavod;
                if($podzavod == 3){
                    $podzavod_zavod_1 = 2;
                }
                if($podzavod == 4){
                    $podzavod_zavod_1 = 3;
                }
                
                if($podzavod == 5){
                    $podzavod_zavod_1 = 3;
                }
                
                //vytáhnema počet času z danneho podzavodu z prvniho zavodu
                $sql3 = "SELECT pocet_casu FROM $this->sqlpodzavody WHERE id_zavodu = $race_id_1 AND poradi_podzavodu = $podzavod_zavod_1";
                $sth3 = $this->db->prepare($sql3);
                $sth3->execute();
                $pocet_casu_zavod_1 = $sth3->fetchColumn();

                //a tady z druheho
                $sql4 = "SELECT pocet_casu FROM $this->sqlpodzavody WHERE id_zavodu = $this->race_id AND poradi_podzavodu = $podzavod";
                $sth4 = $this->db->prepare($sql4);
                $sth4->execute();
                $pocet_casu_zavod_2 = $sth4->fetchColumn();

                $sql1 = "SELECT $this->sqlzavod.cip,$this->sqlzavod.ido,($this->race_year - osoby.rocnik) AS vek FROM $this->sqlzavod,osoby WHERE $this->sqlzavod.ido = osoby.ido AND $this->sqlzavod.poradi_podzavodu = $podzavod ORDER BY ids ASC";
                $sth1 = $this->db->prepare($sql1);
                $sth1->execute();
                
                $zavodnici = Array();
                $zavodnik = Array();

                while($dbdata1 = $sth1->fetchObject()){
                    $sql2 = "SELECT cip FROM $sqlzavod1 WHERE ido = $dbdata1->ido AND poradi_podzavodu = $podzavod_zavod_1";
                    $sth2 = $this->db->prepare($sql2);
                    $sth2->execute();
                    if($sth2->rowCount()){ //pokud je v prvním závodě taky
                         $cip = $sth2->fetchColumn();
                         $sql5 = "SELECT race_time,race_time_sec,rank_gender FROM $sqlvysledky1 WHERE cip = $cip AND time_order = $pocet_casu_zavod_1";
                         $sth5 = $this->db->prepare($sql5);
                         $sth5->execute();
                         if($sth5->rowCount()){
                            $dbdata5 = $sth5->fetch(PDO::FETCH_OBJ);
                            $race_time_sec_1 = $dbdata5->race_time_sec;
                            $sql6 = "SELECT race_time,race_time_sec,rank_gender FROM $this->sqlvysledky WHERE cip = $dbdata1->cip AND time_order = $pocet_casu_zavod_2";
                            $sth6 = $this->db->prepare($sql6);
                            $sth6->execute();
                            if($sth6->rowCount()){
                                $dbdata6 = $sth6->fetch(PDO::FETCH_OBJ);
                                $race_time_sec_2 = $dbdata6->race_time_sec;
                                $soucet_casu_sec = $race_time_sec_1 + $race_time_sec_2;
                                $zavodnici[$dbdata1->ido] = $soucet_casu_sec;
                                $zavodnik[$dbdata1->ido] = Array(
                                    'cas1' => $dbdata5->race_time,
                                    'cas2' => $dbdata6->race_time,
                                    'rank_1' => $dbdata5->rank_gender,
                                    'rank_2' => $dbdata6->rank_gender,
                                );
                                
                                
                            }

                        }

                    }
                }
                
               // print_r($zavodnik);
                
                asort($zavodnici,SORT_NUMERIC);
                
                $chlapi = false;
                $baby = false;
                $poradi_chlapi = 1;
                $poradi_baby = 1;
                foreach($zavodnici as $key => $val){
                    $sql7 = false;
                    $sql7 = "SELECT SEC_TO_TIME($val) AS soucet_casu, CONCAT_WS(' ',$this->sqlosoby.prijmeni,$this->sqlosoby.jmeno) AS jmeno,$this->sqlosoby.pohlavi,$this->sqlosoby.rocnik,tymy.nazev_tymu FROM $this->sqlzavod,$this->sqlosoby,tymy WHERE $this->sqlzavod.ido = $key AND $this->sqlzavod.ido = $this->sqlosoby.ido AND $this->sqlzavod.ido = osoby.ido AND $this->sqlzavod.id_tymu = tymy.id_tymu";
                    $sth7 = $this->db->prepare($sql7);
                    $sth7->execute();
                    if($sth7->rowCount()){
                        $dbdata7 = false;
                        $dbdata7 = $sth7->fetch(PDO::FETCH_OBJ);
                       // echo $dbdata7->pohlavi."-";
                        if($dbdata7->pohlavi == 'M'){
                            if($poradi_chlapi == 1) $best_time_chlapi = $val;
                            $distance_time_chlapi = $this->DynamicDistances($poradi_chlapi,$val,$best_time_chlapi);
                            $chlapi .= "<tr><td class=\"text-center\">$poradi_chlapi</td><td>$dbdata7->jmeno</td><td class=\"text-center\">$dbdata7->rocnik</td><td>$dbdata7->nazev_tymu</td><td class=\"text-center\">{$zavodnik[$key]['cas1']}</td><td class=\"text-center\">{$zavodnik[$key]['rank_1']}</td><td class=\"text-center\">{$zavodnik[$key]['cas2']}</td><td class=\"text-center\">{$zavodnik[$key]['rank_2']}</td><td class=\"text-center\">".$this->DoubleCas($dbdata7->soucet_casu)."</td>";
                            $chlapi .= ($distance_time_chlapi !== '00:00:00.00') ?  ('<td class="text-center">'.$distance_time_chlapi.'</td>') : ('<td class="text-center">-</td>');
                            $chlapi .= "</tr>";
                            $poradi_chlapi++;
                        }
                        else{
                            if($poradi_baby == 1) $best_time_baby = $val;
                            $distance_time_baby = $this->DynamicDistances($poradi_baby,$val,$best_time_baby);
                            $baby .= "<tr><td class=\"text-center\">$poradi_baby</td><td>$dbdata7->jmeno</td><td class=\"text-center\">$dbdata7->rocnik</td><td>$dbdata7->nazev_tymu</td><td class=\"text-center\">{$zavodnik[$key]['cas1']}</td><td class=\"text-center\">{$zavodnik[$key]['rank_1']}</td><td class=\"text-center\">{$zavodnik[$key]['cas2']}</td><td class=\"text-center\">{$zavodnik[$key]['rank_2']}</td><td class=\"text-center\">".$this->DoubleCas($dbdata7->soucet_casu)."</td>";                        
                            $baby .= ($distance_time_baby !== '00:00:00.00') ?  ('<td class="text-center">'.$distance_time_baby.'</td>') : ('<td class="text-center">-</td>');
                            $baby .= "</tr>";
                            $poradi_baby++;
                        }
                    }   
                }
                
                $hlavicka = '<tr class="header"><th class="text-center">Stč</th><th class="text-left">Jméno</th><th class="text-center">Ročník</th><th class="text-left">Tým/Bydliště</th><th class="text-center">'.$nazvy['kategorie_'.$podzavod]['trasy']['trasa_1'].'</th><th class="text-center">Poř</th><th class="text-center">'.$nazvy['kategorie_'.$podzavod]['trasy']['trasa_2'].'</th><th class="text-center">Poř</th><th class="text-center">Double</th><th class="text-center">Odstup</th></tr>';

                if($chlapi){
                    $class = ($i == 1) ? ($class = 'nadpis nopadding') : ('nadpis');
                    $id = $i == 1 ? $id = ' id = "nopadding" ' : ''; //kvůli tomu, aby v prvním nadpisu nebyl padding, pokud se to udělá jen třídou, tak se to nepřepíše, protože i v původním předpisu je !mportant
                    $str .= '<tr><td '.$id.' class="'.$class.'" colspan="3">'.$nazvy['kategorie_'.$podzavod]['nazev']['M'].'</td></tr>';
                    $str .= $hlavicka;
                    $str .= $chlapi; 
                }
                
                if($baby){
                    $str .= '<tr><td class="nadpis" colspan="3">'.$nazvy['kategorie_'.$podzavod]['nazev']['Z'].'</td></tr>';
                    $str .= $hlavicka;
                    $str .= $baby; 
                }
            }
            
            $str .= "</table>";
            $fcdata['results'] = $str;
            echo json_encode($fcdata);           
        }

        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        private function ResultsK23(){
	    $id_kategorie = 1424;
	    $fcdata = Array();
	    $str = '';
	    $str .= '<h4 class="headline-results">'.$this->race_name.', kategorie Absolutní junior</h4>';
	    $str .= '<table class="table table-bordered table-hover table_vysledky">';
	    //$str .= '<thead><tr class="header"><th class="text-center">#</th><th class="text-center">St.č</th><th class="text-center">Čip</th><th>Jméno</th><th class="text-center">Ročník</th><th class="text-center">Tým</th><th class="text-center">Stát</th><th class="text-center">Značka</th><th class="text-center">Čas</th><th class="text-center">Kolo</th><th class="text-center">Odstup</th><th class="text-center">Km/h</th><th class="text-center">Nejlepší čas</th><th class="text-center">Body</th>';
	    $str .= '<thead><tr class="header"><th class="text-center">#</th><th class="text-center">St.č</th><th>Jméno</th><th class="text-center">Ročník</th><th class="text-center">Tým</th><th class="text-center">Stát</th><th class="text-center">Značka</th><th class="text-center">Čas</th><th class="text-center">Kolo</th><th class="text-center">Odstup</th><th class="text-center">Km/h</th><th class="text-center">Nejlepší čas</th><th class="text-center">Pen</th><th class="text-center">Body</th>';
	    $str .= '</tr></thead><tbody>';

	    $event_order = isset($_GET['event_order']) ? $_GET['event_order'] : 1; 
	    $time_order = isset($_GET['time_order']) ? $_GET['time_order'] : $this->MaxTimeOrder(); 
	    $age = date("Y") - 23;
	    $sql2 = "SELECT "
		. "$this->sqlvysledky.ids,"
		. "$this->sqlvysledky.cip,"
		. "COUNT($this->sqlvysledky.id) AS pocet_kol,"
		. "MAX($this->sqlvysledky.race_time) AS finish_time,"
		. "MAX($this->sqlvysledky.time_order) AS time_order ,"
		. "MIN($this->sqlvysledky.lap_time) AS best_lap_time,"
		. "SUM($this->sqlvysledky.lap_time_sec) AS total_lap_time_sec "
		."FROM $this->sqlvysledky,$this->sqlzavod,$this->sqlkategorie,osoby WHERE "
		. "race_time > 0 AND "
		. "time_order > 1 AND "
		. "$this->sqlzavod.cip = $this->sqlvysledky.cip AND "
	
		. "$this->sqlzavod.ido = osoby.ido AND "
		
		. "$this->sqlkategorie.id_kategorie =  $id_kategorie AND "
		. "$this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie AND "
		. "$this->sqlkategorie.poradi_podzavodu = $this->event_order AND "
		
		. "osoby.rocnik >= $age AND "
		. "$this->sqlvysledky.false_time IS NULL AND "
		. "$this->sqlvysledky.lap_only IS NULL "
		. "GROUP BY $this->sqlvysledky.cip "
		. "ORDER BY pocet_kol DESC,finish_time ASC";
	    
	    //echo $sql2;
		$sth2 =  $this->db->prepare($sql2);
		//$sth2->execute(Array(':id_etapy' => $this->id_etapy,':id_kategorie' => $category_id,':event_order' => $this->event_order));
		$sth2->execute();
		if($sth2->rowCount()){ 
		    $poradi = 1;
		    while($dbdata2 = $sth2->fetchObject()){ //2.cyklus
			   $celkova_vzdalenost = $dbdata2->pocet_kol * $this->delka_kola;
			    if($poradi == 1) $max_pocet_kol = $dbdata2->pocet_kol + 1;// nejvyšší počet kol pro počítání odstupů
			    //if($poradi == 1) $max_time_order = $dbdata1->time_order;// nejvyšší počet časů pro počítání odstupů
			    $sql4 = "SELECT $this->sqlvysledky.distance_category FROM $this->sqlvysledky WHERE $this->sqlvysledky.ids = :ids AND time_order = :max_pocet_kol AND id_etapy = :id_etapy"; //vezmeme odstupy, v tomto případě ve zvláštním dotaze
			    $sth4 = $this->db->prepare($sql4);
			    $sth4->execute(Array(':ids' => $dbdata2->ids,':max_pocet_kol' => $max_pocet_kol,':id_etapy' => $this->id_etapy));
			    if($sth4->rowCount()){ //pokud je někdo se stejným, tzn. nejvyšším počttem kol, použijeme časový odstup
				$dbdata4 = $sth4->fetchObject();
				($dbdata4->distance_category != '00:00:00.000') ? ($distance_category = $dbdata4->distance_category) : ($distance_category = '-'); //pokud nemá odstup, tak je první a bude tam pomlčka
			    }
			    else{ // pokud ne, spočítáme odstup v kolech
				$distance_category = $dbdata2->pocet_kol - $max_pocet_kol + 1; 
				if($distance_category == -1){
				    $kola = 'kolo';
				}
				elseif(($distance_category < -1 AND $distance_category > -5) OR $distance_category > -1){
				    $kola = 'kola';
				}
				else{
				    $kola = 'kol';
				}
				$distance_category = $distance_category.' '.$kola;
			    }
			
		    $sql3 = "SELECT CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.psc AS stat,osoby.rocnik,tymy.nazev_tymu,$this->sqlkategorie.nazev_k AS nazev_kategorie,znacky_motocyklu.nazev_motocyklu "
			. "FROM osoby,$this->sqlzavod,tymy,$this->sqlkategorie,znacky_motocyklu WHERE "
			. "$this->sqlzavod.cip =  $dbdata2->cip AND "
			
			. "$this->sqlzavod.ido = osoby.ido AND "
			. "$this->sqlzavod.id_tymu = tymy.id_tymu AND "
			. "$this->sqlzavod.id_kategorie = $this->sqlkategorie.id_kategorie AND "
			. "$this->sqlzavod.id_motocyklu = znacky_motocyklu.id_motocyklu";
			$sth3 = $this->db->prepare($sql3);
			//$sth3->execute(Array(':ids' => $dbdata2->ids,':id_etapy' => $this->id_etapy));
			$sth3->execute();
			if($sth3->rowCount()){
			    $dbdata3 = $sth3->fetchObject();
			    $str .= '<tr id="'.$dbdata2->cip.'">';
			    $str .= '<td class="text-center">'.$poradi.'</td>';
			    $str .= '<td class="text-center">'.$dbdata2->ids.'</td>';
			    //$str .= '<td class="text-center">'.$dbdata2->cip.'</td>';
			    $str .= '<td>'.$dbdata3->jmeno.'</td>';
			    $str .= '<td class="text-center">'.$dbdata3->rocnik.'</td>';
			    $str .= ($dbdata3->nazev_tymu == 'Bez týmu') ? ('<td>&nbsp;</td>') : ('<td class="text-center">'.$dbdata3->nazev_tymu.'</td>');
			    $str .= '<td class="text-center">'.$dbdata3->stat.'</td>';
			    $str .= '<td class="text-center">'.$dbdata3->nazev_motocyklu.'</td>';
			    $str .= '<td class="text-center">'.$dbdata2->finish_time.'</td>';
			    $str .= '<td class="text-center">'.$dbdata2->pocet_kol.'</td>';
			    $str .= '<td class="text-center">'.$distance_category.'</td>';
			    $str .= '<td  class="text-center">'.round($celkova_vzdalenost / $dbdata2->total_lap_time_sec * 3600,1).'</td>';
			    $str .= '<td class="text-center">'.$dbdata2->best_lap_time.'</td>';
			    $str .= '<td class="text-center"></td>';
			    $str .= '<td class="text-center"></td>';
			    $str .= '</tr>';
		      }
		    $poradi++;
		    } //2.cyklus

		}
		$str .= '</tbody></table>';
		$fcdata['results'] = $str;
		echo json_encode($fcdata);
	    }
            
            
            
            
       private function ResultsLEDBila(){
            
  
           
           
            $str = "<div class=\"col-xs-6\" style=\"padding-right:0\">";
            $str .= "<h4 class=\"text-center\" style=\"margin:0\">20KM Muži</h2>";
            $str .= "<div class=\"table-responsive\">";         
            $sql1 = "SELECT MAX($this->sqlvysledky.race_time) AS cilovy_cas,CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno FROM $this->sqlvysledky,osoby,$this->sqlzavod WHERE race_time > 0 AND $this->sqlvysledky.time_order = :time_order AND osoby.pohlavi = :pohlavi AND $this->sqlzavod.cip = $this->sqlvysledky.cip AND $this->sqlzavod.ido = osoby.ido AND $this->sqlzavod.poradi_podzavodu = :poradi_podzavodu AND $this->sqlvysledky.false_time IS NULL AND $this->sqlvysledky.lap_only IS NULL GROUP BY $this->sqlvysledky.ids ORDER BY cilovy_cas ASC LIMIT 0,3";
            $sth1 = $this->db->prepare($sql1);
            $sth1->execute(Array(":time_order" => 1,":pohlavi" => "M",":poradi_podzavodu" => 1));
            if($sth1->rowCount()){
                $str .= "<table class=\"table table-striped table-bordered\">";
                $i = 1;
                while($dbdata1 = $sth1->fetchObject()){
                    $str .= "<tr>";
                    $str .= "<td class=\"text-center\">$i</td><td>$dbdata1->jmeno</td><td class=\"text-center\">$dbdata1->cilovy_cas</td>";
                    $str .= "</tr>";
                    $i++;
                }
                $str .= "</table>";
            }
            $str .= "</div>";         
            $str .= "</div>";         
       
            $str .= "<div class=\"col-xs-6\" style=\"padding-left:0\">";
            $str .= "<h4 class=\"text-center\">20KM Ženy</h2>";
            $str .= "<div class=\"table-responsive\">";         
            $sql1 = "SELECT MAX($this->sqlvysledky.race_time) AS cilovy_cas,CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno FROM $this->sqlvysledky,osoby,$this->sqlzavod WHERE race_time > 0 AND $this->sqlvysledky.time_order = :time_order AND osoby.pohlavi = :pohlavi AND $this->sqlzavod.cip = $this->sqlvysledky.cip AND $this->sqlzavod.ido = osoby.ido AND $this->sqlzavod.poradi_podzavodu = :poradi_podzavodu AND $this->sqlvysledky.false_time IS NULL AND $this->sqlvysledky.lap_only IS NULL GROUP BY $this->sqlvysledky.ids ORDER BY cilovy_cas ASC LIMIT 0,3";
            $sth1 = $this->db->prepare($sql1);
            $sth1->execute(Array(":time_order" => 1,":pohlavi" => "Z",":poradi_podzavodu" => 1));
            if($sth1->rowCount()){
                $str .= "<table class=\"table table-striped table-bordered\">";
                $i = 1;
                while($dbdata1 = $sth1->fetchObject()){
                    $str .= "<tr>";
                    $str .= "<td class=\"text-center\">$i</td><td>$dbdata1->jmeno</td><td class=\"text-center\">$dbdata1->cilovy_cas</td>";
                    $str .= "</tr>";
                    $i++;
                }
                $str .= "</table>";
            }

 
            $str .= "</div>";         
            $str .= "</div>";  
            
            
            
            $fcdata['results'] = $str;
             echo json_encode($fcdata);
           
           
       }     

	
	
	private function ResultsK23Etapy(){
	    $id_kategorie = 331;
	    $fcdata = Array();
	    $str = '';
	    $str .= '<h4 class="headline-results">'.$this->race_name.', kategorie Absolutní junior</h4>';
	    $str .= '<table class="table table-bordered table-hover table_vysledky">';
	    //$str .= '<thead><tr class="header"><th class="text-center">#</th><th class="text-center">St.č</th><th class="text-center">Čip</th><th>Jméno</th><th class="text-center">Ročník</th><th class="text-center">Tým</th><th class="text-center">Stát</th><th class="text-center">Značka</th><th class="text-center">Čas</th><th class="text-center">Kolo</th><th class="text-center">Odstup</th><th class="text-center">Km/h</th><th class="text-center">Nejlepší čas</th><th class="text-center">Body</th>';
	    $str .= '<thead><tr class="header"><th class="text-center">#</th><th class="text-center">St.č</th><th>Jméno</th><th class="text-center">Ročník</th><th class="text-center">Tým</th><th class="text-center">Stát</th><th class="text-center">Značka</th><th class="text-center">Čas</th><th class="text-center">Kolo</th><th class="text-center">Odstup</th><th class="text-center">Km/h</th><th class="text-center">Nejlepší čas</th><th class="text-center">Pen</th><th class="text-center">Body</th>';
	    $str .= '</tr></thead><tbody>';

	    $event_order = isset($_GET['event_order']) ? $_GET['event_order'] : 1; 
	    $time_order = isset($_GET['time_order']) ? $_GET['time_order'] : $this->MaxTimeOrder(); 
	    $age = date("Y") - 23;
	    $sql2 = "SELECT "
		. "$this->sqlvysledky.ids,"
		. "$this->sqlvysledky.cip,"
		. "COUNT($this->sqlvysledky.id) AS pocet_kol,"
		. "MAX($this->sqlvysledky.race_time) AS finish_time,"
		. "MAX($this->sqlvysledky.time_order) AS time_order ,"
		. "MIN($this->sqlvysledky.lap_time) AS best_lap_time,"
		. "SUM($this->sqlvysledky.lap_time_sec) AS total_lap_time_sec "
		."FROM $this->sqlvysledky,$this->sqlzavod,$this->sqlkategorie,osoby WHERE "
		. "race_time > 0 AND "
		. "time_order > 1 AND "
		. "$this->sqlzavod.cip = $this->sqlvysledky.cip AND "
		. "$this->sqlvysledky.id_etapy = $this->id_etapy AND "
		. "$this->sqlvysledky.id_etapy = $this->sqlzavod.id_etapy AND "
		. "$this->sqlzavod.ido = osoby.ido AND "
		
		. "$this->sqlkategorie.id_kategorie =  $id_kategorie AND "
		. "$this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie AND "
		. "$this->sqlkategorie.poradi_podzavodu = $this->event_order AND "
		
		. "osoby.rocnik >= $age AND "
		. "$this->sqlvysledky.false_time IS NULL AND "
		. "$this->sqlvysledky.lap_only IS NULL "
		. "GROUP BY $this->sqlvysledky.cip "
		. "ORDER BY pocet_kol DESC,finish_time ASC";
	    
	    //echo $sql2;
		$sth2 =  $this->db->prepare($sql2);
		//$sth2->execute(Array(':id_etapy' => $this->id_etapy,':id_kategorie' => $category_id,':event_order' => $this->event_order));
		$sth2->execute();
		if($sth2->rowCount()){ 
		    $poradi = 1;
		    while($dbdata2 = $sth2->fetchObject()){ //2.cyklus
			   $celkova_vzdalenost = $dbdata2->pocet_kol * $this->delka_kola;
			    if($poradi == 1) $max_pocet_kol = $dbdata2->pocet_kol + 1;// nejvyšší počet kol pro počítání odstupů
			    //if($poradi == 1) $max_time_order = $dbdata1->time_order;// nejvyšší počet časů pro počítání odstupů
			    $sql4 = "SELECT $this->sqlvysledky.distance_category FROM $this->sqlvysledky WHERE $this->sqlvysledky.ids = :ids AND time_order = :max_pocet_kol AND id_etapy = :id_etapy"; //vezmeme odstupy, v tomto případě ve zvláštním dotaze
			    $sth4 = $this->db->prepare($sql4);
			    $sth4->execute(Array(':ids' => $dbdata2->ids,':max_pocet_kol' => $max_pocet_kol,':id_etapy' => $this->id_etapy));
			    if($sth4->rowCount()){ //pokud je někdo se stejným, tzn. nejvyšším počttem kol, použijeme časový odstup
				$dbdata4 = $sth4->fetchObject();
				($dbdata4->distance_category != '00:00:00.000') ? ($distance_category = $dbdata4->distance_category) : ($distance_category = '-'); //pokud nemá odstup, tak je první a bude tam pomlčka
			    }
			    else{ // pokud ne, spočítáme odstup v kolech
				$distance_category = $dbdata2->pocet_kol - $max_pocet_kol + 1; 
				if($distance_category == -1){
				    $kola = 'kolo';
				}
				elseif(($distance_category < -1 AND $distance_category > -5) OR $distance_category > -1){
				    $kola = 'kola';
				}
				else{
				    $kola = 'kol';
				}
				$distance_category = $distance_category.' '.$kola;
			    }
			
		    $sql3 = "SELECT CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.psc AS stat,osoby.rocnik,tymy.nazev_tymu,$this->sqlkategorie.nazev_k AS nazev_kategorie,znacky_motocyklu.nazev_motocyklu "
			. "FROM osoby,$this->sqlzavod,tymy,$this->sqlkategorie,znacky_motocyklu WHERE "
			. "$this->sqlzavod.cip =  $dbdata2->cip AND "
			
			. "$this->sqlzavod.id_etapy = $this->id_etapy AND "
			. "$this->sqlzavod.ido = osoby.ido AND "
			. "$this->sqlzavod.id_tymu = tymy.id_tymu AND "
			. "$this->sqlzavod.id_kategorie = $this->sqlkategorie.id_kategorie AND "
			. "$this->sqlzavod.id_motocyklu = znacky_motocyklu.id_motocyklu";
			$sth3 = $this->db->prepare($sql3);
			//$sth3->execute(Array(':ids' => $dbdata2->ids,':id_etapy' => $this->id_etapy));
			$sth3->execute();
			if($sth3->rowCount()){
			    $dbdata3 = $sth3->fetchObject();
			    $str .= '<tr id="'.$dbdata2->cip.'">';
			    $str .= '<td class="text-center">'.$poradi.'</td>';
			    $str .= '<td class="text-center">'.$dbdata2->ids.'</td>';
			    //$str .= '<td class="text-center">'.$dbdata2->cip.'</td>';
			    $str .= '<td>'.$dbdata3->jmeno.'</td>';
			    $str .= '<td class="text-center">'.$dbdata3->rocnik.'</td>';
			    $str .= ($dbdata3->nazev_tymu == 'Bez týmu') ? ('<td>&nbsp;</td>') : ('<td class="text-center">'.$dbdata3->nazev_tymu.'</td>');
			    $str .= '<td class="text-center">'.$dbdata3->stat.'</td>';
			    $str .= '<td class="text-center">'.$dbdata3->nazev_motocyklu.'</td>';
			    $str .= '<td class="text-center">'.$dbdata2->finish_time.'</td>';
			    $str .= '<td class="text-center">'.$dbdata2->pocet_kol.'</td>';
			    $str .= '<td class="text-center">'.$distance_category.'</td>';
			    $str .= '<td  class="text-center">'.round($celkova_vzdalenost / $dbdata2->total_lap_time_sec * 3600,1).'</td>';
			    $str .= '<td class="text-center">'.$dbdata2->best_lap_time.'</td>';
			    $str .= '<td class="text-center"></td>';
			    $str .= '<td class="text-center"></td>';
			    $str .= '</tr>';
		      }
		    $poradi++;
		    } //2.cyklus

		}
		$str .= '</tbody></table>';
		$fcdata['results'] = $str;
		echo json_encode($fcdata);
	    }

	private function Pribehy(){
	    $str = '';
	    $fcdata = Array();
	    $pribehy = Array(); 
	    $sql1 = "SELECT id_pribehu FROM pribehy_teribear ORDER BY id_pribehu";
	    $sth1 = $this->db->prepare($sql1);
	    $sth1->execute();
	    if($sth1->rowCount()){
		while($dbdata1 = $sth1->fetchObject()){
		    //$sql2 = "SELECT COUNT($this->sqlvysledky.id) AS laps_count FROM $this->sqlvysledky,$this->sqlzavod WHERE $this->sqlvysledky.cip = $this->sqlzavod.cip AND $this->sqlzavod.id_pribehu = $dbdata1->id_pribehu AND false_time IS NULL";
		    $sql2 = "SELECT COUNT($this->sqlvysledky.id) AS laps_count FROM $this->sqlvysledky WHERE $this->sqlvysledky.id_pribehu = :id_pribehu AND false_time IS NULL and race_time > 0";
		    $sth2 = $this->db->prepare($sql2);
		    $sth2->execute(Array(':id_pribehu' => $dbdata1->id_pribehu));
		    if($sth2->rowCount()){
			while($dbdata2 = $sth2->fetchObject()){
			    $pribehy[$dbdata1->id_pribehu] = $dbdata2->laps_count;
			}
		    }
		}
	 
	 
	    if($pribehy){
		arsort($pribehy,SORT_NUMERIC);
		$str .= '<h4 class="headline-results">'.$this->race_name.', výsledky podle příbehů</h4>';
		$str .= '<table class="table table-bordered table-hover table_vysledky">';
		$str .= '<thead><tr class="header"><th class="text-center">Poř</th><th>Příběh</th><th class="text-center">Počet kol</th></tr></thead><tbody>';
		$poradi = 1;
		foreach($pribehy as $key => $value){
		    $sql3 = "SELECT pribehy_teribear.nazev_pribehu FROM pribehy_teribear,$this->sqlzavod WHERE pribehy_teribear.id_pribehu = '$key'";
		    //$str .= $sql3;
		    $sth3 = $this->db->prepare($sql3);
		    $sth3->execute(Array(':id_pribehu' => $key));
		    if($sth3->rowCount()){
			$dbdata3 = $sth3->fetchObject(); 
			$str .= '<tr><td class="text-center">'.$poradi.'</td><td>'.$dbdata3->nazev_pribehu.'</td><td class="text-center">'.$value.'</td></tr>';
		    }
		    $poradi++; 
		}
		$str .= '</tbody></table>';    
	    }
	    
	    	
	$fcdata['results'] = $str;
	echo json_encode($fcdata);
	    
	}

    }
	
	private function ResultsBirthYear(){
	    $str = '';
	    $event_order = isset($_GET['event_order']) ? $_GET['event_order'] : 1; 
	    $time_order = isset($_GET['time_order']) ? $_GET['time_order'] : $this->MaxTimeOrder(); 
	    $birth_year = isset($_GET['birth_year']) ? $_GET['birth_year'] : date("Y")-1; 
	    $age = date("Y") - $birth_year;
	    $gender = isset($_GET['gender']) ? $_GET['gender'] : 'all'; 
	    $gender_array = Array('M' => 'Muži','Z' => 'Ženy');
	    if($gender == 'all'){ //všecko dokupy
		$sql1 = "SELECT $this->sqlvysledky.ids,$this->sqlvysledky.ids_alias,$this->sqlvysledky.race_time_sec,MAX($this->sqlvysledky.race_time) AS cilovy_cas,CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,osoby.psc AS stat,$this->sqlkategorie.nazev_k AS nazev_kategorie,tymy.nazev_tymu FROM $this->sqlvysledky,osoby,$this->sqlzavod,$this->sqlkategorie,tymy WHERE $this->sqlvysledky.time_order = :time_order AND $this->sqlkategorie.id_zavodu = :race_id AND $this->sqlkategorie.poradi_podzavodu = :event_order AND $this->sqlzavod.ids = $this->sqlvysledky.ids AND $this->sqlzavod.ido = osoby.ido AND osoby.rocnik = :birth_year AND $this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie AND tymy.id_tymu = $this->sqlzavod.id_tymu AND $this->sqlvysledky.false_time IS NULL AND $this->sqlvysledky.lap_only IS NULL GROUP BY $this->sqlvysledky.ids ORDER BY cilovy_cas ASC";
		$sth1 =  $this->db->prepare($sql1);
		$sth1->execute(Array(':time_order' => $time_order,':race_id' => $this->race_id,':event_order' => $event_order,':birth_year' => $birth_year));
		if($sth1->rowCount()){
		    $str .= '<h4 class="headline-results">'.$this->race_name.', výsledky ročníku '.$birth_year.' ('.$age.' let), bez rozdílu pohlaví</h4>';
		    $str .= '<table class="table table-hover">';
		    $str .= '<thead><tr class="header"><th class="text-center">#</th><th class="text-center">St.č</th><th>Jméno</th><th class="text-center">Ročník</th><th>Tým/Bydliště</th><th class="text-center">Stát</th><th>Kategorie</th>';
		    $str .= $this->TableHeader($time_order,$event_order,$this->cislo_kategorie);
		    $str .= '</tr></thead><tbody>';
		    $poradi = 1;
		    while($data1 = $sth1->fetchObject()){
			if($poradi == 1) $best_time = $data1->race_time_sec;
			$distance_time = $this->DynamicDistances($poradi,$data1->race_time_sec,$best_time);
			$str .= '<tr><td class="text-center">'.$poradi.'</td><td class="text-center">'.$data1->ids_alias.'</td><td>'.$data1->jmeno.'</td><td class="text-center">'.$data1->rocnik.'</td><td>'.$data1->nazev_tymu.'</td><td class="text-center">'.$data1->stat.'</td><td>'.$data1->nazev_kategorie.'</td>';
			$sql2 = "SELECT * FROM $this->sqlvysledky WHERE ids = :ids  AND false_time IS NULL AND lap_only IS NULL ORDER BY race_time ASC LIMIT 0,$time_order";
			$sth2 = $this->db->prepare($sql2);
			$sth2->execute(Array(':ids' => $data1->ids));
			$i = 1;
			while($val2 = $sth2->fetchObject()){
			    if($time_order == 1){
				$str .= '<td class="text-center">'.$val2->lap_time.'</td>';
				$str .= ($distance_time !== '00:00:00.00') ?  ('<td class="text-center">'.$distance_time.'</td>') : ('<td class="text-center">&nbsp;</td>');
				//$str .= ($val2->distance_overall != '00:00:00.00') ?  ('<td class="text-center">'.$val2->distance_overall.'</td>') : ('<td class="text-center">&nbsp;</td>');
			    }
			    else{
				if($i <= $time_order){
				    $str .= '<td class="text-center">'.$val2->lap_time.'</td>';
				}
				if($i == $time_order){
				    $str .= '<td class="text-center">'.$val2->race_time.'</td>';
				    $str .= ($distance_time !== '00:00:00.00') ?  ('<td class="text-center">'.$distance_time.'</td>') : ('<td class="text-center">&nbsp;</td>');
				}
			    }
			    $i++;
			}
			$str .= '</tr>';
			$poradi++;
		    }
		    $str .= '</tbody></table>';
		}
		else{
		    $str .= '<p>Žádný výsledek</p>';
		}
	    }
	    elseif($gender == 'all_2'){
		$str .= '<h4 class="headline-results">'.$this->race_name.', výsledky ročníku '.$birth_year.' ('.$age.' let), Muži/Ženy</h4>';
		$str .= '<table class="table table-hover noborder">';
		$k = 1; 
		foreach($gender_array as $key => $gender){
		    $sql1 = "SELECT $this->sqlvysledky.ids,$this->sqlvysledky.ids_alias,$this->sqlvysledky.race_time_sec,MAX($this->sqlvysledky.race_time) AS cilovy_cas,CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,osoby.psc AS stat,$this->sqlkategorie.nazev_k AS nazev_kategorie,tymy.nazev_tymu FROM $this->sqlvysledky,osoby,$this->sqlzavod,$this->sqlkategorie,tymy WHERE $this->sqlkategorie.id_zavodu = :race_id AND $this->sqlvysledky.time_order = :time_order AND $this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie AND $this->sqlkategorie.poradi_podzavodu = :id_event AND osoby.pohlavi = :gender AND osoby.rocnik = :birth_year AND $this->sqlzavod.ids = $this->sqlvysledky.ids AND $this->sqlzavod.ido = osoby.ido AND tymy.id_tymu = $this->sqlzavod.id_tymu AND false_time IS NULL GROUP BY ids ORDER BY cilovy_cas ASC";
		    $sth1 =  $this->db->prepare($sql1);
		    $sth1->execute(Array(':time_order' => $time_order,':race_id' => $this->race_id, ':gender' => $key,':id_event' => $event_order,':birth_year' => $birth_year));
		    if($sth1->rowCount()){
			$class = $k == 1 ? $class = 'nadpis nopadding' : 'nadpis';
			$str .= '<tr><td class="'.$class.'" colspan="3">'.$gender.'</td></tr>';
			$str .= '<tr class="header"><th class="text-center">#</th><th class="text-center">St.č</th><th>Jméno</th><th class="text-center">Ročník</th><th>Tým/Bydliště</th><th class="text-center">Stát</th><th>Kategorie</th>';
			$str .= $this->TableHeader($time_order,$event_order,$this->cislo_kategorie).'</tr>';
			$poradi = 1;
			while($data1 = $sth1->fetchObject()){
			    if($poradi == 1) $best_time = $data1->race_time_sec;
			    $distance_time = $this->DynamicDistances($poradi,$data1->race_time_sec,$best_time);
			    $str .= '<tr><td class="text-center">'.$poradi.'</td><td class="text-center">'.$data1->ids_alias.'</td><td>'.$data1->jmeno.'</td><td class="text-center">'.$data1->rocnik.'</td><td>'.$data1->nazev_tymu.'</td><td class="text-center">'.$data1->stat.'</td><td>'.$data1->nazev_kategorie.'</td>';
			    $sql2 = "SELECT * FROM $this->sqlvysledky WHERE ids = :ids  AND false_time IS NULL ORDER BY race_time ASC LIMIT 0,$time_order";
			    $sth2 = $this->db->prepare($sql2);
			    $sth2->execute(Array(':ids' => $data1->ids));
			    $i = 1;
			    while($val2 = $sth2->fetchObject()){
				if($time_order == 1){
				    $str .= '<td class="text-center">'.$val2->lap_time.'</td>';
				    $str .= ($distance_time !== '00:00:00.00') ?  ('<td class="text-center">'.$distance_time.'</td>') : ('<td class="text-center">&nbsp;</td>');
				}
				else{
				    if($i <= $time_order){
					$str .= '<td class="text-center">'.$val2->lap_time.'</td>';
				    }
				    if($i == $time_order){
					$str .= '<td class="text-center">'.$val2->race_time.'</td>';
					$str .= ($distance_time !== '00:00:00.00') ?  ('<td class="text-center">'.$distance_time.'</td>') : ('<td class="text-center">&nbsp;</td>');
				    }
				}		
				$i++;
			    }
			    $str .= '</tr>';
			    $poradi++;
			}
		    }
		    $k++;
		}
		$str .= '</table>';

	    }
	    else{
		$sql1 = "SELECT $this->sqlvysledky.ids,$this->sqlvysledky.ids_alias,$this->sqlvysledky.race_time_sec,MAX($this->sqlvysledky.race_time) AS cilovy_cas,CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,osoby.psc AS stat,$this->sqlkategorie.nazev_k AS nazev_kategorie,tymy.nazev_tymu FROM $this->sqlvysledky,osoby,$this->sqlzavod,$this->sqlkategorie,tymy WHERE $this->sqlvysledky.time_order = :time_order AND $this->sqlkategorie.poradi_podzavodu = :event_order AND $this->sqlzavod.ids = $this->sqlvysledky.ids AND $this->sqlzavod.ido = osoby.ido AND osoby.rocnik = :birth_year AND osoby.pohlavi = :gender AND $this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie AND tymy.id_tymu = $this->sqlzavod.id_tymu AND $this->sqlvysledky.false_time IS NULL AND $this->sqlvysledky.lap_only IS NULL GROUP BY $this->sqlvysledky.ids ORDER BY cilovy_cas ASC";
		$sth1 =  $this->db->prepare($sql1);
		$sth1->execute(Array(':time_order' => $time_order,':event_order' => $event_order,':birth_year' => $birth_year,':gender' => $gender));
		if($sth1->rowCount()){
		    $str .= '<h4 class="headline-results">'.$this->race_name.', výsledky ročníku '.$birth_year.' ('.$age.' let), '.$gender_array[$gender].'</h4>';
		    $str .= '<table class="table table-hover">';
		    $str .= '<thead><tr class="header"><th class="text-center">#</th><th class="text-center">St.č</th><th>Jméno</th><th class="text-center">Ročník</th><th>Tým/Bydliště</th><th class="text-center">Stát</th><th>Kategorie</th>';
		    $str .= $this->TableHeader($time_order,$event_order,$this->cislo_kategorie);
		    $str .= '</tr></thead><tbody>';
		    $poradi = 1;
		    while($data1 = $sth1->fetchObject()){
			if($poradi == 1) $best_time = $data1->race_time_sec;
			$distance_time = $this->DynamicDistances($poradi,$data1->race_time_sec,$best_time);
			$str .= '<tr><td class="text-center">'.$poradi.'</td><td class="text-center">'.$data1->ids_alias.'</td><td>'.$data1->jmeno.'</td><td class="text-center">'.$data1->rocnik.'</td><td>'.$data1->nazev_tymu.'</td><td class="text-center">'.$data1->stat.'</td><td>'.$data1->nazev_kategorie.'</td>';
			$sql2 = "SELECT * FROM $this->sqlvysledky WHERE ids = :ids  AND false_time IS NULL AND lap_only IS NULL ORDER BY race_time ASC LIMIT 0,$time_order";
			$sth2 = $this->db->prepare($sql2);
			$sth2->execute(Array(':ids' => $data1->ids));
			$i = 1;
			while($val2 = $sth2->fetchObject()){
			    if($time_order == 1){
				$str .= '<td class="text-center">'.$val2->lap_time.'</td>';
				$str .= ($distance_time === '00:00:00.00') ?  ('<td class="text-center">&nbsp;</td>') : ('<td class="text-center">'.$distance_time.'</td>');
			    }
			    else{
				if($i <= $time_order){
				    $str .= '<td class="text-center">'.$val2->lap_time.'</td>';
				}
				if($i == $time_order){
				    $str .= '<td class="text-center">'.$val2->race_time.'</td>';
				    $str .= ($distance_time !== '00:00:00.00') ?  ('<td class="text-center">'.$distance_time.'</td>') : ('<td class="text-center">&nbsp;</td>');
				}
			    }
			    $i++;
			}
			$str .= '</tr>';
			$poradi++;
		    }
		    $str .= '</tbody></table>';
		}
		else{
		    $str .= '<p>Žádný výsledek</p>';
		}
	    }
	    $fcdata['results'] = $str;
	    echo json_encode($fcdata);
	}

	public function ResultsGender(){
	    switch($this->results_type){
		case 22:
		     require_once 'results_gender/type_22.php';
		break;
		case 23:
		     require_once 'results_gender/type_23.php';
		break;
		default:
		     require_once 'results_gender/type_1.php';
	    }
            
	}
	
	
	public function ResultsGenderZal(){
	    $str = '';
	    $event_order = isset($_GET['event_order']) ? $_GET['event_order'] : 1; 
	    $time_order = isset($_GET['time_order']) ? $_GET['time_order'] : $this->MaxTimeOrder(); 
	    $time_order = isset($_GET['time_order']) ? $_GET['time_order'] : 1; 
	    $gender = isset($_GET['gender']) ? $_GET['gender'] : 'all'; 
	    //$colspan = 4 + $this->time_count;
	    $colspan = 4;
	    if(isset($_GET['time_order'])) $time_order = $_GET['time_order'];
	    if(isset($_GET['id_event'])) $id_event = $_GET['id_event'];
	    $gender_array = Array('M' => 'Muži','Z' => 'Ženy');
	    if($gender == 'all'){
		$str = '<h4 class="headline-results">'.$this->race_name.', výsledky Muži/Ženy</h4>';
		$str .= '<table class="table table-hover noborder">';
		$k = 1; 
		foreach($gender_array as $key => $gender){
		    // 18.6.15 $sql1 = "SELECT $this->sqlvysledky.ids,$this->sqlvysledky.ids_alias,MAX($this->sqlvysledky.race_time) AS cilovy_cas,CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,osoby.psc AS stat,$this->sqlkategorie.kod_k AS nazev_kategorie,tymy.nazev_tymu FROM $this->sqlvysledky,osoby,$this->sqlzavod,$this->sqlkategorie,tymy WHERE race_time > '0' AND $this->sqlvysledky.time_order = :time_order AND $this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie AND $this->sqlkategorie.poradi_podzavodu = :id_event AND osoby.pohlavi = :gender AND $this->sqlzavod.ids = $this->sqlvysledky.ids AND $this->sqlzavod.ido = osoby.ido AND tymy.id_tymu = $this->sqlzavod.id_tymu AND false_time IS NULL GROUP BY ids ORDER BY cilovy_cas ASC".$this->rows_limit;
		   
		    $sql1 = "SELECT $this->sqlvysledky.ids,$this->sqlvysledky.cip,$this->sqlvysledky.ids_alias,MAX($this->sqlvysledky.race_time) AS cilovy_cas,CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,osoby.psc AS stat,$this->sqlkategorie.kod_k AS nazev_kategorie,tymy.nazev_tymu FROM $this->sqlvysledky,osoby,$this->sqlzavod,$this->sqlkategorie,tymy WHERE race_time > '0' AND $this->sqlvysledky.time_order = :time_order AND $this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie AND $this->sqlkategorie.poradi_podzavodu = :id_event AND osoby.pohlavi = :gender AND $this->sqlzavod.cip = $this->sqlvysledky.cip AND $this->sqlzavod.ido = osoby.ido AND tymy.id_tymu = $this->sqlzavod.id_tymu AND $this->sqlvysledky.false_time IS NULL AND $this->sqlvysledky.lap_only IS NULL GROUP BY ids ORDER BY cilovy_cas ASC".$this->rows_limit;
		    
		    $sth1 =  $this->db->prepare($sql1);
		    $sth1->execute(Array(':time_order' => $time_order,':gender' => $key,':id_event' => $event_order));
		    if($sth1->rowCount()){
			$class = $k == 1 ? $class = 'nadpis nopadding' : 'nadpis';
			$str .= '<tr><td class="'.$class.'" colspan="3">'.$gender.'</td></tr>';
			$str .= '<tr class="header"><th class="text-center">#</th><th class="text-center">St.č</th><th>Jméno</th><th class="text-center">Ročník</th><th>Tým/Bydliště</th><th class="text-center">Stát</th><th class="text-center">Kat</th>';
			$str .= $this->TableHeader($time_order,$event_order,$this->cislo_kategorie).'</tr>';
			$poradi = 1;
			while($data1 = $sth1->fetchObject()){
			    $str .= '<tr><td class="text-center">'.$poradi.'</td><td class="text-center">'.$data1->ids_alias.'</td><td>'.$data1->jmeno.'</td><td class="text-center">'.$data1->rocnik.'</td><td>'.$data1->nazev_tymu.'</td><td class="text-center">'.$data1->stat.'</td><td class="text-center">'.$data1->nazev_kategorie.'</td>';
			    //18.6.15 $sql2 = "SELECT * FROM $this->sqlvysledky WHERE ids = :ids  AND false_time IS NULL ORDER BY race_time ASC LIMIT 0,$time_order";
			    $sql2 = "SELECT * FROM $this->sqlvysledky WHERE cip = :cip  AND false_time IS NULL ORDER BY race_time ASC LIMIT 0,$time_order";
			    $sth2 = $this->db->prepare($sql2);
			    $sth2->execute(Array(':cip' => $data1->cip));
			    $i = 1;
			    $missing_time = false;
			    while($val2 = $sth2->fetchObject()){
				if($time_order == 1){
				    $str .= '<td class="text-center">'.$val2->lap_time.'</td>';
				    $str .= ($val2->distance_gender != '00:00:00.00') ?  ('<td class="text-center">'.$val2->distance_gender.'</td>') : ('<td class="text-center">&nbsp;</td>');
				}
				else{
				    if($i <= $time_order){
					if($val2->lap_time != '00:00:00.00' AND $missing_time == false){
					    $str .= '<td class="text-center">'.$val2->lap_time.'</td>';
					}
					else{
					    $str .= '<td class="text-center">&nbsp;</td>';
					    $missing_time = true; //byl nalezen nulový čas a tím pádem se vy výsledcích objeví pouze výsledný čas
					}
				    }
				    if($i == $time_order){
					$str .= ($val2->race_time != '00:00:00.00') ? ('<td class="text-center">'.$val2->race_time.'</td>') : ('<td class="text-center">&nbsp;</td>');
					$str .= ($val2->distance_gender != '00:00:00.00') ?  ('<td class="text-center">'.$val2->distance_gender.'</td>') : ('<td class="text-center">&nbsp;</td>');
				    }
				}
				$i++;
			    }
			    $poradi++;
			}
		    }
		    $str .= $this->DNFGender(1,$key);
		    $str .= '</tr>';
		    $k++;
		}
		$str .= '</table>';
	    }
	    else{
		$sql1 = "SELECT $this->sqlvysledky.ids,$this->sqlvysledky.cip,$this->sqlvysledky.ids_alias,MAX($this->sqlvysledky.race_time) AS cilovy_cas,CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,osoby.psc AS stat,$this->sqlkategorie.kod_k AS nazev_kategorie,tymy.nazev_tymu FROM $this->sqlvysledky,osoby,$this->sqlzavod,$this->sqlkategorie,tymy WHERE race_time > '0' AND $this->sqlvysledky.time_order = :time_order AND $this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie AND $this->sqlkategorie.poradi_podzavodu = :id_event AND osoby.pohlavi = :gender AND $this->sqlzavod.ids = $this->sqlvysledky.ids AND $this->sqlzavod.ido = osoby.ido AND tymy.id_tymu = $this->sqlzavod.id_tymu AND false_time IS NULL GROUP BY ids ORDER BY cilovy_cas ASC".$this->rows_limit;
		    $sth1 =  $this->db->prepare($sql1);
		    $sth1->execute(Array(':time_order' => $time_order,':gender' => $gender,':id_event' => $event_order));
		    if($sth1->rowCount()){
			$str = '<h4 class="headline-results">'.$this->race_name.', kategorie '.$gender_array[$gender].'</h4>';
			$str .= '<table class="table table-hover">';
			$str .= '<thead><tr class="header"><th class="text-center">#</th><th class="text-center">St.č</th><th>Jméno</th><th class="text-center">Ročník</th><th>Tým/Bydliště</th><th class="text-center">Stát</th><th class="text-center">Kat</th>';
			$str .= $this->TableHeader($time_order,$event_order,$this->cislo_kategorie).'</tr></thead><tbody>';
			$poradi = 1;
			while($data1 = $sth1->fetchObject()){
			    $str .= '<tr><td class="text-center">'.$poradi.'</td><td class="text-center">'.$data1->ids_alias.'</td><td>'.$data1->jmeno.'</td><td class="text-center">'.$data1->rocnik.'</td><td>'.$data1->nazev_tymu.'</td><td class="text-center">'.$data1->stat.'</td><td class="text-center">'.$data1->nazev_kategorie.'</td>';
			    $sql2 = "SELECT * FROM $this->sqlvysledky WHERE cip = '$data1->cip'  AND false_time IS NULL ORDER BY race_time ASC LIMIT 0,$time_order";
			    $sth2 = $this->db->prepare($sql2);
			    $sth2->execute(Array(':cip' => $data1->ids));
			    $i = 1;
			    $missing_time = false;
			    while($val2 = $sth2->fetchObject()){
				if($time_order == 1){
				    $str .= '<td class="text-center">'.$val2->lap_time.'</td>';
				    $str .= ($val2->distance_gender != '00:00:00.00') ?  ('<td class="text-center">'.$val2->distance_gender.'</td>') : ('<td class="text-center">&nbsp;</td>');
				}
				else{
				    if($i <= $time_order){
					if($val2->lap_time != '00:00:00.00' AND $missing_time == false){
					    $str .= '<td class="text-center">'.$val2->lap_time.'</td>';
					}
					else{
					    $str .= '<td class="text-center">&nbsp;</td>';
					    $missing_time = true; //byl nalezen nulový čas a tím pádem se vy výsledcích objeví pouze výsledný čas
					}
				    }
				    if($i == $time_order){
					$str .= ($val2->race_time != '00:00:00.00') ? ('<td class="text-center">'.$val2->race_time.'</td>') : ('<td class="text-center">&nbsp;</td>');
					$str .= ($val2->distance_gender != '00:00:00.00') ?  ('<td class="text-center">'.$val2->distance_gender.'</td>') : ('<td class="text-center">&nbsp;</td>');
				    }
				}
				$i++;
			    }
			    $poradi++;
			}
			$str .= $this->DNFGender(1,$gender);
			$str .= '</tr>';
		    }
		    else{
			$str .= '<p>Žádný výsledek</p>';
		    }
		$str .= '</tbody></table>';
	    }
	    $change_control_file_info = $this->ChangeControlFileInfo();
	    $fcdata['last_modified'] = $change_control_file_info['last_modified'];
	    $fcdata['change_control_file'] = $change_control_file_info['change_control_file'];
	    $fcdata['results'] = $str;
	    echo json_encode($fcdata);
	}
	
	private function ResultsCategory(){
	    $str = '';
	    $category_id = isset($_GET['category_id']) ? $_GET['category_id'] : 'all'; 
            
            
            //provizorka na city sprint
          //  $category_id = 'all';
                //if(isset($_GET['heat_id'])){
		//$this->heat_id = $_GET['heat_id'];
	    //}
	    
            
            
            
	    require_once 'results_category/type_'.$this->results_type.'.php'; //zavoláme příslušný typ výsledků z šablon
	    
	    /*
	    $fcdata = Array();
	    $change_control_file_info = $this->ChangeControlFileInfo();
	    $fcdata['last_modified'] = $change_control_file_info['last_modified'];
	    $fcdata['change_control_file'] = $change_control_file_info['change_control_file'];
	    $fcdata['results'] = $str;
	    echo json_encode($fcdata);
	    */
	    
	    if($this->race_id == 46000){ //pouze CC
		if(isset($_GET['murinoha'])){
		 if($_GET['murinoha'] == 'ResultsCategory'){
		     if($_GET['category_id'] != 'all'){
			$str .= '<div class="paticka">';
			$str .= '<div class="delka_trati">Délka trati: 9000m</div>';
			$str .= '<div>Hlavní časoměřič a vyhodnocení, TimeChip, www.timechip.cz</div>';
			$str .= '<div>Ředitel závodu: Roman Konečný</div>';
			$str .= '<div>Jury: Karel Kučera </div>';
			$str .= '<div>Výsledky podléhají schválení jury </div>';
			$str .= '<div>Čas vyvěšení:  </div>';
			$str .= '</div>';  
		     }
		 }
	     }

	    }

	    
	    
	    
	    if(isset($_GET['murinoha'])){
		//tlačítko spustit autoreading, nebo jakákoli změna selectu
	       if($_GET['murinoha'] != 'ExportToPDF'){
		    $fcdata = Array();
		    $change_control_file_info = $this->ChangeControlFileInfo();
		    $fcdata['last_modified'] = $change_control_file_info['last_modified'];
		    $fcdata['change_control_file'] = $change_control_file_info['change_control_file'];
		    $fcdata['results'] = $str;
		    echo json_encode($fcdata);
		}
		 else{ //pokud jsou volány výsledky z indexu
		    return $str;
		}
	   }
	   else{ //pokud jsou volány výsledky z indexu
	       return $str;
	   }
	}

	
	private function ResultsOverall(){
	    $str = '';
	    require_once 'results_overall/type_'.$this->results_type.'.php'; //zavoláme příslušný typ výsledků z šablon
	    if(isset($_GET['murinoha'])){
		//tlačítko spustit autoreading, nebo jakákoli změna selectu
	       if($_GET['murinoha'] != 'ExportToPDF'){
		    $fcdata = Array();
		    $change_control_file_info = $this->ChangeControlFileInfo();
		    $fcdata['last_modified'] = $change_control_file_info['last_modified'];
		    $fcdata['change_control_file'] = $change_control_file_info['change_control_file'];
		    $fcdata['results'] = $str;
		    echo json_encode($fcdata);
		}
		 else{ //pokud jsou volány výsledky z indexu
		    return $str;
		}
	   }
	   else{ //pokud jsou volány výsledky z indexu
	       return $str;
	   }
	}
	
    private function ResultsBestLaps(){
	    $fcdata = Array();
	    $str = '';
	    $gender_array = Array('M' => 'Muži','Z' => 'Ženy');
	    $str .= '<table class="table table-striped table-bordered table-hover">';
	    foreach ($gender_array as $key => $gender ){
		$str .= '<tr><td colspan="2">'.$gender.'</td><td class="text-center">Ročník</td><td>Tým/Bydliště</td><td>Kategorie</td><td class="text-center">Kolo</td><td class="text-center">Čas</td></tr>';
		//24 hodin
		//$sql1 = "SELECT $this->sqlvysledky.cip,$this->sqlvysledky.lap_time,$this->sqlvysledky.lap_count,CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,osoby.psc AS stat,$this->sqlkategorie.nazev_k AS nazev_kategorie,tymy.nazev_tymu FROM $this->sqlvysledky,osoby,$this->sqlzavod,$this->sqlkategorie,tymy WHERE race_time > 0 AND $this->sqlzavod.cip = $this->sqlvysledky.cip AND $this->sqlzavod.ido = osoby.ido AND osoby.pohlavi= '$key' AND $this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie$this->cislo_kategorie AND tymy.id_tymu = $this->sqlzavod.tym AND $this->sqlvysledky.false_time IS NULL AND $this->sqlvysledky.lap_only IS NULL AND $this->sqlvysledky.reader = 'CIL' ORDER BY $this->sqlvysledky.lap_time ASC LIMIT 0,10";
		
		//letovice
		$sql1 = "SELECT $this->sqlvysledky.cip,$this->sqlvysledky.lap_time,$this->sqlvysledky.lap_count,CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,osoby.psc AS stat,$this->sqlkategorie.nazev_k AS nazev_kategorie,tymy.nazev_tymu FROM $this->sqlvysledky,osoby,$this->sqlzavod,$this->sqlkategorie,tymy WHERE race_time > 0 AND $this->sqlzavod.cip = $this->sqlvysledky.cip AND $this->sqlzavod.ido = osoby.ido AND osoby.pohlavi= '$key' AND $this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie$this->cislo_kategorie AND tymy.id_tymu = $this->sqlzavod.tym AND $this->sqlvysledky.false_time IS NULL AND $this->sqlvysledky.lap_only IS NULL AND $this->sqlvysledky.reader = 'CIL' AND $this->sqlkategorie.poradi_podzavodu < 4 ORDER BY $this->sqlvysledky.lap_time ASC LIMIT 0,10";



		//normal
		//$sql1 = "SELECT $this->sqlvysledky.cip,$this->sqlvysledky.lap_time,$this->sqlvysledky.lap_count,CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,osoby.psc AS stat,$this->sqlkategorie.nazev_k AS nazev_kategorie,tymy.nazev_tymu FROM $this->sqlvysledky,osoby,$this->sqlzavod,$this->sqlkategorie,tymy WHERE race_time > 0 AND $this->sqlzavod.cip = $this->sqlvysledky.cip AND $this->sqlzavod.ido = osoby.ido AND osoby.pohlavi= '$key' AND $this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie$this->cislo_kategorie AND tymy.id_tymu = $this->sqlzavod.tym AND $this->sqlvysledky.false_time IS NULL AND $this->sqlvysledky.lap_only IS NULL ORDER BY $this->sqlvysledky.lap_time ASC LIMIT 0,10";
		//echo $sql1."\n";
		$sth1 = $this->db->prepare($sql1);
		$sth1->execute();
		if($sth1->rowCount()){
		    while($dbdata1 = $sth1->fetchObject()){
			$str .= '<tr><td class="text-center">'.$dbdata1->cip.'</td><td>'.$dbdata1->jmeno.'</td><td class="text-center">'.$dbdata1->rocnik.'</td><td>'.$dbdata1->nazev_tymu.'</td><td>'.$dbdata1->nazev_kategorie.'</td><td class="text-center">'.$dbdata1->lap_count.'</td><td class="text-center">'.$dbdata1->lap_time.'</td></tr>';
		    }
		}
	    }
	    $str .= '</table>';
        $fcdata['results'] = $str;
	    echo json_encode($fcdata);
	}

	
	private function TableHeaderTymyOrlice($time_order,$event_order){
	    /*
	     * Použito při triatlonu týmů v Orlici, možno použít při jakémkoli závodě tohooto typu
	     */
	    $str = '';
	    if(!$this->ruzny_pocet_casu){ //pokud nejsou nastaveny různé počty časů, bere to hodnoty z prvního podzávodu
		$event_order = 1;
	    }
	    $odstup = 'Odstup';
	    
	    $sql1 = "SELECT nazev_discipliny,id_discipliny FROM $this->sqldiscipliny WHERE id_zavodu = :race_id AND poradi_podzavodu = :event_order ORDER BY poradi_discipliny";
	    $sth1 = $this->db->prepare($sql1);
	    $sth1->execute(Array(':race_id' => $this->race_id,':event_order' => $event_order));
	    if($sth1->rowCount()){
		$pocet_casu = 1;
		while($data1 = $sth1->fetchObject()){
		    if($pocet_casu <= $time_order){
			$str .= '<th class="nazev_discipliny">'.$data1->nazev_discipliny.'</th>'; 
		    }
		    $pocet_casu++;	
		}
		if($time_order == 1){
		    $str .= '<th rowspan="2" class="text-center">'.$odstup.'</th>';
		}
		else{
		    $str .= '<th rowspan="2" class="text-center">Celkem</th><th  rowspan="2" class="text-center">'.$odstup.'</th>';
		}
	    }
	    return $str;
	}
	
        private function TableHeaderTymyOrliceCelkove($time_order,$event_order){
	    /*
	     * Použito při triatlonu týmů v Orlici, možno použít při jakémkoli závodě tohooto typu
	     */
	    $str = '';
	    if(!$this->ruzny_pocet_casu){ //pokud nejsou nastaveny různé počty časů, bere to hodnoty z prvního podzávodu
		$event_order = 1;
	    }
	    $odstup = 'Odstup';
	    
	    $sql1 = "SELECT nazev_discipliny,id_discipliny FROM $this->sqldiscipliny WHERE id_zavodu = :race_id AND poradi_podzavodu = :event_order ORDER BY poradi_discipliny";
	    $sth1 = $this->db->prepare($sql1);
	    $sth1->execute(Array(':race_id' => $this->race_id,':event_order' => $event_order));
	    if($sth1->rowCount()){
		$pocet_casu = 1;
		while($data1 = $sth1->fetchObject()){
		    if($pocet_casu <= $time_order){
			$str .= '<th class="nazev_discipliny text-left">'.$data1->nazev_discipliny.'</th>'; 
		    }
		    $pocet_casu++;	
		}
	    }
	    return $str;
	}
	

	private function TableHeader($time_order,$event_order,$cislo_kategorie){
	    $str = '';
	    if(!$this->ruzny_pocet_casu){ //pokud nejsou nastaveny různé počty časů, bere to hodnoty z prvního podzávodu
		$event_order = 1;
	    }
	    $odstup = 'Odstup';
	    if($cislo_kategorie == '_2'){
		$odstup = 'Odstup za vítězem';
	    }
	    $sql1 = "SELECT nazev_discipliny,id_discipliny FROM $this->sqldiscipliny WHERE id_zavodu = :race_id AND poradi_podzavodu = :event_order ORDER BY poradi_discipliny";
	    $sth1 = $this->db->prepare($sql1);
	    $sth1->execute(Array(':race_id' => $this->race_id,':event_order' => $event_order));
	    if($sth1->rowCount()){
		$pocet_casu = 1;
		while($data1 = $sth1->fetchObject()){
		    if($pocet_casu <= $time_order){
			$str .= '<th class="text-center">'.$data1->nazev_discipliny.'</th>';
		    }
		    $pocet_casu++;	
		}
		if($time_order == 1){
		    $str .= '<th class="text-center">'.$odstup.'</th>';
		}
		else{
		    $str .= '<th class="text-center">Celkem</th><th class="text-center">'.$odstup.'</th>';
		}
	    }
	    return $str;
	}
	
	private function TableHeaderCasyNarustem($time_order,$event_order,$cislo_kategorie){
	    $str = '';
	    if(!$this->ruzny_pocet_casu){ //pokud nejsou nastaveny různé počty časů, bere to hodnoty z prvního podzávodu
		$event_order = 1;
	    }
	    $odstup = 'Odstup';
	    if($cislo_kategorie == '_2'){
		$odstup = 'Odstup za vítězem';
	    }
	    $sql1 = "SELECT nazev_discipliny,id_discipliny FROM $this->sqldiscipliny WHERE id_zavodu = :race_id AND poradi_podzavodu = :event_order ORDER BY poradi_discipliny";
	    $sth1 = $this->db->prepare($sql1);
	    $sth1->execute(Array(':race_id' => $this->race_id,':event_order' => $event_order));
	    if($sth1->rowCount()){
		$pocet_casu = 1;
		while($data1 = $sth1->fetchObject()){
		    if($pocet_casu <= $time_order){ 
			$str .= '<th class="text-center">'.$data1->nazev_discipliny.'</th>';
			if($time_order > 1){
			    //$str .= '<th class="text-center">#PM</th>';
			    $str .= '<th class="text-center">#</th>';
			    
			}
			if($pocet_casu > 1 && $pocet_casu < $time_order){
			    //$str .= '<th class="text-center">#PP</th>';
			    $str .= '<th class="text-center">#</th>';
			}
		    }
		    $pocet_casu++;	
		}
		
		$str .= '<th class="text-center">'.$odstup.'</th>';
	    }
	    return $str;
	}


	
	private function TableHeaderExtend($time_order,$event_order,$cislo_kategorie){
	    $str = '';
	    if(!$this->ruzny_pocet_casu){ //pokud nejsou nastaveny různé počty časů, bere to hodnoty z prvního podzávodu
		$event_order = 1;
	    }
	    $odstup = 'Odstup';
	    if($cislo_kategorie == '_2'){
		$odstup = 'Odstup za vítězem';
	    }
	    $sql1 = "SELECT nazev_discipliny,id_discipliny FROM $this->sqldiscipliny WHERE id_zavodu = :race_id AND poradi_podzavodu = :event_order ORDER BY poradi_discipliny";
	    $sth1 = $this->db->prepare($sql1);
	    $sth1->execute(Array(':race_id' => $this->race_id,':event_order' => $event_order));
	    if($sth1->rowCount()){
		$pocet_casu = 1;
		while($data1 = $sth1->fetchObject()){
		    if($pocet_casu <= $time_order){ 
			$str .= '<th class="text-center">'.$data1->nazev_discipliny.'</th>';
			if($time_order > 1){
			    $str .= '<th class="text-center">#PM</th>';
			    
			}
			//if($pocet_casu > 1 && $pocet_casu < $time_order){
			    //$str .= '<th class="text-center">#PP</th>';
			//}
		    }
		    $pocet_casu++;	
		}
		
		if($time_order == 1){
		    $str .= '<th class="text-center">'.$odstup.'</th>';
		}
		else{
		    $str .= '<th class="text-center">Celkem</th><th class="text-center">'.$odstup.'</th>';
		}
	}
	    return $str;
	}
	
	
	private function TableHeaderValachiarun($time_order,$event_order,$cislo_kategorie){
	    $str = '';
	    if(!$this->ruzny_pocet_casu){ //pokud nejsou nastaveny různé počty časů, bere to hodnoty z prvního podzávodu
		$event_order = 1;
	    }
	    $odstup = 'Odstup';
	    if($cislo_kategorie == '_2'){
		$odstup = 'Odstup za vítězem';
	    }
	    $sql1 = "SELECT nazev_discipliny,id_discipliny FROM $this->sqldiscipliny WHERE id_zavodu = :race_id AND poradi_podzavodu = :event_order ORDER BY poradi_discipliny";
	    $sth1 = $this->db->prepare($sql1);
	    $sth1->execute(Array(':race_id' => $this->race_id,':event_order' => $event_order));
	    if($sth1->rowCount()){
		$pocet_casu = 1;
		while($data1 = $sth1->fetchObject()){
		    if($pocet_casu <= $time_order){
			$str .= '<th class="text-center">'.$data1->nazev_discipliny.'</th>';
		    }
		    $pocet_casu++;	
		}
		if($time_order == 1){
		    //$str .= '<th class="text-center">'.$odstup.'</th>';
		}
		else{
		    //$str .= '<th class="text-center">Celkem</th><th class="text-center">'.$odstup.'</th>';
		    //$str .= '<th class="text-center">'.$odstup.'</th>';
		}
	    }
	    return $str;
	}
    

	private function GenderListSelect(){
	    $fcdata = '<select id="gender_list" class="form-control input-lg"><option value="all">All</option><option value="M">Muži</option><option value="Z">Ženy</option></select>'."\r\n";
	    echo $fcdata;
	}

	private function BirthYearListSelect(){
	    $oldest = 1924;
	    $i = date("Y")-1;
	    $k = 1;
	    $fcdata = '<select id="birth_year_list" class="form-control">';
	    while($i > $oldest){
		$fcdata .= '<option value="'.$i.'">'.$k.' - '.$i.'</option>';
		$i--;
		$k++;
	    }
	    $fcdata.= '</select>'."\r\n";
	    $fcdata .= '<select id="gender_list" class="form-control"><option value="all">Dohromady</option><option value="all_2">Dohromady odděleně</option><option value="M">Muži</option><option value="Z">Ženy</option></select>'."\r\n";
	    echo $fcdata;
	}
	
	private function MaxTimeOrder() {  //tady se musí dodělat podzávody
	    $sql1 = "SELECT MAX(time_order) AS max_time_order FROM $this->sqlvysledky";
	    $sth1 = $this->db->query($sql1);
	    $data1 = $sth1->fetchObject();
	    return $data1->max_time_order;
	}
	private function DetailIds(){
	    $str = '';
	    $hash_url = '#'.$this->race_year.'/'.$this->race_id.'/';
	    if($this->results_type == 5 || $this->results_type == 6){
		$sql1 = "SELECT $this->sqlvysledky.*,CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno FROM $this->sqlvysledky,$this->sqlzavod,osoby WHERE "
			  . "$this->sqlvysledky.ids = '{$_GET['ids']}' AND reader = 'CIL' AND $this->sqlvysledky.cip = $this->sqlzavod.cip AND $this->sqlzavod.ido = osoby.ido "
			  . "ORDER BY race_time";
			  //echo $sql1;
		$sth1 = $this->db->prepare($sql1);
		$sth1->execute();
		if($sth1->rowCount()){
		    $str .= '<table class="table table-bordered table-hover table_vysledky text-center detail_ids">';
		    $str .= '<tr class="header"><th class="text-center">Kolo</th><th class="text-center">Jméno</th><th>St.č</th><th class="text-center">Čas závodu</th><th class="text-center">Čas kola</th><th class="text-center">Rychlost (km/h)</th></tr>';
		    while($dbdata1 = $sth1->fetchObject()){
			$prumerna_rychlost = round($this->delka_kola/$dbdata1->lap_time_sec*3600,1);
			$str .= '<tr id="'.$dbdata1->cip.'">';
			$str .= '<td>'.$dbdata1->lap_count.'</td>';
			$str .= '<td><a class="detail_cipu" href="'.$hash_url.'vysledky">'.$dbdata1->jmeno.'</a></td>';
			$str .= '<td>'.$dbdata1->cip.'</td>';
			$str .= '<td>'.$dbdata1->race_time.'</td>';
			$str .= '<td>'.$dbdata1->lap_time.'</td>';
			$str .= '<td>'.$prumerna_rychlost.'</td>';
			$str .= '</tr>';
		    }
		    $str .= '</table>';
		}

	    }
	    else{
		$sql1 = "SELECT * FROM $this->sqlvysledky WHERE ids = '{$_GET['ids']}' ORDER BY race_time";
		$sth1 = $this->db->prepare($sql1);
		$sth1->execute();
		if($sth1->rowCount()){
		    $str .= '<table class="table table-bordered table-hover table_vysledky text-center detail_ids">';
		    $str .= '<tr class="header"><th class="text-center">Kolo</th><th class="text-center">Čas závodu</th><th class="text-center">Čas kola</th><th class="text-center">Rychlost (km/h)</th></tr>';
		    while($dbdata1 = $sth1->fetchObject()){
			if($dbdata1->reader != 'pulkolo'){
			    $prumerna_rychlost = round($this->delka_kola/$dbdata1->lap_time_sec*3600,1);
			}
			else{
			    $delka_kola = 4;
			    $prumerna_rychlost = round($delka_kola/$dbdata1->lap_time_sec*3600,1);

			}
			$str .= '<tr>';
			$str .= '<td>'.$dbdata1->lap_count.'</td>';
			$str .= '<td>'.$dbdata1->race_time.'</td>';
			$str .= '<td>'.$dbdata1->lap_time.'</td>';
			$str .= '<td>'.$prumerna_rychlost.'</td>';
			$str .= '</tr>';
		    }
		    $str .= '</table>';
		}
	    }
	    echo $str;
	}
	private function DynamicDistances($poradi,$race_time_sec,$best_time){
	    $distance = round($race_time_sec - $best_time,2); //musíme zaokrouhlovat, bo jinak to PHP při určitých hodnotách počítá blbě
	    //echo $distance."\n";
	    $pole_casu = explode(".",$distance);
	    //print_r($pole_casu);
	    //echo "\n";
	    if(isset($pole_casu[1])){
		//echo $pole_casu[1]."\n";
		if(strlen($pole_casu[1]) < 2){
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
	private function DynamicDistancesTisiciny($poradi,$race_time_sec,$best_time){
	    //$distance = round($race_time_sec - $best_time,3); //musíme zaokrouhlovat, bo jinak to PHP při určitých hodnotách počítá blbě
	    $distance = round($race_time_sec - $best_time,3); //musíme zaokrouhlovat, bo jinak to PHP při určitých hodnotách počítá blbě
	    //echo $distance."\n";
	    $pole_casu = explode(".",$distance);
	    //print_r($pole_casu);
	    //echo "\n";
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
		 $distance_time = gmdate("H:i:s",$pole_casu[0]).'.000';
	    }
	    return $distance_time;
	}

	private function DNFOverall($typ){
	    /*
	     * typy
	     *  1 - jednotlivci
	     */
	    $str = '';
	    $hash_url = '#'.$this->race_year.'/'.$this->race_id.'/';
	    if($typ == 1){
		//vybereme jméno a příjmení lidí, kteří jsou uvedeni v tabulce DNF
		$sql1 = "SELECT CONCAT_WS(' ',$this->sqlosoby.prijmeni,$this->sqlosoby.jmeno) AS jmeno,$this->sqlosoby.rocnik,$this->sqlosoby.psc AS stat,$this->sqlosoby.pohlavi,$this->sqlkategorie.kod_k AS nazev_kategorie,tymy.nazev_tymu,$this->sqlzavod.cip,$this->sqlzavod.ids_alias,$this->sqldnf.* FROM $this->sqlosoby,$this->sqlzavod,$this->sqlkategorie,tymy,$this->sqldnf WHERE $this->sqlzavod.cip = $this->sqldnf.cip AND $this->sqldnf.race_id = :race_id AND $this->sqldnf.time_count < :time_order AND $this->sqlzavod.ido = $this->sqlosoby.ido AND $this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie$this->cislo_kategorie AND tymy.id_tymu = $this->sqlzavod.prislusnost AND $this->sqlzavod.poradi_podzavodu = :event_order ORDER BY $this->sqldnf.time_count DESC,$this->sqldnf.cip ASC";
		$sth1 = $this->db->prepare($sql1);
		$sth1->execute(Array(':race_id' => $this->race_id,':event_order' => $this->event_order,':time_order' => $this->time_order));
		if($sth1->rowCount()){
		    while($dbdata1 = $sth1->fetchObject()){
			$str .= '<tr><td class="text-center">DNF</td><td class="text-center">'.$dbdata1->ids_alias.'</td><td>'.$dbdata1->jmeno.'</td><td class="text-center">'.$dbdata1->rocnik.'</td><td>'.$this->NahrazkaPomlcky($dbdata1->nazev_tymu).'</td><td class="text-center">'.$dbdata1->stat.'</td><td class="text-center">'.$dbdata1->nazev_kategorie.'</td><td class="text-center">DNF</td><td class="text-center">'.$dbdata1->pohlavi.'</td><td class="text-center">DNF</td>';
			//podíváme se, kolik mají časů
			$sql2 = "SELECT * FROM $this->sqlvysledky WHERE cip = :cip AND false_time IS NULL AND lap_only IS NULL ORDER BY race_time ASC LIMIT 0,$this->time_order";
			$sth2 = $this->db->prepare($sql2);
			$sth2->execute(Array(':cip' => $dbdata1->cip));
			$i = 0;//záměrně tady a ne jen v případě, že se najdou nějaké časy, aby to mohlo být použito i v případě DNF, kteřé nemsjí ani jeden čas
			if($sth2->rowCount()){//pokud nějaké mají...
			    while($dbdata2 = $sth2->fetchObject()){
				$i++; 
				if($this->time_order == 1){//pokud je to první čas 
				    $str .= '<td class="text-center">'.$dbdata2->lap_time.'</td>';
				    $str .= ($dbdata2->distance_overall != '00:00:00.00') ?  ('<td class="text-center">'.$dbdata2->distance_overall.'</td>') : ('<td class="text-center">&nbsp;</td>');
				}
				else{//pokud je to jiný než první čas
				    if($i <= $this->time_order){ 
					if($dbdata2->lap_time != '00:00:00.00'){
					    $str .= '<td class="text-center">'.$dbdata2->lap_time.'</td>';
					    $str .= '<td class="text-center"><i>'.$dbdata2->rank_overall_lap.'</i></td>';
					   // if($i > 1 && $i < $this->time_order){
						//$str .= '<td class="text-center"><i>'.$dbdata2->rank_overall.'</i></td>';
					    //}

					}
					else{
					    $str .= '<td class="text-center">&nbsp;</td>';
					}
				    }
				}
			    }
			}
			//tady se dopíšou prázdné buňky pro časy, které už nejsou, v případě, že někdo nemá ani jeden čas, tak $i zůstalo na hodnotě 0 a dopíšou se prázdn buňky pro všecky
			if($i < $this->time_order){ 
			    while($i <= $this->time_order){
				//$str .= '<td class="text-center">&nbsp;</td>';
				$i++;
			    }
			    // a tady pro odstup
			    //$str .= '<td class="text-center">&nbsp;</td>';
			}

		    }
		return $str;
		}
	    }
	    elseif($typ == 19){
		//vybereme jméno a příjmení lidí, kteří jsou uvedeni v tabulce DNF
		$sql1 = "SELECT CONCAT_WS(' ',$this->sqlosoby.prijmeni,$this->sqlosoby.jmeno) AS jmeno,$this->sqlosoby.rocnik,$this->sqlosoby.psc AS stat,$this->sqlkategorie.nazev_k AS nazev_kategorie,tymy.nazev_tymu,$this->sqlzavod.cip,$this->sqlzavod.ids_alias,$this->sqldnf.* FROM $this->sqlosoby,$this->sqlzavod,$this->sqlkategorie,tymy,$this->sqldnf WHERE $this->sqlzavod.cip = $this->sqldnf.cip AND $this->sqldnf.race_id = :race_id AND $this->sqldnf.event_order = :event_order AND $this->sqldnf.event_order = $this->sqlzavod.poradi_podzavodu AND $this->sqlzavod.ido = $this->sqlosoby.ido AND $this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie$this->cislo_kategorie AND tymy.id_tymu = $this->sqlzavod.prislusnost ORDER BY $this->sqldnf.time_count DESC,$this->sqldnf.cip ASC";
		$sth1 = $this->db->prepare($sql1);
		$sth1->execute(Array(':race_id' => $this->race_id,':event_order' => $this->event_order));
		if($sth1->rowCount()){
		    while($dbdata1 = $sth1->fetchObject()){
			$str .= '<tr><td class="text-center">DSQ</td><td class="text-center">'.$dbdata1->ids_alias.'</td><td>'.$dbdata1->jmeno.'</td><td class="text-center">'.$dbdata1->rocnik.'</td><td>'.$this->NahrazkaPomlcky($dbdata1->nazev_tymu).'</td><td class="text-center">'.$dbdata1->stat.'</td><td>'.$dbdata1->nazev_kategorie.'</td>';
		    }
		return $str;
		}
	    }
	    elseif($typ == 23){
		$sql1 = "SELECT CONCAT_WS(' ',$this->sqlosoby.prijmeni,$this->sqlosoby.jmeno) AS jmeno,$this->sqlosoby.rocnik,$this->sqlosoby.psc AS stat,$this->sqlosoby.pohlavi,$this->sqlkategorie.kod_k AS nazev_kategorie,tymy.nazev_tymu,$this->sqlzavod.cip,$this->sqlzavod.ids_alias,$this->sqldnf.* FROM $this->sqlosoby,$this->sqlzavod,$this->sqlkategorie,tymy,$this->sqldnf WHERE $this->sqlzavod.cip = $this->sqldnf.cip AND $this->sqldnf.race_id = :race_id AND $this->sqldnf.time_count < :time_order AND $this->sqlzavod.ido = $this->sqlosoby.ido AND $this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie$this->cislo_kategorie AND tymy.id_tymu = $this->sqlzavod.prislusnost AND $this->sqlzavod.poradi_podzavodu = :event_order ORDER BY $this->sqldnf.time_count DESC,$this->sqldnf.cip ASC";
		$sth1 = $this->db->prepare($sql1);
		$sth1->execute(Array(':race_id' => $this->race_id,':event_order' => $this->event_order,':time_order' => $this->time_order));
		if($sth1->rowCount()){
		    while($dbdata1 = $sth1->fetchObject()){
			$str .= '<tr><td class="text-center">DNF</td><td class="text-center">'.$dbdata1->ids_alias.'</td><td><a onclick="detail_cipu_lahofer('.$dbdata1->cip.','.$this->race_id.','.$this->race_year.')" href="'.$hash_url.'vysledky">'.$dbdata1->jmeno.'</a></td><td class="text-center">'.$dbdata1->rocnik.'</td><td>'.$this->NahrazkaPomlcky($dbdata1->nazev_tymu).'</td><td class="text-center">'.$dbdata1->stat.'</td><td class="text-center">'.$dbdata1->nazev_kategorie.'</td><td class="text-center">DNF</td><td class="text-center">'.$dbdata1->pohlavi.'</td><td class="text-center">DNF</td>';
			//podíváme se, kolik mají časů
			$sql2 = "SELECT * FROM $this->sqlvysledky WHERE cip = :cip AND false_time IS NULL AND lap_only IS NULL ORDER BY race_time ASC LIMIT 0,$this->time_order";
			$sth2 = $this->db->prepare($sql2);
			$sth2->execute(Array(':cip' => $dbdata1->cip));
			$i = 0;//záměrně tady a ne jen v případě, že se najdou nějaké časy, aby to mohlo být použito i v případě DNF, kteřé nemsjí ani jeden čas
			if($sth2->rowCount()){//pokud nějaké mají...
			    while($dbdata2 = $sth2->fetchObject()){
				$i++; 
				if($this->time_order == 1){//pokud je to první čas 
				    $str .= '<td class="text-center">'.$dbdata2->race_time.'</td>';
				    $str .= ($dbdata2->distance_overall != '00:00:00.00') ?  ('<td class="text-center">'.$dbdata2->distance_overall.'</td>') : ('<td class="text-center">&nbsp;</td>');
				}
				else{//pokud je to jiný než první čas
				    if($i <= $this->time_order){ 
					if($dbdata2->race_time != '00:00:00.00'){
					    $str .= '<td class="text-center">'.$dbdata2->race_time.'</td>';
					    $str .= '<td class="text-center"><i>'.$dbdata2->rank_overall_lap.'</i></td>';
					    if($i > 1 && $i < $this->time_order){
						$str .= '<td class="text-center"><i>'.$dbdata2->rank_overall.'</i></td>';
					    }

					}
					else{
					    $str .= '<td class="text-center">&nbsp;</td>';
					}
				    }
				}
			    }
			}
			//tady se dopíšou prázdné buňky pro časy, které už nejsou, v případě, že někdo nemá ani jeden čas, tak $i zůstalo na hodnotě 0 a dopíšou se prázdn buňky pro všecky
			if($i < $this->time_order){ 
			    while($i <= $this->time_order){
				//$str .= '<td class="text-center">&nbsp;</td>';
				$i++;
			    }
			    // a tady pro odstup
			    //$str .= '<td class="text-center">&nbsp;</td>';
			}

		    }
		return $str;
		}

	    }
	}

        private function DNFCategory($typ,$category_id){
	    /*
	     * typy
	     *  1 - jednotlivci
	     */
	    $str = '';
	    if($typ == 1){
		//vybereme jméno a příjmení lidí, kteří jsou uvedeni v tabulce DNF
	$sql1 = "SELECT CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,osoby.psc AS stat,$this->sqlkategorie.nazev_k AS nazev_kategorie,tymy.nazev_tymu,$this->sqlzavod.cip,$this->sqlzavod.ids_alias,$this->sqldnf.* FROM osoby,$this->sqlzavod,$this->sqlkategorie,tymy,$this->sqldnf WHERE $this->sqlzavod.cip = $this->sqldnf.cip AND $this->sqldnf.race_id = :race_id AND $this->sqldnf.event_order = :event_order AND  $this->sqlzavod.ido = osoby.ido AND $this->sqlzavod.id_kategorie = :category_id AND $this->sqlzavod.id_kategorie = $this->sqlkategorie.id_kategorie AND tymy.id_tymu = $this->sqlzavod.prislusnost ORDER BY $this->sqldnf.time_count DESC,$this->sqldnf.cip ASC";		//echo $sql1."\n";
		$sth1 = $this->db->prepare($sql1);
		$sth1->execute(Array(':race_id' => $this->race_id,':event_order' => $this->event_order,':category_id' => $category_id));
		if($sth1->rowCount()){
		    while($dbdata1 = $sth1->fetchObject()){
			$str .= '<tr><td class="text-center">DNF</td><td class="text-center">'.$dbdata1->ids_alias.'</td><td>'.$dbdata1->jmeno.'</td><td class="text-center">'.$dbdata1->rocnik.'</td><td>'.$dbdata1->nazev_tymu.'</td><td class="text-center">'.$dbdata1->stat.'</td>';
			//podíváme se, kolik mají časů
			$sql2 = "SELECT * FROM $this->sqlvysledky WHERE cip = :cip AND false_time IS NULL AND lap_only IS NULL ORDER BY race_time ASC LIMIT 0,$this->time_order";
			$sth2 = $this->db->prepare($sql2);
			$sth2->execute(Array(':cip' => $dbdata1->cip));
			$i = 0;//záměrně tady a ne jen v případě, že se najdou nějaké časy, aby to mohlo být použito i v případě DNF, kteřé nemsjí ani jeden čas
			if($sth2->rowCount()){//pokud nějaké mají...
			    while($dbdata2 = $sth2->fetchObject()){
				$i++; 
				if($this->time_order == 1){//pokud je to první čas 
				    $str .= '<td class="text-center">'.$dbdata2->lap_time.'</td>';
				    $str .= ($dbdata2->distance_overall != '00:00:00.00') ?  ('<td class="text-center">'.$dbdata2->distance_overall.'</td>') : ('<td class="text-center">&nbsp;</td>');
				}
				else{//pokud je to jiný než první čas
				    if($i <= $this->time_order){ 
					if($dbdata2->lap_time != '00:00:00.00'){
					    $str .= '<td class="text-center">'.$dbdata2->lap_time.'</td>';
					}
					else{
					    $str .= '<td class="text-center">&nbsp;</td>';
					}
				    }
				}
			    }
			}
			//tady se dopíšou prázdné buňky pro časy, které už nejsou, v případě, že někdo nemá ani jeden čas, tak $i zůstalo na hodnotě 0 a dopíšou se prázdn buňky pro všecky
			if($i < $this->time_order){ 
			    while($i <= $this->time_order){
				$str .= '<td class="text-center">&nbsp;</td>';
				$i++;
			    }
			    // a tady pro odstup
			    $str .= '<td class="text-center">&nbsp;</td>';
			}

		    }
		return $str;
		}
	    }
	    elseif($typ == 19){
		//vybereme jméno a příjmení lidí, kteří jsou uvedeni v tabulce DNF
		$sql1 = "SELECT CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,osoby.psc AS stat,$this->sqlkategorie.nazev_k AS nazev_kategorie,tymy.nazev_tymu,$this->sqlzavod.cip,$this->sqlzavod.ids_alias,$this->sqldnf.* FROM osoby,$this->sqlzavod,$this->sqlkategorie,tymy,$this->sqldnf WHERE $this->sqlzavod.cip = $this->sqldnf.cip AND $this->sqldnf.race_id = $this->race_id AND $this->sqlzavod.ido = osoby.ido AND $this->sqlzavod.id_kategorie = $category_id AND $this->sqlzavod.id_kategorie = $this->sqlkategorie.id_kategorie AND tymy.id_tymu = $this->sqlzavod.prislusnost AND $this->sqldnf.event_order = $this->event_order AND $this->sqldnf.event_order = $this->sqlzavod.poradi_podzavodu ORDER BY $this->sqldnf.time_count DESC,$this->sqldnf.cip ASC";
		//echo $sql1."\n";
		$sth1 = $this->db->prepare($sql1);
		$sth1->execute(Array(':race_id' => $this->race_id,':category_id' => $category_id,':event_order' => $this->event_order));
		if($sth1->rowCount()){
		    while($dbdata1 = $sth1->fetchObject()){
			$str .= '<tr><td class="text-center">DSQ</td><td class="text-center">'.$dbdata1->ids_alias.'</td><td>'.$dbdata1->jmeno.'</td><td class="text-center">'.$dbdata1->rocnik.'</td><td>'.$dbdata1->nazev_tymu.'</td><td class="text-center">'.$dbdata1->stat.'</td>';
		    }
		return $str;
		}
	    }

	}
	private function DNFGender($typ,$gender){
	    /*
	     * typy
	     *  1 - jednotlivci
	     */
	    $str = '';
	    if($typ == 1){
		//vybereme jméno a příjmení lidí, kteří jsou uvedeni v tabulce DNF
		$sql1 = "SELECT CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,osoby.psc AS stat,$this->sqlkategorie.nazev_k AS nazev_kategorie,tymy.nazev_tymu,$this->sqlzavod.cip,$this->sqlzavod.ids_alias,$this->sqldnf.* FROM osoby,$this->sqlzavod,$this->sqlkategorie,tymy,$this->sqldnf WHERE $this->sqlzavod.cip = $this->sqldnf.cip AND $this->sqldnf.race_id = :race_id AND $this->sqldnf.event_order = :event_order AND $this->sqlzavod.ido = osoby.ido AND osoby.pohlavi = :gender AND $this->sqlkategorie.id_kategorie = $this->sqlzavod.id_kategorie$this->cislo_kategorie AND tymy.id_tymu = $this->sqlzavod.prislusnost ORDER BY $this->sqldnf.time_count DESC,$this->sqldnf.cip ASC";
		$sth1 = $this->db->prepare($sql1);
		$sth1->execute(Array(':race_id' => $this->race_id,':event_order' => $this->event_order,':gender' => $gender));
		if($sth1->rowCount()){
		    while($dbdata1 = $sth1->fetchObject()){
			$str .= '<tr><td class="text-center">DNF</td><td class="text-center">'.$dbdata1->ids_alias.'</td><td>'.$dbdata1->jmeno.'</td><td class="text-center">'.$dbdata1->rocnik.'</td><td>'.$dbdata1->nazev_tymu.'</td><td class="text-center">'.$dbdata1->stat.'</td><td>'.$dbdata1->nazev_kategorie.'</td>';
			//podíváme se, kolik mají časů
			$sql2 = "SELECT * FROM $this->sqlvysledky WHERE cip = :cip AND false_time IS NULL AND lap_only IS NULL ORDER BY race_time ASC LIMIT 0,$this->time_order";
			$sth2 = $this->db->prepare($sql2);
			$sth2->execute(Array(':cip' => $dbdata1->cip));
			$i = 0;//záměrně tady a ne jen v případě, že se najdou nějaké časy, aby to mohlo být použito i v případě DNF, kteřé nemsjí ani jeden čas
			if($sth2->rowCount()){//pokud nějaké mají...
			    while($dbdata2 = $sth2->fetchObject()){
				$i++; 
				if($this->time_order == 1){//pokud je to první čas 
				    $str .= '<td class="text-center">'.$dbdata2->lap_time.'</td>';
				    $str .= ($dbdata2->distance_overall != '00:00:00.00') ?  ('<td class="text-center">'.$dbdata2->distance_overall.'</td>') : ('<td class="text-center">&nbsp;</td>');
				}
				else{//pokud je to jiný než první čas
				    if($i <= $this->time_order){ 
					if($dbdata2->lap_time != '00:00:00.00'){
					    $str .= '<td class="text-center">'.$dbdata2->lap_time.'</td>';
					}
					else{
					    $str .= '<td class="text-center">&nbsp;</td>';
					}
				    }
				}
			    }
			}
			//tady se dopíšou prázdné buňky pro časy, které už nejsou, v případě, že někdo nemá ani jeden čas, tak $i zůstalo na hodnotě 0 a dopíšou se prázdn buňky pro všecky
			if($i < $this->time_order){ 
			    while($i <= $this->time_order){
				$str .= '<td class="text-center">&nbsp;</td>';
				$i++;
			    }
			    // a tady pro odstup
			    $str .= '<td class="text-center">&nbsp;</td>';
			}

		    }
		return $str;
		}
	    }
	}
	
				
	function SecToTimeZAl($timesource){
	    $time = trim($timesource);
	    $time = explode(".",$timesource);
	    
	    if(count($time) > 1){
		//$vystupni_cas = gmdate("H:i:s",$time[0]).'.'.$time[1];
		//i pro variantu, že bude čas větsi ne 24 hodin, coz samotne gmdae neumi
		$vystupni_cas = floor($time[0] / 3600) . gmdate(":i:s", $time[0] % 3600).'.'.$time[1];
	    }
	    else{
		$vystupni_cas = floor($time[0] / 3600) . gmdate(":i:s", $time[0] % 3600);
	    }
	    //echo $vystupni_cas."\n";
	    return $vystupni_cas;
	}
	
	
					
	function SecToTime($timesource){
	    //je to varianta i pro případ, když je cas vetsi nez 24 hodin
	    $time = trim($timesource);
	    $time = explode(".",$timesource);
	    $seconds = $time[0];
	    $H = floor($seconds / 3600);
	    $i = ($seconds / 60) % 60;
	    $s = $seconds % 60;
	    if(count($time) > 1){
		$vystupni_cas = sprintf("%02d:%02d:%02d.%02d", $H, $i, $s,$time[1]);
	    }
	    else{
		$vystupni_cas = sprintf("%02d:%02d:%02d", $H, $i, $s);
	    }
	    return $vystupni_cas;
	}
	
	
	
   
	
	
	private function TimeToSec($timesource){
	    $time = trim($timesource);
	    $time = explode(".",$time);
	    /*
	     * pokud se zadá čas bez desetin
	     */
	    if(count($time) > 1){
		    $sectime = strtotime($time[0]) - strtotime('00:00:00');
		    $sectime = $sectime.'.'.$time[1];
	    }
	    else{
		    $sectime = strtotime($timesource) - strtotime('00:00:00');
	    }
	    return $sectime;
	}

	
	function ResultsSearch(){
	    $str = '';
	    $odpocet = $this->Odpocet($_GET['search_val']);
	    $casovka = $this->Casovka($_GET['search_val']); 
	    $sql1= "SELECT tymy.nazev_tymu FROM tymy,$this->sqlzavod WHERE $this->sqlzavod.ids = {$_GET['search_val']} AND $this->sqlzavod.tym = tymy.id_tymu GROUP BY tymy.nazev_tymu";
	    $sth1 = $this->db->prepare($sql1);
	    $sth1->execute();
	    if($sth1->rowCount()){
		$dbdata1 = $sth1->fetchObject();
		$str .= '<table class="table table-bordered" style="font-size:20px;width:auto;"><tr><td>'.$dbdata1->nazev_tymu.'</td>';
		$day_time_sec = $this->TimeToSec(date("H:i:s"));
		$race_time_sec = $day_time_sec - $this->start_time - $odpocet - $casovka;
		$race_time = $this->SecToTime($race_time_sec);
		$str .= '<td>'.$race_time.'</td></tr></table>';
	    }
	    echo $str;
	}
	
	
	private function Casovka($ids){
	    $casovka = false;
	    $sql1 = "SELECT casovka FROM $this->sqlzavod WHERE $this->sqlzavod.ids = :ids";
	    $sth1 = $this->db->prepare($sql1);
	    $sth1->execute(Array(':ids' => $ids));
	    if($sth1->rowCount()){
		$dbdata = $sth1->fetchObject();
		$casovka = $dbdata->casovka;
	    }
	    return $casovka;
	}

	private function Odpocet($ids){
	    $odpocet = false;
	    $sql1 = "SELECT $this->sqlkategorie.odpocet_casu FROM $this->sqlkategorie,$this->sqlzavod WHERE $this->sqlkategorie.kod_k LIKE $this->sqlzavod.kategorie AND $this->sqlzavod.ids = :ids AND $this->sqlkategorie.id_zavodu = :race_id";
	    //echo $sql1."\n";
	    $sth1 = $this->db->prepare($sql1);
	    $sth1->execute(Array(':ids' => $ids,':race_id' => $this->race_id));
	    
	    if($sth1->rowCount()){
		$dbdata = $sth1->fetchObject();
		$odpocet = $dbdata->odpocet_casu * 60;
	    }
	    return $odpocet;
	}

	
	
	
	
	function ResultsSearchZal(){
	    $str = '';
	     $str2 = '';
	    $sql1 = "SELECT COUNT($this->sqlvysledky.id) AS pocet_radku, $this->sqlzavod.cip,$this->sqlzavod.ids_alias,CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,osoby.psc AS stat,$this->sqlkategorie.nazev_k AS nazev_kategorie,tymy.nazev_tymu "
		      . "FROM osoby,$this->sqlzavod,$this->sqlkategorie,tymy,$this->sqlvysledky "
		      . "WHERE "
		      . "$this->sqlzavod.ids = {$_GET['search_val']} AND "
		      . "$this->sqlzavod.ido = osoby.ido AND "
		      . "$this->sqlzavod.prislusnost = tymy.id_tymu AND "
		      . "$this->sqlzavod.id_kategorie = $this->sqlkategorie.id_kategorie AND "
		    . "$this->sqlvysledky.ids = $this->sqlzavod.ids";
	    //$str .= $sql1;
	    $sth1 = $this->db->prepare($sql1);
	    $sth1->execute();
	    if($sth1->rowCount()){
		$str .= '<table class="table table-hover table-bordered">';
		//$str .= '<thead><tr class="header"><th class="text-center">#</th><th class="text-center">St.č</th><th>Jméno</th><th class="text-center">Ročník</th><th>Tým/Bydliště</th><th class="text-center">Stát</th><th class="text-center">Kat</th><th class="text-center">#</th>';
		$str .= '<thead><tr class="header"><th class="text-center">St.č</th><th>Jméno</th><th class="text-center">Ročník</th><th>Tým/Bydliště</th><th class="text-center">Stát</th><th class="text-center">Poř</th><th class="text-center">Kat</th><th class="text-center">Poř</th>';
		$sql3 = "SELECT nazev_discipliny FROM $this->sqldiscipliny WHERE id_zavodu = '$this->race_id' AND poradi_podzavodu = 1 ORDER BY poradi_discipliny";
		//$str .= $sql3;
		    $sth3 = $this->db->prepare($sql3);
		    $sth3->execute();
		    if($sth3->rowCount()){
			while($dbdata3 = $sth3->fetchObject()){
			    //$str .= '<th class="text-center">'.$dbdata3->nazev_discipliny.'</th>';
			    //$str .= '<th class="text-center">C</th>';
			    //$str .= '<th class="text-center">K</th>';
			}
			
		    }
		    echo $dbdata3[1]['nazev_discipliny'];

		
		$str .= '<th class="text-center">Celkový čas</th>';
		$str .= '</thead>';
		$dbdata1 = $sth1->fetchObject();
		$str .= '<tr>';
		
		$sql2 = "SELECT * FROM $this->sqlvysledky WHERE cip = $dbdata1->cip AND false_time IS NULL AND lap_only IS NULL ORDER BY time_order";
		$sth2 = $this->db->prepare($sql2);
		$sth2->execute(Array(':cip' => $dbdata1->cip));
		if($sth2->rowCount()){
		    
		    
		    $i = 1;
		   
		    while($dbdata2 = $sth2->fetchObject()){
			if($i == $sth2->rowCount()){
			    $finish_time = $dbdata2->race_time;
			    //$str .= '<td class="text-center">'.$dbdata2->rank_overall.'</td>';

			    $str .= '<td class="text-center">'.$dbdata1->ids_alias.'</td>';
			    $str .= '<td>'.$dbdata1->jmeno.'</td>';
			    $str .= '<td class="text-center">'.$dbdata1->rocnik.'</td>';
			    $str .= '<td>'.$dbdata1->nazev_tymu.'</td>';
			    $str .= '<td class="text-center">'.$dbdata1->stat.'</td>';
			    $str .= '<td class="text-center">'.$dbdata2->rank_overall.'</td>';

			    $str .= '<td class="text-center">'.$dbdata1->nazev_kategorie.'</td>';
			    $str .= '<td class="text-center">'.$dbdata2->rank_category.'</td>';
			}
			//$str2 .= '<td class="text-center">'.$dbdata2->lap_time.'</td>';
			//$str2 .= '<td class="text-center">'.$dbdata2->rank_overall_lap.'</td>';
			//$str2 .= '<td class="text-center">'.$dbdata2->rank_gender_lap.'</td>';
			$i++;
		    }
		    $str .= $str2;
		    $str .= '<td class="text-center">'.$finish_time.'</td>';
		}
		$str .= '</tr>';

		$str .= '</table>';
	    }
	    echo $str;
	}
	
	public function ExportToPDF(){
	    $str = '';
	   switch($_GET['results_type']){
		case 'ResultsOverall':
                    if($this->race_id == 25 OR $this->race_id == 49 OR $this->race_id == 50){
			$str .= '<div class="reklama-lista-horni"><img src="../images/results/lista_horni_'.$this->race_code.'_2017.jpg" /></div>';
		    }
		    
                    
		    $str .= $this->ResultsOverall();
                      if($this->race_id == 25 OR $this->race_id == 49 OR $this->race_id == 50){
			$str .= '<div class="reklama-lista-dolni"><img src="../images/results/lista_dolni_'.$this->race_code.'_2017.jpg" /></div>';
		    }
                    break;
		case 'ResultsCategory':
		    if($this->race_id == 25 OR $this->race_id == 49 OR $this->race_id == 50){
			$str .= '<div class="reklama-lista-horni"><img src="../images/results/lista_horni_'.$this->race_code.'_2017.jpg" /></div>';
		    }
		    
		    $str .= $this->ResultsCategory();
		    if($this->race_id == 25 OR $this->race_id == 49 OR $this->race_id == 50){
			$str .= '<div class="reklama-lista-dolni"><img src="../images/results/lista_dolni_'.$this->race_code.'_2017.jpg" /></div>';
		    }
                break;
		case 'ResultsGender':
		    if($this->race_id == 25 OR $this->race_id == 49 OR $this->race_id == 50){
			$str .= '<div class="reklama-lista-horni"><img src="../images/results/lista_horni_'.$this->race_code.'_2017.jpg" /></div>';
		    }
		    
		    $str .= $this->ResultsGender();
		    if($this->race_id == 25){
			$str .= '<div class="reklama-lista-dolni"><img src="../images/results/lista_dolni_'.$this->race_code.'_2017.jpg" /></div>';
		    }
		break;
		
	    }
	    
	    include('../libs/mpdf60/mpdf.php');
	    
	   //$mpdf=new mPDF('utf-8','A4-L'); //L = vodorovně
	   $mpdf=new mPDF('utf-8','A4-T'); //T = na stojáka
	    
            
            
            $stylesheet = file_get_contents('../css/pdf_print.css');
	    
	   // $mpdf->setFooter('{PAGENO}');
	    $mpdf->WriteHTML($stylesheet,1);
	    $mpdf->WriteHTML($str,2);
	    //$mpdf->Output();
	    $mpdf->Output($_GET['results_type'].'.pdf','D');
	    exit;
	}
	
	
	public function NahrazkaPomlcky($input){
	    switch($input){
                case '-':
                    $str = '&nbsp;';
                break;
                case 'Bez tymu':
                    $str = '&nbsp;';
                break;
                default:
                    $str = $input;
	    }
	    return $str;
	}
	
	public function ExportToXLS(){
	   
	    
	    //require "../libs/phpexcel/Classes/PHPExcel.php";
	    require "../phpexcel/Classes/PHPExcel.php";
		/** Error reporting */
	       
		/*
		error_reporting(E_ALL);
	       ini_set('display_errors', TRUE);
	       ini_set('display_startup_errors', TRUE);
	       date_default_timezone_set('Europe/London');

	       if (PHP_SAPI == 'cli') die('This example should only be run from a Web Browser');
	       $objPHPExcel = new PHPExcel();
	       $objPHPExcel->setActiveSheetIndex(0)
			    ->setCellValue('A1','AHOJ');
	       
	       
		$objPHPExcel->getActiveSheet()->setTitle('Přihlášky');

		// Set active sheet index to the first sheet, so Excel opens this as the first sheet
		$objPHPExcel->setActiveSheetIndex(0);


		// Redirect output to a client’s web browser (Excel5)
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="TEst.xls"');
		header('Cache-Control: max-age=0');
		// If you're serving to IE 9, then the following may be needed
		header('Cache-Control: max-age=1');

		// If you're serving to IE over SSL, then the following may be needed
		header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
		header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
		header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
		header ('Pragma: public'); // HTTP/1.0

		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		$objWriter->save('php://output');
		
		exit;
	       */
	       
	       
	       
	       
	       
	        $xls = new PHPExcel();
		//$xls->loadData($formattedData); //I assume that there is a similar loadData() method
		
		$xls->exportToFile('live.timechip.loc/public/files/new_excel.xls'); // I assume that there is an exportToFile() method

		$response = array(
		    'success' => true,
		    'url' => 'live.timechip.loc/public/files/new_excel.xls'
		);

		//header('Content-type: application/json');

		// and in the end you respond back to javascript the file location
		echo json_encode($response);
        }
	       
	       
	public function DetailCipuEnduro(){
            $str = "";
            $sql = "SELECT * FROM $this->sqlzavody WHERE id_zavodu = '$this->race_id'";
	    $sth = $this->db->query($sql);
	    $sth->execute();
	    $dbdata = $sth->fetchObject();
	    $this->delka_kola = $dbdata->delka_kola;
	    $this->race_code = $dbdata->kod_zavodu;
	    $this->sqlvysledky = 'vysledky_'.$this->race_code.'_'.$this->race_year.'_test';
	    $this->sqlzavod = 'zavod_'.$this->race_code.'_'.$this->race_year;
	    $str = '';
	    $sql2 = "SELECT "
		      . "CONCAT_WS(' ',osoby.jmeno,osoby.prijmeni) AS jmeno,nazev_tymu,"
		      . "COUNT($this->sqlvysledky.id) AS lap_count,"
		      . "SEC_TO_TIME(SUM($this->sqlvysledky.lap_time_sec)) AS total_lap_time,"
		      . "SUM($this->sqlvysledky.lap_time_sec) AS total_lap_time_sec, "
                      . "MIN($this->sqlvysledky.lap_time) AS best_lap_time,"
                      . "MAX($this->sqlvysledky.lap_time) AS slowest_lap_time "
		      . "FROM $this->sqlzavod,osoby,tymy,$this->sqlvysledky "
		      . "WHERE "
		      . "$this->sqlzavod.cip = :cip AND "
		      . "$this->sqlvysledky.time_order > 1 AND "
		      . "$this->sqlzavod.ido = osoby.ido AND "
		      . "$this->sqlzavod.prislusnost = tymy.id_tymu AND "
		      . "$this->sqlzavod.cip = $this->sqlvysledky.cip";
            	    //echo $sql2;
	    $sth2 = $this->db->prepare($sql2);
	    $sth2->execute(Array(':cip' => $_GET['cip']));
	    $dbdata2 = $sth2->fetchObject();
            $str .= '<div style="text-align:center;background:blue">';
	    $str .= '<h3 style="background:none">'.$_GET['cip'].'@'.$dbdata2->jmeno.'</h3>';
	    
	    if($dbdata2->lap_count > 0){
		$celkova_vzdalenost = $dbdata2->lap_count * $this->delka_kola;
		$str .= 'Počet kol: '.$dbdata2->lap_count.'<br />';
		$str .= 'Celkový čas: '.$dbdata2->total_lap_time.'<br />';
		$str .= 'Průměrná rychlost: '.round($celkova_vzdalenost / $dbdata2->total_lap_time_sec * 3600,1).' km/h<br />';
		$str .= '</div>';
	    }
	    $sql1 = "SELECT $this->sqlvysledky.* FROM $this->sqlvysledky,$this->sqlzavod WHERE $this->sqlvysledky.time_order > 1 AND $this->sqlvysledky.false_time IS NULL AND $this->sqlvysledky.cip = :cip AND $this->sqlvysledky.cip = $this->sqlzavod.cip ORDER BY race_time";
	   // echo $sql1;
	    $sth1 = $this->db->prepare($sql1);
	    $sth1->execute(Array(':cip' => $_GET['cip']));
	    if($sth1->rowCount()){
		$row_count = $sth1->rowCount();
		$str .= '<table class="table table-bordered table-hover table-striped table_vysledky_enduro text-center" style="width:100%">';
		$str .= '<thead><tr class="header"><th class="text-center">Kolo</th><th class="text-center">Čas kola</th><th class="text-center">Čas závodu</th><th class="text-center">Denní čas</th><th class="text-center">Km/h</th></tr></thead><tbody>';
               $kolo = 1;
	       $posledni_kolo = false;
		while($dbdata1 = $sth1->fetchObject()){
		    if($dbdata1->lap_time_sec > 0){
			$prumerna_rychlost = round($this->delka_kola/$dbdata1->lap_time_sec*3600,1);
		    }
			$str .= '<tr>';
			    $str .= '<td>'.($dbdata1->time_order-1).'</td>';
                if($dbdata2->best_lap_time == $dbdata1->lap_time){
                    $str .= '<td style="background:blue">'.substr($dbdata1->lap_time,1,-4).'</td>';
                }
                elseif($dbdata2->slowest_lap_time == $dbdata1->lap_time){
                    $str .= '<td style="background:green">'.substr($dbdata1->lap_time,1,-4).'</td>';
                }
                else{
                    $str .= '<td>'.substr($dbdata1->lap_time,1,-4).'</td>';
                }

			    $str .= '<td>'.substr($dbdata1->race_time,1,-4).'</td>';
			    $str .= '<td>'.substr($dbdata1->day_time,0,-4).'</td>';
			    $str .= '<td>'.$prumerna_rychlost.'</td>';
			$str .= '</tr>';
		    $kolo++;
		}
		$str .= '</tbody></table>';
	    }
            echo $str;
            
            
            
            
            
            }
	       
	       
	       
	       
	       
	       
	       
	   
	   
	   


	
	


}

$neco = New Vysledky();
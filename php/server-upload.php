<?php
    require_once '../libs/settings.php';
    class ServerUpload extends Settings{
	private $ftp_count = 1;
	
	public function __construct(){
	    parent::__construct();
	    if(isset($_GET['murinoha'])){
		if(method_exists(get_class(),$_GET['murinoha'])){
		    $this->$_GET['murinoha']();
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
	    $sourcefile = '';
	    $fcdata = '<div class="panel panel-default panel-collapse">';
	    $fcdata .= '<div class="panel-body">';
	    $i = 1;
	    while($i <= $this->ftp_count){
		$fcdata .= '<div class="navbar navbar-raceadmin"><form id="ftp_upload_form_'.$i.'" class="navbar-form">';
		$fcdata .= '<select class="form-control" id="ftp_sourcefile_'.$i.'" name="ftp_sourcefile">';
		$handle = opendir('../sourcefiles');
		while (($file = readdir($handle)) !== false) {
		    $fcdata .= '<option value="'.$file.'">'.$file.'</option>';
		} 
		closedir($handle);
		$fcdata .= '</select> ';
		$fcdata .= '<input id="ftp_time_interval_'.$i.'" name="ftp_time_interval" class="form-control text-center" size="1" value="" /> ';
		$fcdata .=  '<button type="button" id="ftp_upload_button_'.$i.'" class="btn btn-default ftp_start_'.$i.'">FTP start</button> ';
		$fcdata .= '<span id="ftp_control_'.$i.'"></span>';
		$fcdata .= '</form></div>';
		$i++;
	    }
	    $fcdata .= '</div>';
	    $fcdata .= '</div>';
	    echo json_encode($fcdata);
	}

	private function FtpUpload(){
	    $str = '';
	    $source_file = '../sourcefiles/'.$_GET['ftp_sourcefile'];
	    $destination_file = './raceadmin.timechip/sourcefiles/'.$_GET['ftp_sourcefile'];
	    // set up basic connection
	    $conn_id = ftp_connect(FTP_SERVER); 
	    // login with username and password
	    $login_result = ftp_login($conn_id, FTP_USER, FTP_PASS); 
	    // check connection
	    if((!$conn_id) || (!$login_result)){ 
		$str .= "FTP connection has failed!";
		$str .= "Attempted to connect to ".FTP_SERVER." for user ".FTP_USER; 
		exit; 
	    } 
	    else{
		//$str .= "Connected to ".FTP_SERVER.", for user ".FTP_USER."<br />";
	    }
	    ftp_pasv($conn_id, true);
	    // upload the file
	    $upload1 = ftp_put($conn_id, $destination_file, $source_file, FTP_BINARY); 
	    // check upload status
	    if(!$upload1) { 
		$str .= "FTP upload has failed!";
	    } 
	    else{
		$str .= 'Zdrojový soubor '.$source_file.' byl nahrán na server v '.date("H:i:s").'<br />';
	    }
	    // close the FTP stream 
	    ftp_close($conn_id);
	    echo $str;
	}
	

}
New ServerUpload();
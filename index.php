<!DOCTYPE html>
<html lang="cs" dir="ltr">
<head>
    <title>TimeChip Live</title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="./css/bootstrap.css" media="screen" />
    <link rel="stylesheet" href="./css/default2.css" media="screen" />
    <link rel="stylesheet" href="./css/jquery-ui.css" media="screen" />
    <link rel="stylesheet" href="./css/jquery-ui.structure.min.css" media="screen" />
    <link rel="stylesheet" href="./css/jquery-ui.theme.min.css" media="screen" />
    <link rel="stylesheet"  href="./css/print.css" media="print" />
    <script type="text/javascript" src="./js/jquery-1.11.0.min.js"></script>
    <script type="text/javascript" src="./js/jquery-ui.custom.min.js"></script>
    <script type="text/javascript" src="./js/jquery.fileDownload.js"></script>
    <script type="text/javascript" src="./js/jquery.table2excel.js"></script>
    <script type="text/javascript" src="./js/default2.js"></script>
    <script type="text/javascript" src="./js/bootstrap.min.js"></script>
 </head>
<body>
<div class="navbar navbar-default navbar-fixed-top navbar-custom" role="navigation">
  <div class="container-fluid">
    <div class="navbar-header">
	<button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
	    <span class="sr-only">Toggle navigation</span>
	    <span class="icon-bar"></span>
	    <span class="icon-bar"></span>
	    <span class="icon-bar"></span>
	</button>
	  <a href="/" class="navbar-brand"><img src="/images/logo.png" alt="TimeChip" width="210" height="50" /></a>
    </div>
    <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
	<ul id="menu" class="nav navbar-nav"></ul>
	<form class="navbar-form navbar-right timechip-menu-navbar">
	    <select id="race_select" class="form-control input-lg"></select> 
	    <select id="race_year" class="form-control input-lg"></select>   
	 </form>
    </div><!-- /.navbar-collapse -->
  </div><!-- /.container-fluid -->
</div>
<div id="content" class="container-fluid">
</div><!-- /.container -->
<script>
function detail_ids(ids,race_id,race_year) {
    window.open("./php/sub_files/detail_ids.php?ids="+ids+"&race_id="+race_id+"&race_year="+race_year);
}
function detail_cipu(cip,race_id,race_year) {
    window.open("./php/sub_files/detail_cipu.php?cip="+cip+"&race_id="+race_id+"&race_year="+race_year);
}
function detail_ids_2(ids,race_id,race_year) {
    window.open("./php/sub_files/detail_ids_2.php?ids="+ids+"&race_id="+race_id+"&race_year="+race_year);
}
function detail_cipu_2(cip,race_id,race_year) {
    window.open("./php/sub_files/detail_cipu_2.php?cip="+cip+"&race_id="+race_id+"&race_year="+race_year);
}
function detail_ids_cc(ids,race_id,race_year,id_etapy) {
    window.open("./php/sub_files/detail_ids_cc.php?ids="+ids+"&race_id="+race_id+"&race_year="+race_year+"&id_etapy="+id_etapy);
}
function detail_cipu_cc(cip,race_id,race_year,id_etapy) {
    window.open("./php/sub_files/detail_cipu_cc.php?cip="+cip+"&race_id="+race_id+"&race_year="+race_year+"&id_etapy="+id_etapy);
}
function detail_cipu_cc_bez_etap(cip,race_id,race_year) {
    window.open("./php/sub_files/detail_cipu_cc_bez_etap.php?cip="+cip+"&race_id="+race_id+"&race_year="+race_year);
}
function detail_ids_cc_bez_etap(ids,race_id,race_year,id_etapy) {
    window.open("./php/sub_files/detail_ids_cc_bez_etap.php?ids="+ids+"&race_id="+race_id+"&race_year="+race_year);
}
function detail_cipu_plavani(cip,race_id,race_year,event_order) {
    window.open("./php/sub_files/detail_cipu_plavani.php?cip="+cip+"&race_id="+race_id+"&race_year="+race_year+"&event_order="+event_order);
}
function detail_cipu_lahofer(cip,race_id,race_year) {
    window.open("./php/sub_files/detail-cipu-lahofer.php?cip="+cip+"&race_id="+race_id+"&race_year="+race_year);
}
function detail_tymu_teribear(id_tymu,race_id,race_year) {
    window.open("./php/sub_files/detail-tymu-teribear.php?id_tymu="+id_tymu+"&race_id="+race_id+"&race_year="+race_year);
}
function detail_cipu_eng(cip,race_id,race_year) {
    window.open("./php/sub_files/detail-cipu-eng.php?cip="+cip+"&race_id="+race_id+"&race_year="+race_year);
}
function detail_ids_rv(ids,race_id,race_year) {
    window.open("./php/sub_files/detail-ids-rv.php?ids="+ids+"&race_id="+race_id+"&race_year="+race_year);
}
function detail_cipu_rv(cip,race_id,race_year) {
    window.open("./php/sub_files/detail-cipu-rv.php?cip="+cip+"&race_id="+race_id+"&race_year="+race_year);
}
function detail_tymu(ids,race_id,race_year,time_order,pocet_clenu) {
    window.open("./php/sub_files/detail-tymu.php?ids="+ids+"&race_id="+race_id+"&race_year="+race_year+"&time_order="+time_order+"&pocet_clenu="+pocet_clenu);
}






</script>
</body>
</html>	

var timeout;
var kategorie = 'ResultsCategory';
var stafety = 'Štafety';
var pohlavi = 'Podle pohlaví';
var roky_narozeni = 'ResultsBirthYear'; 
var results_interval = 60000;
//var results_interval = 100000000;
var right_now_interval = 2000; //zdá se, že zůstává viset i tento interval i když se z Právě teď přepneme ny Výsledky



/* Vyhledavani  */ 
var result_search = {
     /* spousteci funkce, v ktere jsou vsecky udalosti */
    ResultSearchConstructor :function(url_file,url_params){
	result_search.ResultSearchEnter();
	result_search.ResultSearchClear();
	result_search.ResultSearchSubmit(url_file,url_params);
    },
    
    ResultSearchEnter :function(){
	$("#table-keyboard td.keyboard_number").click(function(){
	    var keyboard_number = $(this).text();
	    var result_search_ids_length = $("#result_search_ids span").text().length;
	    if(result_search_ids_length < 5){
		$("#vycpavka").css("display","none");
		// $("#result_search_ids").val($("#result_search_ids").val() + keyboard_number);
		$("#result_search_ids span").text($("#result_search_ids span").text() + keyboard_number);
		$('div#result_table').empty();
	    }
	});
    },
    
    
    
    ResultSearchSubmit :function(url_file,url_params){
	$("#table-keyboard #result_search_submit").click(function(){
	    var search_val  = $("#result_search_ids span").text();
	    if(search_val == ''){
		alert('Není zadáno startovní číslo');
	    }
	    else{
		$.get('./php/' + url_file + url_params,{murinoha:'ResultsSearch',search_val:search_val},function(xhr){
		    $('div#result_table').html(xhr);
		    $("#table-keyboard td.result_search_ids span").empty();
		    $("#vycpavka").css("display","block");
		});
	    }
	});
    },
   
    ResultSearchClear :function(){
	$("#table-keyboard #result_search_clear").click(function(){
	   $("#table-keyboard td.result_search_ids span").empty();
	   $('div#result_table').empty();
           $("#vycpavka").css("display","block");
	});
    }
    
    
}


/* vlastně results autoreading  */ 
var right_now = {
     /* spousteci funkce, v ktere jsou vsecky udalosti */
    RightNowConstructor :function(url_file,url_params){
	if(url_file == 'prave-ted.php'){ // pouze uvýsledků
	    right_now.ResultsAutoreading(url_file,url_params);
	}
    },
    ResultsAutoreading :function(url_file,url_params){
	$.getJSON('./php/' + url_file + url_params,{murinoha: 'ResultsAutoreading'},function(xhr){
	    $('div#panel_content').html(xhr.results);
	    var last_modified = xhr.last_modified;
	    var change_control_file = xhr.change_control_file;
	    right_now.HeartbeatRightNow(last_modified,change_control_file,right_now_interval,url_file,url_params);	
	});

    },
    

    /* heartbeat s funkcí zastavení po zmáčknutí tlačítko Zastavit Autoreading.. na konci je kontrolka pro indikaci, že funkce pracuje */
    HeartbeatRightNow :function(stary_cas,change_control_file,interval,url_file,url_params){
	var xhr = $.ajax({type: "HEAD",cache: false,async: true,url: change_control_file}).done(function(){
	    var x = xhr.getResponseHeader("Last-Modified");
	    var str = String(new Date(x).getTime()); //pokud se to nedalo do stringu, nedaly se odřezat ty 3 nuly na konci
	    var last_modified = str.substring(0,10);
	    if(stary_cas == last_modified){
		var timeout2 = setTimeout(function(){right_now.HeartbeatRightNow(last_modified,change_control_file,interval,url_file,url_params)},interval);
	    }
	    else{
		right_now.ResultsAutoreading(url_file,url_params);
	    }
	});
    },
}

/* Startovní listina  */ 
var starting_list = {
     /* spousteci funkce, v ktere jsou vsecky udalosti */
    StartingListConstructor :function(url_file,url_params){
	starting_list.StartingListLoad(url_file,url_params);
	starting_list.DynamicSelectEmpty();
    },
    
    StartingListLoad : function(url_file,url_params){
       $('form#starting_list_form').on('change','select',function(){
	    var select_type = $(this).attr('id');
	    var results_type = $('select#starting_list_type option:selected').val();
	    //alert(select_type);
	    //alert(results_type);
	   if(results_type === 'StartingListCategory' && (select_type === 'starting_list_type' || select_type === 'event_list')){
		starting_list.StartingListCategory(url_file,url_params,results_type);
	    }
	    else{
		starting_list.StartingListRequest(url_file,url_params,results_type);
	    }
	});
	
    },
    
        /* Posílá požadavek na zpracování výsledků podle kategorií dle aktuálního nastevení selectů */
    StartingListCategory : function(url_file,url_params,results_type){
	var event_order = $('select#event_list option:selected').val();
	var url_controller = './php/' + url_file + url_params;
	$.get(url_controller,{murinoha:'CategoryListSelect',event_order: event_order},function(xhr){
	    $('span#dynamic_select').html(xhr);
	    starting_list.StartingListRequest(url_file,url_params,results_type);
	 });
    },

    
    StartingListRequest : function(url_file,url_params,results_type){
	var starting_list_params = starting_list.StartingListParams();
	var url_controller = './php/' + url_file + url_params;
	$.get(url_controller,{
		murinoha: results_type,
		event_order: starting_list_params.event_order,
		time_order: starting_list_params.time_order,
		category_id: starting_list_params.category_id,
		gender: starting_list_params.gender,
		birth_year: starting_list_params.birth_year
		    },function(xhr){
		$('div#result_table').html(xhr);
	});
    },
    
    /* sejme všecky hodnoty se selectů a poskytne je tomu, kdo si o ně řekl */
    StartingListParams : function(){
	return {
	    event_order: $('select#event_list option:selected').val(),
	    time_order: $('select#lap_name option:selected').val(),
	    category_id: $('select#category_list option:selected').val(),
	    gender: $('select#gender_list option:selected').val(),
	    birth_year: $('select#birth_year_list option:selected').val()
	};
    },
        /* vyprázdní span pro vkládání dnamických selectů v případě, že se zadají výsledky bez kategorií, bo ty žádné dynamické selecty nemají */
    DynamicSelectEmpty : function(){
	$('form#starting_list_form').on('change','select#starting_list_type',function(){
	    if($(this).val() == 'StartingListOverall'){
		$('span#dynamic_select').empty(); 
	    }
	});
    }
}

/* Výsledky */ 
var results = {
     /* spousteci funkce, v ktere jsou vsecky udalosti */
    ResultsEvents :function(url_file,url_params){
	if(url_file == 'vysledky.php'){ // pouze uvýsledků
	    results.ResultsAutoreading(url_file,url_params);
	}
	results.ResultsLoad(url_file,url_params);
	results.GenderListSelect(url_file,url_params);
	results.BirthYearListSelect(url_file,url_params);
	results.DynamicSelectEmpty();
	results.LapsListSelect(url_file,url_params); //zatím nevím kam to umístit, při změne podzávodu na jiný, který má menší počet disciplín, atd...
	results.DetailIds(url_file,url_params);
	results.DetailCipu(url_file,url_params);
	results.HeatListSelect(url_file,url_params);
	results.ResultsSearch(url_file,url_params);
	results.ExportToPDF(url_file,url_params);
	results.ExportToXLS(url_file,url_params);
	results.RowsLimit(url_file,url_params);
	results.ResultsPreloader();
    },
    
    
    ResultsPreloader : function(){
	$(document).ajaxStart(function () {
	    $('#spinner').fadeIn('fast');
	}).ajaxStop(function () {
	    $('#spinner').stop().fadeOut('fast');
	});
    }, 
    

    
    ExportToPDF : function(url_file,url_params){
	  //alert(url_params); 
	$("#export_to_pdf").click(function(e){
	    var results_type = $('select#results_type option:selected').val();
	    //alert(results_type);
	    var results_params = results.ResultsParams();
	    $.fileDownload('./php/' + url_file + url_params, {
		preparingMessageHtml: "We are preparing your report, please wait...",
		failMessageHtml: "There was a problem generating your report, please try again.",
		httpMethod: "POST",
		httpMethod: "GET",
		data:{
		    murinoha: 'ExportToPDF',
		    results_type: results_type,
		    event_order: results_params.event_order,
		    time_order: results_params.time_order,
		    category_id: results_params.category_id,
		    gender:results_params.gender,
		    birth_year: results_params.birth_year,
		    id_etapy:results_params.id_etapy,
		    rows_limit:results_params.rows_limit
		}
	    });
	    
	    e.preventDefault();
	});
	
    },
    
        ExportToXLS : function(url_file,url_params){
	  //alert(url_params); 
	$("#export_to_xls").click(function(e){
	    var results_type = $('select#results_type option:selected').val();
	    //alert(results_type);
	    var results_params = results.ResultsParams();
	    var nadpis = $(".headline-results").text();
	    /*
	    $.fileDownload('./php/' + url_file + url_params, {
		//preparingMessageHtml: "We are preparing your report, please wait...",
		//failMessageHtml: "There was a problem generating your report, please try again.",
		//httpMethod: "POST",
		httpMethod: "GET",
		data:{
		    murinoha: 'ExportToXLS'
		}
	    });
	    */
	   
	   /*
	    $.ajax({
		url: './php/' + url_file + url_params, // the url of the php file that will generate the excel file
		data:{
		    murinoha: 'ExportToXLS'
		},
		success: function(response){
		    //window.location.href = response.url;
		}
	    })
	   */
	  
	    $("#table2excel").table2excel({
		   exclude: ".noExl",
		   name: "Excel Document Name",
		   filename: nadpis
	   }); 
	   
	  e.preventDefault();
	});
	
    },


    ResultsSearch : function(url_file,url_params){
	$("#results_search").keyup(function(){
	    var search_val = $(this).val();
	    $.get('./php/' + url_file + url_params,{murinoha:'ResultsSearch',search_val:search_val},function(xhr){
		$('div#result_table').html(xhr);
	    });
	});
    },
    
    RowsLimit : function(url_file,url_params){
	// tohle tam zatím nedávám, bylo to trochu matoucí, protože při reloadu stránky tam sice hodnota držela, ale ResultsParams ji nesnímal
	/*
	if(sessionStorage.rows_limit){
	    $('input#rows_limit').val(sessionStorage.rows_limit);
	}
	*/
	$("#rows_limit").keyup(function(){
	    var rows_limit = $(this).val();
	    sessionStorage.rows_limit = rows_limit; 
	    var select_type = $(this).attr('id');
	    var results_type = $('select#results_type option:selected').val();
	    var heat_id = $('select#heat_list option:selected').val();
	    if(results_type === 'ResultsCategory' && (select_type === 'results_type' || select_type === 'event_list')){
		results.ResultsCategory(url_file,url_params,results_type,heat_id);
	    }
	    else{
		results.ResultsRequest(url_file,url_params,results_type,heat_id);  //heat_id jsou rozjíždky, děláno v chvatu na MAdeju, koncepční to asi není
	    }	
	});
    },
    
    
    DetailIds : function(url_file,url_params){
       $('div#content').on('click','a.detail_ids',function(){
	   var ids = $(this).parents().eq(1).attr('id');
	    $.get('./php/' + url_file + url_params,{murinoha:'DetailIds',ids:ids},function(xhr){
		$('div#content').html(xhr);
	    });
	});
    },
    DetailCipu : function(url_file,url_params){
       /*
        $('div#content').on('click','a.detail_cipu',function(){
	   var cip = $(this).parents().eq(1).attr('id');
	    $.get('./php/' + url_file + url_params,{murinoha:'DetailCipu',cip:cip},function(xhr){
		$('div#content').html(xhr);
	    });
	});
        
        
        */
       
           $('div#content').on('click','tr.tr_class',function(){
            var tendiv = $(this).attr("id");
        $.get('./php/' + url_file + url_params,{murinoha:'DetailCipuEnduro',cip:tendiv},function(xhr){
                var cil = $("#div_"+tendiv);
                $("#div_"+tendiv).html(xhr);
                $("#div_"+tendiv).toggle("slow").css("display","block");  
                
                
        })
    });

        
        
    },

    
    /* Po změně na jakémkoli selectu se  spousti pozadavek na server, v pripadě kategorii se nejdrive musi nacist dynamicky kategorie, teprve
     * pak se posílá poždavek
     */
    ResultsLoad : function(url_file,url_params){
        var limit_od;

       $('form#vysledky_form').on('change','select',function(){
	    var select_type = $(this).attr('id');
	    var results_type = $('select#results_type option:selected').val();
	    var heat_id = $('select#heat_list option:selected').val();
	    if(results_type === 'ResultsCategory' && (select_type === 'results_type' || select_type === 'event_list')){
		results.ResultsCategory(url_file,url_params,results_type,heat_id,select_type);
	    }
	    else{
	       results.ResultsRequest(url_file,url_params,results_type,heat_id,select_type);  //heat_id jsou rozjíždky, děláno v chvatu na MAdeju, koncepční to asi není
	    }
	});
	
	$("#laps_only").click(function(){
	    var select_type = $(this).attr('id');
	    var results_type = $('select#results_type option:selected').val();
	    var heat_id = $('select#heat_list option:selected').val();
	    if(results_type === 'ResultsCategory' && (select_type === 'results_type' || select_type === 'event_list')){
		results.ResultsCategory(url_file,url_params,results_type,heat_id,select_type);
	    }
	    else{
	       results.ResultsRequest(url_file,url_params,results_type,heat_id,select_type);  //heat_id jsou rozjíždky, děláno v chvatu na MAdeju, koncepční to asi není
	    }

	});
        
	$('div#content').on('click','ul#strankovani li a',function(){
	    var x = $(this).text();
	    var arr = x.split("-");
            
            
            
            var select_type = $(this).attr('class');
	    var results_type = $('select#results_type option:selected').val();
	    var heat_id = $('select#heat_list option:selected').val();
            limit_od = arr[0] - 1;
	    if(results_type === 'ResultsCategory' && (select_type === 'results_type' || select_type === 'event_list')){
		results.ResultsCategory(url_file,url_params,results_type,heat_id,select_type);
	    }
	    else{
	       results.ResultsRequest(url_file,url_params,results_type,heat_id,select_type,limit_od);  
	    }
	    return false;
	});
	
    
    
        
        
        
        
        
        
    },
    
    
    /* Posílá požadavek na zpracování výsledků podle kategorií dle aktuálního nastevení selectů */
    ResultsCategory : function(url_file,url_params,results_type,heat_id,select_type){
	var event_order = $('select#event_list option:selected').val();
	var url_controller = './php/' + url_file + url_params;
	$.get(url_controller,{murinoha:'CategoryListSelect',event_order: event_order},function(xhr){
	    $('span#dynamic_select').html(xhr);
	    results.ResultsRequest(url_file,url_params,results_type,heat_id,select_type);
	 });
    },

    
    /*
     * funkce s požadavkem na server nejdříve "sejme" všecky parametry ze všech selectů a posílá vlastní požadavek a přijímá odpověď
     */
    ResultsRequest : function(url_file,url_params,results_type,heat_id,select_type,limit_od){
	var results_params = results.ResultsParams();
	var url_controller = './php/' + url_file + url_params;
	/*
	 * prázdná hodnota value v případě, že select s rozjížkama exituje, nebo undefined, když select neexistuje vůbec
	 * funguje tady oprátor &&, já bych to spíš dal ||, ale ten nefunguje, nicméně to je pořád asi moje hostorická nejasnot
	 * celkově je to nekoncepční, mělo by to být sbíráno taky s REsultsPArams
	 */
	if(typeof(heat_id) != 'undefined' && heat_id != ''){
	    //alert();
	    var event_list = false;
	    $.getJSON(url_controller,{
		    murinoha: results_type,
		    event_order: results_params.event_order,
		    time_order: results_params.time_order,
		    category_id: results_params.category_id,
		    gender: results_params.gender,
		    birth_year: results_params.birth_year,
		    heat_id : heat_id,
		    id_etapy : results_params.id_etapy,
		    rows_limit: results_params.rows_limit,
		    event_list:event_list
		},function(xhr){
                    $('div#result_table').html(xhr.results);
                });

	}
	else{
	    var event_list = false;
                var now = new Date();
                var day = now.getDate();
                var month = now.getMonth() + 1;
                var year = now.getFullYear();
                var h = now.getHours();
                var m = now.getMinutes();
                var cas_vytisteni = day+"."+month+"."+year+", "+h+":"+m;
	    if(select_type === "event_list"){
		event_list = true;
	    }
	    $.getJSON(url_controller,{  
		    murinoha: results_type,
		    event_order: results_params.event_order,
		    time_order: results_params.time_order,
		    category_id: results_params.category_id,
		    gender: results_params.gender,
		    birth_year: results_params.birth_year,
		    heat_id : heat_id,
		    id_etapy : results_params.id_etapy,
		    rows_limit: results_params.rows_limit,
		    event_list:event_list,
		    laps_only: results_params.laps_only,
                    limit_od: limit_od
		},function(xhr){
                    $('div#result_table').html(xhr.results);
                    $('span#cas_vytisteni').text(cas_vytisteni);
                });
	}
    },
    
    /* sejme všecky hodnoty se selectů a poskytne je tomu, kdo si o ně řekl */
    ResultsParams : function(){
	return {
	    event_order: $('select#event_list option:selected').val(),
	    time_order: $('select#lap_name option:selected').val(),
	    category_id: $('select#category_list option:selected').val(),
	    gender: $('select#gender_list option:selected').val(),
	    birth_year: $('select#birth_year_list option:selected').val(),
	    id_etapy: $('select#id_etapy option:selected').val(),
	    rows_limit: $('input#rows_limit').val(),
	    laps_only: $('input#laps_only:checked').val()
	};
    },
    
    /* přidá do lišty select na pohlaví */
    GenderListSelect : function(url_file,url_params){
	    $('form#vysledky_form').on('change','select#results_type',function(){
		if($(this).find('option:selected').text() === pohlavi){
		url_controller = './php/' + url_file + url_params;
		$.get(url_controller,{murinoha:'GenderListSelect'},function(xhr){
		    $('span#dynamic_select').html(xhr);
		});
	    }
	    return false;
	});
    },
    
        /* přidá do lišty select na pohlaví */
    LapsListSelect : function(url_file,url_params){
	$('form#vysledky_form').on('change','select#event_list',function(){ //změna podzávodu
	   var event_order = $(this).find('option:selected').val(); //najde číslo podzávodu
	   if($(this).find('option:selected').text() === stafety && $('select#results_type option:selected').text() === pohlavi){ //pokud je podzávod štafety a typ výsledků podle pohlaví, dás se pryč dynamický select 
		$('span#dynamic_select select').css('display','none');
	    }
	    else if($(this).find('option:selected').text() !== stafety && $('select#results_type option:selected').text() === pohlavi){ // a tohle ej opačně
	       $('span#dynamic_select select').css('display','inline');
	    }
	    var url_controller = './php/' + url_file + url_params;
	    $.get(url_controller,{murinoha:'LapsListSelect',event_order:event_order},function(xhr){
		$('span#laps_list_select_wrapper').html(xhr);
	    });
	return false;
	});
    },
    
    
            /* přidá do lišty select rozjizdky */
    HeatListSelect : function(url_file,url_params){
	$('form#vysledky_form').on('change','select#event_list',function(){ //změna podzávodu
	   var event_order = $(this).find('option:selected').val(); //najde číslo podzávodu
	    var url_controller = './php/' + url_file + url_params;
	    $.get(url_controller,{murinoha:'HeatListSelect',event_order:event_order},function(xhr){
		$('span#heat_list_select_wrapper').html(xhr);
	    });
	return false;
	});
    },

    
    


    /*
     * Po zmáčknutí tlačítka Spustit Autoreading se spouští autoreading  
     */
    ResultsAutoreadingStart :function(url_file,url_params){
	//$('#results-autoreading-button').on('click','.spustit-results-autoreading',function(){
	    //$(this).removeClass("spustit-results-autoreading").addClass('zastavit-results-autoreading').text('Zastavit autoreading');
	    //results.ResultsAutoreading(url_file,url_params);
	    //return false;
	//});
    },
    
    /* 
     * pošle se požadavek na server s parametry ze všech selectů, načte se výsledek podle momentálního nastavení a spouští se heartbeat
    */
    ResultsAutoreading :function(url_file,url_params){
	var results_params = results.ResultsParams();
	var url_controller = './php/' + url_file + url_params;
	var results_type = $('select#results_type option:selected').val();
	$.getJSON(url_controller,{murinoha: results_type,event_order: results_params.event_order,time_order: results_params.time_order,category_id: results_params.category_id,gender:results_params.gender,birth_year: results_params.birth_year,id_etapy:results_params.id_etapy,rows_limit:results_params.rows_limit},function(xhr){
	    $('div#result_table').html(xhr.results);
	    var last_modified = xhr.last_modified;
	    var change_control_file = xhr.change_control_file;
	    results.HeartbeatResults(last_modified,change_control_file,results_interval,url_file,url_params);	
	});
    },

    /* heartbeat s funkcí zastavení po zmáčknutí tlačítko Zastavit Autoreading.. na konci je kontrolka pro indikaci, že funkce pracuje */
    HeartbeatResults :function(stary_cas,change_control_file,interval,url_file,url_params){
	var xhr = $.ajax({type: "HEAD",cache: false,async: true,url: change_control_file,crossDomain:true}).done(function(){
	    var x = xhr.getResponseHeader("Last-Modified");
	    var str = String(new Date(x).getTime()); //pokud se to nedalo do stringu, nedaly se odřezat ty 3 nuly na konci
	    var last_modified = str.substring(0,10);
	    if(stary_cas == last_modified){
		var timeout1 = setTimeout(function(){results.HeartbeatResults(last_modified,change_control_file,interval,url_file,url_params)},interval);
	    }
	    else{
		results.ResultsAutoreading(url_file,url_params);
	    }
	});
    },

    /* přidá do lišty select s ročníkama narození */
    BirthYearListSelect : function(url_file,url_params){
	$('form#vysledky_form').on('change','select#results_type',function(){
	    if($(this).find('option:selected').val() === roky_narozeni){
		var url_controller = './php/' + url_file + url_params;
		$.get(url_controller,{murinoha:'BirthYearListSelect'},function(xhr){
		    $('span#dynamic_select').html(xhr);
		});
	    }
	    return false;
	});
    },

    /* vyprázdní span pro vkládání dnamických selectů v případě, že se zadají výsledky bez kategorií, bo ty žádné dynamické selecty nemají */
    DynamicSelectEmpty : function(){
	$('form#vysledky_form').on('change','select#results_type',function(){
	    if($(this).val() == 'ResultsOverall'){
		$('span#dynamic_select').empty(); 
	    }
	});
    }
};


//**** routing ****//  
/* Po kliknutí na jakýkoli odkaz z menu se vezmou parametry z URL, podle nich se pošle požadavek na server a čeká se na události */
$.fn.SelectAction = function(){
    $('ul#menu').on('click','a[name != "header"]',function(){
	var url = $(this).UrlParams(this.hash);
	$.getJSON('./php/' + url.file + url.params,function(xhr){
	    $('#content').html(xhr);
	    $(this).RcEvents(url.file,url.params);
	});
    }); 
    
    $('ul#menu').on('click','a[name = "header"]',function(){
	//return false;
    }); 
};

$.fn.UrlParams = function(url_source){
    var race_year,race_id,hash,pole,str,params,param_race_year,param_race_id,param_murinoha,murinoha,param_murinoha_basic;
    var komponenty_file = 'komponenty.php';
    var file = '';
    var params_count = 0;
    params = '';
    param_race_year = '?race_year=';
    param_race_id = '&race_id=';
    param_murinoha_basic = 'komponenty.php';
    param_murinoha = '&murinoha=';
    //var neco = window.location.hash;
    if(url_source){
	str = url_source;
	hash = str.substring(str.indexOf('#')+1);
	pole = hash.split('/');
	params_count = pole.length;
	//alert(pole.length);
	if(params_count == 1){
	   race_year = pole[0];
	   params  += param_race_year + race_year;
	}
	else if(params_count == 2){
	   
	    //params  += param_murinoha_basic;
	    race_year = pole[0];
	    race_id = pole[1];
	    params  += param_race_year + race_year;
	    params  += param_race_id + race_id;
	    
	}
	else if(params_count == 3){
	    file  += pole[2] + '.php';
	    race_year = pole[0];
	    race_id = pole[1];
	    params  += param_race_year + race_year;
	    params  += param_race_id + race_id;
	}
	else if(params_count == 4){
	    file  += pole[2] + '.php';
	    race_year = pole[0];
	    race_id = pole[1];
	    murinoha = pole[3];
	    params  += param_race_year + race_year;
	    params  += param_race_id + race_id;
	    params  += param_race_id + race_id;
	    params  += param_murinoha + murinoha;
	}

    }
    else{
	//param_murinoha;
	//params  += param_murinoha_basic;
    }
    
    //alert(params);
    
    if(typeof(race_id) == 'undefined'){
	$("#menu").css("display","none");
    }
    else{
	$("#menu").css("display","block");
    }
    
   // alert(race_year);
    return {
	komponenty_file: komponenty_file,
	file: file,
        race_id: race_id,
	race_year: race_year,
        params: params,
	params_count: params_count
    };
 };



$.fn.BasicRaceData = function(){
    var url_params = $(this).UrlParams(window.location.hash);
    $.getJSON('./php/' + url_params.komponenty_file + url_params.params,function(xhr){
	$('ul#menu').html(xhr.navbar_menu);
	$('select#race_select').html(xhr.race_select);
	$('select#race_select option[value='+url_params.race_id+']').prop('selected',true);
	$('select#race_year').html(xhr.race_year);
	//$('h1').html(xhr.basic_race_info.nazev_zavodu);
	$('#content').empty();
	if(url_params.params_count > 2){
	    $.getJSON('./php/' + url_params.file + url_params.params,function(xhr1){
		$('#content').html(xhr1);
		$(this).RcEvents(url_params.file,url_params.params,url_params.race_year,url_params.race_id);
	    });
	}
    });
};


$.fn.RcEvents = function(url_file,url_params){
    results.ResultsEvents(url_file,url_params);
    starting_list.StartingListConstructor(url_file,url_params);
    right_now.RightNowConstructor(url_file,url_params);
    result_search.ResultSearchConstructor(url_file,url_params);
};

$.fn.RaceYearSelect = function(){
    $('select#race_year').change(function(){
	var race_year = $('select#race_year').val(); 
	$.getJSON('./php/komponenty.php',{race_year:race_year},function(xhr){
	    $('select#race_select').html(xhr.race_select);
	    $('select#race_year option[value='+race_year+']').prop('selected',true);
	    $('h1').empty();
	    $('ul#menu').css('display','none');
	    window.history.pushState('blabla', 'Něco','#'+race_year);
	});
	return false;
    });
};

$.fn.RaceSelect = function(){
    $('select#race_select').change(function(){
	var race_id = $(this).val();
	var race_year = $('select#race_year').val(); 
	window.history.pushState('blabla', 'Něco', '#'+race_year+'/'+race_id);
	$(this).BasicRaceData();
	return false;
    });
};

	
$(document).ready(function(){
    $(this).BasicRaceData();
    $(this).RaceYearSelect();
    $(this).RaceSelect();
    $(this).SelectAction();

 });
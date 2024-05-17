<?php
   define('URL','http://livepublic.timechip.loc/');
   //define('URL','http://10.11.12.13/');
   //define('URL','http://10.11.12.12/');
   // define('URL','http://192.168.1.13/');
   //define('URL','http://comment.timechip.loc/');
    
    /* název souboru pro kontrolu změn */
    define('CHANGE_CONTROL_FILE','change_control_file.txt');
    /* základní adresářová cesta */
    define('FILE_PATH','sourcefiles/');
    /* file_get_contents() */
    define('FILE_URL_PATH',URL.FILE_PATH);
    /* fopen() */
    define('CHANGE_CONTROL_FILE_PATH','../'.FILE_PATH.CHANGE_CONTROL_FILE);
    /* javascript: getResponseHeader(), php: get_headers() */
    define('CHANGE_CONTROL_FILE_URL_PATH',FILE_URL_PATH.CHANGE_CONTROL_FILE);
   
    define('LIBS','libs/');
    define('DB_TYPE','mysql');
    define('DB_HOST','localhost');
    define('DB_NAME','timechip_cz');
    define('DB_USER','skybedy');
    define('DB_PASS','mk1313life');
    //define('SERIE','bikePrague');
    define('SERIE','lednice');
    define('EXPORT_CSV',true);
    
    
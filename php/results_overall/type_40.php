<?php  
    /* variantA TYMY, WINTER hEI RUN
     * 
     * 
     */


    

    $hash_url = '#'.$this->race_year.'/'.$this->race_id.'/';

    if($this->rows_limit_number){
	 $rows_limit_number = $this->rows_limit_number;
     }
     else{
	 $rows_limit_number = 1000;
     }

    $sql = "SELECT pocet_casu FROM $this->sqlpodzavody WHERE id_zavodu = :race_id AND poradi_podzavodu = :event_order";
    $sth = $this->db->prepare($sql);
    $sth->execute(Array(':race_id' => $this->race_id,':event_order' => $this->event_order));
    $dbdata = $sth->fetchObject();
    $time_order = $dbdata->pocet_casu;

    $tym = Array(); 
    
    $sql1 = "SELECT tym FROM $this->sqlzavod WHERE poradi_podzavodu = :event_order GROUP BY tym ORDER BY id DESC";
    $sth1 = $this->db->prepare($sql1);
    $sth1->execute(Array(':event_order' => $this->event_order));
    if($sth1->rowCount()){
        while($dbdata1 = $sth1->fetchObject()){
           //v tomto dotazu musí být tzv. own alais,což je "AS SUBQUERY" 
           $sql2 = "SELECT COUNT(race_time_sec) AS row_count,SUM(race_time_sec) as finish_time FROM (SELECT $this->sqlvysledky.race_time_sec FROM $this->sqlvysledky,$this->sqlzavod WHERE $this->sqlvysledky.cip = $this->sqlzavod.cip AND time_order = :time_order AND $this->sqlzavod.tym = :tym AND $this->sqlzavod.poradi_podzavodu = :event_order AND false_time IS NULL ORDER BY $this->sqlvysledky.race_time_sec LIMIT 0,$this->team_racer_count) AS subquery";
           $sth2 = $this->db->prepare($sql2);
           $sth2->execute(Array(':tym' => $dbdata1->tym,':time_order' => $time_order,':event_order' => $this->event_order));
           if($sth2->rowCount()){
               while($dbdata2 = $sth2->fetchObject()){
                   if($dbdata2->row_count >= 4){ //melo y to byt dynamicky podle nastaveni, ted neni cas
                        $tym[$dbdata1->tym] = $dbdata2->finish_time;

                   }
               }
           }
        }

          //print_r($tym);

        if($tym){
           asort($tym,SORT_NUMERIC);
           // print_r($tym);
           $str .= '<h4 class="headline-results">'.$this->race_name.', '.$this->event_name.'výsledky týmů</h4>';
           $str .= '<table id="table2excel" class="table table-bordered table_vysledky">';
           $str .= '<thead><tr class="header"><th class="text-center">#</th><th class="text-left">Název týmu</th><th>Kategorie</th><th class="text-left">Složení</th><th class="text-center">Čip</th><th class="text-center">Ročník</th><th class="text-center">Čas</th><th class="text-center">Poř</th><th class="text-center">Celkový čas</th><th class="text-center">Odstup</th></tr></thead><tbody>';
           $poradi = 1;
          
           foreach($tym as $key => $value){
               $celkovy_cas = $this->SecToTime($value);
               if($poradi == 1) $best_time = $value;

               if($poradi <= $rows_limit_number){
                   $distance_time = $this->DynamicDistances($poradi,$value,$best_time);
                   
                   $sql3 = "SELECT $this->sqlzavod.ids,tymy.nazev_tymu,$this->sqlkategorie.nazev_k AS kategorie FROM tymy,$this->sqlkategorie,$this->sqlzavod WHERE tymy.id_tymu = $key AND tymy.id_tymu = $this->sqlzavod.tym AND $this->sqlzavod.id_kategorie = $this->sqlkategorie.id_kategorie AND $this->sqlzavod.poradi_podzavodu = $this->event_order GROUP BY tymy.nazev_tymu";
                   //$str .= $sql3;
                   $sth3 = $this->db->prepare($sql3);
                   $sth3->execute(Array(':id_tymu' => $key));
                   if($sth3->rowCount()){
                       $dbdata3 = $sth3->fetchObject();
                       $sql4 = "SELECT $this->sqlzavod.ids_alias,$this->sqlzavod.cip,CONCAT_WS(' ',osoby.prijmeni,osoby.jmeno) AS jmeno,osoby.rocnik,$this->sqlvysledky.race_time,$this->sqlvysledky.rank_overall FROM $this->sqlvysledky,$this->sqlzavod,osoby WHERE $this->sqlvysledky.cip = $this->sqlzavod.cip AND $this->sqlzavod.tym = :tym AND $this->sqlzavod.ido = osoby.ido AND time_order = :time_order AND false_time IS NULL AND $this->sqlvysledky.ids = :ids ORDER BY $this->sqlvysledky.race_time ASC LIMIT 0,$this->team_racer_count";
                       $sth4 = $this->db->prepare($sql4);
                       $sth4->execute(Array(':tym' => $key,':time_order' => $time_order,':ids' => $dbdata3->ids));
                       if($sth4->rowCount()){
                           $pocet_clenu = $sth4->rowCount();
                           $k = 1;
                           while($dbdata4 = $sth4->fetchObject()){
                               if($k == 1){
                                   $str .= '<tr>';
                                   $str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$poradi.'</td>';
                                   $str .= '<td rowspan="'.$pocet_clenu.'"><a onclick="detail_tymu('.$dbdata3->ids.','.$this->race_id.','.$this->race_year.','.$time_order.','.$pocet_clenu.')" href="'.$hash_url.'vysledky">'.$dbdata3->nazev_tymu.'</a></td>';
                                   $str .= '<td rowspan="'.$pocet_clenu.'">'.$dbdata3->kategorie.'</td>';
                                   $str .= '<td>'.$dbdata4->jmeno.'</td>';
                                   $str .= '<td class="text-center">'.$dbdata4->cip.'</td>';
                                   $str .= '<td class="text-center">'.$dbdata4->rocnik.'</td>';
                                   $str .= '<td class="text-center">'.$dbdata4->race_time.'</td>';
                                   $str .= '<td class="text-center">'.$dbdata4->rank_overall.'</td>';
                                   $str .= '<td rowspan="'.$pocet_clenu.'" class="text-center">'.$celkovy_cas.'</td>';
                                   $str .= ($distance_time != '00:00:00.00') ?  ('<td rowspan="'.$pocet_clenu.'" class="text-center">'.$distance_time.'</td>') : ('<td rowspan="'.$pocet_clenu.'" class="text-center">-</td>');
                                   $str .= '</tr>';
                               }
                               else{
                                   $str .= '<tr>';
                                   $str .= '<td>'.$dbdata4->jmeno.'</td>';
                                   $str .= '<td class="text-center">'.$dbdata4->cip.'</td>';
                                   $str .= '<td class="text-center">'.$dbdata4->rocnik.'</td>';
                                   $str .= '<td class="text-center">'.$dbdata4->race_time.'</td>';
                                   $str .= '<td class="text-center">'.$dbdata4->rank_overall.'</td>';
                                   $str .= '</tr>';
                               }
                           $k++;
                           }
                       }
                   }
                   $poradi++; 

               }





           }
        $str .= '</tbody></table>';    
        }

    }
?>
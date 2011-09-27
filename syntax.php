<?php
/******************************************************************************
*  rater Plugin
//session_start(); */
if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');  
    
/******************************************************************************
* All DokuWiki plugins to extend the parser/rendering mechanism
* need to inherit from this class
*/
class syntax_plugin_rater extends DokuWiki_Syntax_Plugin 
{
/******************************************************************************/
/* return some info
*/
    function getInfo(){
        return confToHash(dirname(__FILE__).'/plugin.info.txt');
    }

    function getType(){ return 'substition';}
    function getPType(){ return 'block';}
    function getSort(){ return 168;}
    
/******************************************************************************/
/* Connect pattern to lexer
*/
    function connectTo($mode){
        $this->Lexer->addSpecialPattern('\{\(rater>[^}]*\)\}',$mode,'plugin_rater');
    }    
/******************************************************************************/
/* Handle the match
*/
    function handle($match, $state, $pos, &$handler){
        $match = substr($match,8,-2); //strip markup from start and end
        
        //handle params
        $data = array();
        $params = explode('|',$match);
        
        //Default Value
        $data['rater_id'] = 1;
        $data['rater_name'] = "this";
        $data['rater_type'] = "rate";
        $data['rater_end'] = "never";
        $data['rater_trace'] = "ip";
        
        foreach($params as $param){            
            $splitparam = explode('=',$param);
            if ($splitparam[1] != '')
            {
                if ($splitparam[0]=='id')
                	{$data['rater_id'] = $splitparam[1];    // unique item id
                    /*continue;*/}
                if ($splitparam[0]=='name')
                	{$data['rater_name'] = $splitparam[1];  // descriptive item name
                    /*continue;*/}
                if ($splitparam[0]=='type')
                	{$data['rater_type'] = $splitparam[1];  // rate or vote or stat
                    /*continue;*/}  
                if ($splitparam[0]=='end')
                	{$data['rater_end'] = $splitparam[1];   // date or never
                    /*continue;*/}  
                if ($splitparam[0]=='trace')
                	{$data['rater_trace'] = $splitparam[1]; // ip, user name, none
                    /*continue;*/}  
                if ($splitparam[0]=='tracedetails')
                	{$data['rater_tracedetails'] = $splitparam[1]; // ip, user name, none
                    /*continue;*/}  
            }           
        }    
        return $data;
    }    
/******************************************************************************/
/* Create output
*/
    function render($mode, &$renderer, $data) {        
        global $ID;          
        global $lang;
        
        if ($mode == 'xhtml'){            
            $renderer->info['cache'] = false;     
            
          // Config settings
          $rater_ip_voting_restriction = $this->getConf('voting_restriction'); // restrict ip address voting (true or false)
          $rater_ip_vote_qty           = $this->getConf('vote_qty');           // how many times an ip address / user name can vote
          $rater_already_rated_msg     = sprintf($this->getConf('already_rated_msg'),$rater_ip_vote_qty);
          $rater_not_selected_msg      = $this->getConf('not_selected_msg');
          $rater_thankyou_msg          = $this->getConf('thankyou_msg');
          $rater_generic_text          = $this->getConf('generic_text');       // generic item text
          $rater_end_of_line_char      = $this->getConf('eol_char');           // may want to change for different operating systems
          
          $msg_ratingend       = $this->getLang('msg_ratingend');
          $msg_ratingended     = $this->getLang('msg_ratingended');
          $msg_votend          = $this->getLang('msg_votend');
          $msg_votended        = $this->getLang('msg_votended');
          $btn_submit          = $this->getLang('btn_submit');
          
          $rater_id = $data['rater_id'];
          $rater_name = $data['rater_name'];
          if (!isset($data['rater_end'])) $data['rater_end']='never';
          if(!isset($data['rater_tracedetails'])) $data['rater_tracedetails']='0';
          if(!isset($rater_id)) $rater_id=1;
          $rater_item_name = $data['rater_name'];                              // item name what is to be rated
          if(!isset($rater_item_name)) $rater_item_name=$rater_generic_text;
          $rater_type = $data['rater_type'];
          if(!isset($rater_type)) $rater_type="stars";          
          
          // DO NOT MODIFY BELOW THIS LINE
          $rater_filename = metaFN('rater_'.$data['rater_id'].'_'.$data['rater_name'].'_'.$data['rater_type'], '.rating');
          $rater_rating=0;
          $rater_stars="";
          $rater_stars_txt="";
          $rater_rating=0;
          $rater_votes=0;
          $rater_msg="";
          
          // check to trace ip or user name
          $user_grp = pageinfo();
          $rater_realname =  $user_grp['userinfo']['name'];
          if ($data['rater_trace']==='user') {
             $rater_ip = $user_grp['userinfo']['name'];
          }
          else {    
             $rater_ip = getenv("REMOTE_ADDR");
          }         

/******************************************************************************/
          // Rating action
          if(isset($_REQUEST["rate".$rater_id])){
           if(isset($_REQUEST["rating_".$rater_id])){
              while(list($key,$val)=each($_REQUEST["rating_".$rater_id])){
                $rater_rating=$val;
              }
              $rater_file=fopen($rater_filename,"a+");
              $rater_str="";
              $rater_str = rtrim(fread($rater_file, 1024*8),$rater_end_of_line_char);
              if($rater_str!=""){
                 if($rater_ip_voting_restriction){
                    $rater_data=explode($rater_end_of_line_char,$rater_str);
                	  $rater_ip_vote_count=0;
                    foreach($rater_data as $d){
                    	 $rater_tmp=explode("|",$d);
                    	 $rater_oldip=str_replace($rater_end_of_line_char,"",$rater_tmp[1]);
                    	 if($rater_ip==$rater_oldip){
                    	  $rater_ip_vote_count++;
                    	 }
                    }
                  	if($rater_ip_vote_count > ($rater_ip_vote_qty - 1)){
                       $rater_msg=$rater_already_rated_msg;
                  	}else{
                       fwrite($rater_file,$rater_rating."|".$rater_ip.$rater_end_of_line_char);
                       $rater_msg=$rater_thankyou_msg;
                  	}
                 }else{
                   fwrite($rater_file,$rater_rating."|".$rater_ip.$rater_end_of_line_char);
                   $rater_msg=$rater_thankyou_msg;                 }
              }else{
                fwrite($rater_file,$rater_rating."|".$rater_ip.$rater_end_of_line_char);
                $rater_msg=$rater_thankyou_msg;              }
              fclose($rater_file);
           }else{
              $rater_msg=$rater_not_selected_msg;
           }
          }
          
/******************************************************************************/
          if ($rater_type=="rate") {          
              // Get current rating
              $r1 = '0'; $r2 = '0'; $r3 = '0'; $r4 = '0'; $r5 = '0';
              $rater_votes = '0'; $rater_sum = '0';
              if(is_file($rater_filename)){
                 $tmp_array = $this->calc_rater_rating($rater_filename);
                 $rater_rating = $tmp_array[0][0];
                 $r1 += $tmp_array[0][1];
                 $r2 += $tmp_array[0][2];
                 $r3 += $tmp_array[0][3];
                 $r4 += $tmp_array[0][4];
                 $r5 += $tmp_array[0][5];
                 $rater_votes  += $tmp_array[0][6];
                 $rater_sum += $tmp_array[0][7];                                  
              }
              else{
               $rater_file=fopen($rater_filename,"w");
               fclose($rater_file);
              }

              // Assign star image
              $rater_stars = $this->assign_star_image($rater_rating);
              
              // build the return value for details
              if (($data['rater_end']!='never') && (date('d.m.Y',strtotime($data['rater_end']))>=date('d.m.Y')))
              {  $ret_details = '<div class="rating__details">'.sprintf($msg_ratingend,date('d.m.Y',strtotime($data['rater_end']))).'<br>'; 
                 $alink_Details = '<a href="#" onclick="hidden'.$rater_id.'()">(Details)</a>'; }
              elseif (($data['rater_end']!='never') && (date('d.m.Y',strtotime($data['rater_end']))<date('d.m.Y')))
              {  $ret_details = '<div class="rating__details">'.sprintf($msg_ratingended,date('d.m.Y',strtotime($data['rater_end']))).'<br>'; 
                 $alink_Details = '<a href="#" onclick="hidden'.$rater_id.'()">(Details)</a>';}
              else 
              {  $ret_details ='<div class="rating__details">';
                 $alink_Details = '';}
              
              if ($data['rater_tracedetails']!='0') {
                  if ($alink_Details === '')
                    { $alink_Details = '<a href="#" onclick="hidden'.$rater_id.'()">(Details)</a>'; }
                  $ret_details .= '<img src="'.DOKU_BASE.'lib/plugins/rater/img/1star.gif?w=40&amp;" alt="1 Star" width="40" align="left" /> '.$r1.' visitor votes<BR>';              
                  $ret_details .= '<img src="'.DOKU_BASE.'lib/plugins/rater/img/2star.gif?w=40&amp; alt="2 Stars" width="40" align="left" /> '.$r2.' visitor votes<BR>';              
                  $ret_details .= '<img src="'.DOKU_BASE.'lib/plugins/rater/img/3star.gif?w=40&amp;" alt="3 Stars" width="40" align="left" /> '.$r3.' visitor votes<BR>';              
                  $ret_details .= '<img src="'.DOKU_BASE.'lib/plugins/rater/img/4star.gif?w=40&amp;" alt="4 Stars" width="40" align="left" /> '.$r4.' visitor votes<BR>';
                  $ret_details .= '<img src="'.DOKU_BASE.'lib/plugins/rater/img/5star.gif?w=40&amp;" alt="5 Stars" width="40" align="left" /> '.$r5.' visitor votes<BR>';                            
              }
              $ret_details .= '</div>';
              
              
              // Output rate
              $ret .= '<TABLE class="hreview">';
              $ret .= '<form method="post" action="doku.php?id=' . $ID .'" >';
              $ret .= '<TR><TD class="item">Rate '.$rater_item_name.'</TD></TR>';
              $ret .= '<TR>';
              
              $ret .= '<script type="text/javascript" language="JavaScript1.2">
                          var visible = false;
                          function hidden'.$rater_id.'() 
                          {   if (visible)
                              {   document.getElementById("details_'.$rater_id.'").style.display = "none";
                                  visible = false; }
                              else
                              {   document.getElementById("details_'.$rater_id.'").style.display = "block";
                                  visible = true; }
                          } 
                        </script>';
                            
              $ret .= '<TD class="rating"><img src="'.$rater_stars.'?x='.uniqid((double)microtime()*1000000,1).'" alt="'.$rater_stars_txt.' stars" />&nbsp'.$rater_stars_txt.'</span> from <span class="reviewcount"> '.$rater_votes.' votes '.$alink_Details.' </TD></TR>';
              $ret .= '<tr><td style="display : none" id="details_'.$rater_id.'">'.$ret_details.'</td></tr>'; 
              $ret .= '<TR><TD>';
              
              if (($data['rater_end']!='never') && (date('d.m.Y',strtotime($data['rater_end']))<date('d.m.Y')))
              {
                  $rater_msg =''; }
              else {
                  $ret .= '<label for="rate1_'.$rater_id.'"><input type="radio" value="1" name="rating_'.$rater_id.'[]" id="rate1_'.$rater_id.'" />&nbsp</label>';
                  $ret .= '<label for="rate2_'.$rater_id.'"><input type="radio" value="2" name="rating_'.$rater_id.'[]" id="rate2_'.$rater_id.'" />&nbsp</label>';
                  $ret .= '<label for="rate3_'.$rater_id.'"><input type="radio" value="3" name="rating_'.$rater_id.'[]" id="rate3_'.$rater_id.'" />&nbsp</label>';
                  $ret .= '<label for="rate4_'.$rater_id.'"><input type="radio" value="4" name="rating_'.$rater_id.'[]" id="rate4_'.$rater_id.'" />&nbsp</label>';
                  $ret .= '<label for="rate5_'.$rater_id.'"><input type="radio" value="5" name="rating_'.$rater_id.'[]" id="rate5_'.$rater_id.'" />&nbsp</label>';
                  $ret .= '<input type="hidden" name="rs_id" value="'.$rater_id.'" />';
                  $ret .= '<input type="submit" name="rate'.$rater_id.'" value="'.$btn_submit.'" /></TD></TR>';
              }
              
              $ret .= '</div>';
              if($rater_msg!="") $ret .= "<div>".$rater_msg."</div>";
              $ret .= '</form>';
              $ret .= '</TABLE>';
          }
          
/******************************************************************************/
          elseif ($rater_type=="vote") {
              // Get current rating 
              if(is_file($rater_filename)){
                 $rater_file=fopen($rater_filename,"r");
                 $rater_str="";
                 $rater_str = fread($rater_file, 1024*8);
                 if($rater_str!=""){
                    $rater_data=explode($rater_end_of_line_char,$rater_str);
                    $rater_votes=count($rater_data)-1;
                    $vote1='0';
                    $vote2='0';
                    foreach($rater_data as $d){
                       $d=explode("|",$d);
                       if ($d[0]==='1') {
                          $vote1 = $vote1 +1;
                          $who1 .= $d[1]."<br>";
                       }
                       elseif ($d[0]==='2') {
                          $vote2 = $vote2 +1;
                          $who2 .= $d[1]."<br>";
                       }
                    }   
                 }
                 else{
                    $vote1='0';
                    $vote2='0';
                 }
                 fclose($rater_file);
               }
               //check if vote period already ended
              if (($data['rater_end']!='never') && (date('d.m.Y',strtotime($data['rater_end']))>=date('d.m.Y')))
                  {$rater_endmsg = sprintf($msg_votend,date('d.m.Y',strtotime($data['rater_end']))).'<br>';}
              elseif (($data['rater_end']!='never') && (date('d.m.Y',strtotime($data['rater_end']))<date('d.m.Y')))
                  {$rater_endmsg = sprintf($msg_votended,date('d.m.Y',strtotime($data['rater_end']))).'<br>';}
              else
                  {$rater_endmsg ='';}
              // build the return value for details if details option is on
              $ret_details = '<div class="rating__details">'.$rater_endmsg.'<hr>';
              if ($data['rater_tracedetails']!='0') {
                  $ret_details .= '<img src="'.DOKU_BASE.'lib/plugins/rater/img/thumbup.gif?h=12&amp;" alt="Pro" align="left" /> <p align="left">('.$vote1.') </p><p>'. $who1.'</p><BR><hr>';              
                  $ret_details .= '<img src="'.DOKU_BASE.'lib/plugins/rater/img/thumbdown.gif?h=12&amp;" alt="Contra" align="left" /> <p align="left">('.$vote2.') </p><p>'. $who2.'</p><BR>';
              }
              $ret_details .= '</div>';                            
              
              // Output
              $ret .= '<TABLE class="hreview">';
              $ret .= '<form name="'.$data['rater_id'].$data['rater_name'].$data['rater_type'].'" method="post" action="doku.php?id=' . $ID .'">';
              

              
              if($_REQUEST["info"]!="ppp")
              { $addMSG = '';
              }
              else { $addMSG = $rater_already_rated_msg.'<br>';}
              $ret .= '<script type="text/javascript" language="JavaScript1.2">
                          var visible = false;
                          function hidden'.$rater_id.'() 
                          {   if (visible)
                              {   document.getElementById("details_'.$rater_id.'").style.display = "none";
                                  visible = false; }
                              else
                              {   document.getElementById("details_'.$rater_id.'").style.display = "block";
                                  visible = true; }
                          } 
                        </script>';                                  
              $ret .= '<TR><TD>'.$addMSG.'<a class="thumbup tup" href="doku.php?id='.$ID.'&do=rate_voteup&rater_id='.$rater_id.'&rater_ip='.$rater_ip.'&rater_end='.$data['rater_end'].'&rater_name='.$rater_name.'" /></a>'.
                   '<span id="vote1_1" style="color:#5b8f22">('.$vote1.')&nbsp</span>'.
                   '<a class="thumbdown tdn" href="doku.php?id='.$ID.'&do=rate_votedown&rater_id='.$rater_id.'&rater_ip='.$rater_ip.'&rater_end='.$data['rater_end'].'&rater_name='.$rater_name.'" /></a>'.
                   '<span id="vote1_2" style="color:#FF1822">('.$vote2.')</span><a href="#" onclick="hidden'.$rater_id.'()">(Details)</a></TD></TR>';
              $ret .= '<tr><td style="display : none" id="details_'.$rater_id.'">'.$ret_details.'</td></tr>';
              if($rater_msg!="") $ret .= "<div>".$rater_msg."</div>";
              $ret .= '</form>';
              $ret .= '</TABLE>';              
          }
/******************************************************************************/
          elseif ($rater_type=="stat") {
            global $conf;
            $data = array();
            
            // 1. load all rating files into array
            // create an array of all rating files   
            $delim1 = ".rating";
            $delim2 = ".txt";
            clearstatcache();
            $listRatingFiles = $this->list_files_in_array($conf['metadir'], $delim1, $params_array);
            // create a list of all page-files
            $listPages = $this->list_rec_files_in_array($conf['datadir'], $delim2, $params_array);
            

              // loop through pages
            foreach($listPages as &$page_filepath) {                            
      
                //read the content of the page file to be analyzed for rater items
                $body = '';
                $body = file_get_contents($page_filepath);
                
                // find all rater items on this page file
                $links = array();
                // define('LINK_PATTERN', "/\{\{.*\}\}/"); 
                define('LINK_PATTERN', "(rater>[^}]*)");
                // check for rater syntax on current page
                if( preg_match(LINK_PATTERN, $body) ) {
                   preg_match_all(LINK_PATTERN, $body, $links);
                }
                
                // loop through all rater items of this page
                if (count($links)>0) {
                   foreach($links as $wse) {
                      foreach($wse as $link) {
                          // strip präfix "rater>" = left 6 signs and last sign = ")"
                          $link = substr($link,6,-1);
                          // ignore all "type=stat" references
                          if (stripos($link,"type=stat") === false) {
                              // extract rater file name
                              $fileReference = explode("|",$link);
                              foreach ($fileReference as $param) {                            
                                  if(stripos($param,"id")!== false) { $id = substr($param,3); }
                                  elseif(stripos($param,"name=")!== false) { $name = substr($param,5); }
                                  elseif(stripos($param,"type=")!== false) { $type = substr($param,5); }
                              }
    
                              $cFlag = false;
                              // loop through rater list to find matching rater
                              foreach($listRatingFiles as $ratingFile) {
                                  // check if rater name is part of path
                                  if(stripos($ratingFile,$id.'_'.$name.'_'.$type)>0) {
                                      //extract page file name
                                      $p_filename = basename($page_filepath);
                                      
                                      //cut everything before pages/ from link
                                      $y_pos=strpos($page_filepath, "pages");
                                      $t1 = substr($page_filepath, $y_pos);
                                
                                      $t1 = substr(str_replace( ".txt" , "" , $t1 ) , 5, 9999);
                                      // turn it into wiki link without "pages"
                                      $t2 = str_replace("/", ":", $t1);
                                      $t2 = substr($t2, 1, strlen($t2));                                     
                                      $t1 = '<a class=wikilink1 href="'. DOKU_URL . "doku.php?id=" . $t2 . '" title="' . $t1 . '" rel="nofollow">' . $id.' '.$name . '</a>';                   
                        
                                      // differ between rate and vote
                                      if (stripos($ratingFile,'rate.rating')>0){
                                          $rate_counter = $rate_counter+1;
                                          // store page file and rater file link for output
                                          $found_ratings[] = array('item' => $t1 , 'file' => basename($ratingFile));
                                          
                                          $cFlag = true;
                                          break;
                                      }                     
                                      elseif (stripos($ratingFile,'vote.rating')>0){                      
                                          $vote_counter = $vote_counter+1;
                                          // store page file and rater file link for output
                                          $found_votings[] = $t1 . " : " . basename($ratingFile);
                                          $cFlag = true;
                                          break;
                                      }
                                  }                    
                              }
                              // link on page but rater file not existent due to no votes registerd so far
                              if($cFlag === false) {                      
                                  $mis_counter = $mis_counter+1;
                                  //extract page file name
                                  $p_filename = basename($page_filepath);
                                  
                                  //cut everything before pages/ from link
                                  $y_pos=strpos($page_filepath, "pages");
                                  $t1 = substr($page_filepath, $y_pos);
                            
                                  $t1 = substr(str_replace( ".txt" , "" , $t1 ) , 5, 9999);
                                  // turn it into wiki link without "pages"
                                  $t2 = str_replace("/", ":", $t1);
                                  $t2 = substr($t2, 1, strlen($t2));                                     
                                  $t1 = '<a class=wikilink1 href="'. DOKU_URL . "doku.php?id=" . $t2 . '" title="' . $t1 . '" rel="nofollow">' . $id.' '.$name . '</a>';                   
                                  // store page file where rater file not existent
                                  $found_nok[] = $t1 . " : " . basename($ratingFile);
                              }
                          }
                      }
                   }
                }
            }
            
            // calculate votes
            for ($a=0;$a<count($found_ratings)-1;$a++) {
                $rater_filename = $conf['metadir'].'/'.$found_ratings[$a]['file']; 
                if(is_file($rater_filename)) {
                    $tmp_array = $this->calc_rater_rating($rater_filename);
                    $rater_rating = $tmp_array[0][0];
                    if ($tmp_array[0][1] < 1) { $tmp_array[0][1] = '0'; }
                    if ($tmp_array[0][2] < 1) { $tmp_array[0][2] = '0'; }
                    if ($tmp_array[0][3] < 1) { $tmp_array[0][3] = '0'; }
                    if ($tmp_array[0][4] < 1) { $tmp_array[0][4] = '0'; }
                    if ($tmp_array[0][5] < 1) { $tmp_array[0][5] = '0'; }
                    if ($tmp_array[0][6] < 1) { $tmp_array[0][6] = '0'; }
                    if ($tmp_array[0][7] < 1) { $tmp_array[0][7] = '0'; }
                    $found_ratings[$a][] = array('value' => $rater_rating);
                    $rater_stars = $this->assign_star_image($rater_rating);
                    $found_ratings[$a][] = array('image' => $rater_stars);
                    $found_ratings[$a][] = array($tmp_array[0][1],$tmp_array[0][2],$tmp_array[0][3],$tmp_array[0][4],$tmp_array[0][5],$tmp_array[0][6]);  
                }
            }
            // sort array
            $found_ratings = $this->array_sort($found_ratings, 'value', SORT_DESC);
            
            
            // output statistic
            $ret = '<TABLE class="rating_stat_table"><form method="post" action="doku.php?id=' . $ID .'" >'.
                   '<TR><TH class="rating_stat_th">Item</TH><TH class="rating_stat_th">Value</TH><TH class="rating_stat_th">Details</TH></TR>';
            foreach($found_ratings as $findings) {
                  $dtls_id = uniqid((double)microtime()*1000000,1);
                  $alink_id++;
                  $ret_details ='<div class="rating__details">';
                  $ret_details .= '<img src="'.DOKU_BASE.'lib/plugins/rater/img/1star.gif?w=40&amp;" alt="1 Star" width="40" align="left" /> '.$findings[2][0].' visitor votes<BR>';              
                  $ret_details .= '<img src="'.DOKU_BASE.'lib/plugins/rater/img/2star.gif?w=40&amp; alt="2 Stars" width="40" align="left" /> '.$findings[2][1].' visitor votes<BR>';              
                  $ret_details .= '<img src="'.DOKU_BASE.'lib/plugins/rater/img/3star.gif?w=40&amp;" alt="3 Stars" width="40" align="left" /> '.$findings[2][2].' visitor votes<BR>';              
                  $ret_details .= '<img src="'.DOKU_BASE.'lib/plugins/rater/img/4star.gif?w=40&amp;" alt="4 Stars" width="40" align="left" /> '.$findings[2][3].' visitor votes<BR>';
                  $ret_details .= '<img src="'.DOKU_BASE.'lib/plugins/rater/img/5star.gif?w=40&amp;" alt="5 Stars" width="40" align="left" /> '.$findings[2][4].' visitor votes<BR>';                            
                  $ret_details .= '</div>';
                  $ret_script .= '<script type="text/javascript" language="JavaScript1.2">
                          var visible = false;
                          function hidden'.$alink_id.'() 
                          {   if (visible)
                              {   document.getElementById("details_'.$alink_id.'").style.display = "none";
                                  visible = false; }
                              else
                              {   document.getElementById("details_'.$alink_id.'").style.display = "block";
                                  visible = true; }
                          } 
                        </script>';
                $ret .= '<TR class="rating_stat_tr">'.$ret_script.
                           '<TD class="rating_stat_td_col1">'.$findings['item'].'</TD>'.
                           '<TD class="rating_stat_td_col2">'.$findings[0]['value'].'</TD>'.
                           '<TD class="rating_stat_td_col3">'.
                           '<img src="'.$findings[1]['image'].'?x='.$dtls_id.'" alt="'.$findings[0]['value'].' stars" />'.
                           '&nbsp; '.$findings[2][5].' votes <a href="#" onclick="hidden'.$alink_id.'()">(Details)</a></TD>'.
                        '</TR>'.
                        '<tr>'.
                           '<td></td><td></td><td style="display : none" id="details_'.$alink_id.'">'.$ret_details.'</td>'.
                        '</tr>';
            }
            $ret .= '</form></TABLE>';
      }
      // Render            
      $renderer->doc .= $ret;
    }
  }  
/******************************************************************************/
  function assign_star_image($rater_rating) {
                  // Assign star image
              if ($rater_rating <= 0  ){$rater_stars = DOKU_BASE."lib/plugins/rater/img/00star.gif";$rater_stars_txt="Not Rated";}
              if ($rater_rating >= 0.5){$rater_stars = DOKU_BASE."lib/plugins/rater/img/05star.gif";$rater_stars_txt="0.5";}
              if ($rater_rating >= 1  ){$rater_stars = DOKU_BASE."lib/plugins/rater/img/1star.gif";$rater_stars_txt="1";}
              if ($rater_rating >= 1.5){$rater_stars = DOKU_BASE."lib/plugins/rater/img/15star.gif";$rater_stars_txt="1.5";}
              if ($rater_rating >= 2  ){$rater_stars = DOKU_BASE."lib/plugins/rater/img/2star.gif";$rater_stars_txt="2";}
              if ($rater_rating >= 2.5){$rater_stars = DOKU_BASE."lib/plugins/rater/img/25star.gif";$rater_stars_txt="2.5";}
              if ($rater_rating >= 3  ){$rater_stars = DOKU_BASE."lib/plugins/rater/img/3star.gif";$rater_stars_txt="3";}
              if ($rater_rating >= 3.5){$rater_stars = DOKU_BASE."lib/plugins/rater/img/35star.gif";$rater_stars_txt="3.5";}
              if ($rater_rating >= 4  ){$rater_stars = DOKU_BASE."lib/plugins/rater/img/4star.gif";$rater_stars_txt="4";}
              if ($rater_rating >= 4.5){$rater_stars = DOKU_BASE."lib/plugins/rater/img/45star.gif";$rater_stars_txt="4.5";}
              if ($rater_rating >= 5  ){$rater_stars = DOKU_BASE."lib/plugins/rater/img/5star.gif";$rater_stars_txt="5";}
              return $rater_stars;
  }
/******************************************************************************/
  function array_sort($array, $on, $order)
  {
     $new_array = array();
     $sortable_array = array();
 
    if (count($array) > 0) {
         foreach ($array as $k => $v) {
             if (is_array($v)) {
                 foreach ($v as $k2 => $v2) {
                     if ($k2 == $on) {
                         $sortable_array[$k] = $v2;
                     }
                 }
             } else {
                 $sortable_array[$k] = $v;
             }
         }
 
        switch ($order) {
             case SORT_ASC:
                 asort($sortable_array);
             break;
             case SORT_DESC:
                 arsort($sortable_array);
             break;
         }
 
        foreach ($sortable_array as $k => $v) {
             $new_array[$k] = $array[$k];
         }
     }
    return $new_array;
  } 
/******************************************************************************/
  function calc_rater_rating($rater_filename) {
        $rater_end_of_line_char      = $this->getConf('eol_char');
        $rater_file=fopen($rater_filename,"r");
        $rater_str="";
        $rater_str = fread($rater_file, 1024*8);
        if($rater_str!=""){
          $rater_data=explode($rater_end_of_line_char,$rater_str);
          $rater_votes=count($rater_data)-1;
          $rater_sum=0;
        
          foreach($rater_data as $d){
              $d=explode("|",$d);
              $rater_sum+=$d[0];
        
              // collect votes per level to display the details
              if ($d[0] === '1'  ){$r1 = $r1 + 1;}
              if ($d[0] === '2'  ){$r2 = $r2 + 1;}
              if ($d[0] === '3'  ){$r3 = $r3 + 1;}
              if ($d[0] === '4'  ){$r4 = $r4 + 1;}
              if ($d[0] === '5'  ){$r5 = $r5 + 1;}
          }
          $rater_rating=number_format(($rater_sum/$rater_votes), 2, '.', '');
        }
        fclose($rater_file);
        $tmp_array[] = array($rater_rating,$r1,$r2,$r3,$r4,$r5,$rater_votes,$rater_sum);
        return $tmp_array;
    }
/******************************************************************************/
    // search given directory store all files into array 
    function list_files_in_array($dir, $delim) 
    { 
        $listDir = array(); 
        if($handler = opendir($dir)) { 
            while (FALSE !== ($sub = readdir($handler))) { 
                if ($sub !== "." && $sub !== "..") { 
                    if(is_file($dir."/".$sub)) {   
                        $x = strpos(basename($dir."/".$sub),$delim);                        
                        if(($x > 0)){
                            $listDir[] = $dir."/".$sub; 
                        }            
                      //if(DEBUG) echo sprintf("<p><b>%s</b></p>\n", $dir."/".$sub);
                    }
                } 
            }    
            closedir($handler); 
        } 
        return $listDir;    
    }
/******************************************************************************/
    // search given directory recursively and store all files into array 
    function list_rec_files_in_array($dir, $delim, $excludes) 
    { 
        $max_count_files = 10;
        $listDir = array(); 
        if($handler = opendir($dir)) { 
            while (FALSE !== ($sub = readdir($handler))) { 
                if ($sub !== "." && $sub !== "..") { 
                    if(is_file($dir."/".$sub)) {   
                        $x = strpos(basename($dir."/".$sub),".txt");                        
                        if(($delim === '.txt') && ($x > 0)){
                            $listDir[] = $dir."/".$sub; 
                          }            
                        elseif($delim === 'all') {
                            $listDir[] = $dir."/".$sub;
                        } 
                    }
                    elseif(is_dir($dir."/".$sub)) { 
                        $listDir[$sub] = $this->list_rec_files_in_array($dir."/".$sub, $delim,$excludes);
                    } 
                } 
            }    
            closedir($handler); 
        }
        $listDir = $this->array_flat($listDir);
        sort($listDir); 
        return $listDir;    
    }
/******************************************************************************/
    // flatten the hierarchical arry to store path + file at first "column"
    function array_flat($array) {   
        $out=array();
        foreach($array as $k=>$v){  
            if(is_array($array[$k]))  { $out=array_merge($out,$this->array_flat($array[$k])); }
            else  { $out[]=$v; }
        }     
        return $out;
    }
/******************************************************************************/
}
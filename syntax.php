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
    function getSort(){ return 167;}
    
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
        
        foreach($params as $param){            
            $splitparam = explode('=',$param);
            if ($splitparam[1] != '')
            {
                if ($splitparam[0]=='id')
                	{$data['rater_id'] = $splitparam[1];
                    /*continue;*/}
                if ($splitparam[0]=='name')
                	{$data['rater_name'] = $splitparam[1];
                    /*continue;*/}
                if ($splitparam[0]=='type')
                	{$data['rater_type'] = $splitparam[1];
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
        
        if ($mode == 'xhtml'){            
            $renderer->info['cache'] = false;     
            
          // User settings
          $rater_ip_voting_restriction = true; // restrict ip address voting (true or false)
          $rater_ip_vote_qty=1; // how many times an ip address can vote
          $rater_already_rated_msg="You have already rated this item. You were allowed ".$rater_ip_vote_qty." vote(s).";
          $rater_not_selected_msg="You have not selected a rating value.";
          $rater_thankyou_msg="Thankyou for voting.";
          $rater_generic_text="this item"; // generic item text
          $rater_end_of_line_char="\n"; // may want to change for different operating systems
          
          
          $rater_id = $data['rater_id'];
          $rater_name = $data['rater_name'];

          if(!isset($rater_id)) $rater_id=1;
          $rater_item_name = $data['rater_name'];
          if(!isset($rater_item_name)) $rater_item_name=$rater_generic_text;
          $rater_type = $data['rater_type'];
          if(!isset($rater_type)) $rater_type="stars";          
          
          // DO NOT MODIFY BELOW THIS LINE
          $rater_filename = metaFN('rater_'.$data['rater_id'].$data['rater_name'].$data['rater_type'], '.rating');
          $rater_rating=0;
          $rater_stars="";
          $rater_stars_txt="";
          $rater_rating=0;
          $rater_votes=0;
          $rater_msg="";

          // Rating action
          if(isset($_REQUEST["rate".$rater_id])){
           if(isset($_REQUEST["rating_".$rater_id])){
            while(list($key,$val)=each($_REQUEST["rating_".$rater_id])){
             $rater_rating=$val;
            }
            $rater_ip = getenv("REMOTE_ADDR"); 
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
              $rater_msg=$rater_thankyou_msg;
             }
            }else{
             fwrite($rater_file,$rater_rating."|".$rater_ip.$rater_end_of_line_char);
             $rater_msg=$rater_thankyou_msg;
            }
            fclose($rater_file);
           }else{
            $rater_msg=$rater_not_selected_msg;
           }
          }
          
          
          if ($rater_type=="rate") {          
              // Get current rating
              if(is_file($rater_filename)){
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
                }
                $rater_rating=number_format(($rater_sum/$rater_votes), 2, '.', '');
               }
               fclose($rater_file);
              }else{
               $rater_file=fopen($rater_filename,"w");
               fclose($rater_file);
              }

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
              
              
              // Output
              $ret .= '<TABLE class="hreview">';
              $ret .= '<form method="post" action="doku.php?id=' . $ID .'">';
              $ret .= '<TR><TD class="item">Rate '.$rater_item_name.'</TD></TR>';
              $ret .= '<TR><TD class="rating"><img src="'.$rater_stars.'?x='.uniqid((double)microtime()*1000000,1).'" alt="'.$rater_stars_txt.' stars" />&nbsp'.$rater_stars_txt.'</span> from <span class="reviewcount"> '.$rater_votes.' votes</TD></TR>.';
              $ret .= '<TR><TD>';
              $ret .= '<label for="rate1_'.$rater_id.'"><input type="radio" value="1" name="rating_'.$rater_id.'[]" id="rate1_'.$rater_id.'" />&nbsp</label>';
              $ret .= '<label for="rate2_'.$rater_id.'"><input type="radio" value="2" name="rating_'.$rater_id.'[]" id="rate2_'.$rater_id.'" />&nbsp</label>';
              $ret .= '<label for="rate3_'.$rater_id.'"><input type="radio" value="3" name="rating_'.$rater_id.'[]" id="rate3_'.$rater_id.'" />&nbsp</label>';
              $ret .= '<label for="rate4_'.$rater_id.'"><input type="radio" value="4" name="rating_'.$rater_id.'[]" id="rate4_'.$rater_id.'" />&nbsp</label>';
              $ret .= '<label for="rate5_'.$rater_id.'"><input type="radio" value="5" name="rating_'.$rater_id.'[]" id="rate5_'.$rater_id.'" />&nbsp</label>';
              $ret .= '<input type="hidden" name="rs_id" value="'.$rater_id.'" />';
              $ret .= '<input type="submit" name="rate'.$rater_id.'" value="Rate" /></TD></TR>';
              $ret .= '</div>';
              if($rater_msg!="") $ret .= "<div>".$rater_msg."</div>";
              $ret .= '</form>';
              $ret .= '</TABLE>';
          }
          
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
                       }
                       elseif ($d[0]==='2') {
                          $vote2 = $vote2 +1;
                       }
                    }   
                 }
                 else{
                    $vote1='0';
                    $vote2='0';
                 }
                 fclose($rater_file);
               }
              
              // Output
               
              $ret .= '<TABLE class="hreview">';
              $ret .= '<form name="'.$data['rater_id'].$data['rater_name'].$data['rater_type'].'" method="post" action="doku.php?id=' . $ID .'">';
                                  
              $ret .= '<TR><TD><a class="thumbup tup" href="doku.php?id='.$ID.'&do=rate_voteup&rater_id='.$rater_id.'&rater_name='.$rater_name.'" /></a>'.
                   '<span id="vote1_1" style="color:#5b8f22">('.$vote1.')&nbsp</span>'.
                   '<a class="thumbdown tdn" href="doku.php?id='.$ID.'&do=rate_votedown" /></a>'.
                   '<span id="vote1_2" style="color:#FF1822">('.$vote2.')</span></TD></TR>';
              if($rater_msg!="") $ret .= "<div>".$rater_msg."</div>";
              $ret .= '</form>';
              $ret .= '</TABLE>';              

                      
          }
        }
        
        // Render            
        $renderer->doc .= $ret;

    }
/******************************************************************************/
}
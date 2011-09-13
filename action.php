<?php
/******************************************************************************
**
**  action script related to IssueTracker
**  Action to display details of a selected issue
*/
/******************************************************************************
**  must run within Dokuwiki
**/
if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'action.php');

/******************************************************************************/
class action_plugin_rater extends DokuWiki_Action_Plugin {

    var $parameter = "";
 
  /**
   * return some info
   */
  function getInfo(){
    return array(
         'author' => 'Taggic',
         'email'  => 'Taggic@t-online.de',
         'date'   => '2011-09-13',
         'name'   => 'rater (action plugin component)',
         'desc'   => 'to store votes.',
         'url'    => 'http://www.dokuwiki.org/plugin:issuetracker',
         );
  }
/******************************************************************************
**  Register its handlers with the dokuwiki's event controller
*/
     function register(&$controller) {
         $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, '_handle_act', array());
         $controller->register_hook('TPL_ACT_UNKNOWN', 'BEFORE', $this, 'output', array());
     }

/******************************************************************************
**  Handle the action
*/
     function _handle_act(&$event, $param) {
         if ($event->data === 'rate_voteup') {
             $this->raterfile = $_GET['rater_file'];
             $this->rater_id = $_GET['rater_id'];
             $this->rater_name = $_GET['rater_name'];
             $this->vote = 1;         
         }
         elseif ($event->data === 'rate_votedown') {
             $this->raterfile = $_GET['rater_file'];
             $this->rater_id = $_GET['rater_id'];
             $this->rater_name = $_GET['rater_name'];
             $this->vote = 2;
         }
         else return;
         
         $event->preventDefault(); // https://www.dokuwiki.org/devel:events#event_object  
     }
/******************************************************************************
**  Generate output
*/
    function output(&$data) {
      global $ID;
      $rater_type = "vote";
      $rater_id = $this->rater_id;
      $rater_name = $this->rater_name;

          // User settings
          $rater_ip_voting_restriction = true; // restrict ip address voting (true or false)
          $rater_ip_vote_qty=1; // how many times an ip address can vote
          $rater_already_rated_msg="You have already rated this item. You were allowed ".$rater_ip_vote_qty." vote(s).";
          $rater_not_selected_msg="You have not selected a rating value.";
          $rater_thankyou_msg="Thankyou for voting.";
          $rater_generic_text="this item"; // generic item text
          $rater_end_of_line_char="\n"; // may want to change for different operating systems
      
      if (($data->data == 'rate_voteup') && ($this->vote == 1)) {
              $data->preventDefault();
              $rater_rating = 1;
      } 
      elseif (($data->data == 'rate_votedown') && ($this->vote == 2)) {
              $data->preventDefault();
              $rater_rating = 2;
      }

    //        save vote
            $rater_filename = metaFN('rater_'.$rater_id.$rater_name.$rater_type, '.rating');
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
      
      // reload original page
      $ret .= $rater_msg.'<br><a href="doku.php?id='.$ID.'" />back</a>';
      echo $ret;
      $renderer->doc .= $ret; 
    }
/******************************************************************************/
}
<?php
/**
 * Metadata for configuration manager plugin
 * Additions for the rater plugin
 *
 * @author   Taggic@t-online.de 
 */

$meta['voting_restriction'] = array('onoff');  // restrict ip address voting (true or false)
$meta['vote_qty']           = array('string'); // how many times an ip address can vote
$meta['already_rated_msg']  = array('string');
$meta['not_selected_msg']   = array('string');
$meta['thankyou_msg']       = array('string');
$meta['generic_text']       = array('string'); // generic item text
$meta['eol_char']           = array('string'); // may want to change for different operating systems
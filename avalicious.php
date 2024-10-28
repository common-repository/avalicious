<?php
/*
Plugin Name: Avalicious!
Plugin URI: https://github.com/alisinfinite/avalicious
Description: A plugin for integrating Dreamwidth, LiveJournal, and Tumblr icons into WordPress comments. Based on <a href="http://alltrees.org/Wordpress/#ALA">Also LJ Avatar</a>.
Version: 1.3.3
Author: Alis
Author URI: http://alis.me/

	yay for debugging:  mail( 'email', 'subject', print_r( get_defined_vars(), true ) );
*/
defined('ABSPATH') or die(':(');


//** DEFINE SOME CONSTANTS *******************************************//
if(!defined('AVDIR'))    { define( 'AVDIR', dirname(__FILE__) ); }
if(!defined('AVUPDIR'))  { define( 'AVUPDIR', AVDIR . '/danga-icons/' ); }
if(!defined('AVIMGDIR')) { define( 'AVIMGDIR', get_option( 'siteurl' ) .'/wp-content/plugins/avalicious/danga-icons/' ); }


//** INIT AND DEINIT **************************************************//
function avInit(){
  wp_schedule_event(time(), 'monthly', 'doAvPicCron');
}

function avDeInit(){
  wp_clear_scheduled_hook('doAvPicCron');
} 

//** GOGO FUNCTIONS **************************************************//
function avDangaIcon($avatar) {
  //global $comment;
  $_JOURNALS = array(
    'livejournal.com',
    'insanejournal.com',
    //'journalfen.net',
    'deadjournal.com',
    'dreamwidth.org',
    'tumblr.com',
  );
  $jurl = ''; $jname = '';
  
  // extract the height/width specified in the theme, if any, to use as size
  $size = (preg_match("/height='([0-9]*)'/", $avatar, $m) || preg_match('/height="([0-9]*)"/', $avatar, $m))
        ? $m[1]
        : 80;

  // get our user's url...
  $auth_url = get_comment_author_url();

  // if the url matches an LJ configuration, get our journal and login name
  // if we dont get this far, we're good to return
  foreach($_JOURNALS as $j) {
    if(preg_match( "/$j/", $auth_url))
      { $jurl = $j; }
  }

  if(!$jurl)
    { return $avatar; }
  
  // making this more compatible with tumblr and less with older configs of LJ/DW/etc. 
  //if( preg_match( "#http://([a-z0-9_-]*)\.$jurl/{0,1}~{0,1}([a-z0-9_-]{0,})/{0,1}([a-z0-9_-]{0,})#i", $auth_url, $m ) )
  //  { $jname = empty( $m[3] ) ? !empty( $m[2] ) ? $m[2] : $m[1] : $m[3]; }
	$jhttp = 'https';
  if(preg_match("#(https{0,1})://([a-z0-9_-]*)\.$jurl#i", $auth_url, $m))
    { $jhttp = $m[1]; $jname = $m[2]; }

  // okay, now we've got our journal and our username... let's go!
  $jname = strtolower($jname);

  // first, let's check our cache...
  if(file_exists( AVUPDIR . $jname .'.'. $jurl))
    { $avatar = '<img class="avatar" src="'. AVIMGDIR . $jname .'.'. $jurl .'" height="'. $size .'" width="'. $size .'" />'; }
  
  // if that doesn't work, download the pic and try again
  elseif($jpic = avGetUserpic($jname, $jurl, $jhttp))
    { $avatar = '<img class="avatar" src="'. $jpic .'" height="'. $size .'" width="'. $size .'" />'; }

  return $avatar;
}

// if the user parses as an LJ-clone user and does not have a pic cached, go get their default user icon (This code is by Alex Bishop)
function avGetUserpic($danga_user, $danga_journal_url, $http = 'https') {
  $url = ''; $service = 'danga';
  if($danga_journal_url == 'tumblr.com'){
    $url = "$http://$danga_user.$danga_journal_url/mobile";
    $service = 'tumblr';
  } elseif( $danga_journal_url == 'dreamwidth.org' ){
    $url = "$http://$danga_user.$danga_journal_url/icons";
  } else {
    $url = "$http://www.$danga_journal_url/allpics.bml?user=$danga_user";
  }

  // start curling!
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // return the data, don't echo it
  curl_setopt($ch, CURLOPT_URL, $url); // set our url
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1); // set our timeout; useful for when LJ is down...
  $allpics = curl_exec($ch); // go go gadget cURL!
  curl_close($ch);
  // end cURL
  
  // get our pic!
  if($service == 'danga'){
    preg_match('#https{0,1}://(www.|userpic.|[a-z]-userpic.|v.)'. $danga_journal_url .'/(userpic/){0,1}[0-9]{1,}/[0-9]{1,}#', $allpics, $pics);
  } else {
    preg_match('#https{0,1}://[0-9]{1,2}\.media\.tumblr\.com/avatar_[0-9a-z]{1,}_[0-9]{1,4}\.(png|pnj|gif|jpg|jpeg)#', $allpics, $pics);
  }

  // if we got something, let's cache it
  if(is_array($pics) && array_key_exists(0, $pics) && $pics[0]){
    $fp = fopen( AVUPDIR . $danga_user .'.'. $danga_journal_url, 'w');
    // start curling!
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $pics[0]); // set our url
    curl_setopt($ch, CURLOPT_FILE, $fp);     // set up upload location
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1); // set our timeout; useful for when LJ is down...
    curl_setopt ( $ch, CURLOPT_FOLLOWLOCATION, true); // follow redirects... useful for tumblrs with custom domains
    curl_exec($ch);                          // go go gadget cURL!
    curl_close($ch);
    // end cURL
    fclose($fp);
  } 
  if(file_exists(AVUPDIR . $danga_user .'.'. $danga_journal_url))
    { return AVIMGDIR . $danga_user .'.'. $danga_journal_url; }
  elseif(is_array($pics) && array_key_exists(0, $pics))
    { return $pics[0]; }
  else
    { return false; }
}


//** CRON ************************************************************//
// this is our wp-cron function to clean up old userpics; it trawls the
// AVUPDIR directory and deletes user icons older than 30 days
// (2,592,000 seconds)
function avPicCron() {
  $f = opendir(AVUPDIR);
  while(($file = readdir($f)) !== false){
    if($file != "." && $file != ".." && is_file(AVUPDIR . $file)){
      $fage = time() - filemtime(AVUPDIR . $file);
      if($fage > (60 * 60 * 24 * 30))
        { unlink(AVUPDIR . $file); }
    }
  }
  closedir($f);
}

//** HOOKS ***********************************************************//
register_activation_hook(plugin_basename(__FILE__), 'avInit');
register_deactivation_hook(plugin_basename(__FILE__), 'avDeInit');

add_filter('get_avatar', 'avDangaIcon', 9999);

add_action('doAvPicCron', 'avPicCron');

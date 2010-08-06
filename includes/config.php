<?php
/**
 * Main Configuration file and setup for the application
 *
 * The config file is required by all pages of the application and should be reference / included by all pages wanting to leverage the identity of the user or to make use of the framework session data. This file parses an ini file with actual config values and sets debug modes, sets up DB connections, templates etc. 
 * @author Ketan Majmudar <ket@twical.net>
 * @version 
 *
 * @package twical
 * @subpackage config
 **/

$configs = parse_ini_file('twical.local.ini', true);

// Setup debug level - needs expanding - refining
if(isset($configs['debug']['level'])) define('DEBUG_LEVEL',$configs['debug']['level']);

if (!defined(DEBUG_LEVEL)) {define('DEBUG_LEVEL',0);}
if (DEBUG_LEVEL > 0) {
	ini_set('display_errors', 1);
	error_reporting(E_ALL);
	include_once(dirname(__FILE__) . '/debug.php');
}

// System Paths
define('CLASSES_PATH',$configs['server']['domain_path'] . '/classes/' );

// DSN for MDB2 connections
$dsn = "mysqli://".$configs['twicaldb']['user'].":".$configs['twicaldb']['pass']."@".$configs['twicaldb']['host']."/".$configs['twicaldb']['db'];

// TwitterOauth Config
define('CONSUMER_KEY', $configs['twitteroauth']['consumer_key']);
define('CONSUMER_SECRET', $configs['twitteroauth']['consumer_secret']);
define('OAUTH_CALLBACK', $configs['twitteroauth']['oauth_callback']);
define('TWOAUTH_CLASS_PATH', $configs['server']['domain_path'] . $configs['twitteroauth']['path']);


// API KEYS

define('DEFAULT_BITLY_API_KEY', $configs['bitly']['apikey']);
define('DEFAULT_BITLY_LOGIN', $configs['bitly']['login']);
// Templates

define('TEMPLATE_PATH', $configs['server']['domain_path'] . $configs['template']['path']);
define('LANGUAGE_PATH', $configs['server']['domain_path'] . $configs['language']['path']. '/'. $configs['language']['default'] );
include_once(LANGUAGE_PATH.'/default.lng.php');
?>
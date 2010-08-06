<?php
$systemMessages = '<div id="systemMessageHeader">';
function queryTimeCheck($hoursBefore){
	
	switch ($hoursBefore) {
		
		case 24: // Checks for events in 24 hours time
		$dateCheck = 'DATE_ADD(CURDATE(), INTERVAL 1 DAY)';
		$timeCheck = "TIME_FORMAT(CURTIME(), '%H:%i')";
		break;
		
		case 1: // Checks for events in 1 hours time
		$dateCheck = 'CURDATE()';
		$timeCheck = "TIME_FORMAT(TIMESTAMPADD(HOUR,1,NOW()), '%H:%i')";
		break;
		
		case 0:
		$dateCheck = 'CURDATE()';
		$timeCheck = "TIME_FORMAT(CURTIME(), '%H:%i')";
		break;
		
		default:
		return false;
	}
	
	$sql = "SELECT 	e.twitter_username, 
					e.summary, 
					e.start_date, 
					e.start_time,
					e.location, 
					e.latitude, 
					e.longitude, 
					e.url, 
					p.twitter_token, 
					p.twitter_secret 
					FROM event e 
					INNER JOIN person p 
					ON e.twitter_username = p.account_name 
					WHERE e.start_date =  $dateCheck
					AND e.start_time = $timeCheck";
	return $sql;
	
}
function stringDump($value){
	echo '<pre>
	**** START DUMP ****
	';
	print_r($value);
	echo '
	**** END DUMP ****
	</pre>';
}
function sendTweetByUser($event,$alertPeriod){
require_once(TWOAUTH_CLASS_PATH .'/twitteroauth.php');
$systemMessages = '<div id="systemMessageHeader">';
stringDump($event);
// Build Tweet message up	

if (isset($event['url'])){
	$url = "http://api.bit.ly/v3/shorten?login=".DEFAULT_BITLY_LOGIN."&apiKey=".DEFAULT_BITLY_API_KEY."&longUrl=".urlencode($event['url'])."&format=txt&history=1";

	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	$surl = curl_exec($curl);
	$last_response_code = curl_getinfo($curl,CURLINFO_HTTP_CODE);
	curl_close($curl);
}

switch ($alertPeriod) {
	case 24:
	$tPeriod = 'in 24 hours';
	break;
	
	case 1:
	$tPeriod = 'in 1 hour';
	break;
	
	case 0;
	$tPeriod = 'now';
	break;
}


	if (isset($event['summary'])) {
		$tweet = $event['summary'] . ' ';
	} else {
		$tweet = 'Event ';
	}
	
	
$tweet .= $tPeriod;
	if (isset($event['location'])) {
		// Reverse Geo Lookup if no geo data attached to ics file 
		$tweet .= ' at ' . substr($event['location'], 0, 20);
		if (isset($event['latitude']) && isset($event['longitude'])) {
			$parameters = array('lat' => $event['latitude'], 'long' => $event['longitude']);
		}
	}
	
	if (isset($surl)) {
		$tweet .= ', see ' . $surl;
	}

$tweet .= ' #twical';

stringDump($tweet);

/* Create a TwitterOauth object with consumer/user tokens. */
$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $event['twitter_token'], $event['twitter_secret']);

/* If method is set change API call made. Test is called by default. */

$content = $connection->get('account/rate_limit_status');
$systemMessages .= '<span class="notice">Current API hits remaining: ' . $content->remaining_hits . '</span>';
$systemMessages .= '<span class="notice">Number of tweets to send out: ' . count($event) .'</span>';

$parameters['status'] = $tweet;
$content = $connection->post('statuses/update', $parameters);
stringDump($content);
if (isset($content->{'error'})) {
	$systemMessages .= '<p class="failureMessage">' .$content->{'error'} . '</p>' ;
} else {
	$systemMessages .= '<p class="notice"> Status ID:' . 
		$content->{'id'} . 
		' <br/> Geo Enabled: ' .
		$content->{'user'}->{'geo_enabled'}. 
		'<br /> Text Sent'.$content->{'text'} .
		'</p>';
		}
	return $systemMessages;
}

include_once(dirname(__FILE__) . '/includes/config.php');
session_start();

require_once ('MDB2.php');
$conn = MDB2::connect ($dsn);

if (PEAR::isError ($conn)){
	die ("MDB2 Error - Cannot connect: " . $conn->getUserInfo () . "\n");
}

define('PAGE_TITLE','twiCal Maintenance Cycle');

// 24 hours
$sql = queryTimeCheck(24);				
$results = $conn->query($sql);
while($resultset = $results->fetchRow(MDB2_FETCHMODE_ASSOC)) {
	// if results are positive - it needs to be tweeted out by that user.
	$systemMessages .= sendTweetByUser($resultset,24);
}

// 1 hour
$sql = queryTimeCheck(1);		
$results = $conn->query($sql);
while($resultset = $results->fetchRow(MDB2_FETCHMODE_ASSOC)) {
	// if results are positive - it needs to be tweeted out by that user.
	$systemMessages .= sendTweetByUser($resultset,1);
}

// On event
$sql = queryTimeCheck(0);		
$results = $conn->query($sql);
while($resultset = $results->fetchRow(MDB2_FETCHMODE_ASSOC)) {
	// if results are positive - it needs to be tweeted out by that user.
	$systemMessages .= sendTweetByUser($resultset,0);
}

$systemMessages .= '</div>';
include_once(TEMPLATE_PATH . '/generic-header.tpl.php'); 
?>
</body>
</html>
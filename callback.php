<?php
/**
 * @file
 * Take the user when they return from Twitter. Get access tokens.
 * Verify credentials and redirect to based on response from Twitter.
 */

include_once(dirname(__FILE__) . '/includes/config.php');
session_start();
require_once(TWOAUTH_CLASS_PATH .'/twitteroauth.php');
$vevent_string = '';
/* If the oauth_token is old redirect to the connect page. */
if (!isset($_REQUEST['oauth_token']) && $_SESSION['oauth_token'] !== $_REQUEST['oauth_token']) {
  $_SESSION['oauth_status'] = 'oldtoken';
  // TODO  Clear sessions should also clear the DB tokens.
  header('Location: ./clearsessions.php');
}

/* Create TwitteroAuth object with app key/secret and token key/secret from default phase */
$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);

/* Request access tokens from twitter */
$access_token = $connection->getAccessToken($_REQUEST['oauth_verifier']);

/* Save the access tokens. Normally these would be saved in a database for future use. */

require_once ('MDB2.php');
$conn = MDB2::connect ($dsn);
if (PEAR::isError ($conn)){
	die ("MDB2 Error - Cannot connect: " . $conn->getUserInfo () . "\n");
}

$sql = "SELECT account_name FROM person WHERE account_name = '".$access_token['screen_name']."' AND twitter_userid = " . $access_token['user_id'] . " AND twitter_token = '".$access_token['oauth_token']."'";

$result_chk = $conn->query($sql);
$results = $result_chk->fetchRow();
if (PEAR::isError($results)) {
								die ("Execute Prepared Statement Error: " . $results->getUserInfo() . "\r\r");
							} else {
								$vevent_string .= '<span class="successMessage">Your twitter account is authorised</span>';
							}
							
// if user name does not exist in DB add it to the db here
if (!$results) {

$sql = "INSERT INTO person (twitter_token, twitter_secret, twitter_userid, account_name, created_at)";
$sql .= " VALUES (?,?,?,?, NOW())";
//$sql .= " VALUES ('".$access_token['oauth_token']."','".$access_token['oauth_token_secret']."',".$access_token['user_id'].",'".$access_token['screen_name']."')";
$sqlTypes = array('text','text','integer','text');				
$statement = $conn->prepare($sql, $sqlTypes);

$sqlArray = array (	$access_token['oauth_token'],
					$access_token['oauth_token_secret'],
					$access_token['user_id'],
					$access_token['screen_name']);

$result_chk = $statement->execute($sqlArray);
if (PEAR::isError($result_chk)) {
								die ("Execute Prepared Statement Error: " . $result_chk->getUserInfo() . "\r\r");
							} else {
								$vevent_string .= '<span class="successMessage">Your twitter account is authorised</span>';
							}

}

$_SESSION['access_token'] = $access_token;

/* Remove no longer needed request tokens */
unset($_SESSION['oauth_token']);
unset($_SESSION['oauth_token_secret']);

/* If HTTP response is 200 continue otherwise send to connect page to retry */
if (200 == $connection->http_code) {
  /* The user has been verified and the access tokens can be saved for future use */
  $_SESSION['status'] = 'verified';

// Check if the user exists in the DB - if not update the DB table with current session values.


							
							
/* Get user access tokens out of the session. */
$access_token = $_SESSION['access_token'];

							
/* Create a TwitterOauth object with consumer/user tokens. */
$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $access_token['oauth_token'], $access_token['oauth_token_secret']);

$user = $connection->get('account/verify_credentials');

$_SESSION['twittername'] = $user->screen_name;
$_SESSION['profile_image_url'] = $user->profile_image_url;
$_SESSION['geo_enabled'] = $user->geo_enabled;

// Set all session tokens with DB data.

  header('Location: ./dashboard.php');
} else {
  /* Save HTTP status for error dialog on connnect page.*/
  header('Location: ./clearsessions.php');
}

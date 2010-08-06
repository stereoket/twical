<?php
/**
 * Dashboard page to upload feeds (ics files)
 *
 * This handler file manages the ics file uploads and interacts with the CalUpload class
 * @author Ketan Majmudar <ket@twical.net>
 *
 * @package twical
 * @subpackage calendar
 **/

include_once(dirname(__FILE__) . '/includes/config.php');
session_start();
require_once(TWOAUTH_CLASS_PATH .'/twitteroauth.php');

// check that the user is authenticated.
/* If access tokens are not available redirect to connect page. -- this should be quite an aggressive check and be in an includes to be called for verification when needed */
if (empty($_SESSION['access_token']) || empty($_SESSION['access_token']['oauth_token']) || empty($_SESSION['access_token']['oauth_token_secret'])) {
    header('Location: ./clearsessions.php');
}

// DASHBOARD CONTENT
// FILE UPLOAD CHECK
$systemMessages = '<div id="systemMessageHeader">';

	if (isset($_REQUEST['submit']) &&
	 	$_REQUEST['submit'] == 'add' ) {
	
		if ($_FILES['upload_ics']['type'] != 'text/calendar' && $_FILES['upload_ics']['error'] == 0) 
			$systemMessages .=  '<p class="failureMessage">This is not a calendar file</p>';
	
			$calParams['fileDirectory'] = $configs['server']['domain_path']  .'/uploads/';
			$calParams['newFileName'] = $_SESSION['twittername'] . '-' . $_FILES['upload_ics']['name'];
		
		if (move_uploaded_file($_FILES['upload_ics']['tmp_name'], $calParams['fileDirectory'] .
		 $calParams['newFileName'])) {
				$systemMessages .= '<p class="successMessage">File Uploaded</p>';
				$systemMessages .= '<span class="notice">' .
		 					$_FILES['upload_ics']['name'] .
		 					' <em>('.round(($_FILES['upload_ics']['size'] / 1024),2) .
							' KB)</em></span>';
		
				} elseif(!isset($_REQUEST['calURL'])) {
							$systemMessages .=  '<p class="failureMessage">Problem saving calendar file</p>';
							$err = 1;
				} else {
							$calParams['webURL'] = $_REQUEST['calURL'];
							$systemMessages .= '<span class="notice">URL detected' .
		 					'<br/>'.$_REQUEST['calURL'].'</em></span>';
		}


//	

//	require_once(dirname(__FILE__) .'/includes/pear-debug.php');	
// TODO - create a file upload page to manage this, not in the dashboard	
		require_once(CLASSES_PATH .'cal/iCalUploader.php');		
		$v = new CalImporter($dsn);
	// check needs to be made if url
	/* start parse of local file */
		$v->parseiCalImport($calParams);
		$vevent_string = $v->vevent_string;
	
} 
define('PAGE_TITLE','twiCal Dashboard');
include_once(TEMPLATE_PATH . '/generic-header.tpl.php'); 
?>
<div id="formUpload">
<h3>Add a new Calendar URL</h3>
<p>[<a href="./">Manage Feeds</a>]</p>

<form method="POST" action="/dashboard.php" ENCTYPE="multipart/form-data">
	<p>Please select a file or URL to upload:</p>
	<label for="upload_ics">.ics calendar file</label>
	<input type="file" name="upload_ics" size="30" /><br />
	<label for="calURL">calendar URL</label>
	<input type="text" name="calURL" size="40" /><br />
	<div class="formActionButtons"><input type="submit" name="submit" value="add" />
	<input type="reset" value="clear" /></div>
</form>
</div>

<?php 
// Calendar event text if any
if (isset($vevent_string)) echo $vevent_string; 

?>
</body>
</html>
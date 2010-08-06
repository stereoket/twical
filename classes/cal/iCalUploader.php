<?php
/**
 * CalUploader class manages all user cal upload and management functions  
 *
 * Peforms timezone lookups and calculations - stores all relevant offsets in the local database
 * @author Ketan Majmudar <ket@twical.net>
 * @version 
 * @link 
 * @package twical
 * @subpackage calendar
 **/


require_once('iCalcreator.class.php');

/**
 * Extends the vcalendar class to add intemediary functions in storing, exploding and analysing cal data
 *
 * @package twical
 * @author Ketan Majmudar
 **/

class CalImporter extends vcalendar
{
	public $conn;
	public $dsn;
	public $statement;
	public $debugString;
	private $sqlCalInsert;
	private $sqlCalStatement;
	public $vevent_string;
	public $event_lat;
	public $event_long;
	public $systemMessage;
	
	public function __construct($dsn)
	{

		require_once ('MDB2.php');
		$this->dsn = $dsn;
		$this->conn = MDB2::connect($this->dsn);
		if (PEAR::isError ($this->conn)){
			die ("MDB2 Error - Cannot connect: " . $this->conn->getUserInfo() . "\n");
		}
		$this->conn->setFetchMode(MDB2_FETCHMODE_ASSOC);	
		$this->conn->setOption('portability', MDB2_PORTABILITY_ALL ^ MDB2_PORTABILITY_EMPTY_TO_NULL);
		
	}
	
	public function stringDump($value)
	{	
		print_r($value);
		echo "\n";
	}
	
	public function stringDumpStart()
	{
		echo '<pre class="debug">
		**** START DUMP ****
		';
	}
	
	public function stringDumpEnd()
	{
		echo '
		**** END DUMP ****
		</pre>';
	}

/**
  * Setup a prepared SQL statement using Pear::MDB2 
  *
  * The twitter username is required in the session data, but the rest of the query is prepared for execution 
  * @return $this->sqlCalStatement
  * @author Ketan Majmudar
  **/
	private function setupSQLCalInsert()
	{
// Build MDB2 prepared statement for multiple insert commands
		$this->sqlCalInsert = "INSERT INTO event";
		$this->sqlCalInsert .= 	" (	twitter_username,
								summary,
								start_date,
								start_time,
								end_date,
								end_time,
								location,
								description,
								created_at,
								latitude,
								longitude,
								timezoneid,
								timezone_offset)";			
		$this->sqlCalInsert .= " VALUES ( '" .$_SESSION['twittername']."', ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?)";
// Set data types to help prevent SQL injections and integrity of the data
// TODO the data types are causing an error. $statement = $conn->prepare($sql, $sqlTypes, MDB2_PREPARE_MANIP);
		$sqlTypes = array ('text','text','date','time','date','time','text','text','timestamp','text','text','text','int');
		return $this->conn->prepare($this->sqlCalInsert, $sqlTypes);	
	}
	
	public function getTimestamp($dateObj, $tzid)
	{
		if (PHP_VERSION >= 5.3) {
			$timestamp =  $dateObj->getTimestamp();
		} else {
// PHP version < 5.3 can not get timezone using date functions, so we manually set it with mktime here
			date_default_timezone_set($tzid);
			$timestamp = mktime($dateObj->format('H'),$dateObj->format('i'),$dateObj->format('s'),$dateObj->format('n'),$dateObj->format('j'),$dateObj->format('Y'));
		}
		// create timestamp version
		return $timestamp;
		
	}
	public function parseiCalImport($params)
	{
		
		$this->sqlCalStatement = $this->setupSQLCalInsert();
		
		if (!isset($params['webURL']))
		{
		$this->setConfig( 'directory', $params['fileDirectory'] ); 
		$this->setConfig( 'filename', $params['newFileName'] );
			} else {
		$this->setConfig( 'url', $params['webURL'] );
		}
		$this->parse();
		
		$this->vevent_string = '';		
		foreach($this->components as $component => $info)
		{

		// TODO check if the same event for the same person has been entered into the db - if so - prompt for the user to overwrite this data
		// TODO a check for events in the past should be run so that they can be ignored from being imported into the database. Controls ?

			if ($comp = $this->getComponent("VEVENT"))
			{

				$summary_array = $comp->getProperty("summary", 1, TRUE);
				$this->vevent_string .=  '<div class="vevent"><p class="event">Event #' .
										($component + 1) .
										': ' . 
										$summary_array['value'] . 
										'</p>';

				$dtstart_array = $comp->getProperty("dtstart", 1, TRUE);
				$dtstart = $dtstart_array["value"];
				$startDateTime = "{$dtstart["year"]}-{$dtstart["month"]}-{$dtstart["day"]} " . 
									"{$dtstart["hour"]}:{$dtstart["min"]}:{$dtstart["sec"]}";
				
				if (!isset($dtstart_array['params']['TZID']))
				{
					$timezoneid = date_default_timezone_get();
				} 
					else 
					{
						$timezoneid = $dtstart_array['params']['TZID'];
					}
				
				$tzObj = new DateTimeZone($timezoneid);
				$startDateObj = new DateTime($startDateTime , $tzObj);
				$currentDateObj = new DateTime('NOW');
				
				if ($startDateObj < $currentDateObj)
				{
					$this->vevent_string .= '<span class="failureMessage">Would not import entry ' . 
											($component + 1) . 
											' As it is in the past</span>';
					continue;
				}
				
				
				$offsetSec = $startDateObj->getOffset();
				$offsetHour = ($offsetSec/60)/60;
// TODO timestamp retreival should be its own method to work out php version and use appropriate function.
				$ts = $this->getTimestamp($startDateObj,$timezoneid);
				$startDate = gmdate("Y-m-d", $ts);
				$startTime = gmdate("H:i:s", $ts);
				$dtend_array = $comp->getProperty("dtend", 1, TRUE);
				$dtend = $dtend_array["value"];
				$endDateTime = "{$dtend["year"]}-{$dtend["month"]}-{$dtend["day"]}" . ' ' .
								"{$dtend["hour"]}:{$dtend["min"]}:{$dtend["sec"]}";
				try 
				{
					$endDateObj = new DateTime($endDateTime , $tzObj);
				} catch (Exception $e) {
					echo $e->getMessage();
					$this->stringDumpStart();
					$this->stringDump($dtend_array);
					$this->stringDump($endDateTime);
					$this->stringDumpEnd();
					exit (1);
				}
				$ts = $this->getTimestamp($endDateObj,$timezoneid);
				$endDate = gmdate("Y-m-d", $ts);
				$endTime = gmdate("H:i:s", $ts);
	

				$this->vevent_string .=  '<span class="startTime">Starts: ' .
											$startDate .
											" @ " .
											$startTime .
											"</span><br />\n";

				$this->vevent_string .= '<span class="endTime">Ends: ' .
											$endDate . 
											" @ " . 
											$endTime . 
											"</span><br />\n";

				$location_array = $comp->getProperty("location", 1, TRUE);
				$this->paresGeoData($location_array["value"]);
				$description_array = $comp->getProperty("description", 1, TRUE);
				$this->vevent_string .=  '<p class="description">Description: '. $description_array["value"]. "</p>\n</div>";

		// Put values into an array for the prepared query statment (MDB2)
				$veventDataArray = 	array(	$summary_array["value"],
											$startDate,
											$startTime,
											$endDate,
											$endTime,
											$location_array["value"],
											$description_array["value"],
											$this->event_lat,
											$this->event_long,
											$timezoneid,
											$offsetSec
											);							
						$result_chk = $this->sqlCalStatement->execute($veventDataArray);
						if (PEAR::isError($result_chk)) 
						{
								die ("Execute Prepared Statement Error: " . $result_chk->getUserInfo() . "\r\r");
					    	} else {
									$this->vevent_string .= '<span class="successMessage">This event has been successfully added to the database</span>';
						}
			}
		}
		return $this->vevent_string;
	}
	
	public function paresGeoData($locationString)
	{
		if (isset($locationString)) 
		{
			$this->vevent_string .=  '<p class="location">Location: ' . $locationString . "</p>\n";
// create geo reference from Location field:
			$queryUrl = "http://query.yahooapis.com/v1/public/yql?q=select%20country,centroid%20from%20geo.places%20where%20text%3D%22".urlencode($locationString)."%22&diagnostics=false&format=json";
			$curl = curl_init($queryUrl);
			curl_setopt($curl, CURLOPT_HEADER, false);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			$json = curl_exec($curl);
			$last_response_code = curl_getinfo($curl,CURLINFO_HTTP_CODE);
			curl_close($curl);
			if ($last_response_code == 200)
			{
				$yqlData = json_decode($json);
				if (isset($yqlData->{'query'}->{'results'}->{'place'}->{'centroid'})) 
				{
					$this->event_lat = $yqlData->{'query'}->{'results'}->{'place'}->{'centroid'}->{'latitude'};	
					$this->event_long = $yqlData->{'query'}->{'results'}->{'place'}->{'centroid'}->{'longitude'};	
				} 
				elseif($yqlData->{'query'}->{'results'}) 
					{
// TODO Get first result set from group of results. Maybe Ajax or data write internally required here
						$this->event_lat = $yqlData->{'query'}->{'results'}->{'place'}[0]->{'centroid'}->{'latitude'};	
						$this->event_long= $yqlData->{'query'}->{'results'}->{'place'}[0]->{'centroid'}->{'longitude'};
					} 
					else 
						{
							$this->vevent_string .= '<span class="failureMessage">Could not get lat/long Location info - an error occurred.</span>';
						}
			} 
		}

			if (!isset($this->event_lat) || !isset($this->event_long )) 
			{
				$this->event_lat = null;
				$this->event_long = null;
			}
			
	
	}
	
	public function importIntoDB()
	{
		
	}
	
	public function parseFile()
	{
		
	}
	
	public function parseURL()
	{
		
	}
	
	public function displayCalData()
	{
		
	}
	
	public function editGeoData()
	{
		
	}
}
?>
<?php

if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
  throw new \Exception('please run "composer require google/apiclient:~2.0" in "' . __DIR__ .'"');
}

require_once __DIR__ . '/vendor/autoload.php';

if (isset($_GET['q'])) {

  // Set $DEVELOPER_KEY to the "API key" value
  $DEVELOPER_KEY = 'AIzaSyAsmKbjsSAXARfIZ9XO0RmvU4iLMnU3dCc';

  $client = new Google_Client();
  $client->setDeveloperKey($DEVELOPER_KEY);

  // Define an object that will be used to make all API requests.
  $youtube = new Google_Service_YouTube($client);

  try {

    // Call the search.list method to retrieve results from the YouTube API
    $maxResults = 50; // Max value as defined by the YouTube API
    if (isset($_GET['channelId'])) {
       $searchResponse = $youtube->search->listSearch('id,snippet', array('q' => $_GET['q'], 'channelId' => $_GET['channelId'], 'maxResults' => $maxResults));
    } else {
       $searchResponse = $youtube->search->listSearch('id,snippet', array('q' => $_GET['q'], 'maxResults' => $maxResults));
    }

    //$query = $_GET['q'];

    // Set up the database connection
    $connection = mysqli_connect('localhost','devuser','fnord12345','mydb');
    if ($connection->connect_errno) {
        error_log("Database connection failed: %s\n", $connection->connect_error);
        exit();
    }
    mysqli_set_charset($connection, "ISO-8859-1"); // In order to handle exotic characters in titles

    // Write the results into the database
    foreach ($searchResponse['items'] as $searchResult) {
      switch ($searchResult['id']['kind']) {
        case 'youtube#video':
 
          /*
           *  Add video to database if not exists
           */

          // Set up variables
          $videoId = $searchResult['id']['videoId'];
          $videoTitle = mysqli_real_escape_string($connection, $searchResult['snippet']['title']);
          $videoPublishedAt = $searchResult['snippet']['publishedAt'];

          // Sanitise title even further by converting from UTF-8 encoding to ISO-8859-1
          $videoTitle = iconv("UTF-8", "ISO-8859-1//TRANSLIT", $videoTitle);

          // Limit the length of the title to 45 charaters to follow the MySQL schema
          $videoTitle = substr($videoTitle, 0, 45);

          // Create a unique ID based on CRC32 encoding and avoid overrunning the INT 11 max value
          $videoIdCRC32 = crc32($videoId) % 2147483647;

          // Audit log
          $auditMessage = "\n\nYouTube API: Processing started " . date('m/d/Y h:i:s a', time()) . "\n";
          error_log($auditMessage, 3, "application.log");
          $auditMessage = "YouTube API: Video located (ID: " . $videoId . " | Title: '" . $videoTitle . "')\n";
          error_log($auditMessage, 3, "application.log");

          // Default to 200 unless there is a problem
          $httpResponse = 200;

          // Check if the video exists in the database
          $videoQuery = "SELECT id FROM videos WHERE id = " . $videoIdCRC32 . "";
          if ($result = $connection->query($videoQuery)) {

             //error_log($videoQuery); // DEBUG

	     if ($result->num_rows == 0) {

	     	// Add to the database
                $dateTime = DateTime::createFromFormat('Y-m-d\TH:i:s\.\0\0\0\Z', $videoPublishedAt); // Example: 2018-02-15T15:26:11.000Z
                $dateTimeForDb = $dateTime->format('Y-m-d H:i:s');

                $videoInsertQuery = "INSERT INTO videos(id, title, date) VALUES (".$videoIdCRC32.",'".$videoTitle."','".$dateTimeForDb."')";

		//error_log($videoInsertQuery . "\n"); // DEBUG                     

		if ($insertResult = mysqli_query($connection, $videoInsertQuery)) {
		   // Success
                   $auditMessage = "MySQL: Video added ok (ID: " . $videoId . ")\n";
                } else {
                   // Failure
                   $errorMessage = "Video insert failed: " . mysqli_error($connection) . "\n";
                   error_log($errorMessage);
                   $httpResponse = 500;
                 }
             } else {
                // Already exists
                $auditMessage = "MySQL: Video already exists (ID: " . $videoId . ")\n";
             }

             // Event audit log
             error_log($auditMessage, 3, "application.log");
 
             $result->close();
          }

          /*
           * Add channel to database if not exists
           */

          // Set up variables
          $channelId = $searchResult['snippet']['channelId'];
          $channelTitle = mysqli_real_escape_string($connection, $searchResult['snippet']['channelTitle']);

          // Sanitise title even further by converting from UTF-8 encoding to ISO-8859-1
          $channelTitle = iconv("UTF-8", "ISO-8859-1//TRANSLIT", $channelTitle);

          // Limit the length of the title to 45 charaters to follow the MySQL schema
          $channelTitle = substr($channelTitle, 0, 45);

          // Create a unique ID based on CRC32 encoding and avoid overrunning the INT 11 max value
          $channelIdCRC32 = crc32($channelId) % 2147483647;

          // Check if the channel exists in the db
          $channelQuery = "SELECT id FROM channels WHERE id = " . $channelIdCRC32 . "";
          if ($result = $connection->query($channelQuery)) {

	     //error_log($channelQuery); // DEBUG

	     if ($result->num_rows == 0) {

	     	// Add to the database
                $channelInsertQuery = "INSERT INTO channels(id, channel_name) VALUES (".$channelIdCRC32.",'".$channelTitle."')";

		//error_log($channelInsertQuery . "\n"); // DEBUG
 
		if ($insertResult = mysqli_query($connection, $channelInsertQuery)) {
		   // Success
                   $auditMessage = "MySQL: Channel added ok (ID: " . $channelId . ")\n";
                } else {
                   // Failure
                   $errorMessage = "Channel insert failed: " . mysqli_error($connection) . "\n";
                   error_log($errorMessage);
                   $httpResponse = 500;
                }
             } else {
                // Already exists
                $auditMessage = "MySQL: Channel already exists (ID: " . $channelId . ")\n";
             }

             // Event audit log
             error_log($auditMessage, 3, "application.log");

             $result->close();
          }

          // HTTP response
          $response=array();
          http_response_code($httpResponse);
          header('Content-Type: application/json');
          echo json_encode($response);

          break;
        case 'youtube#channel':

          break;
        case 'youtube#playlist':

          break;
      }
    }

  } catch (Google_Service_Exception $e) {
    $serviceError = "A service error occurred: %s" . htmlspecialchars($e->getMessage()) . "\n";
    error_log($serviceError);
  } catch (Google_Exception $e) {
    $serviceError = "A client error occurred: %s" .  htmlspecialchars($e->getMessage()) . "\n";
    error_log($serviceError);
  }
}

?>


<?php

// Require GuzzleHttp via Composer
require 'vendor/autoload.php';

// Open file with search queries
echo "Opening 'search_filter' file\n\n";
$filecontents = file_get_contents("search_filter");
$queries = explode("\n", $filecontents);

// Channel IDs
$channelIdGCN = "UCuTaETsuCOkJ0H_GAztWt0Q"; // Global Cycling Network
$channelIdGMBN = "UC_A--fhX5gea0i4UtpD99Gg"; // Global Mountain Bike Network

if ($filecontents) {

    // Set up the database connection
    $connection = mysqli_connect('localhost','devuser','fnord12345','mydb');
    if ($connection->connect_errno) {
        error_log("Database connection failed: %s\n", $connection->connect_error);
        exit();
    }
    mysqli_set_charset($connection, "ISO-8859-1"); // In order to handle exotic characters in titles

    // Clear the video table
    $deleteQuery = "DELETE FROM videos";
    if ($result = $connection->query($deleteQuery)) {
       echo "Cleared the 'video' database table\n";
    } else {
       error_log("Video database table clearing failed\n");
    }

    // Clear the channel table
    $deleteQuery = "DELETE FROM channels";
    if ($result = $connection->query($deleteQuery)) {
       echo "Cleared the 'channel' database table\n";
    } else {
       error_log("Channel database table clearing failed\n");
    }

    echo "\n";

    // Iterate line by line
    foreach ($queries as $query) {

    	  /*
           * Call API focusing on the GCN channel
           */

          echo "Calling YouTube API searching Global Cycling Network channel for '" . $query . "'\n";
          $apiurl = 'http://dev.jwrnr.co/api/youtube.php?q=' . urlencode(trim($query)) . '&channelId=' . $channelIdGCN;
          // TODO: API URL to be cleaned up in .htaccess via ReWrite rules

          // Audit log
          $auditMessage = "\n\nCalling API (GCN channel): " . $apiurl . " at " . date('m/d/Y h:i:s a', time()) . "\n";
          error_log($auditMessage, 3, "application.log");

          // REST API call
	  $client = new GuzzleHttp\Client();
          try {
  	  	$response = $client->get($apiurl);
   	        echo "Internal API HTTP response: " . $response->getStatusCode() . "\n\n";
          }
	  catch (Exception $e) {
                echo "Error in HTTP request\n\n";
          }

    	  /*
           * Call API focusing on the GMBN channel
           */

          echo "Calling YouTube API searching Global Mountain Bike Network channel for '" . $query . "'\n";
          $apiurl = 'http://dev.jwrnr.co/api/youtube.php?q=' . urlencode(trim($query)) . '&channelId=' . $channelIdGMBN;
          // TODO: API URL to be cleaned up in .htaccess via ReWrite rules

          // Audit log
          $auditMessage = "\n\nCalling API (GCN channel): " . $apiurl . " at " . date('m/d/Y h:i:s a', time()) . "\n";
          error_log($auditMessage, 3, "application.log");

          // REST API call
	  $client = new GuzzleHttp\Client();
          try {
  	  	$response = $client->get($apiurl);
    	        echo "Internal API HTTP response: " . $response->getStatusCode() . "\n\n";
          }
	  catch (Exception $e) {
                echo "Error in HTTP request\n\n";
                // TODO: Catch 500 and other more specific error codes
          }

    }

    echo "Done";

} else {

    error_log("File not found");

} 

?>
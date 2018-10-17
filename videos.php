<?php

// Set up the database connection
$connection = mysqli_connect('localhost','devuser','fnord12345','mydb');
if ($connection->connect_errno) {
    error_log("Database connection failed: %s\n", $connection->connect_error);
    exit();
}
mysqli_set_charset($connection, "ISO-8859-1"); // In order to handle exotic characters in titles

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {

   if (isset($_GET['id'])) {
      $deleteQuery = "DELETE FROM videos WHERE id = " . $_GET['id'];
      
      // Execute the MySQL statement
      if ($result = $connection->query($deleteQuery)) {
      	 $response = array(
		'status' => 1,
		'status_message' =>'Deletion Successful'
	 );
       } else {
      	 $response = array(
		'status' => 0,
		'status_message' =>'Deletion Failed'
	 );
       }

      //print_r($response); // DEBUG
      
      header('Content-Type: application/json; charset=iso-8859-1');
      echo json_encode($response);

   }

}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

   // Default behaviour is returning all videos
   $videoQuery = "SELECT * FROM videos";

   // If an ID is given only that video is returned
   if (isset($_GET['id'])) {
       $videoQuery .= " WHERE id = " . $_GET['id'];
   }

   // Execute the MySQL statement
   if ($result = $connection->query($videoQuery)) {

      $response = array();

      // Store the results into an associative array
      while($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            $row[title] = utf8_decode($row[title]);
	    $response[] = $row;
      }

      //print_r($response); // DEBUG
      
      header('Content-Type: application/json; charset=iso-8859-1');
      echo json_encode($response);

    }

}

?>
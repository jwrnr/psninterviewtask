# Play Sports Network interview task

Jan Warner

## Introduction

The solution is based on PHP, MySQL, Apache and uses two external packages imported via Composer - Google Services and GuzzleHttp.

## Source files

### /api/store_by_filter.php

Opens the search_filter text file, iterates over each row and calls the /api/youtube endpoint to search for relevant results from the GCN and GMBN channels. In order to guarantee a predictable behavior the database tables are cleared at the start of the process.

### /api/youtube.php

A GET call to this API endpoint calls the Google YouTube API with the query string and channel ID provided in the GET variables. The resulting video and channel items received back are then stored in the local MySQL database accordingly. Returns 200 when finsiehd.

### /api/videos.php

This endpoint comprises the list (GET), search (GET with ID) and delete (DELETE) video functionality relating to the locally stored data in the MySQL database. The GET requests return a JSON structure with the information about either all or one video, depending on whether an ID has been specfied. If called with DELETE and an ID, this video is deleted from the local MySQL database.

## Live demonstration

Apart from the source files, there is a working implementation running in an Amazon Web Services EC2 instance which is available here:

* (GET) http://dev.jwrnr.co/api/store_by_filter.php
* (GET) http://dev.jwrnr.co/api/videos.php
* (GET) http://dev.jwrnr.co/api/videos.php?id=1981227674

The DELETE functionality is best demonstrated using an inspection tool such as Postman.

## Known issues - discussion points

* The .htaccess RewriteRules engine should be used to hide the .php part of the URL to adhere to the noun-only terminology of REST.
* The endpoints should ideally be secured under HTTPS.
* The endpoint HTTP response codes should be handled in more detail.
* The database structure could be enhanced with AUTO_INCREMENT on the primary key, making the title field longer and creating a link back to the channels table for each video.
* The charset is currently set to ISO-8859-1 to deal with complicated characters but should be UTF-8 in a final implementation.
* The datetime format returned by the videos API endpoint should be converted back to standard Javascript datetime format.
* The MySQL connection calls in the PHP source code expose the password in clear text.
* The source code could certainly be refactored in a multitude of ways. :-)
* The solution could be simplfied by using a framework such as Laravel.
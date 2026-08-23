<?php
  if (__FILE__ == $_SERVER['SCRIPT_FILENAME']) exit('No direct access allowed.');
  $hostname = "localhost";
  $username = "CryptoShitter";
  $password = "4!Dc6wx7703dskQ!";
  $dbname   = "CryptoShitMaker";
  
  $mysqli = new mysqli($hostname, $username, $password, $dbname);
 mysqli_set_charset($mysqli,"utf8");
 // Check connection
 if (mysqli_connect_errno()){
   printf("Failed to connect to MySQL: " . mysqli_connect_error());
   }
?>

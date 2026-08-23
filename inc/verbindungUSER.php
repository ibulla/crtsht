<?php
  if (__FILE__ == $_SERVER['SCRIPT_FILENAME']) exit('No direct access allowed.');
  $hostname = "localhost";
  $username = "MyOnlyUser";
  $password = "EyP3Qadnz96F[aDyuB54G30foLfYcB10ez7/VFISStQ9#1gl1rZg6nZtyuB54GyoR07";
  $dbname   = "CryptoShitMaker";
  
  $mysqli_USER = new mysqli($hostname, $username, $password, $dbname);
 mysqli_set_charset($mysqli_USER,"utf8");
 // Check connection
 if (mysqli_connect_errno()){
   printf("Failed to connect to MySQL: " . mysqli_connect_error());
   }
?>

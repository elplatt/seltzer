<?php

// Create connection
$config_db_host = 'yourserver.or.ipaddress';
$config_db_user = 'mysql_user_name';
$config_db_password = 'mysql_user_password';
$config_db_db = 'name_of_your_db';
$con=mysqli_connect($config_db_host,$config_db_user,$config_db_password,$config_db_db);

// Check connection
if (mysqli_connect_errno()) {
  echo "Failed to connect to MySQL: " . mysqli_connect_error();
}

//echo "Connected OK! <br>";

?>
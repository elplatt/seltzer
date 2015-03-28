<?php

error_reporting(E_ERROR | E_PARSE);

// Save path of directory containing index.php
$crm_root = realpath(dirname(__FILE__) . '/..');
// Bootstrap the crm.
// This brings in the global variables like the host, user, pw, etc.
require_once($crm_root . '/include/crm.inc.php');

// Create the DB connection for SQL queries
$con=mysqli_connect($config_db_host,$config_db_user,$config_db_password,$config_db_db);

// Check connection
if (mysqli_connect_errno()) {
  echo "Failed to connect to MySQL DB: " . mysqli_connect_error();
}

//echo "Connected OK! <br>";

?>
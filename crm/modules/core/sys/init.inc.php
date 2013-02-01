<?php

/*
    Copyright 2009-2013 Edward L. Platt <ed@elplatt.com>
    
    This file is part of the Seltzer CRM Project
    init.inc.php - Initialization code called before modules are loaded

    Seltzer is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    any later version.

    Seltzer is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Seltzer.  If not, see <http://www.gnu.org/licenses/>.
*/

// Connect to database server
$res = mysql_connect($config_db_host, $config_db_user, $config_db_password);
if (!$res) die(mysql_error());

// Select database
$res = mysql_selectdb($config_db_db);
if (!$res) die(mysql_error());

// Connect to session
session_start();

// Escape all http parameters
$esc_get = array();
foreach ($_GET as $k => $v) {
    $esc_get[$k] = mysql_real_escape_string($v);
}
$esc_post = array();
foreach ($_POST as $k => $v) {
    $esc_post[$k] = mysql_real_escape_string($v);
}

// Initialize error array
if (empty($_SESSION['errorList'])) {
    $_SESSION['errorList'] = array();
}

// Initialize message array
if (empty($_SESSION['messageList'])) {
    $_SESSION['messageList'] = array();
}

// Initialize member filter array
if (empty($_SESSION['member_filter'])) {
    $_SESSION['member_filter'] = array();
}

// Initialize the sytlesheet and script list
$core_stylesheets = array();
$core_scripts = array();

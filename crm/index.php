<?php

/*
    Copyright 2009-2019 Edward L. Platt <ed@elplatt.com>
    
    This file is part of the Seltzer CRM Project
    index.php - Application main page
    
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

// Save path of directory containing index.php
$crm_root = dirname(__FILE__);

// Bootstrap the site
require_once('include/crm.inc.php');

// Check for GET/POST command
$post_command = array_key_exists('command', $_POST) ? $_POST['command'] : '';
$get_command = array_key_exists('command', $_GET) ? $_GET['command'] : '';
$command = !empty($post_command) ? $post_command : $get_command;

if (!empty($command)) {
    // Handle command and redirect
    header('Location: ' . command($command));
    die();
}

if(isset($_GET['q'])) {
    if ($_GET['q'] == 'logout') {
        session_destroy();
    }
}

$template_vars = array('path' => path());
if (!user_id()) {
    if (path() != 'login' && path() != 'reset' && path() != 'reset-confirm' && path() != 'register') {
        $template_vars = array('path' => 'login');
    }
}
print template_render('page', $template_vars);

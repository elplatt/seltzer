<?php

/*
    Copyright 2009-2013 Edward L. Platt <ed@elplatt.com>
    
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

require_once('include/crm.inc.php');

// Check for GET/POST command
$post_command = $_POST['command'];
$get_command = $_GET['command'];
$command = !empty($post_command) ? $post_command : $get_command;

if (!empty($command)) {
    // Handle command and redirect
    header('Location: ' . command($command));
    die();
}

$template_vars = array('path' => path());
print template_render('page', $template_vars);

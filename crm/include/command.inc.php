<?php

/*
    Copyright 2009-2010 Edward L. Platt <elplatt@alum.mit.edu>
    
    This file is part of the Seltzer CRM Project
    command.inc.php - Core event handlers

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

/**
 * Handle login request.
 */
function command_login () {
    global $esc_post;
    
    // Calculate hash
    $esc_hash = sha1($_POST['password']);
    
    // Query database for given user
    $sql = "
        SELECT *
        FROM `user`
        WHERE `username`='$esc_post[username]' AND `hash`='$esc_hash'";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    $row = mysql_fetch_assoc($res);
    
    // Check for user
    if (!empty($row)) {
        user_login($row['uid']);
        $next = 'index.php';
    } else {
        error_register('Invalid username/password');
        $next = 'login.php';
    }
    
    // Redirect to index
    return $next;
}

/**
 * Handle logout request.
 */
function command_logout () {

    // Destroy session data
    session_destroy();
    
    // Redirect to index
    return 'index.php';
}


?>
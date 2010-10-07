<?php

/*
    Copyright 2009-2010 Edward L. Platt <elplatt@alum.mit.edu>
    
    This file is part of the Seltzer CRM Project
    user.inc.php - Core user functions

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
 * Return logged in user id
*/
function user_id() {
    return $_SESSION['userId'];
}

/**
 * Set logged in user
 *
 * @param $user_id The logged in user id
*/
function user_login($user_id) {
    $_SESSION['userId'] = $user_id;
}

/**
 * Return logged in user structure
 *
 * @param $uid The id of the user being queried, defaults to current user
*/
function user_get_user($uid = 0) {
    
    // Default to logged in user
    if ($uid == 0) {
        $uid = user_id();
    }
    
    // Query database
    $uid = mysql_real_escape_string($uid);
    $sql = "SELECT * FROM `user` WHERE `uid`='$uid'";
    $res = mysql_query($sql);
    if (!$res) { die(mysql_error()); }
    
    // Return user structure
    $user = mysql_fetch_assoc($res);
    return $user;
}

/**
 * Check if a user has the given role
 *
 * @param $role The role.
*/
function user_check_role ($role, $user_id = NULL) {
    
    // Choose logged in user if no user id
    if (!$user_id) {
        $user_id = user_id();
    }
    
    // Special case for authenticated user
    if ($user_id == user_id() && user_id()) {
        if ($role == 'authenticated') {
            return true;
        }
    }
    
    // Query for users roles
    $sql = "
        SELECT * FROM
        `user` LEFT JOIN `role` ON `user`.`uid`=`role`.`uid`
        WHERE `user`.`uid`='$user_id'";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    $row = mysql_fetch_array($res);
    
    // Check if user exists
    if (!$row) {
        return false;
    }
    
    // Check role
    if ($row[$role]) {
        return true;
    }
    
    // Default to false
    return false;
}

/**
 * Check if user has permissions for a given action
 *
 * @param $action The action
*/
function user_access ($action) {
    global $config_permissions;
    
    // Get user id
    $uid = user_id();
    
    // Query user's role info
    $sql = "SELECT * FROM `role` WHERE `uid`='$uid'";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    $roles = mysql_fetch_assoc($res);
    
    // Loop through allowed roles
    if (!empty($config_permissions[$action])) {
        foreach ($config_permissions[$action] as $role) {
            if ($roles[$role]) {
                return true;
            }
        }
    }
    
    // Default to no permission
    return false;
}


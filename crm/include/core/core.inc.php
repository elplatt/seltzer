<?php

/*
    Copyright 2009-2011 Edward L. Platt <elplatt@alum.mit.edu>
    
    This file is part of the Seltzer CRM Project
    core.inc.php - Core functions

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
 * Register an error.
 *
 * @param $error The error.
*/
function error_register ($error) {
    $_SESSION['errorList'][] = $error;
}

/**
 * Register a message.
 *
 * @param $message The message.
*/
function message_register ($message) {
    $_SESSION['messageList'][] = $message;
}

/**
 * Return all registered errors and clear the list.
 *
 * @return An array of error strings.
*/
function error_list () {
    $errors = $_SESSION['errorList'];
    $_SESSION['errorList'] = array();
    return $errors;
}

/**
 * Return all registered messages and clear the list.
 *
 * @return An array of message strings.
*/
function message_list () {
    $errors = $_SESSION['messageList'];
    $_SESSION['messageList'] = array();
    return $errors;
}

/**
 * Returns a sitemap containing only pages the logged in user has permission
 * to see.
 *
 * @return A tree structure representing the sitemap.
*/
function sitemap () {
    global $config_sitemap;
    
    // Initialize sitemap array
    $sitemap = array();
    
    // Loop through sections
    foreach ($config_sitemap as $section) {
        if (empty($section['visible']) || user_check_role($section['visible'])) {
            $sitemap[] = $section;
        }
    }
    
    return $sitemap;
}

/**
 * @return list of installed modules.
 */
function module_list () {
    global $config_modules;
    
    return $config_modules;
}

?>
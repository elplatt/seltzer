<?
/*
    Copyright 2009-2013 Edward L. Platt <ed@elplatt.com>
    
    This file is part of the Seltzer CRM Project
    error.inc.php - Core error-reporting functions

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
 * @return The themed html string for any errors currently registered.
*/
function theme_errors () {

    // Pop and check errors
    $errors = error_list();
    if (empty($errors)) {
        return '';
    }
    
    $output = '<fieldset><ul>';
    
    // Loop through errors
    foreach ($errors as $error) {
        $output .= '<li>' . $error . '</li>';
    }
    
    $output .= '</ul></fieldset>';
    return $output;
}

/**
 * @return The themed html string for any registered messages.
*/
function theme_messages () {

    // Pop and check messages
    $messages = message_list();
    if (empty($messages)) {
        return '';
    }
    
    $output = '<fieldset><ul>';
    
    // Loop through errors
    foreach ($messages as $message) {
        $output .= '<li>' . $message . '</li>';
    }
    
    $output .= '</ul></fieldset>';
    return $output;
}

<?
/*
    Copyright 2009-2012 Edward L. Platt <elplatt@alum.mit.edu>
    
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

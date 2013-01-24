<?
/*
    Copyright 2009-2013 Edward L. Platt <ed@elplatt.com>
    
    This file is part of the Seltzer CRM Project
    commmand.inc.php - Core command processing functions

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
 * Process a command and redirect.
 * @param $command The name of the command to process
 * @return The url to redirect to.
 */
function command ($command) {
    
    // Initialize url and parameters
    $url = '';
    $params = array();
    
    // Call legacy handler if it exists
    $handler = "command_$command";
    if (function_exists($handler)) {
        $res = call_user_func($handler);
        
        // Split result into file and params
        $parts = explode('?', $res);
        $url = $parts[0];
        if (sizeof($parts) > 0) {
            $clauses = explode('&', $parts[1]);
            foreach ($clauses as $clause) {
                $keyvalue = explode('=', $clause);
                if (sizeof($keyvalue) > 1) {
                    $params[$keyvalue[0]] = $keyvalue[1];
                }
            }
        }
    }
    
    // Call the handler for each module if it exists
    foreach (module_list() as $module) {
        $handler = "{$module}_command";
        if (function_exists($handler)) {
            $handler($command, $url, $params);
        }
    }
    
    // Error if the url is still empty
    if (empty($url)) {
        error_register('No such command: ' . $command);
        $url = 'index.php';
    }
    
    $url .= '?';
    $parts = array();
    foreach ($params as $key => $value) {
        $parts[] = $key . '=' . $value;
    }
    return $url . implode('&', $parts);
}

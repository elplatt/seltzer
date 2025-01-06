<?php

/*
    Copyright 2009-2025 Edward L. Platt <ed@elplatt.com>
    
    This file is part of the Seltzer CRM Project
    data.inc.php - Core data functionality
    
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
 * Get an array of data structures from the database, and allow all modules
 * to extend them.  This function will call hook_data() to get the data and
 * hook_data_alter() to allow modules to alter the data.
 * @param $type The type of data.
 * @param $opts An associative array of options.
 * @return An array of data structures.
 */
function crm_get_data ($type, $opts = array()) {
    // Get the base data
    $hook = "${type}_data";
    if (!function_exists($hook)) {
        error_register('No such data type: ' . $type);
        die();
    }
    $data = call_user_func($hook, $opts);
    if (!empty($data)) {
        // Let other modules extend the data
        foreach (module_list() as $module) {
            // Make sure module is really installed
            $rev_hook = "${module}_revision";
            $hook = "${module}_data_alter";
            if (function_exists($hook)) {
                if (module_get_schema_revision($module) != call_user_func($rev_hook)) {
                    error_register("Database schema needs to be upgraded for module $module.");
                    continue;
                }
                $data = call_user_func($hook, $type, $data, $opts);
                // Make sure the hook actually returned data
                if (is_null($data)) {
                    error_register('Hook returned null: ' . $hook);
                }
            }
        }
    }
    return $data;
}

/**
 * Get a single data structure from the database, and allow all modules
 * to extend it.  This function will call hook_data() to get the data and
 * hook_data_alter() to allow modules to alter the data.
 * @param $type The type of data.
 * @param $opts An associative array of options.
 * @return An array of data structures.
 */
function crm_get_one ($type, $opts = array()) {
    $opts['limit'] = 1;
    $data = crm_get_data ($type, $opts);
    if (count($data) > 0) {
        return $data[0];
    }
    return array();
}

/**
 * Take an indexed array of data structures and create an associative array.
 * @param $data The list of data structures.
 * @param $key The key of the data strucutre to use as a key for the array.
 * @return The associative array.
 */
function crm_map ($data, $key) {
    $map = array();
    foreach ($data as $row) {
        $map[$row[$key]] = $row;
    }
    return $map;
}

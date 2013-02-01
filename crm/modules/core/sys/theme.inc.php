<?php

/*
    Copyright 2009-2013 Edward L. Platt <ed@elplatt.com>
    
    This file is part of the Seltzer CRM Project
    theme.inc.php - Core theme system.

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
 * @return the path to the theme folder without leading or trailing slashes.
 */
function path_to_theme() {
    return 'themes/inspire';
}

/**
 * Map theme calls to appropriate theme handler.
 *
 * At least one parmaeter is required, namely the element being themed.
 * Additional parameters will be passed on to the theme handler.
 *
 * @param $element The element to theme.
 * @return The themed html string for the specified element.
*/
function theme () {
    
    // Check for arguments
    if (func_num_args() < 1) {
        return "";
    }
    $args = func_get_args();
    
    // Construct handler name
    $element = $args[0];
    $handler = 'theme_' . $element;
    
    // Construct handler arguments
    $handler_args = array();
    for ($i = 1; $i < count($args); $i++) {
        $handler_args[] = $args[$i];
    }
    
    // Check for undefined handler
    if (!function_exists($handler)) {
        return "";
    }
    
    return call_user_func_array($handler, $handler_args);
}

/**
 * @return Themed html for script includes.
 */
function theme_scripts () {
    global $core_scripts;
    $output = '';
    foreach ($core_scripts as $script) {
        $output .= '<script type="text/javascript" src="' . $script . '"></script>';
    }
    return $output;
}

/**
 * @return Themed html for stylesheet includes.
 */
function theme_stylesheets () {
    global $core_stylesheets;
    $output = '';
    foreach ($core_stylesheets as $sheet) {
        $output .= '<link rel="stylesheet" type="text/css" href="' . $sheet . '"/>';
    }
    return $output;
}

<?php

/*
    Copyright 2009-2010 Edward L. Platt <elplatt@alum.mit.edu>
    
    This file is part of the Seltzer CRM Project
    page.inc.php - Core tabbed page system

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
 * Construct data structure for a specified page
 *
 * @param $page The page to construct
 * @param $options An associative array of options
 *
 * Returns the page data structure.
 */
function page($page, $options) {
    
    // Initialize page structure
    $data = array();
    
    // Loop through modules
    foreach (module_list() as $module) {
        
        // Check if hook exists and execute
        $hook = $module . '_page';
        if (function_exists($hook)) {
            $hook($data, $page, $options);
        }
    }
    
    return $data;
}

?>
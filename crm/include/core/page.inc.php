<?php

/*
    Copyright 2009-2011 Edward L. Platt <elplatt@alum.mit.edu>
    
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
 * Construct the data structure for a specified page.
 *
 * @param $page The page to construct.
 * @param $options An associative array of options.
 *
 * @returnsthe page data structure.
 */
function page ($page, $options) {
    
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

/**
 * Set the title of a page.
 * @param &$page_data The page data.
 * @param $title.
 */
function page_set_title (&$page_data, $title) {
    $page_data['#title'] = $title;
}

/**
 * Add content to the top of a page tab.
 * @param &$page_data The page data structure.
 * @param $tab_name The name of the tab.
 * @param $content The themed html content to add.
 */
function page_add_content_top (&$page_data, $tab_name, $content) {
    if (!isset($page_data[$tab_name])) {
        $page_data[$tab_name] = array();
    }
    array_unshift($page_data[$tab_name], $content);
}

/**
 * Add content to the bottom of a page tab.
 * @param &$page_data The page data structure.
 * @param $tab_name The name of the tab.
 * @param $content The themed html content to add.
 */
function page_add_content_bottom (&$page_data, $tab_name, $content) {
    if (!isset($page_data[$tab_name])) {
        $page_data[$tab_name] = array();
    }
    array_push($page_data[$tab_name], $content);
}

/**
 * Theme an entire page.
 *
 * @param $page_name The page name.
 * @param $options The options to pass to page().
 * @return The themed html for the page.
 */
function theme_page ($page_name, $options = array()) {
    
    // Create data structure
    $data = page($page_name, $options);
    
    // Initialize output
    $tabs = '';
    $header = '';
    
    // Add page title to header
    if (!empty($data['#title'])) {
        $header .= '<h1>' . $data['#title'] . '</h1>';
    }
    
    // Add button list to header
    $header .= '<ul class="page-nav">';
    
    // Loop through each tab
    foreach ($data as $tab => $tab_data) {
        
        // Skip special keys
        if ($tab{0} === '#') {
            continue;
        }
        
        // Generate tab name
        $tab_name = preg_replace('/\W+/', '-', strtolower($tab));
        
        $header .= '<li><a href="#tab-' . $tab_name . '">' . $tab . '</a></li>';
        
        $tabs .= '<fieldset id="tab-' . $tab_name . '" class="tab">';
        
        $tabs .= '<legend><a name="' . $tab_name . '">' . $tab . '</a></legend>';
        
        $tabs .= join($tab_data);
        
        $tabs .= '</fieldset>';
    }
    
    // Close header button list
    $header .= '</ul>';
    
    return $header . $tabs;
}

?>
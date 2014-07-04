<?php
/*
    Copyright 2009-2014 Edward L. Platt <ed@elplatt.com>
    
    This file is part of the Seltzer CRM Project
    util.inc.php - Core utility functions

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
 * @return The base path to the directory containing index.php.
 */
function base_path () {
    global $config_base_path;
    return $config_base_path;
}

/**
 * @return the path to the current page.
 */
function path () {
    return array_key_exists('q', $_GET) ? $_GET['q'] : '';
}

/**
 * @return The title of the site.
 */
function title () {
    global $config_site_title;
    return $config_site_title;
}

/**
 * @return An array of navigation links
 */
function links () {
    global $config_links;
    return $config_links;
}

/**
 * Return a url to an internal path.
 * @param $path The path to convert to a url.
 * @param $opts An associative array of options.  Keys are:
 *   'query' - An array of query paramters to add to the url.
 * @return A string containing the url.
 */
function crm_url ($path = '', $opts = array()) {
    $url = base_path() . "index.php?";
    $terms = array();
    // Construct terms of the query string
    if ($path != '<front>') {
        $terms[] = "q=$path";
    }
    if (isset($opts['query'])) {
        foreach ($opts['query'] as $key => $value) {
            $terms[] = "$key=$value";
        }
    }
    $url .= implode('&', $terms);
    return $url;
}

/**
 * Return a link.
 * @param $text The text of the link.
 * @param $path The path to link to.
 * @param $opts Options array to pass to crm_url().
 * @return A string containing the html link.
 */
function crm_link ($text, $path, $opts = array()) {
    return '<a href="' . crm_url($path, $opts) . '">' . $text . '</a>';
}

/**
 * Die and print and print a debug backtrace.
 * @param $text
 */
function crm_error ($text) {
    print "<pre>$text";
    print_r(debug_backtrace());
    print "</pre>";
    die();
}

/**
 * Parse and return the version of the CRM as specified in crm.inc.php under £crm_version.
 * @return A string representation of the array
 */
function crm_version() {
    global $crm_version;
    $version = implode(".", $crm_version);
    return $version;
}

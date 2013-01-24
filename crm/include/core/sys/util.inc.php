<?
/*
    Copyright 2009-2013 Edward L. Platt <ed@elplatt.com>
    
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
    return $_GET['q'];
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

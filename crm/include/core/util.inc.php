<?
/*
    Copyright 2009-2012 Edward L. Platt <elplatt@alum.mit.edu>
    
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
        if (empty($section['visible'])) {
            $sitemap[] = $section;
        } else {
            foreach ($section['visible'] as $perm) {
                if ($perm == 'authenticated' && user_id() != 0) {
                    $sitemap[] = $section;
                    break;
                }
                if (user_access($perm)) {
                    $sitemap[] = $section;
                    break;                    
                }
            }
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

/**
 * @return The title of the site.
 */
function title () {
    global $config_site_title;
    
    return $config_site_title;
}

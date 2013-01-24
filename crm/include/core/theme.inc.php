<?php

/*
    Copyright 2009-2013 Edward L. Platt <ed@elplatt.com>
    
    This file is part of the Seltzer CRM Project
    theme.inc.php - Provides theming for core elements

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
 * @return The themed html string for a page header.
*/
function theme_header () {
    $output = '';
    $output .= theme('logo');
    $output .= theme('login_status');
    $output .= theme('navigation');
    return $output;
}

/**
 * @return The themed html string for a page footer.
*/
function theme_footer() {
    return 'Powered by <a href="http://github.com/elplatt/seltzer">Seltzer CRM</a>';
}

/**
 * @return The themed html string for logo.
*/
function theme_logo () {
    return '<div class="logo"><img alt="i3 Detroit" src="' . path_to_theme() . '/images/logo.png"/></div>';
}

/**
 * @return The themed html string for user login status.
*/
function theme_login_status () {
    
    $output = '<div class="login-status">';
    if (user_id()) {
        $output .= 'Welcome, ' . user_username() . '. <a href="index.php?command=logout">Log out</a>';
    } else {
        $output .= '<a href="index.php?q=login">Log in</a>&nbsp;&nbsp;&nbsp;';
        $output .= '<a href="index.php?q=reset">Reset password</a>';
    }
    $output .= '</div>';
    
    return $output;
}

/**
 * @return The themed html string for the navigation menu.
*/
function theme_navigation () {
    $output = '<ul class="nav">';
    $links = links();
    $sitemap = page_sitemap();
    foreach ($links as $path => $title) {
        if (in_array($path, $sitemap)) {
            $output .= '<li>' . theme('navigation_link', $path, $title) . '</li>';
        }
    }
    $output .= '</ul>';
    
    return $output;
}

/**
 * Theme a link.
 *
 * @param $path The path to the page.
 * @param $title The page title.
 * @return The themed html string for a single link.
*/
function theme_navigation_link ($path, $title) {
    if ($path == '<front>') {
        $path = '';
    }
    $output = '<a href="' . base_path() . '?q=' . $path . '">' . $title . '</a>';
    return $output;
}

/**
 * Generate a themed delete confirmation form.
 * 
 * @param $type The type of element to delete.
 * @param $id The id of the element to delete.
 * @return The themed html for a delete confirmation form.
*/
function theme_delete_form ($type, $id) {
    return theme('form', delete_form($type, $id));
}

?>
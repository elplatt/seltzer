<?php

/*
    Copyright 2009-2012 Edward L. Platt <elplatt@alum.mit.edu>
    
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
 * Render a template.
 * @param $name The template name.
 * @param $path The template path.
 */
function template_render ($name, $path) {
    // Load variables into local scope
    $variables = template_preprocess($path, $path);
    extract($variables);
    
    // Construct the template filename
    $filename = path_to_theme() . '/' . $name . '.tpl.php';
    
    // Render template
    include($filename);
}

/**
 * Assign variables to be set for a template.
 * @param $path The path to the current page
 */
function template_preprocess ($path) {
    $variables = array();
    $variables['scripts'] = theme('scripts');
    $variables['stylesheets'] = theme('stylesheets');
    $variables['title'] = title();
    $variables['header'] = theme('header');
    $variables['errors'] = theme('errors');
    $variables['messages'] = theme('messages');
    $variables['content'] = theme('page', $path);
    $variables['footer'] = theme('footer');
    return $variables;
}

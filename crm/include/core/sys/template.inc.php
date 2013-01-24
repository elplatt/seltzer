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
 * Render a template.
 * @param $name The template name.
 * @param $vars An array mapping template variable names to their values.  If
 *   the value is a string, it will be treated as a path.
 */
function template_render ($name, $vars = NULL) {
    
    // Use template_preprocess_page by default
    // TODO: The template variable system should use modular hooks
    if (!$vars) {
        $vars = template_preprocess_page();
    }
    
    // If $vars is a string, we want to generate vars from a path
    if (!is_array($vars)) {
        $path = $vars;
        $generator = 'template_preprocess_' . $name;
        if (function_exists($generator)) {
            $vars = call_user_func($generator, $path);
        } else {
            $vars = array();
        }
    }
    
    extract($vars);
    
    // Construct the template filename
    $filename = path_to_theme() . '/' . $name . '.tpl.php';
    
    // Render template
    ob_start();
    include($filename);
    $output = ob_get_contents();
    ob_end_clean();
    
    return $output;
}

/**
 * Assign variables to be set for a template.
 * @param $path The path to the current page
 */
function template_preprocess_page ($path = '') {
    global $config_org_name;
    global $config_base_path;
    
    $variables = array();
    $variables['scripts'] = theme('scripts');
    $variables['stylesheets'] = theme('stylesheets');
    $variables['title'] = title();
    $variables['org_name'] = $config_org_name;
    $variables['base_path'] = $config_base_path;
    $variables['hostname'] = $_SERVER['SERVER_NAME'];
    $variables['header'] = theme('header');
    $variables['errors'] = theme('errors');
    $variables['messages'] = theme('messages');
    $variables['content'] = theme('page', $path, $_GET);
    $variables['footer'] = theme('footer');
    return $variables;
}

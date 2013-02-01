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
function template_render ($name, $vars = array()) {
    // Run default preprocess hook
    $vars = array_merge($vars, template_preprocess($vars));
    // TODO run module preprocess hooks
    // Run default preprocess hook for template
    $generator = 'template_preprocess_' . $name;
    if (function_exists($generator)) {
        $vars = array_merge($vars, call_user_func($generator, $vars));
    }
    // TODO run page-specific module preprocess hooks
    extract($vars);
    
    // Render template
    if (isset($vars['type'])) {
        $filename = path_to_theme() . '/' . "$name-$vars[type]" . '.tpl.php';
    } else {
        // TODO This should be a fallback, not an else -Ed 2013-01-29
        $filename = path_to_theme() . '/' . $name . '.tpl.php';
    }
    ob_start();
    include($filename);
    $output = ob_get_contents();
    ob_end_clean();
    
    return $output;
}

/**
 * Assign variables to be set for a template.
 * @param $vars The previously set variables.
 */
function template_preprocess ($vars) {
    global $config_org_name;
    global $config_base_path;
    
    $vars['title'] = title();
    $vars['org_name'] = $config_org_name;
    $vars['base_path'] = $config_base_path;
    $vars['hostname'] = $_SERVER['SERVER_NAME'];
    
    return $vars;
}

/**
 * Assign variables to be set for a template.
 * @param $vars The previously set variables.
 */
function template_preprocess_page ($vars) {
    global $config_org_name;
    global $config_base_path;
    
    $vars['scripts'] = theme('scripts');
    $vars['stylesheets'] = theme('stylesheets');
    $vars['header'] = theme('header');
    $vars['errors'] = theme('errors');
    $vars['messages'] = theme('messages');
    $vars['content'] = theme('page', $vars['path'], $_GET);
    $vars['footer'] = theme('footer');
    
    return $vars;
}

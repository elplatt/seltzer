<?php

/*
    Copyright 2009-2013 Edward L. Platt <ed@elplatt.com>
    
    This file is part of the Seltzer CRM Project
    page.inc.php - Core pages

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
 * @return An array of pages provided by this module.
 */
function core_page_list () {
    $pages = array();
    $pages[] = '<front>';
    $pages[] = 'install';
    $pages[] = 'login';
    $pages[] = 'reset';
    $pages[] = 'reset-confirm';
    $pages[] = 'delete';
    if (user_access('report_view')) {
        $pages[] = 'reports';
    }
    if (user_access('user_permissions_edit')) {
        $pages[] = 'permissions';
    }
    if (user_access('module_upgrade')) {
        $pages[] = 'upgrade';
    }
    return $pages;
}

/**
 * Page hook.  Adds member module content to a page before it is rendered.
 *
 * @param &$page_data Reference to data about the page being rendered.
 * @param $page_name The name of the page being rendered.
 * @param $options The array of options passed to theme('page').
*/
function core_page (&$page_data, $page_name, $options) {
    
    switch ($page_name) {
        
        case '<front>':
            page_add_content_top($page_data, '<p>Welcome to SeltzerCRM!</p>');
            break;
        case 'install':
            page_add_content_top($page_data, theme('form', module_install_form()));
            break;
        case 'login':
            page_add_content_top($page_data, theme('login_form'));
            break;
        case 'reset':
            page_add_content_top($page_data, theme('user_reset_password_form'));
            break;
        case 'reset-confirm':
            page_add_content_top($page_data, theme('user_reset_password_confirm_form', $_GET['v']));
            break;
        case 'delete':
            page_add_content_top($page_data, theme('delete_form', $_GET['type'], $_GET['id']));
            break;
        case 'reports':
            if (user_access('report_view')) {
                page_set_title($page_data, 'Reports');
            }
            break;
        case 'permissions':
            if (user_access('user_permissions_edit')) {
                page_set_title($page_data, 'Permissions');
                page_add_content_top($page_data, theme('form', user_permissions_form()));
            }
            break;
        case 'upgrade':
            if (user_access('module_upgrade')) {
                page_set_title($page_data, 'Upgrade Modules');
                $content = theme('table', 'module_upgrade');
                $content .= theme('form', module_upgrade_form());
                page_add_content_top($page_data, $content);
            }
    }   
}

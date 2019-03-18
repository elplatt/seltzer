<?php

/*
    Copyright 2009-2019 Edward L. Platt <ed@elplatt.com>
    
    This file is part of the Seltzer CRM Project
    devel.inc.php - Developer and debugging utilities
    
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
 * @return This module's revision number.  Each new release should increment
 * this number.
 */
function devel_revision () {
    return 1;
}

/**
 * Page hook.  Adds module content to a page before it is rendered.
 *
 * @param &$page_data Reference to data about the page being rendered.
 * @param $page_name The name of the page being rendered.
*/
function devel_page (&$page_data, $page_name) {
    switch ($page_name) {
        case 'contact':
            // Capture contact id
            $cid = $_GET['cid'];
            if (empty($cid)) {
                return;
            }
            if (!user_access('contact_view')) {
                return;
            }
            $contact = crm_get_one('contact', array('cid'=>$cid));
            // Add devel tab
            page_add_content_top($page_data, '<pre>' . print_r($contact, true) . '</pre>', 'Devel');
            break;
    }
}

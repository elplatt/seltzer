<?php 

/*
    Copyright 2009-2013 Edward L. Platt <ed@elplatt.com>
    
    This file is part of the Seltzer CRM Project
    profile_picture.inc.php - Defines contact entity

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
function profile_picture_revision () {
    return 1;
}

// Installation functions //////////////////////////////////////////////////////

/**
 * Install or upgrade this module.
 * @param $old_revision The last installed revision of this module, or 0 if the
 *   module has never been installed.
 */
function profile_picture_install ($old_revision = 0) {
    if ($old_revision < 1) {
        
    }
}

// Pages ///////////////////////////////////////////////////////////////////////

/**
 * @return An array of pages provided by this module.
 */
function profile_picture_page_list () {
    $pages = array();
//    if (user_access('contact_view')) {
//        $pages[] = 'contact';
 //   }
    return $pages;
}

/**
 * Page hook.  Adds profile_picture module content to a page before it is rendered.
 *
 * @param &$page_data Reference to data about the page being rendered.
 * @param $page_name The name of the page being rendered.
*/
function profile_picture_page (&$page_data, $page_name) {
    switch ($page_name) {
        case 'contact':
            // Capture contact id
            $cid = $_GET['cid'];
            if (empty($cid)) {
                return;
            }
            $contact_data = crm_get_data('contact', array('cid'=>$cid));
            $contact = $contact_data[0];
            // Set page title
            page_set_title($page_data, theme('contact_name', $contact));
            // Add view tab
            $view_content = '';
            if (user_access('contact_view')) {
                $view_content .= '<h3>Profile Picture</h3>';
                $opts = array(
                    'cid' => $cid
                    , 'ops' => false
                );
                $view_content .= theme('profile_picture', $contact);
            }
            if (!empty($view_content)) {
                page_add_content_top($page_data, $view_content, 'View');
            }
            break;
    }
}

// Themeing ////////////////////////////////////////////////////////////////////

/**
 * Theme a contact's profile picture.
 * 
 * @param $contact The contact data structure or cid.
 * @param $link True if the name should be a link (default: false).
 * @param $path The path that should be linked to.  The cid will always be added
 *   as a parameter.
 *
 * @return the html of the user profile picture.
 */
function theme_profile_picture ($contact) {
    if (!is_array($contact)) {
        $contact = crm_get_one('contact', array('cid'=>$contact));
    }
    $email = $contact['email'];
    $size = 120; //default size of the gravatar
    $grav_url = "http://www.gravatar.com/avatar/" . md5(strtolower(trim($email)))."?d=retro&s=" .$size;
    $html = '<div class="userimage"><img src = "'.$grav_url.'"></div>';
    return $html;
}
?>
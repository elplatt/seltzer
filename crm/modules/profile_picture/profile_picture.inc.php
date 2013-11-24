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
    return 2;
}

// Installation functions //////////////////////////////////////////////////////

/**
 * Install or upgrade this module.
 * @param $old_revision The last installed revision of this module, or 0 if the
 *   module has never been installed.
 */
function profile_picture_install ($old_revision = 0) {
    if ($old_revision < 1) {
        // There is nothing to install. Do nothing
    }
    if ($old_revision < 2) {
        // Create a table to associate pictures with a CID
        $sql = '
            CREATE TABLE IF NOT EXISTS `profile_picture` (
              `cid` mediumint(8) unsigned NOT NULL,
              `filename` varchar(255) NOT NULL,
              PRIMARY KEY (`cid`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
        ';
        $res = mysql_query($sql);
        if (!$res) die(mysql_error());
        // Create folder directory if it does not exist to store uploaded profile pictures in.
        if(!file_exists('./files/profile_picture')){
            if (!mkdir('./files/profile_picture/', 0775, true)) {
                error_register('Failed to create folder. Please check folder permissions.');
            }
        }
    }
}

// Pages ///////////////////////////////////////////////////////////////////////

/**
 * @return An array of pages provided by this module.
 */
function profile_picture_page_list () {
    $pages = array();
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
            $view_content .= theme('form', crm_get_form('profile_picture_upload',  $cid));
            if (!empty($view_content)) {
                page_add_content_top($page_data, $view_content, 'View');
            }
            break;
    }
}


/**
 * @return a profile picture upload form structure.
 */
function profile_picture_upload_form ($cid) {
    return array(
        'type' => 'form'
        , 'method' => 'post'
        , 'enctype' => 'multipart/form-data'
        , 'command' => 'profile_picture_upload'
        , 'hidden' => array(
            'cid' => $cid
        )
        , 'fields' => array(
            array(
                'type' => 'fieldset'
                , 'label' => 'Upload Picture'
                , 'fields' => array(
                    array(
                        'type' => 'message'
                        , 'value' => 'Use this form to upload a different profile picture'
                    )
                    , array(
                        'type' => 'file'
                        , 'label' => 'Picture'
                        , 'name' => 'profile-picture-file'
                    )
                    , array(
                        'type' => 'submit'
                        , 'value' => 'Upload'
                    )
                )
            )
        )
    );
}

/**
 * Handle profile picture upload request.
 *
 * @return The url to display on completion.
 */
function command_profile_picture_upload () {
    if (!array_key_exists('profile-picture-file', $_FILES)) {
        error_register('No profile picture uploaded');
        return crm_url('contact&cid=' . $_POST['cid']);
    }
    //qualify file as an image that is less than 20mb
    $allowedExts = array("gif", "jpeg", "jpg", "png");
    $temp = explode(".", $_FILES['profile-picture-file']['name']);
    $extension = end($temp);
    if ((($_FILES['profile-picture-file']['type'] == "image/gif")
    || ($_FILES['profile-picture-file']['type'] == "image/jpeg")
    || ($_FILES['profile-picture-file']['type'] == "image/jpg")
    || ($_FILES['profile-picture-file']['type'] == "image/pjpeg")
    || ($_FILES['profile-picture-file']['type'] == "image/x-png")
    || ($_FILES['profile-picture-file']['type'] == "image/png"))
    && ($_FILES['profile-picture-file']['size'] < 20000*1024)
    && in_array($extension, $allowedExts)) {
        if ($_FILES['profile-picture-file']["error"] > 0) {
            error_register("Error: " . $_FILES['profile-picture-file']['error']);
            return crm_url('contact&cid=' . $_POST['cid']);
        } else {
            
            //TODO: Resize the picture
            
            
            //generate md5 char pic hash from the contents of the uploaded image file
            $hash = hash_file('md5', $_FILES['profile-picture-file']['tmp_name']);
            //generate filepath to save file
            $destFileName = $hash . '.' . $extension;
            $destFilePath = "files/profile_picture/" . $destFileName;
            //update SQL server
            
            //TODO: Remove existing profile picture associated with this CID (both the file, and the row in the database)
            
            // Associate this CID with uploaded file by storing a cid=>filepath row in the profile_picture table
            $esc_cid = mysql_real_escape_string($_POST['cid']);
            $sql = "INSERT INTO `profile_picture` (`cid`, `filename`) VALUES ('$esc_cid', '$destFileName')";
                    $res = mysql_query($sql);
                    if (!$res) die(mysql_error());
                    
            //save the file. Literally just moving from /tmp/ to the right directory
            if(!move_uploaded_file($_FILES['profile-picture-file']['tmp_name'], $destFilePath)){
                error_register('Error Saving Image to Server');
                error_register('Tried moving: ' .  $_FILES['profile-picture-file']['tmp_name'] . 'to: ' . $destFilePath);
            } else {
              message_register("Successfully uploaded user profile picture");  
            }
        }
    } else {
        error_register('Invalid file. Did you upload an image (gif, jpeg, jpg, png) that is less than 20mb?');
        error_register('File Type is: ' . $_FILES['profile-picture-file']['type']);
        error_register('File Size is: ' . $_FILES['profile-picture-file']['size'] / 1024 . "kB");
    } 
    return crm_url('contact&cid=' . $_POST['cid']);
}


// Themeing ////////////////////////////////////////////////////////////////////

/**
 * Theme a contact's profile picture.
 * 
 * @param $contact The contact data structure or cid.
 *
 * @return The html of the user's profile picture.
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
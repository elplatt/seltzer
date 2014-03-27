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

function email_list_revision () {
    return 1;
}

// Installation functions //////////////////////////////////////////////////////

/**
 * Install or upgrade this module.
 * @param $old_revision The last installed revision of this module, or 0 if the
 *   module has never been installed.
 */
function email_list_install ($old_revision = 0) {
    if ($old_revision < 1) {
        // Create a table to associate email addresses with a CID
        $sql = '
            CREATE TABLE IF NOT EXISTS `email_list_associations` (
              `lid` mediumint(8) unsigned NOT NULL,
              `cid` mediumint(8) unsigned NOT NULL,
              `email` varchar(255) NOT NULL,
              PRIMARY KEY ( `lid`,`cid`,`email`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
        ';
        $res = mysql_query($sql);
        if (!$res) die(mysql_error());
        
        // Create a table to associate email list names with a LID (list ID)
        $sql = '
              CREATE TABLE IF NOT EXISTS `email_lists` (
              `lid` mediumint(8) unsigned NOT NULL,
              `list_name` varchar(255) NOT NULL,
              PRIMARY KEY (`lid`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
        ';
        $res = mysql_query($sql);
        if (!$res) die(mysql_error());
    }
}
// Pages ///////////////////////////////////////////////////////////////////////

/**
 * @return An array of pages provided by this module.
 */
function email_list_page_list () {
    $pages = array();
    return $pages;
}

/**
 * Page hook.  Adds profile_picture module content to a page before it is rendered.
 *
 * @param &$page_data Reference to data about the page being rendered.
 * @param $page_name The name of the page being rendered.
*/
function email_list_page (&$page_data, $page_name) {
    switch ($page_name) {
        case 'contact':
            // Capture contact id
            $cid = $_GET['cid'];
            if (empty($cid)) {
                return;
            }
            $contact_data = crm_get_data('contact', array('cid'=>$cid));
            $contact = $contact_data[0];
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


// Forms ///////////////////////////////////////////////////////////////////////

/**
 * @return Array mapping payment method values to descriptions.
 */
function email_list_options () {
    $options = array();
    
    //populate an array with available email list options.
    //it should end up looking something like this:
    
    // query database for a list of  email lists available.
    
    $sql = '
     SELECT
        `list_name`
        FROM `email_lists`
        WHERE 1";
    ';
    $sql .= "
        ORDER BY `list_name`, `lid` ASC";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    // Store data
    $keys = array();
    $row = mysql_fetch_assoc($res);
    while (!empty($row)) {
        // Contents of row are kid, cid, start, end, serial, slot
        $keys[] = $row;
        $row = mysql_fetch_assoc($res);
    }
    $options[0] = 'Announce';
    $options[1] = 'Members Only';
    $options[2] = 'Public';
    return $options;
}

/**
 * @return an email add form structure.
 */
function email_list_add_form ($cid) {
    return array(
        'type' => 'form'
        , 'method' => 'post'
        , 'enctype' => 'multipart/form-data'
        , 'command' => 'email_list_add'
        , 'hidden' => array(
            'cid' => $cid
        )
        , 'fields' => array(
            array(
                'type' => 'fieldset'
                , 'label' => 'Add Contact Email'
                , 'fields' => array(
                    array(
                        'type' => 'message'
                        , 'value' => 'Use this form to add an additional email for this member'
                    )
                    ,array(
                        'type' => 'text'
                        , 'label' => 'Email List'
                        , 'name' => 'list'
                        , 'options' => email_list_options()
                    )
                    , array(
                        'type' => 'text'
                        , 'label' => 'Email'
                        , 'name' => 'email'
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
function command_email_list_add () {
    $cid = $_POST['cid'];
    
    if (!array_key_exists('profile-picture-file', $_FILES)) {
        error_register('No profile picture uploaded');
        return crm_url('contact&cid=' . $_POST['cid']);
    }
    //qualify the email as a valid email.
    $allowedExts = array("gif", "jpeg", "jpg", "png");
    $temp = explode(".", $_FILES['profile-picture-file']['name']);
    $extension = end($temp);
    if ((($_FILES['profile-picture-file']['type'] == "image/gif")
    || ($_FILES['profile-picture-file']['type'] == "image/jpeg")
    || ($_FILES['profile-picture-file']['type'] == "image/jpg")
    || ($_FILES['profile-picture-file']['type'] == "image/pjpeg")
    || ($_FILES['profile-picture-file']['type'] == "image/x-png")
    || ($_FILES['profile-picture-file']['type'] == "image/png"))
    && ($_FILES['profile-picture-file']['size'] < 1000*1024)
    && in_array($extension, $allowedExts)) {
        if ($_FILES['profile-picture-file']["error"] > 0) {
            error_register("Error: " . $_FILES['profile-picture-file']['error']);
            return crm_url('contact&cid=' . $_POST['cid']);
        } else {
            //------- Resize the image -------
            if (!(extension_loaded('gd') && function_exists('gd_info'))) {
                error_register("It looks like GD, an image manipulation library,
                               is not configured for your PHP installation. Therefore,
                               Image Resizing is disabled.");
            } else {
                define('THUMBNAIL_IMAGE_MAX_WIDTH', 120);
                define('THUMBNAIL_IMAGE_MAX_HEIGHT', 120);
                
                $source_image_path = $_FILES['profile-picture-file']['tmp_name'];
                list($source_image_width, $source_image_height, $source_image_type) = getimagesize($source_image_path);
                switch ($source_image_type) {
                    case IMAGETYPE_GIF:
                        $source_gd_image = imagecreatefromgif($source_image_path);
                        break;
                    case IMAGETYPE_JPEG:
                        $source_gd_image = imagecreatefromjpeg($source_image_path);
                        break;
                    case IMAGETYPE_PNG:
                        $source_gd_image = imagecreatefrompng($source_image_path);
                        break;
                }
                if ($source_gd_image === false) {
                    return false;
                }
                $source_aspect_ratio = $source_image_width / $source_image_height;
                $thumbnail_aspect_ratio = THUMBNAIL_IMAGE_MAX_WIDTH / THUMBNAIL_IMAGE_MAX_HEIGHT;
                if ($source_image_width <= THUMBNAIL_IMAGE_MAX_WIDTH && $source_image_height <= THUMBNAIL_IMAGE_MAX_HEIGHT) {
                    $thumbnail_image_width = $source_image_width;
                    $thumbnail_image_height = $source_image_height;
                } elseif ($thumbnail_aspect_ratio > $source_aspect_ratio) {
                    $thumbnail_image_width = (int) (THUMBNAIL_IMAGE_MAX_HEIGHT * $source_aspect_ratio);
                    $thumbnail_image_height = THUMBNAIL_IMAGE_MAX_HEIGHT;
                } else {
                    $thumbnail_image_width = THUMBNAIL_IMAGE_MAX_WIDTH;
                    $thumbnail_image_height = (int) (THUMBNAIL_IMAGE_MAX_WIDTH / $source_aspect_ratio);
                }
                $thumbnail_gd_image = imagecreatetruecolor($thumbnail_image_width, $thumbnail_image_height);
                imagecopyresampled($thumbnail_gd_image, $source_gd_image, 0, 0, 0, 0, $thumbnail_image_width, $thumbnail_image_height, $source_image_width, $source_image_height);
                imagejpeg($thumbnail_gd_image, $_FILES['profile-picture-file']['tmp_name'], 90);
                imagedestroy($source_gd_image);
                imagedestroy($thumbnail_gd_image);
            }
            // ------- End Image Resizing -------
            
            //generate md5 hash from the contents of the uploaded resized image file
            $hash = hash_file('md5', $_FILES['profile-picture-file']['tmp_name']);
            //generate filepath to save file
            $destFileName = $hash . '.' . $extension;
            $destFilePath = "files/profile_picture/" . $destFileName;
            // ------- update SQL server and files -------
            if (!profile_picture_delete($cid)){
                return crm_url('contact&cid=' . $_POST['cid']);
            }
            $esc_cid = mysql_real_escape_string($cid);
            // Associate this CID with uploaded file by storing a cid=>filepath row in the profile_picture table
            $sql = "INSERT INTO `profile_picture` (`cid`, `filename`) VALUES ('$esc_cid', '$destFileName')";
                    $res = mysql_query($sql);
                    if (!$res) die(mysql_error());
                    
            //save the file. Literally just moving from /tmp/ to the right directory
            if(!move_uploaded_file($_FILES['profile-picture-file']['tmp_name'], $destFilePath)){
                error_register('Error Saving Image to Server');
                error_register('Tried moving: ' .  $_FILES['profile-picture-file']['tmp_name'] . 'to: ' . $destFilePath);
            } else {
              message_register("Successfully uploaded new user profile picture");  
            }
        }
    } else {
        error_register('Invalid file. Did you upload an image (gif, jpeg, jpg, png) that is less than 20mb?');
        error_register('File Type is: ' . $_FILES['profile-picture-file']['type']);
        error_register('File Size is: ' . $_FILES['profile-picture-file']['size'] / 1024 . "kB");
    } 
    return crm_url('contact&cid=' . $_POST['cid']);
}


?>
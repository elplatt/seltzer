<?php 

/*
    Copyright 2009-2014 Edward L. Platt <ed@elplatt.com>
    
    This file is part of the Seltzer CRM Project
    email_list.inc.php - Associates contact's emails with email lists

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
        if (user_access('contact_view')) {
        $pages[] = 'email_lists';
    }
    return $pages;
}

/**
 * Page hook.  Adds email_lists module content to a page before it is rendered.
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
            
            // Add email lists tab
            if (user_access('contact_view') || user_access('contact_edit') || user_access('contact_delete') || $cid == user_id()) {
                $email_lists = theme('table', 'email_lists', array('cid' => $cid));
                $email_lists .= theme('email_list_subscribe_form', $cid);
                page_add_content_bottom($page_data, $email_lists, 'Emails');
            }
            break;
        case 'email_lists':
            page_set_title($page_data, 'Email Lists');
            if (user_access('email_lists_view')) {
                $email_lists = theme('table', 'email_lists', array('join'=>array('contact', 'member'), 'show_export'=>true));
                page_add_content_top($page_data, $email_lists, 'View');
            }
            break;
    }
}


// DB to Object mapping ////////////////////////////////////////////////////////

/**
 * Return data for one or more email lists
 *
 * @param $opts An associative array of options, possible keys are:
 *   'lid' If specified, returns all contacts associated with this email list;
 *   'cid' If specified, returns all email lists subscribed to with specified id;
 *   'join' A list of tables to join to the key table.
 * @return An array with each element representing a single key card assignment.
*/ 
function email_list_data ($opts = array()) {
    // Query database
    $sql = "
        SELECT
        `lid`
        , `cid`
        , `email`
        FROM `email_list_associations`
        WHERE 1";
    if (!empty($opts['lid'])) {
        $esc_lid = mysql_real_escape_string($opts['lid']);
        $sql .= " AND `lid`='$esc_lid'";
    }
    if (!empty($opts['cid'])) {
        if (is_array($opts['cid'])) {
            $terms = array();
            foreach ($opts['cid'] as $cid) {
                $esc_cid = mysql_real_escape_string($cid);
                $terms[] = "'$cid'";
            }
            $sql .= " AND `cid` IN (" . implode(', ', $terms) . ") ";
        } else {
            $esc_cid = mysql_real_escape_string($opts['cid']);
            $sql .= " AND `cid`='$esc_cid'";
        }
    }
  
    $sql .= "
        ORDER BY `lid`, `cid` ASC";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    // Store data
    $email_list_associations = array();
    $row = mysql_fetch_assoc($res);
    while (!empty($row)) {
        // Contents of row are lid, cid, email
        $email_list_associations[] = $row;
        $row = mysql_fetch_assoc($res);
    }
    // Return data
    return $email_list_associations;
}

/**
 * Implementation of hook_data_alter().
 * @param $type The type of the data being altered.
 * @param $data An array of structures of the given $type.
 * @param $opts An associative array of options.
 * @return An array of modified structures.
 */
function email_list_data_alter ($type, $data = array(), $opts = array()) {
    switch ($type) {
        case 'contact':
            // Get cids of all contacts passed into $data
            $cids = array();
            foreach ($data as $contact) {
                $cids[] = $contact['cid'];
            }
            // Add the cids to the options
            $email_list_opts = $opts;
            $email_list_opts['cid'] = $cids;
            // Get an array of email list subscriptions for each cid
            $email_list_data = crm_get_data('email_list', $email_list_opts);
            // Create a map from cid to an array of key structures
            $cid_to_email_lists = array();
            foreach ($email_list_data as $subscription) {
                $cid_to_email_lists[$subscription['cid']][] = $subscription;
            }
            // Add key structures to the contact structures
            foreach ($data as $i => $contact) {
                if (array_key_exists($contact['cid'], $cid_to_email_lists)) {
                    $email_lists = $cid_to_email_lists[$contact['cid']];
                    $data[$i]['email_lists'] = $email_lists;
                }
            }
            break;
    }
    return $data;
}

/**
 * Save an email list subscription structure.  If $subscription has a 'lid' element, an existing key will
 * be updated, otherwise a new key will be created.
 * @param $subscription The subscription structure
 * @return The key structure with as it now exists in the database.
 */
function email_list_save ($subscription) {
    // Escape values
    $fields = array('lid', 'cid', 'serial', 'slot', 'start', 'end');
    if (isset($subscription['kid'])) {
        // Update existing key
        $lid = $subscription['lid'];
        $esc_lid = mysql_real_escape_string($lid);
        $clauses = array();
        foreach ($fields as $k) {
            if ($k == 'end' && empty($subscription[$k])) {
                continue;
            }
            if (isset($subscription[$k]) && $k != 'kid') {
                $clauses[] = "`$k`='" . mysql_real_escape_string($subscription[$k]) . "' ";
            }
        }
        $sql = "UPDATE `email_list_subscriptions` SET " . implode(', ', $clauses) . " ";
        $sql .= "WHERE `lid`='$esc_lid'";
        $res = mysql_query($sql);
        if (!$res) die(mysql_error());
        message_register('Key updated');
    } else {
        // Insert new key
        $cols = array();
        $values = array();
        foreach ($fields as $k) {
            if (isset($subscription[$k])) {
                if ($k == 'end' && empty($subscription[$k])) {
                    continue;
                }
                $cols[] = "`$k`";
                $values[] = "'" . mysql_real_escape_string($subscription[$k]) . "'";
            }
        }
        $sql = "INSERT INTO `email_list_subscriptions` (" . implode(', ', $cols) . ") ";
        $sql .= " VALUES (" . implode(', ', $values) . ")";
        $res = mysql_query($sql);
        if (!$res) die(mysql_error());
        message_register('Email list subscription added');
    }
    //return crm_get_one('email_list', array('kid'=>$kid));
}

/**
 * Delete an email list.
 * @param $lid The list id of the list to delete
 */
function email_list_delete ($lid) {
    $esc_lid = mysql_real_escape_string($lid);
    $sql = "DELETE FROM `email_lists` WHERE `lid`='$esc_lid'";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    if (mysql_affected_rows() > 0) {
        message_register('List deleted.');
    }
    //TODO: also delete any subscriptions that are associated with this list.
    
}


// Table data structures ///////////////////////////////////////////////////////

/**
 * Return a table structure for a table of email lists subscriptions.
 *
 * @param $opts The options to pass to email_list_data().
 * @return The table structure.
*/
function email_lists_table ($opts) {
    // Determine settings
    $export = false;
    foreach ($opts as $option => $value) {
        switch ($option) {
            case 'export':
                $export = $value;
                break;
        }
    }
    // Get email list data
    $data = crm_get_data('email_list', $opts);
    if (count($data) < 1) {
        return array();
    }
    // Get contact info
    $contact_opts = array();
    foreach ($data as $row) {
        $contact_opts['cid'][] = $row['cid'];
    }
    $contact_data = crm_get_data('contact', $contact_opts);
    $cid_to_contact = crm_map($contact_data, 'cid');
    // Initialize table
    $table = array(
        "id" => '',
        "class" => '',
        "rows" => array(),
        "columns" => array()
    );
    // Add columns
    if (user_access('contact_view') || $opts['cid'] == user_id()) {
        if ($export) {
            $table['columns'][] = array("title"=>'cid', 'class'=>'', 'id'=>'');
            $table['columns'][] = array("title"=>'lid'. 'class'=>'', 'id'=>'');
        }
        $table['columns'][] = array("title"=>'Name', 'class'=>'', 'id'=>'');
        $table['columns'][] = array("title"=>'Email', 'class'=>'', 'id'=>'');
        $table['columns'][] = array("title"=>'List', 'class'=>'', 'id'=>'');
    }
    // Add ops column
    if (!$export && (user_access('contact_edit') || user_access('contact_delete'))) {
        $table['columns'][] = array('title'=>'Ops','class'=>'');
    }
    // Add rows
    foreach ($data as $subscription) {
        // Add list data
        $row = array();
        if (user_access('contact_view') || $opts['cid'] == user_id()) {
            // Add cells
            if ($export) {
                $row[] = $subscription['cid'];
                $row[] = $subscription['lid'];
            }
            $row[] = theme('contact_name', $cid_to_contact[$subscription['cid']], !$export);
            $row[] = $subscription['email'];
            //fetch list_name here.
            $row[] = $key['list_name'];
        }
        if (!$export && (user_access('contact_edit') || user_access('contact_delete'))) {
            // Construct ops array
            $ops = array();
            // Add edit op
            if (user_access('contact_edit')) {
                //TODO: not sure what to do here quite yet.
                //$ops[] = '<a href=' . crm_url('key&kid=' . $key['kid'] . '#tab-edit') . '>edit</a> ';
            }
            // Add delete op
            if (user_access('contact_delete')) {
                //$ops[] = '<a href=' . crm_url('delete&type=key&id=' . $key['kid']) . '>delete</a>';
            }
            // Add ops row
            $row[] = join(' ', $ops);
        }
        $table['rows'][] = $row;
    }
    return $table;
}




// Forms ///////////////////////////////////////////////////////////////////////

/**
 * @return Array mapping payment method values to descriptions.
 */
function email_list_options () {
    $options = array();
    
    //populate an array with available email list options.
    //it should end up looking something like this:
    //    $options[0] = 'Announce';
    //    $options[1] = 'Members Only';
    //    $options[2] = 'Public';
    
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
        // Contents of row are list_name
        $keys[] = $row;
        $row = mysql_fetch_assoc($res);
    }
    
    // put email lists into an options array
    foreach ($keys as $key){
        $options[] = $key['list_name'];
    }

    return $options;
}

/**
 * @return an email subscribe form structure.
 */
function email_list_subscribe_form ($cid) {
    return array(
        'type' => 'form'
        , 'method' => 'post'
        , 'enctype' => 'multipart/form-data'
        , 'command' => 'email_list_subscribe'
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
                        , 'value' => 'Use this form to subscribe a contact to an email list'
                    )
                    ,array(
                        'type' => 'text'
                        , 'label' => 'Email List'
                        , 'name' => 'list_name'
                        , 'options' => email_list_options()
                    )
                    , array(
                        // TODO: by default, pre-populate this field w/ member's existing email
                        'type' => 'text'
                        , 'label' => 'Email'
                        , 'name' => 'email'
                    )
                    , array(
                        'type' => 'submit'
                        , 'value' => 'Subscribe'
                    )
                )
            )
        )
    );
}

/**
 * @return an email list create form structure.
 */
function email_list_create_form ($cid) {
    return array(
        'type' => 'form'
        , 'method' => 'post'
        , 'enctype' => 'multipart/form-data'
        , 'command' => 'email_list_create'
        , 'hidden' => array(
            'cid' => $cid
        )
        , 'fields' => array(
            array(
                'type' => 'fieldset'
                , 'label' => 'Create Email List'
                , 'fields' => array(
                    array(
                        'type' => 'message'
                        , 'value' => 'Use this form to create a new email list'
                    )
                    ,array(
                        'type' => 'text'
                        , 'label' => 'Email List Name'
                        , 'name' => 'list_name'
                    )
                    , array(
                        'type' => 'submit'
                        , 'value' => 'Create'
                    )
                )
            )
        )
    );
}


/**
 * Handle email list subscribe request.
 *
 * @return The url to display on completion.
 */
function command_email_list_subscribe () {
    $cid = $_POST['cid'];
    $email = $_POST['email'];
    $lid = $_POST['lid'];
    
    //qualify the email as a valid email.
    if (/* email is valid */ true) {
        //save the email
        $esc_email = mysql_real_escape_string($email);
        $sql = "INSERT INTO `email_list_associations` (`lid`, `cid`, `email`) VALUES ('$lid', '$cid', '$esc_email')";
        $res = mysql_query($sql);
        if (!$res) die(mysql_error());
        
        message_register('Successfully subscribed user to email list.');
    } else {
        error_register('Invalid email. Check email syntax.');

    } 
    return crm_url('contact&cid=' . $cid);
}

/**
 * Handle email list creation request.
 *
 * @return The url to display on completion.
 */
function command_email_list_create () {
    // list_name from _POST 
    $list_name = $_POST['list_name'];
    
    //escape list name
    $esc_list_name = mysql_real_escape_string($list_name);
    
    //insert into table
    $sql = "INSERT INTO `email_lists` (`list_name`) VALUES ('$esc_list_name')";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    
    message_register('Email List created');
    return crm_url('email_lists');
}

?>
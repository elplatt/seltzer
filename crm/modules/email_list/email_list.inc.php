<?php 

/*
    Copyright 2009-2014 Edward L. Platt <ed@elplatt.com>
    
    This file is part of the Seltzer CRM Project
    email_list.inc.php - Associates contact's emails with email lists

    Seltzer is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    any later version.
=
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

/**
 * @return An array of the permissions provided by this module.
 */
function email_list_permissions () {
    return array(
        'email_list_view'
        , 'email_list_edit'
        , 'email_list_delete'
        , 'email_list_subscribe'
        , 'email_list_unsubscribe'
        , 'email_list_edit_subscription'
    );
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
            CREATE TABLE IF NOT EXISTS `email_list_subscriptions` (
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
              `lid` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
              `list_name` varchar(255) NOT NULL,
              PRIMARY KEY (`lid`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
        ';
        $res = mysql_query($sql);
        if (!$res) die(mysql_error());
        
        //Set Permissions
        $roles = array(
            '1' => 'authenticated'
            , '2' => 'member'
            , '3' => 'director'
            , '4' => 'president'
            , '5' => 'vp'
            , '6' => 'secretary'
            , '7' => 'treasurer'
            , '8' => 'webAdmin'
        );
        $default_perms = array(
            'director' => array('email_list_view', 'email_list_edit', 'email_list_delete'
                                , 'email_list_subscribe', 'email_list_unsubscribe'
                                , 'email_list_edit_subscription')
            , 'webAdmin' => array('email_list_view', 'email_list_edit', 'email_list_delete'
                                  , 'email_list_subscribe', 'email_list_unsubscribe'
                                  , 'email_list_edit_subscription')
        );
        foreach ($roles as $rid => $role) {
            $esc_rid = mysql_real_escape_string($rid);
            if (array_key_exists($role, $default_perms)) {
                foreach ($default_perms[$role] as $perm) {
                    $esc_perm = mysql_real_escape_string($perm);
                    $sql = "INSERT INTO `role_permission` (`rid`, `permission`) VALUES ('$esc_rid', '$esc_perm')";
                    $res = mysql_query($sql);
                    if (!$res) die(mysql_error());
                }
            }
        }
    }
}
// Pages ///////////////////////////////////////////////////////////////////////

/**
 * @return An array of pages provided by this module.
 */
function email_list_page_list () {
    $pages = array();
        if (user_access('email_list_view')) {
        $pages[] = 'email_lists';
        $pages[] = 'email_lists/unsubscribe';
    }
    return $pages;
}

/**
 * Page hook.  Adds email_lists module content to a page before it is rendered.
 *
 * @param &$page_data Reference to data about the page being rendered.
 * @param $page_name The name of the page being rendered.
*/
function email_list_page (&$page_data, $page_name, $options) {
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
            if (user_access('contact_view') || user_access('contact_edit') 
                    || user_access('contact_delete') || $cid == user_id()) {
                $email_lists = theme('table', crm_get_table('email_list_subscriptions', array('cid' => $cid)));
                $email_lists .= theme('form', crm_get_form('email_list_subscribe', $cid));
                page_add_content_bottom($page_data, $email_lists, 'Emails');
            }
            break;
        case 'email_lists':
            page_set_title($page_data, 'Email Lists');
            if (user_access('contact_view')) {
                $email_lists = theme('table', 'email_list'
                    , array('join'=>array('contact', 'member'), 'show_export'=>false
                        , 'lists_only'=>true));
                $email_lists .= theme('form', crm_get_form('email_list_create'));
                page_add_content_top($page_data, $email_lists, 'View');
            }
            break;
        case 'email_list/unsubscribe':
            // Capture contact id and lid
            $cid = $_GET['cid'];
            $lid = $_GET['lid'];
            if (empty($cid) || empty($lid)) {
                return;
            }
            page_set_title($page_data, 'Email Lists');
                if (user_access('email_list_unsubscribe')) {
                    $email_lists = theme('form', crm_get_form('email_list_unsubscribe', array('cid'=>$cid, 'lid'=>$lid)));
                    page_add_content_top($page_data, $email_lists);
                }
            break;
        case 'email_list':
            $lid = $options['lid'];
            if(empty($lid)) {
                return;
            }
            //Set page title
            page_set_title($page_data, email_list_description($lid));
            // Add edit tab
            if (user_access('email_list_view') || user_access('email_list_edit')) {
                page_add_content_top($page_data, theme('form', crm_get_form('email_list_edit', $lid), 'Edit'));
            }
            break;
    }
}

// Utility functions ///////////////////////////////////////////////////////////

/**
 * Generate a descriptive string for a single email list.
 *
 * @param $lid The lid of the email list to describe.
 * @return The description string.
 */
function email_list_description ($lid) {
    // Get key data
    $data = crm_get_data('email_list', array('lid' => $lid, 'lists_only'=>true));
    if (empty($data)) {
        return 'ERROR: NULL LIST';
    }
    $list = $data[0];
    
    // Construct description
    $description = 'Email List: ';
    $description .= $list['list_name'];
    
    return $description;
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
    // Query database for subscriptions
    if(!empty($opts['lists_only'])){
        // This block queries only the lists themselves. It does not return
        // the users who are subscribed to the lists.
        $sql = "
            SELECT
            `email_lists`.`lid`
            , `email_lists`.`list_name`
            FROM `email_lists`
            WHERE 1";
        if (!empty($opts['lid'])) {
            $esc_lid = mysql_real_escape_string($opts['lid']);
            $sql .= " AND `email_lists`.`lid`='$esc_lid'";
        }
        $sql .= "
            ORDER BY `email_lists`.`lid` ASC";
    } else {
        // This block also queries users who are subscribed to the lists
        // in addition to the lists themselves
        $sql = "
            SELECT
            `email_lists`.`lid`
            , `email_lists`.`list_name`
            , `cid`
            , `email`
            FROM `email_list_subscriptions`
            INNER JOIN `email_lists`
            ON `email_list_subscriptions`.`lid`=`email_lists`.`lid`
            WHERE 1";
        if (!empty($opts['lid'])) {
            $esc_lid = mysql_real_escape_string($opts['lid']);
            $sql .= " AND `email_lists`.`lid`='$esc_lid'";
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
            ORDER BY `email_lists`.`lid`, `cid` ASC";
    }
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    // Store data
    $email_list_data = array();
    $row = mysql_fetch_assoc($res);
    while (!empty($row)) {
        // Contents of row are lid, cid, email
        $email_list_data[] = $row;
        $row = mysql_fetch_assoc($res);
    }
    // Return data
    return $email_list_data;
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
            // Create a map from cid to an array of list subscriptions
            $cid_to_email_lists = array();
            foreach ($email_list_data as $subscription) {
                $cid_to_email_lists[$subscription['cid']][] = $subscription;
            }
            // Add email subscription structures to the contact structures
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
 * Save an email list list structure.  If $list has a 'lid' element, an existing email list will
 * be updated, otherwise a new email list will be created.
 * @param $list The list structure
 * @return The key structure with as it now exists in the database.
 */
function email_list_save ($list) {
    // Escape values
    $fields = array('lid', 'list_name');
    if (isset($list['lid'])) {
        // Update existing email list
        $lid = $list['lid'];
        $esc_lid = mysql_real_escape_string($lid);
        $clauses = array();
        foreach ($fields as $k) {
            if (isset($list[$k]) && $k != 'lid') {
                $clauses[] = "`$k`='" . mysql_real_escape_string($list[$k]) . "' ";
            }
        }
        $sql = "UPDATE `email_lists` SET " . implode(', ', $clauses) . " ";
        $sql .= "WHERE `lid`='$esc_lid'";
        $res = mysql_query($sql);
        if (!$res) die(mysql_error());
        message_register('Email list updated');
    } else {
        // Insert new email list
        $cols = array();
        $values = array();
        foreach ($fields as $k) {
            if (isset($list[$k])) {
                $cols[] = "`$k`";
                $values[] = "'" . mysql_real_escape_string($list[$k]) . "'";
            }
        }
        $sql = "INSERT INTO `email_lists` (" . implode(', ', $cols) . ") ";
        $sql .= " VALUES (" . implode(', ', $values) . ")";
        $res = mysql_query($sql);
        if (!$res) die(mysql_error());
        message_register('Email list created');
    }
    //return crm_get_one('email_list', array('kid'=>$kid));
}

/**
 * Delete an email list.
 * @param $list the list data structure to delete, must have a 'lid' element.
 */
function email_list_delete ($list) {
    $esc_lid = mysql_real_escape_string($list['lid']);
    $sql = "DELETE FROM `email_lists` WHERE `lid`='$esc_lid'";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    if (mysql_affected_rows() > 0) {
        message_register('List deleted.');
    }
    
    //delete any subscriptions that are associated with this list.
    $sql = "DELETE FROM `email_list_subscriptions` WHERE `lid`='$esc_lid'";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    if (mysql_affected_rows() > 0) {
        message_register('Associated subscriptions deleted.');
    }
}

/**
 * Delete a subscription.
 * @param $subscription The subscription structure to delete, must have both 'cid' and 'lid' element.
 */
function email_list_unsubscribe ($subscription) {
    $esc_lid = mysql_real_escape_string($subscription['lid']);
    $esc_cid = mysql_real_escape_string($subscription['cid']);
    
    $sql = "DELETE FROM `email_list_subscriptions` WHERE `lid`='$esc_lid' AND `cid`='$esc_cid'";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    if (mysql_affected_rows() > 0) {
        message_register('Subscription deleted.');
    } if (mysql_affected_rows() > 1){
        error_register("More than one subscription was deleted. This shouldn't happen
                       under normal circumstances. Please make sure your database is okay!");
    }
}

// Table data structures ///////////////////////////////////////////////////////

/**
 * Return a table structure for a table of email lists.
 *
 * @param $opts The options to pass to email_list_data().
 * @return The table structure.
*/
function email_list_table ($opts) {
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
            $table['columns'][] = array("title"=>'lid', 'class'=>'', 'id'=>'');
        }
        $table['columns'][] = array("title"=>'List Name', 'class'=>'', 'id'=>'');
    }
    // Add ops column
    if (!$export && (user_access('email_list_edit') || user_access('email_list_delete'))) {
        $table['columns'][] = array('title'=>'Ops','class'=>'');
    }
    // Add rows
    foreach ($data as $list) {
        // Add list data
        $row = array();
        if (user_access('email_list_view') || $opts['cid'] == user_id()) {
            // Add cells
            if ($export) {
                $row[] = $list['lid'];
            }
            //fetch list_name here.
            $row[] = $list['list_name'];
        }
        if (!$export && (user_access('email_list_edit') || user_access('email_list_delete'))) {
            // Construct ops array
            $ops = array();
            // Add edit op
            if (user_access('email_list_edit')) {
                $ops[] = '<a href=' . crm_url('email_list&lid=' . $list['lid'] . '#tab-edit') . '>edit</a> ';
            }
            // Add delete op
            if (user_access('email_list_delete')) {
                $ops[] = '<a href=' . crm_url('delete&type=email_list&id=' . $list['lid']) . '>delete</a>';
            }
            // Add ops row
            $row[] = join(' ', $ops);
        }
        $table['rows'][] = $row;
    }
    return $table;
}

/**
 * Return a table structure for a table of email lists subscriptions.
 *
 * @param $opts The options to pass to email_list_data().
 * @return The table structure.
*/
function email_list_subscriptions_table ($opts) {
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
            $table['columns'][] = array("title"=>'lid', 'class'=>'', 'id'=>'');
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
        if (user_access('email_list_view') || $opts['cid'] == user_id()) {
            // Add cells
            if ($export) {
                $row[] = $subscription['cid'];
                $row[] = $subscription['lid'];
            }
            $row[] = theme('contact_name', $cid_to_contact[$subscription['cid']], !$export);
            $row[] = $subscription['email'];
            //fetch list_name here.
            $row[] = $subscription['list_name'];
        }
        if (!$export && (user_access('email_list_unsubscribe'))) {
            // Construct ops array
            $ops = array();
            // Add unsubscribe op
            if (user_access('email_list_unsubscribe')) {
                $ops[] = '<a href=' . crm_url('email_list/unsubscribe&cid=' 
                    . $subscription['cid']) . '&lid=' . $subscription['lid']. '>unsubscribe</a>';
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
        `lid`
        ,`list_name`
        FROM `email_lists`
        WHERE 1
    ';
    $sql .= "
        ORDER BY `list_name`, `lid` ASC
    ";
    
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    // Store data
    $lists = array();
    $row = mysql_fetch_assoc($res);
    while (!empty($row)) {
        // Contents of row are lid, list_name
        $lists[] = $row;
        $row = mysql_fetch_assoc($res);
    }
    
    // put email lists into an options array
    foreach ($lists as $list){
        $options[$list['lid']] = $list['list_name'];
    }
    return $options;
}

/**
 * @return an email subscribe form structure.
 */
function email_list_subscribe_form ($cid) {
    $contact_data = crm_get_data('contact', array('cid'=>$cid));
    $contact = $contact_data[0];
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
                        'type' => 'select'
                        , 'label' => 'Email List'
                        , 'name' => 'lid'
                        , 'options' => email_list_options()
                    )
                    , array(
                        // By default, pre-populate this field with member's existing email
                        'type' => 'text'
                        , 'label' => 'Email'
                        , 'name' => 'email'
                        , 'value' => $contact['email']
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
 * Return the email list unsubscribe form structure.
 *
 * @param $subscription The the subscription to delete. Must have both 'lid' and 'cid' element
 * @return The form structure.
*/
function email_list_unsubscribe_form ($subscription) {
    // Ensure user is allowed to unsubscribe
    if (!user_access('email_list_unsubscribe')) {
        error_register('User does not have permission: email_list_unsubscribe');
        return NULL;
    }
    
    // Get subscription data    
    $data = crm_get_data('email_list', array('lid'=>$subscription['lid']
        , 'cid'=>$subscription['cid']));
    $subscription = $data[0];
    
    // Construct email list subscription name
    $subscription_name = "email:" . $subscription['email'] ." from list:"
        . $subscription['list_name'];
    
    // Create form structure
    $form = array(
        'type' => 'form',
        'method' => 'post',
        'command' => 'email_list_unsubscribe',
        'hidden' => array(
            'lid' => $subscription['lid'],
            'cid' => $subscription['cid']
        ),
        'fields' => array(
            array(
                'type' => 'fieldset',
                'label' => 'Email List Unsubscribe',
                'fields' => array(
                    array(
                        'type' => 'message',
                        'value' => '<p>Are you sure you want to unsubscribe "' 
                        . $subscription_name . '"? This cannot be undone.',
                    ),
                    array(
                        'type' => 'submit',
                        'value' => 'Unsubscribe'
                    )
                )
            )
        )
    );
    return $form;
}

/**
 * @return an email list create form structure.
 */
function email_list_create_form () {
    $form = array(
        'type' => 'form'
        , 'method' => 'post'
        , 'enctype' => 'multipart/form-data'
        , 'command' => 'email_list_create'
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
    return $form;
}

/**
 * @return an email list edit form structure.
 * @param $lid the lid of the email list to edit
 * @return The form structure.
 */
function email_list_edit_form ($lid) {
    if (!user_access('email_list_edit')) {
        error_register('User does not have permission: email_list_edit');
        return NULL;
    }
    
    // Get email list data
    $data = crm_get_data('email_list', array('lid'=>$lid, 'lists_only'=>true));
    if (count($data) < 1) {
        return array();
    }
    $list = $data[0];
    
    return array(
        'type' => 'form'
        , 'method' => 'post'
        , 'enctype' => 'multipart/form-data'
        , 'command' => 'email_list_edit'
        , 'hidden' => array(
            'lid' => $lid
        ),
        'fields' => array(
            array(
                'type' => 'fieldset'
                , 'label' => 'Edit Email List'
                , 'fields' => array(
                    array(
                        'type' => 'message'
                        , 'value' => 'Use this form to edit an email list'
                    )
                    ,array(
                        'type' => 'text'
                        , 'label' => 'Email List Name'
                        , 'name' => 'list_name'
                        , 'value' => $list['list_name']
                    )
                    , array(
                        'type' => 'submit'
                        , 'value' => 'Update'
                    )
                )
            )
        )
    );
    return $form;
}

/**
 * Return the delete email_list  form structure.
 *
 * @param $lid The lid of the key assignment to delete.
 * @return The form structure.
*/
function email_list_delete_form ($lid) {
    
    // Ensure user is allowed to delete keys
    if (!user_access('email_list_delete')) {
        error_register('User does not have permission: email_list_delete');
        return NULL;
    }
    
    // Get email list data
    $data = crm_get_data('email_list', array('lid'=>$lid, 'lists_only'=>true));
    $list = $data[0];
    
    // Construct key name
    $list_name = $list['list_name'];
    
    // Create form structure
    $form = array(
        'type' => 'form',
        'method' => 'post',
        'command' => 'email_list_delete',
        'hidden' => array(
            'lid' => $list['lid']
        ),
        'fields' => array(
            array(
                'type' => 'fieldset',
                'label' => 'Delete List',
                'fields' => array(
                    array(
                        'type' => 'message',
                        'value' => '<p>Are you sure you want to delete the email list "' . $list_name . '"? This cannot be undone.',
                    ),
                    array(
                        'type' => 'submit',
                        'value' => 'Delete'
                    )
                )
            )
        )
    );
    return $form;
}

// Command handlers ////////////////////////////////////////////////////////////

/**
 * Handle email list subscribe request.
 *
 * @return The url to display on completion.
 */
function command_email_list_subscribe () {
    $cid = $_POST['cid'];
    $email = $_POST['email'];
    $lid = $_POST['lid'];
    
    //TODO: Qualify the email as a valid email.
    if (/* email is valid */ true) {
        //save the email
        $esc_email = mysql_real_escape_string($email);
        $sql = "INSERT INTO `email_list_subscriptions` (`lid`, `cid`, `email`)
            VALUES ('$lid', '$cid', '$esc_email')";
        $res = mysql_query($sql);
        if (!$res) die(mysql_error());
        
        message_register('Successfully subscribed user to email list.');
    } else {
        error_register('Invalid email. Check email syntax.');
    } 
    return crm_url('contact&cid=' . $cid);
}

/**
 * Handle email list unsubscribe request.
 *
 * @return The url to display on completion.
 */
function command_email_list_unsubscribe () {
    global $esc_post;
    
    // Verify permissions
    if (!user_access('email_list_unsubscribe')) {
        error_register('Permission denied: email_list_unsubscribe');
        return crm_url('contact&cid=' . $esc_post['cid']);
    }
    email_list_unsubscribe($_POST);
    return crm_url('contact&cid=' . $esc_post['cid'] . '#tab-emails');
}

/**
 * Handle email list creation request.
 *
 * @return The url to display on completion.
 */
function command_email_list_create () {
    //verify permissions first
    if (!user_access('email_list_edit')) {
        error_register('Permission denied: email_list_edit');
        return crm_url('email_lists');
    }
    email_list_save($_POST);
    return crm_url('email_lists');
}

/**
 * Handle email list cpdate request.
 *
 * @return The url to display on completion.
 */
function command_email_list_edit () {
    //verify permissions first
    if (!user_access('email_list_edit')) {
        error_register('Permission denied: email_list_edit');
        return crm_url('email_lists');
    }
    email_list_save($_POST);
    return crm_url('email_lists&lid=' . $_POST['lid'] . '&tab=edit');
}

/**
 * Handle email list delete request.
 *
 * @return The url to display on completion.
 */
function command_email_list_delete () {
    global $esc_post;
    //verify permissions first
    if (!user_access('email_list_delete')) {
        error_register('Permission denied: email_list_delete');
        return crm_url('email_lists');
    }
    email_list_delete($_POST);
    return crm_url('email_lists');
}

// Themeing ////////////////////////////////////////////////////////////////////

/**
 * Return the themed html for an email list creation form.
 * 
 * @return The themed html string.
 */
function theme_email_list_create_form () {
    return theme('form', crm_get_form('email_list_create'));
}

/**
 * Return themed html for an edit list form.
 *
 * @param $kid The kid of the key assignment to edit.
 * @return The themed html string.
 */
function theme_email_list_edit_form ($kid) {
    return theme('form', crm_get_form('email_list_edit', $lid));
}

/**
 * Return themed html for an email list subscribe form.
 *
 * @param $cid The cid of the contact to subscribe
 * @return The themed html string.
 */
function theme_email_list_subscribe_form ($cid) {
    return theme('form', crm_get_form('email_list_subscribe', $cid));
}

/**
 * Return themed html for an email list unsubscribe form.
 *
 * @param $cid The cid of the contact to subscribe
 * @return The themed html string.
 */
function theme_email_list_unsubscribe_form ($subscription) {
    return theme('form', crm_get_form('email_list_unsubscribe', $subscription));
}
?>
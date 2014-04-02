<?php 

/*
    Copyright 2009-2014 Edward L. Platt <ed@elplatt.com>
    
    This file is part of the Seltzer CRM Project
    contact.inc.php - Defines contact entity

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
function contact_revision () {
    return 1;
}

/**
 * @return Array of paths to stylesheets relative to this module's directory.
 */
function contact_stylesheets () {
}

/**
 * @return An array of the permissions provided by this module.
 */
function contact_permissions () {
    return array(
        'contact_view'
        , 'contact_add'
        , 'contact_edit'
        , 'contact_delete'
    );
}

// Installation functions //////////////////////////////////////////////////////

/**
 * Install or upgrade this module.
 * @param $old_revision The last installed revision of this module, or 0 if the
 *   module has never been installed.
 */
function contact_install ($old_revision = 0) {
    if ($old_revision < 1) {
        $sql = '
            CREATE TABLE IF NOT EXISTS `contact` (
              `cid` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
              `firstName` varchar(255) NOT NULL,
              `middleName` varchar(255) NOT NULL,
              `lastName` varchar(255) NOT NULL,
              `email` varchar(255) NOT NULL,
              `phone` varchar(32) NOT NULL,
              `emergencyName` varchar(255) NOT NULL,
              `emergencyPhone` varchar(16) NOT NULL,
              PRIMARY KEY (`cid`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
        ';
        $res = mysql_query($sql);
        if (!$res) crm_error(mysql_error());
    }
}

// DB to Object mapping ////////////////////////////////////////////////////////

/**
 * Return data for one or more contacts.
 * 
 * @param $opts An associative array of options, possible keys are:
 *   'cid' A cid or array of cids to return contacts for.
 *   'filter' An array mapping filter names to filter values
 * @return An array with each element representing a contact.
*/ 
function contact_data ($opts = array()) {
    // Query database
    $sql = "
        SELECT * FROM `contact`
        WHERE 1 ";
    // Add contact id
    if (isset($opts['cid'])) {
        if (is_array($opts['cid'])) {
            if (!empty($opts['cid'])) {
                $terms = array();
                foreach ($opts['cid'] as $cid) {
                    $terms[] = "'" . mysql_real_escape_string($cid) . "'";
                }
                $esc_list = '(' . implode(',', $terms) . ')';
                $sql .= " AND `cid` IN $esc_list";
            }
        } else {
            $esc_cid = mysql_real_escape_string($opts['cid']);
            $sql .= " AND `cid`='$esc_cid'";
        }
    }
    // Add filters
    if (isset($opts['filter'])) {
        foreach ($opts['filter'] as $name => $param) {
            switch ($name) {
                case 'nameLike':                    
                    // Split on first comma and create an array of name parts in "first middle last" order
                    $parts = explode(',', $param, 2);
                    $names = array();
                    foreach (array_reverse($parts) as $part) {
                        $nameParts = preg_split('/\s+/', $part);
                        foreach ($nameParts as $name) {
                            if (!empty($name)) {
                               $names[] = mysql_real_escape_string($name);
                            }
                        }
                    }
                    // Set where clauses based on number of name segments given
                    if (sizeof($names) === 1) {
                        $sql .= "AND (`firstName` LIKE '%$names[0]%' OR `middleName` LIKE '%$names[0]%' OR `lastName` LIKE '%$names[0]%') ";
                    } else if (sizeof($names) === 2) {
                        $sql .= "
                            AND (
                                (`firstName` LIKE '%$names[0]%' AND (`middleName` LIKE '%$names[1]%' OR `lastName` LIKE '%$names[1]%'))
                                OR (`middleName` LIKE '%$names[0]%' AND `lastName` LIKE '%$names[1]%')
                            )
                        ";
                    } else if (sizeof($names) === 3) {
                        $sql .= "
                            AND `firstName` LIKE '%$names[0]%'
                            AND `middleName` LIKE '%$names[1]%'
                            AND `lastName` LIKE '%$names[2]%'
                        ";
                    }
                    break;
                default:
                break;
            }
        }
    }
    $sql .= "
        ORDER BY `lastName`, `firstName`, `middleName` ASC";
    $res = mysql_query($sql);
    if (!$res) crm_error(mysql_error());
    // Store data
    $contacts = array();
    $row = mysql_fetch_assoc($res);
    while (!empty($row)) {
        $contacts[] = array(
            'cid' => $row['cid'],
            'firstName' => $row['firstName'],
            'middleName' => $row['middleName'],
            'lastName' => $row['lastName'],
            'email' => $row['email'],
            'phone' => $row['phone'],
            'emergencyName' => $row['emergencyName'],
            'emergencyPhone' => $row['emergencyPhone']
        );
        $row = mysql_fetch_assoc($res);
    }
    // Return data
    return $contacts;
}

/**
 * Saves a contact.
 */
function contact_save ($contact) {
    $fields = array('cid', 'firstName', 'middleName', 'lastName', 'email', 'phone', 'emergencyName', 'emergencyPhone');
    $escaped = array();
    foreach ($fields as $field) {
        $escaped[$field] = mysql_real_escape_string($contact[$field]);
    }
    if (isset($contact['cid'])) {
        // Update contact
        $sql = "
            UPDATE `contact`
            SET `firstName`='$escaped[firstName]'
                , `middleName`='$escaped[middleName]'
                , `lastName`='$escaped[lastName]'
                , `email`='$escaped[email]'
                , `phone`='$escaped[phone]'
                , `emergencyName`='$escaped[emergencyName]'
                , `emergencyPhone`='$escaped[emergencyPhone]'
            WHERE `cid`='$escaped[cid]'
        ";
        $res = mysql_query($sql);
        if (!$res) crm_error(mysql_error());
        if (mysql_affected_rows() < 1) {
            return null;
        }
        $contact = module_invoke_api('contact', $contact, 'update');
    } else {
        // Add contact
        $sql = "
            INSERT INTO `contact`
            (`firstName`,`middleName`,`lastName`,`email`,`phone`,`emergencyName`,`emergencyPhone`)
            VALUES
            ('$escaped[firstName]','$escaped[middleName]','$escaped[lastName]','$escaped[email]','$escaped[phone]','$escaped[emergencyName]','$escaped[emergencyPhone]')";
        $res = mysql_query($sql);
        if (!$res) crm_error(mysql_error());
        $contact['cid'] = mysql_insert_id();
        $contact = module_invoke_api('contact', $contact, 'create');
    }
    return $contact;
}

/**
 * Delete a contact.
 * @param $cid The contact id.
 */
function contact_delete ($cid) {
    $contact = crm_get_one('contact', array('cid'=>$cid));
    if (empty($contact)) {
        error_register("No contact with cid $cid");
        return;
    }
    // Notify other modules the contact is being deleted
    $contact = module_invoke_api('contact', $contact, 'delete');
    // Remove the contact from the database
    $esc_cid = mysql_real_escape_string($cid);
    $sql = "DELETE FROM `contact` WHERE `cid`='$esc_cid'";
    $res = mysql_query($sql);
    if (!$res) crm_error(mysql_error());
    message_register('Deleted contact: ' . theme('contact_name', $contact));
}

// Autocomplete functions //////////////////////////////////////////////////////

/**
 * Return a list of contacts matching a text fragment.
 * @param $fragment
 */
function contact_name_autocomplete ($fragment) {
    $data = array();
    if (user_access('contact_view')) {
        $contacts = crm_get_data('contact', array('filter'=>array('nameLike'=>$fragment)));
        foreach ($contacts as $contact) {
            $row = array();
            $row['value'] = $contact['cid'];
            $row['label'] = theme('contact_name', $contact);
            $data[] = $row;
        }
    }
    return $data;
}

// Table data structures ///////////////////////////////////////////////////////

/**
 * Return a table structure representing contacts.
 *
 * @param $opts Options to pass to contact_data().
 * @return The table structure.
*/
function contact_table ($opts = array()) {
    // Ensure user is allowed to view contacts
    if (!user_access('contact_view')) {
        return NULL;
    }
    // Create table structure
    $table = array();
    // Determine settings
    $export = false;
    $show_ops = isset($opts['ops']) ? $opts['ops'] : true;
    foreach ($opts as $option => $value) {
        switch ($option) {
            case 'export':
                $export = $value;
                break;
        }
    }
    // Get contact data
    $contact_data = crm_get_data('contact', $opts);
    $table['data'] = $contact_data;
    // Add columns
    $table['columns'] = array();
    if ($export) {
        $table['columns'][] = array('title'=>'Contact ID','class'=>'');
        $table['columns'][] = array('title'=>'Last','class'=>'');
        $table['columns'][] = array('title'=>'First','class'=>'');
        $table['columns'][] = array('title'=>'Middle','class'=>'');
    } else {
        $table['columns'][] = array('title'=>'Name','class'=>'');
    }
    $table['columns'][] = array('title'=>'E-Mail','class'=>'');
    $table['columns'][] = array('title'=>'Phone','class'=>'');
    if (!array_key_exists('exclude', $opts) || !in_array('emergencyName', $opts['exclude'])) {
        $table['columns'][] = array('title'=>'Emergency Contact','class'=>'');
    }
    if (!array_key_exists('exclude', $opts) || !in_array('emergencyPhone', $opts['exclude'])) {
        $table['columns'][] = array('title'=>'Emergency Phone','class'=>'');
    }
    // Add ops column
    if ($show_ops && !$export && (user_access('contact_edit') || user_access('contact_delete'))) {
        $table['columns'][] = array('title'=>'Ops','class'=>'');
    }
    // Loop through contact data and add rows to the table
    $table['rows'] = array();
    foreach ($contact_data as $contact) {
        
        $row = array();
        // Construct name
        $name_link = theme('contact_name', $contact, true);
        
        // Add cells
        if ($export) {
            $row[] = $contact['cid'];
            $row[] = $contact['lastName'];
            $row[] = $contact['firstName'];
            $row[] = $contact['middleName'];
        } else {
            $row[] = $name_link;
        }
        $row[] = $contact['email'];
        $row[] = $contact['phone'];
        if (!array_key_exists('exclude', $opts) || !in_array('emergencyName', $opts['exclude'])) {
            $row[] = $contact['emergencyName'];
        }
        if (!array_key_exists('exclude', $opts) || !in_array('emergencyPhone', $opts['exclude'])) {
            $row[] = $contact['emergencyPhone'];
        }
        
        // Construct ops array
        $ops = array();
        
        // Add edit op
        if (user_access('contact_edit')) {
            $ops[] = '<a href=' . crm_url('contact&cid=' . $contact['cid'] . '&tab=edit') . '>edit</a> ';
        }
        
        // Add delete op
        if (user_access('contact_delete')) {
            $ops[] = '<a href=' . crm_url('delete&type=contact&amp;id=' . $contact['cid']) . '>delete</a>';
        }
        
        // Add ops row
        if ($show_ops && !$export && (user_access('contact_edit') || user_access('contact_delete'))) {
            $row[] = join(' ', $ops);
        }
        
        // Add row to table
        $table['rows'][] = $row;
    }
    
    // Return table
    return $table;
}

// Forms ///////////////////////////////////////////////////////////////////////

/**
 * Return the form structure for adding or editing a contact.  If $opts['cid']
 * is specified, an edit form will be returned, otherwise an add form will be
 * returned.
 * 
 * @param $opts An associative array of options, possible keys are:
 * @return The form structure.
*/
function contact_form ($opts = array()) {
    // Create form
    $form = array(
        'type' => 'form'
        , 'method' => 'post'
        , 'command' => 'contact_add'
        , 'fields' => array()
        , 'submit' => 'Add'
    );
    // Get contact data
    if (array_key_exists('cid', $opts)) {
        $cid = $opts['cid'];
        $data = crm_get_data('contact', array('cid'=>$cid));
        $contact = $data[0];
    }
    // Change to an edit form
    if (isset($contact)) {
        $form['command'] = 'contact_update';
        $form['submit'] = 'Update';
        $form['hidden'] = array('cid' => $cid);
        $form['values'] = array();
        foreach ($contact as $key => $value) {
            if (is_string($value)) {
                $form['values'][$key] = $value;
            }
        }
        $label = 'Edit Contact Info';
    } else {
        $label = 'Add Contact';
    }
    // Add fields
    $form['fields'][] = array(
        'type' => 'fieldset',
        'label' => $label,
        'fields' => array(
            array(
                'type' => 'text'
                , 'label' => 'First Name'
                , 'name' => 'firstName'
            )
            , array(
                'type' => 'text'
                , 'label' => 'Middle Name'
                , 'name' => 'middleName'
            )
            , array(
                'type' => 'text'
                , 'label' => 'Last Name'
                , 'name' => 'lastName'
            )
            , array(
                'type' => 'text'
                , 'label' => 'Email'
                , 'name' => 'email'
            )
            , array(
                'type' => 'text'
                , 'label' => 'Phone'
                , 'name' => 'phone'
            )
            , array(
                'type' => 'text'
                , 'label' => 'Emergency Contact'
                , 'name' => 'emergencyName'
            )
            , array(
                'type' => 'text'
                , 'label' => 'Emergency Phone'
                , 'name' => 'emergencyPhone'
            )
        )
    );
    return $form;
}

/**
 * Return the form structure to delete a contact.
 *
 * @param $cid The cid of the contact to delete.
 * @return The form structure.
*/
function contact_delete_form ($cid) {
    // Ensure user is allowed to delete contacts
    if (!user_access('contact_delete')) {
        return array();
    }
    // Get contact data
    $data = contact_data(array('cid'=>$cid));
    $contact = $data[0];
    if (empty($contact) || count($contact) < 1) {
        error_register('No contact for cid ' . $cid);
        return array();
    }
    // Create form structure
    $name = theme('contact_name', $contact);
    $message = "<p>Are you sure you want to delete the contact \"$name\"? This cannot be undone.";
    $form = array(
        'type' => 'form'
        , 'method' => 'post'
        , 'command' => 'contact_delete'
        , 'submit' => 'Delete'
        , 'hidden' => array(
            'cid' => $contact['cid']
        )
        , 'fields' => array(
            array(
                'type' => 'fieldset'
                , 'label' => 'Delete Contact'
                , 'fields' => array(
                    array(
                        'type' => 'message'
                        , 'value' => $message
                    )
                )
            )
        )
    );
    return $form;
}

// Request Handlers ////////////////////////////////////////////////////////////

/**
 * Handle contact add request.
 *
 * @return The url to display when complete.
 */
function command_contact_add () {
    // Check permissions
    if (!user_access('contact_add')) {
        error_register('Permission denied: contact_add');
        return crm_url('members');
    }
    // Build contact object
    $contact = array(
        'firstName' => $_POST['firstName']
        , 'middleName' => $_POST['middleName']
        , 'lastName' => $_POST['lastName']
        , 'email' => $_POST['email']
        , 'phone' => $_POST['phone']
        , 'emergencyName' => $_POST['emergencyName']
        , 'emergencyPhone' => $_POST['emergencyPhone']
    );
    // Save to database
    $contact = contact_save($contact);
    $cid = $contact['cid'];
    return crm_url("contact&cid=$cid");
}

/**
 * Handle contact update request.
 *
 * @return The url to display on completion.
 */
function command_contact_update () {
    global $esc_post;
    // Verify permissions
    if (!user_access('contact_edit') && $_POST['cid'] != user_id()) {
        error_register('Permission denied: contact_edit');
        return crm_url('members');
    }
    $contact_data = crm_get_data('contact', array('cid'=>$_POST['cid']));
    $contact = $contact_data[0];
    if (empty($contact)) {
        error_register("No contact for cid: $_POST[cid]");
        return crm_url('members');
    }
    // Update contact data
    $contact['firstName'] = $_POST['firstName'];
    $contact['middleName'] = $_POST['middleName'];
    $contact['lastName'] = $_POST['lastName'];
    $contact['email'] = $_POST['email'];
    $contact['phone'] = $_POST['phone'];
    $contact['emergencyName'] = $_POST['emergencyName'];
    $contact['emergencyPhone'] = $_POST['emergencyPhone'];
    // Save changes to database
    $contact = contact_save($contact);
    return crm_url('members');
}

/**
 * Handle contact delete request.
 *
 * @return The url to display on completion.
 */
function command_contact_delete () {
    // Verify permissions
    if (!user_access('contact_delete')) {
        error_register('Permission denied: contact_delete');
        return crm_url('members');
    }
    contact_delete($_POST['cid']);
    return crm_url('members');
}

// Pages ///////////////////////////////////////////////////////////////////////

/**
 * @return An array of pages provided by this module.
 */
function contact_page_list () {
    $pages = array();
    if (user_access('contact_view')) {
        $pages[] = 'contacts';
        $pages[] = 'contact';
    }
    return $pages;
}

/**
 * Page hook.  Adds contact module content to a page before it is rendered.
 *
 * @param &$page_data Reference to data about the page being rendered.
 * @param $page_name The name of the page being rendered.
*/
function contact_page (&$page_data, $page_name) {
    switch ($page_name) {
        case 'contacts':
            // Set page title
            page_set_title($page_data, 'Contacts');
            // Add view tab
            if (user_access('contact_view')) {
                $opts = array(
                    'show_export'=>true
                    , 'exclude'=>array('emergencyName', 'emergencyPhone')
                );
                $view = theme('table', crm_get_table('contact', $opts));
                page_add_content_top($page_data, $view, 'View');
            }
            // Add add tab
            if (user_access('contact_add')) {
                page_add_content_top($page_data, theme('form', crm_get_form('contact')), 'Add');
            }
            break;
        case 'contact':
            // Capture contact id
            $cid = $_GET['cid'];
            if (empty($cid)) {
                return;
            }
            if (!user_access('contact_view') && $cid !== user_id()) {
                error_register('Permission denied: contact_view');
                return;
            }
            $contact_data = crm_get_data('contact', array('cid'=>$cid));
            $contact = $contact_data[0];
            // Set page title
            page_set_title($page_data, theme('contact_name', $contact));
            // Add view tab
            $view_content = '';
            if (user_access('contact_view')) {
                $view_content .= '<h3>Contact Info</h3>';
                $opts = array(
                    'cid' => $cid
                    , 'ops' => false
                );
                $view_content .= theme('table_vertical', crm_get_table('contact', array('cid' => $cid)));
            }
            if (!empty($view_content)) {
                page_add_content_top($page_data, $view_content, 'View');
            }
            // Add edit tab
            if (user_access('contact_edit') || $cid == user_id()) {
                $opts = array('cid' => $cid);
                $form = crm_get_form('contact', $opts);
                page_add_content_top($page_data, theme('form', $form), 'Edit');
            }
            break;
    }
}

// Reports /////////////////////////////////////////////////////////////////////
//require_once('report.inc.php');

// Themeing ////////////////////////////////////////////////////////////////////

/**
 * Theme a contact's name.
 * 
 * @param $contact The contact data structure or cid.
 * @param $link True if the name should be a link (default: false).
 * @param $path The path that should be linked to.  The cid will always be added
 *   as a parameter.
 *
 * @return the name string.
 */
function theme_contact_name ($contact, $link = false, $path = 'contact') {
    if (!is_array($contact)) {
        $contact = crm_get_one('contact', array('cid'=>$contact));
    }
    $first = $contact['firstName'];
    $middle = $contact['middleName'];
    $last = $contact['lastName'];
    $name = "$last, $first";
    if (!empty($middle)) {
        $name .= " $middle";
    }
    if ($link) {
        $url_opts = array('query' => array('cid' => $contact['cid']));
        $name = crm_link($name, $path, $url_opts);
    }
    return $name;
}

?>
<?php 

/*
    Copyright 2009-2013 Edward L. Platt <ed@elplatt.com>
    
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
//require_once('install.inc.php');

// Utility functions ///////////////////////////////////////////////////////////
//require_once('utility.inc.php');

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
        WHERE 1";
        
    // Add contact id
    if ($opts['cid']) {
        if (is_array($opts['cid'])) {
            $terms = array();
            foreach ($opts['cid'] as $cid) {
                $terms[] = "'" . mysql_real_escape_string($cid) . "'";
            }
            $esc_list = '(' . implode(',', $terms) . ')';
            $sql .= " AND `cid` IN $esc_list";
        } else {
            $esc_cid = mysql_real_escape_string($opts['cid']);
            $sql .= " AND `cid`='$esc_cid'";
        }
    }
    
    // Add filters
    if (isset($opts['filter'])) {
        foreach ($opts['filter'] as $name => $param) {
            switch ($name) {
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
                , `lastName`='$escaped[middleName]'
                , `email`='$escaped[email]'
                , `phone`='$escaped[phone]'
                , `emergencyName`='$escaped[emergencyName]
                , `emergencyPhone`='$escaped[emergencyPhone]
            WHERE `cid`='$escaped[cid]
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

// Autocomplete functions //////////////////////////////////////////////////////

/**
 * Return a list of contacts matching a text fragment.
 * @param $fragment
 */
function contact_name_autocomplete ($fragment) {
    $data = array();
    if (user_access('contact_view')) {
        $contacts = contact_data(array('filter'=>array('nameLike'=>$fragment)));
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
//require_once('table.inc.php');

// Forms ///////////////////////////////////////////////////////////////////////
/**
 * @return The form structure for adding a member.
*/
function contact_add_form () {
    
    // Ensure user is allowed to add contact
    if (!user_access('contact_add')) {
        return NULL;
    }
    
    // Create form structure
    $form = array(
        'type' => 'form',
        'method' => 'post',
        'command' => 'contact_add',
        'fields' => array(
            array(
                'type' => 'fieldset',
                'label' => 'Contact Info',
                'fields' => array(
                    array(
                        'type' => 'text',
                        'label' => 'First Name',
                        'name' => 'firstName'
                    ),
                    array(
                        'type' => 'text',
                        'label' => 'Middle Name',
                        'name' => 'middleName'
                    ),
                    array(
                        'type' => 'text',
                        'label' => 'Last Name',
                        'name' => 'lastName'
                    ),
                    array(
                        'type' => 'text',
                        'label' => 'Email',
                        'name' => 'email'
                    ),
                    array(
                        'type' => 'text',
                        'label' => 'Phone',
                        'name' => 'phone'
                    ),
                    array(
                        'type' => 'text',
                        'label' => 'Emergency Contact',
                        'name' => 'emergencyName'
                    ),
                    array(
                        'type' => 'text',
                        'label' => 'Emergency Phone',
                        'name' => 'emergencyPhone'
                    )
                )
            ),
            array(
                'type' => 'submit',
                'value' => 'Add'
            )
        )
    );
    
    return $form;
}

// Request Handlers ////////////////////////////////////////////////////////////
//require_once('command.inc.php');

// Member pages ////////////////////////////////////////////////////////////////
//require_once('page.inc.php');

// Member reports //////////////////////////////////////////////////////////////
//require_once('report.inc.php');

// Themeing ////////////////////////////////////////////////////////////////////

/**
 * Theme a contact's name.
 * 
 * @param $contact
 * @param $link True if the name should be a link (default: false).
 * @param $path The path that should be linked to.  The cid will always be added
 *   as a parameter.
 *
 * @return the name string.
 */
function theme_contact_name ($contact, $link = false, $path = 'contact') {
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
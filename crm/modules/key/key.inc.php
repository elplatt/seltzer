<?php 

/*
    Copyright 2009-2013 Edward L. Platt <ed@elplatt.com>
    
    This file is part of the Seltzer CRM Project
    key.inc.php - Key tracking module

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

// Installation functions //////////////////////////////////////////////////////

/**
 * @return This module's revision number.  Each new release should increment
 * this number.
 */
function key_revision () {
    return 2;
}

/**
 * @return An array of the permissions provided by this module.
 */
function key_permissions () {
    return array(
        'key_view'
        , 'key_edit'
        , 'key_delete'
    );
}

/**
 * Install or upgrade this module.
 * @param $old_revision The last installed revision of this module, or 0 if the
 *   module has never been installed.
 */
function key_install($old_revision = 0) {
    if ($old_revision < 1) {
        $sql = '
            CREATE TABLE IF NOT EXISTS `key` (
              `kid` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
              `cid` mediumint(8) unsigned NOT NULL,
              `start` date DEFAULT NULL,
              `end` date DEFAULT NULL,
              `serial` varchar(255) NOT NULL,
              `slot` mediumint(8) unsigned NOT NULL,
              PRIMARY KEY (`kid`)
            ) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
        ';
        $res = mysql_query($sql);
        if (!$res) die(mysql_error());
    }
    // Permissions moved to DB, set defaults on install/upgrade
    if ($old_revision < 2) {
        // Set default permissions
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
            'director' => array('key_view', 'key_edit', 'key_delete')
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

// Utility functions ///////////////////////////////////////////////////////////

/**
 * Generate a descriptive string for a single key.
 *
 * @param $kid The kid of the key to describe.
 * @return The description string.
 */
function key_description ($kid) {
    
    // Get key data
    $data = key_data(array('kid' => $kid));
    if (empty($data)) {
        return '';
    }
    $key = $data[0];
    
    // Construct description
    $description = 'Key ';
    $description .= $key['serial'];
    
    return $description;
}

// DB to Object mapping ////////////////////////////////////////////////////////

/**
 * Return data for one or more key card assignments.
 *
 * @param $opts An associative array of options, possible keys are:
 *   'kid' If specified, returns a single memeber with the matching key id;
 *   'cid' If specified, returns all keys assigned to the contact with specified id;
 *   'filter' An array mapping filter names to filter values;
 *   'join' A list of tables to join to the key table.
 * @return An array with each element representing a single key card assignment.
*/ 
function key_data ($opts = array()) {
    
    // Determine joins
    $join_contact = false;
    $join_member = false;
    if (array_key_exists('join', $opts)) {
        foreach ($opts['join'] as $table) {
            if ($table === 'contact') {
                $join_contact = true;
            }
            if ($table === 'member') {
                $join_member = true;
            }
        }
    }
    
    // Create map from cids to contact names if necessary
    // TODO: Add filters for speed
    if ($join_contact) {
        $contacts = member_contact_data();
        $cidToContact = array();
        foreach ($contacts as $contact) {
            $cidToContact[$contact['cid']] = $contact;
        }
    }
    
    if ($join_member) {
        $members = member_data();
        $cidToMember = array();
        foreach ($members as $member) {
            $cidToMember[$member['cid']] = $member;
        }
    }
    
    // Query database
    $sql = "
        SELECT
        `kid`
        , `cid`
        , `start`
        , `end`
        , `serial`
        , `slot`
        FROM `key`
        WHERE 1";
    if (!empty($opts['kid'])) {
        $esc_kid = mysql_real_escape_string($opts['kid']);
        $sql .= " AND `kid`='$esc_kid'";
    }
    if (!empty($opts['cid'])) {
        $esc_cid = mysql_real_escape_string($opts['cid']);
        $sql .= " AND `cid`='$esc_cid'";
    }
    if (!empty($opts['filter'])) {
        foreach ($opts['filter'] as $name => $param) {
            switch ($name) {
                case 'active':
                    if ($param) {
                        $sql .= " AND (`start` IS NOT NULL AND `end` IS NULL)";
                    } else {
                        $sql .= " AND (`start` IS NULL OR `end` IS NOT NULL)";
                    }
                    break;
            }
        }
    }
    $sql .= "
        ORDER BY `start`, `kid` ASC";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    
    // Store data
    $keys = array();
    $row = mysql_fetch_assoc($res);
    while (!empty($row)) {
        $key = array(
            'kid' => $row['kid'],
            'cid' => $row['cid'],
            'start' => $row['start'],
            'end' => $row['end'],
            'serial' => $row['serial'],
            'slot' => $row['slot'],
        );
        if ($join_contact) {
            if (array_key_exists($row['cid'], $cidToContact)) {
                $key['contact'] = $cidToContact[$row['cid']];
            }
        }
        if ($join_contact) {
            if (array_key_exists($row['cid'], $cidToMember)) {
                $key['member'] = $cidToMember[$row['cid']];
            }
        }
        $keys[] = $key;
        $row = mysql_fetch_assoc($res);
    }
    
    // Return data
    return $keys;
}

// Table data structures ///////////////////////////////////////////////////////

/**
 * Return a table structure for a table of key assignments.
 *
 * @param $opts The options to pass to key_data().
 * @return The table structure.
*/
function key_table ($opts) {
    
    // Determine settings
    $export = false;
    foreach ($opts as $option => $value) {
        switch ($option) {
            case 'export':
                $export = $value;
                break;
        }
    }
    
    // Get contact data
    $data = key_data($opts);
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
    if (user_access('key_view') || $opts['cid'] == user_id()) {
        if (array_key_exists('join', $opts) && in_array('contact', $opts['join'])) {
            $table['columns'][] = array("title"=>'Last Name', 'class'=>'', 'id'=>'');
            $table['columns'][] = array("title"=>'First Name', 'class'=>'', 'id'=>'');
            $table['columns'][] = array("title"=>'Middle Name', 'class'=>'', 'id'=>'');
        }
        if (array_key_exists('join', $opts) && in_array('member', $opts['join'])) {
            $table['columns'][] = array("title"=>'Membership', 'class'=>'', 'id'=>'');
        }
        $table['columns'][] = array("title"=>'Serial', 'class'=>'', 'id'=>'');
        $table['columns'][] = array("title"=>'Slot', 'class'=>'', 'id'=>'');
        $table['columns'][] = array("title"=>'Start', 'class'=>'', 'id'=>'');
        $table['columns'][] = array("title"=>'End', 'class'=>'', 'id'=>'');
    }
    // Add ops column
    if (!$export && (user_access('key_edit') || user_access('key_delete'))) {
        $table['columns'][] = array('title'=>'Ops','class'=>'');
    }
    
    // Add rows
    foreach ($data as $key) {
        
        // Add key data
        $row = array();
        if (user_access('key_view') || $opts['cid'] == user_id()) {
            
            // Add cells
            if (array_key_exists('join', $opts) && in_array('contact', $opts['join'])) {
                $row[] = $key['contact']['lastName'];
                $row[] = $key['contact']['firstName'];
                $row[] = $key['contact']['middleName'];
            }
            if (array_key_exists('join', $opts) && in_array('member', $opts['join'])) {
                // Construct membership info
                $member = $key['member'];
                $plan = '';
                if (!empty($member)) {
                    $recentMembership = end($member['membership']);
                    if (!empty($recentMembership) && empty($recentMembership['end'])) {
                        $plan = $recentMembership['plan']['name'];
                    }
                }
                $row[] = $plan;
            }
            $row[] = $key['serial'];
            $row[] = $key['slot'];
            $row[] = $key['start'];
            $row[] = $key['end'];
        }
        
        if (!$export && (user_access('key_edit') || user_access('key_delete'))) {
            // Construct ops array
            $ops = array();
            
            // Add edit op
            if (user_access('key_edit')) {
                $ops[] = '<a href="index.php?q=key&kid=' . $key['kid'] . '#tab-edit">edit</a> ';
            }
            
            // Add delete op
            if (user_access('key_delete')) {
                $ops[] = '<a href="index.php?q=delete&type=key&id=' . $key['kid'] . '">delete</a>';
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
 * Return the form structure for the add key assignment form.
 *
 * @param The cid of the contact to add a key assignment for.
 * @return The form structure.
*/
function key_add_form ($cid) {
    
    // Ensure user is allowed to edit keys
    if (!user_access('key_edit')) {
        return NULL;
    }
    
    // Create form structure
    $form = array(
        'type' => 'form',
        'method' => 'post',
        'command' => 'key_add',
        'hidden' => array(
            'cid' => $cid
        ),
        'fields' => array(
            array(
                'type' => 'fieldset',
                'label' => 'Add Key Assignment',
                'fields' => array(
                    array(
                        'type' => 'text',
                        'label' => 'Serial',
                        'name' => 'serial'
                    ),
                    array(
                        'type' => 'text',
                        'label' => 'Slot',
                        'name' => 'slot'
                    ),
                    array(
                        'type' => 'text',
                        'label' => 'Start',
                        'name' => 'start',
                        'value' => date("Y-m-d"),
                        'class' => 'date'
                    ),
                    array(
                        'type' => 'text',
                        'label' => 'End',
                        'name' => 'end',
                        'class' => 'date'
                    ),
                    array(
                        'type' => 'submit',
                        'value' => 'Add'
                    )
                )
            )
        )
    );
    
    return $form;
}

/**
 * Return the form structure for an edit key assignment form.
 *
 * @param $kid The kid of the key assignment to edit.
 * @return The form structure.
*/
function key_edit_form ($kid) {
    
    // Ensure user is allowed to edit key
    if (!user_access('key_edit')) {
        return NULL;
    }
    
    // Get key data
    $data = key_data(array('kid'=>$kid));
    $key = $data[0];
    if (empty($key) || count($key) < 1) {
        return array();
    }
    
    // Get corresponding contact data
    $data = member_contact_data(array('cid'=>$key['cid']));
    $contact = $data[0];
    
    // Construct member name
    $name = member_name($contact['firstName'], $contact['middleName'], $contact['lastName']);
    
    // Create form structure
    $form = array(
        'type' => 'form',
        'method' => 'post',
        'command' => 'key_update',
        'hidden' => array(
            'kid' => $kid
        ),
        'fields' => array(
            array(
                'type' => 'fieldset',
                'label' => 'Edit Key Info',
                'fields' => array(
                    array(
                        'type' => 'readonly',
                        'label' => 'Name',
                        'value' => $name
                    ),
                    array(
                        'type' => 'text',
                        'class' => 'date',
                        'label' => 'Start',
                        'name' => 'start',
                        'value' => $key['start']
                    ),
                    array(
                        'type' => 'text',
                        'class' => 'date',
                        'label' => 'End',
                        'name' => 'end',
                        'value' => $key['end']
                    ),
                    array(
                        'type' => 'text',
                        'label' => 'Serial',
                        'name' => 'serial',
                        'value' => $key['serial']
                    ),
                    array(
                        'type' => 'text',
                        'label' => 'Slot',
                        'name' => 'slot',
                        'value' => $key['slot']
                    ),
                    array(
                        'type' => 'submit',
                        'value' => 'Update'
                    )
                )
            )
        )
    );
    
    return $form;
}

/**
 * Return the delete key assigment form structure.
 *
 * @param $kid The kid of the key assignment to delete.
 * @return The form structure.
*/
function key_delete_form ($kid) {
    
    // Ensure user is allowed to delete keys
    if (!user_access('key_delete')) {
        return NULL;
    }
    
    // Get key data
    $data = key_data(array('kid'=>$kid));
    $key = $data[0];
    
    // Construct key name
    $key_name = "key:$key[kid] serial:$key[serial] slot:$key[slot] $key[start] -- $key[end]";
    
    // Create form structure
    $form = array(
        'type' => 'form',
        'method' => 'post',
        'command' => 'key_delete',
        'hidden' => array(
            'kid' => $key['kid']
        ),
        'fields' => array(
            array(
                'type' => 'fieldset',
                'label' => 'Delete Key',
                'fields' => array(
                    array(
                        'type' => 'message',
                        'value' => '<p>Are you sure you want to delete the key assignment "' . $key_name . '"? This cannot be undone.',
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

// Request Handlers ////////////////////////////////////////////////////////////

/**
 * Command handler.
 * @param $command The name of the command to handle.
 * @param &$url A reference to the url to be loaded after completion.
 * @param &$params An associative array of query parameters for &$url.
 */
function key_command ($command, &$url, &$params) {
    switch ($command) {
        case 'member_add':
            $params['tab'] = 'keys';
            break;
    }
}

/**
 * Handle key add request.
 *
 * @return The url to display on completion.
 */
function command_key_add() {
    global $esc_post;
    
    // Verify permissions
    if (!user_access('key_edit')) {
        error_register('Permission denied: key_edit');
        return 'index.php?q=key&kid=' . $esc_post['kid'];
    }
    
    // Query database
    $sql = "
        INSERT INTO `key`
        (`cid`, `serial`, `slot`, `start`";
    if (!empty($esc_post['end'])) {
        $sql .= ", `end`";
    }
    $sql .= "
        )
        VALUES
        ('$esc_post[cid]', '$esc_post[serial]', '$esc_post[slot]', '$esc_post[start]'";
    if (!empty($esc_post['end'])) {
        $sql .= ", '$esc_post[end]'";
    }
    $sql .= ")";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    
    return 'index.php?q=member&cid=' . $_POST['cid'] . '&tab=keys';
}

/**
 * Handle key update request.
 *
 * @return The url to display on completion.
 */
function command_key_update() {
    global $esc_post;
    
    // Verify permissions
    if (!user_access('key_edit')) {
        error_register('Permission denied: key_edit');
        return 'index.php?q=key&kid=' . $_POST['kid'];
    }
    
    // Query database
    $sql = "
        UPDATE `key`
        SET
        `start`='$esc_post[start]',";
    if (!empty($esc_post[end])) {
        $sql .= "`end`='$esc_post[end]',";
    } else {
        $sql .= "`end`=NULL,";
    }
    $sql .= "
        `serial`='$esc_post[serial]',
        `slot`='$esc_post[slot]'
        WHERE `kid`='$esc_post[kid]'";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    
    return 'index.php?q=key&kid=' . $esc_post['kid'] . '&tab=edit';
}

/**
 * Handle key delete request.
 *
 * @return The url to display on completion.
 */
function command_key_delete() {
    global $esc_post;
    
    // Verify permissions
    if (!user_access('key_delete')) {
        error_register('Permission denied: key_delete');
        return 'index.php?q=key&kid=' . $esc_post['kid'];
    }
    
    // Query database
    $sql = "
        DELETE FROM `key`
        WHERE `kid`='$esc_post[kid]'";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    
    return 'index.php?q=members';
}

// Pages ///////////////////////////////////////////////////////////////////////

/**
 * @return An array of pages provided by this module.
 */
function key_page_list () {
    $pages = array();
    if (user_access('key_view')) {
        $pages[] = 'keys';
    }
    return $pages;
}

/**
 * Page hook.  Adds module content to a page before it is rendered.
 *
 * @param &$page_data Reference to data about the page being rendered.
 * @param $page_name The name of the page being rendered.
 * @param $options The array of options passed to theme('page').
*/
function key_page (&$page_data, $page_name, $options) {
    
    switch ($page_name) {
        
        case 'member':
            
            // Capture member cid
            $cid = $options['cid'];
            if (empty($cid)) {
                return;
            }
            
            // Add keys tab
            if (user_access('key_view') || user_access('key_edit') || user_access('key_delete') || $cid == user_id()) {
                $keys = theme('table', 'key', array('cid' => $cid));
                $keys .= theme('key_add_form', $cid);
                page_add_content_bottom($page_data, $keys, 'Keys');
            }
            
            break;
        
        case 'keys':
            page_set_title($page_data, 'Keys');
            if (user_access('key_view')) {
                $keys = theme('table', 'key', array('join'=>array('contact', 'member'), 'show_export'=>true));
                page_add_content_top($page_data, $keys, 'View');
            }
            break;
        
        case 'key':
            
            // Capture key id
            $kid = $options['kid'];
            if (empty($kid)) {
                return;
            }
            
            // Set page title
            page_set_title($page_data, key_description($kid));
            
            // Add edit tab
            if (user_access('key_view') || user_access('key_edit') || user_access('key_delete')) {
                page_add_content_top($page_data, theme('key_edit_form', $kid), 'Edit');
            }
            
            break;
    }
}

// Themeing ////////////////////////////////////////////////////////////////////

/**
 * Return the themed html for an add key assignment form.
 *
 * @param $cid The id of the contact to add a key assignment for.
 * @return The themed html string.
 */
function theme_key_add_form ($cid) {
    return theme('form', key_add_form($cid));
}

/**
 * Return themed html for an edit key assignment form.
 *
 * @param $kid The kid of the key assignment to edit.
 * @return The themed html string.
 */
function theme_key_edit_form ($kid) {
    return theme('form', key_edit_form($kid));
}

?>
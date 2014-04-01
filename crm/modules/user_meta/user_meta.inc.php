<?php

/*
    Copyright 2009-2013 Edward L. Platt <ed@elplatt.com>
    Copyright 2013 David "Buzz" Bussenschutt <davidbuzz@gmail.com>

    This file is part of the Seltzer CRM Project
    user_meta.inc.php - Meta-Tag tracking module
    This module is for associating arbitrary "meta data" with member/s.
    This can be useful for making arbitrary groupings of users that have special meaning to you.
    Examples:
    We have one called "Respected", which entitles a member to get a physical key to the building....
    Or one called "Machinist", which means they have passed basic safety assessment to permit them to use our Mill/Lathe.
    It's kinda like an extension to the Permissions system, but for managing things external to Seltzer.

    Seltzer is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    any later version.

    Seltzer is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Seltzer. If not, see <http://www.gnu.org/licenses/>.
*/

// Installation functions //////////////////////////////////////////////////////

/**
 * @return This module's revision number. Each new release should increment
 * this number.
 */
function user_meta_revision () {
    return 1;
}

/**
 * @return An array of the permissions provided by this module.
 */
function user_meta_permissions () {
    return array(
        'user_meta_view'
        , 'user_meta_edit'
        , 'user_meta_delete'
    );
}

/**
 * Install or upgrade this module.
 * @param $old_revision The last installed revision of this module, or 0 if the
 * module has never been installed.
 */
function user_meta_install($old_revision = 0) {
    if ($old_revision < 1) {
        $sql = '
            CREATE TABLE IF NOT EXISTS `user_meta` (
            `umid` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
            `cid` mediumint(8) unsigned NOT NULL,
            `start` date DEFAULT NULL,
            `end` date DEFAULT NULL,
            `tagstr` varchar(255) NOT NULL,
            PRIMARY KEY (`umid`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
            ';
        $res = mysql_query($sql);
        if (!$res) die(mysql_error());
        
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
            'director' => array('user_meta_view', 'user_meta_edit', 'user_meta_delete'),
            'webAdmin' => array('user_meta_view', 'user_meta_edit', 'user_meta_delete'),
            'member' => array('user_meta_view')
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
 * Generate a descriptive string for a single Meta-Tag .
 *
 * @param $umid The umid of the meta-tag to describe.
 * @return The description string.
 */
function user_meta_description ($umid) {
    
    // Get meta data
    $data = crm_get_data('user_meta', array('umid' => $umid));
    if (empty($data)) {
        return '';
    }
    $user_meta = $data[0];
    
    // Construct description
    $description = 'Meta ';
    $description .= $user_meta['tagstr'];
    
    return $description;
}

// DB to Object mapping ////////////////////////////////////////////////////////

/**
 * Return data for one or more meta-tag assignments.
 *
 * @param $opts An associative array of options, possible metas are:
 * 'umid' If specified, returns a single memeber with the matching meta id;
 * 'cid' If specified, returns all metas assigned to the contact with specified id;
 * 'filter' An array mapping filter names to filter values;
 * 'join' A list of tables to join to the meta table.
 * @return An array with each element representing a single meta-tag assignment.
 */
function user_meta_data ($opts = array()) {
    
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
        `umid`
        , `cid`
        , `start`
        , `end`
        , `tagstr`
        FROM `user_meta`
        WHERE 1";
    if (!empty($opts['umid'])) {
        $esc_umid = mysql_real_escape_string($opts['umid']);
        $sql .= " AND `umid`='$esc_umid'";
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
        ORDER BY `tagstr` ASC";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    
    // Store data
    $user_metas = array();
    $row = mysql_fetch_assoc($res);
    while (!empty($row)) {
        $user_meta = array(
            'umid' => $row['umid'],
            'cid' => $row['cid'],
            'start' => $row['start'],
            'end' => $row['end'],
            'tagstr' => $row['tagstr'],
        );
        if ($join_contact) {
            if (array_key_exists($row['cid'], $cidToContact)) {
                $user_meta['contact'] = $cidToContact[$row['cid']];
            }
        }
        if ($join_contact) {
            if (array_key_exists($row['cid'], $cidToMember)) {
                $user_meta['member'] = $cidToMember[$row['cid']];
            }
        }
        $user_metas[] = $user_meta;
        $row = mysql_fetch_assoc($res);
    }
    
    // Return data
    return $user_metas;
}

/**
 * Implementation of hook_data_alter().
 * @param $type The type of the data being altered.
 * @param $data An array of structures of the given $type.
 * @param $opts An associative array of options.
 * @return An array of modified structures.
 */
function user_meta_data_alter ($type, $data = array(), $opts = array()) {
    switch ($type) {
        case 'contact':
            // Get cids of all contacts passed into $data
            $cids = array();
            foreach ($data as $contact) {
                $cids[] = $contact['cid'];
            }
            // Add the cids to the options
            $user_meta_opts = $opts;
            $user_meta_opts['cid'] = $cids;
            // Get an array of user meta data structures for each cid
            $user_meta_data = crm_get_data('user_meta', $user_meta_opts);
            // Create a map from cid to an array of user meta data structures
            $cid_to_user_metas = array();
            foreach ($user_meta_data as $user_meta) {
                $cid_to_user_metas[$user_meta['cid']][] = $user_meta;
            }
            // Add user meta data structures to the contact structures
            foreach ($data as $i => $contact) {
                if (array_key_exists($contact['cid'], $cid_to_user_metas)) {
                    $user_metas = $cid_to_user_metas[$contact['cid']];
                    $data[$i]['user_metas'] = $user_metas;
                }
            }
            break;
    }
    return $data;
}

/**
 * Save a user meta data structure.  If $user_meta has a 'umid' element, an existing user meta data will
 * be updated, otherwise a new user meta data will be created.
 * @param $umid The user meta data structure
 * @return The user meta data structure with as it now exists in the database.
 */
function user_meta_save ($user_meta) {
    // Escape values
    $fields = array('umid', 'cid', 'tagstr', 'start', 'end');
    if (isset($user_meta['umid'])) {
        // Update existing user meta data
        $umid = $user_meta['umid'];
        $esc_umid = mysql_real_escape_string($umid);
        $clauses = array();
        foreach ($fields as $k) {
            if ($k == 'end' && empty($user_meta[$k])) {
                continue;
            }
            if (isset($user_meta[$k]) && $k != 'umid') {
                $clauses[] = "`$k`='" . mysql_real_escape_string($user_meta[$k]) . "' ";
            }
        }
        $sql = "UPDATE `user_meta` SET " . implode(', ', $clauses) . " ";
        $sql .= "WHERE `umid`='$esc_umid'";
        $res = mysql_query($sql);
        if (!$res) die(mysql_error());
        message_register('User Meta  Data updated');
    } else {
        // Insert new user meta data
        $cols = array();
        $values = array();
        foreach ($fields as $k) {
            if (isset($user_meta[$k])) {
                if ($k == 'end' && empty($user_meta[$k])) {
                    continue;
                }
                $cols[] = "`$k`";
                $values[] = "'" . mysql_real_escape_string($user_meta[$k]) . "'";
            }
        }
        $sql = "INSERT INTO `user_meta` (" . implode(', ', $cols) . ") ";
        $sql .= " VALUES (" . implode(', ', $values) . ")";
        $res = mysql_query($sql);
        if (!$res) die(mysql_error());
        $umid = mysql_insert_id();
        message_register('User Meta Data added');
    }
    return crm_get_one('user_meta', array('umid'=>$umid));
}

/**
 * Delete a piece of user meta data.
 * @param $user_meta The user meta data data structure to delete, must have a 'umid' element.
 */
function user_meta_delete ($user_meta) {
    $esc_umid = mysql_real_escape_string($user_meta['umid']);
    $sql = "DELETE FROM `user_meta` WHERE `umid`='$esc_umid'";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    if (mysql_affected_rows() > 0) {
        message_register('User Meta Data deleted.');
    }
}

// Table data structures ///////////////////////////////////////////////////////

/**
 * Return a table structure for a table of meta tag assignments.
 * displays the tag data all in one row ( a column each )
 *
 * @param $opts The options to pass to user_meta_data().
 * @return The table structure.
 */
function user_meta_cross_table ($opts) {
    
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
    $data = user_meta_data($opts);
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
    $tableid = 0 ;
    $uniq = array();
    
    // determine max/total number of tags, as we'll use one column for each:
    $sql = "SELECT distinct tagstr from user_meta order by tagstr asc";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    $count = mysql_num_rows($res); // just one row.
        $tags = array();
        while ($row = mysql_fetch_array($res, MYSQL_NUM))
        {
            $tags[] = $row[0];
        }
    
    // Add column headers
    if (user_access('user_meta_view') || $opts['cid'] == user_id()) {
        if (array_key_exists('join', $opts) && in_array('contact', $opts['join'])) {
            $table['columns'][] = array("title"=>'Name', 'class'=>'', 'id'=>''); // column 1
        }
        for ( $i = 0 ; $i < $count; $i++) {
            $table['columns'][] = array("title"=>$tags[$i], 'class'=>'', 'id'=>''); // column 2 -> almost end
        }
    }
    // Add ops column
    if (!$export && (user_access('user_meta_edit'))) {
        $table['columns'][] = array('title'=>'Ops','class'=>''); // last column.
    }
    
    // Add row data
    foreach ($data as $user_meta) {
        
        // Add meta data
        $row = array();
        
        // user not already on screen, add them, and all details, and first tag.
        if ( ! array_key_exists($user_meta['contact']['lastName'].$user_meta['contact']['firstName'], $uniq) ) {
            
            $uniq[$user_meta['contact']['lastName'].$user_meta['contact']['firstName']] = $tableid;
            
            if (user_access('user_meta_view') || $opts['cid'] == user_id()) {
                
                // Add cells
                if (array_key_exists('join', $opts) && in_array('contact', $opts['join'])) {
                    $row[] = theme('contact_name', $user_meta['cid'], true);
                }
                if (array_key_exists('join', $opts) && in_array('member', $opts['join'])) {
                    // Construct membership info
                    $member = $user_meta['member'];
                    $plan = '';
                    if (!empty($member)) {
                        $recentMembership = end($member['membership']);
                        if (!empty($recentMembership) && empty($recentMembership['end'])) {
                            $plan = $recentMembership['plan']['name'];
                        }
                    }
                }
                
                // insert new tag in new row at a fixed offset.
                for ( $i = 1 ; $i < $count+1; $i++) {
                    if ( $table['columns'][$i]['title'] == $user_meta['tagstr'] ) {
                        $row[$i] = '<input type="checkbox" name="'.$user_meta['tagstr'].'" value="1" checked="checked" disabled=true/>';
                    } else {
                        if ( ! array_key_exists($i, $row) ) { $row[$i] = ''; }
                    }
                }
            }
            if (!$export && (user_access('user_meta_edit') || user_access('user_meta_delete'))) {
                // Construct ops array
                $ops = array();
                // Add edit op
                if (user_access('user_meta_edit')) {
                    $ops[] = '<a href=' . crm_url('contact&cid=' . $user_meta['cid'] . '#tab-meta-tags') . '>edit</a>';
                }
                // Add ops row
                $row[] = join(' ', $ops);
            }
            $table['rows'][$tableid] = $row;
            $tableid++;
        }
            else {
                //print "burp<br>\n";
                // user alresdy, just add additional tag for them ...
                
                $previd = $uniq[$user_meta['contact']['lastName'].$user_meta['contact']['firstName']];
                $row = $table['rows'][$previd];
                
                // insert new tag to existing row:
                for ( $i = 2 ; $i < $count+2; $i++) {
                    if ( $table['columns'][$i]['title'] == $user_meta['tagstr'] ) {
                        $row[$i] = '<input type="checkbox" name="'.$user_meta['tagstr'].'" value="1" checked="checked" disabled=true/>';
                    }
                }
                $table['rows'][$previd] = $row;
            }
    }
    
    //var_dump($uniq);
    
    return $table;
}

/**
 * Return a normal table structure for a table of meta assignments.
 *
 * @param $opts The options to pass to user_meta_data().
 * @return The table structure.
 */
function user_meta_table ($opts) {
    
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
    $data = crm_get_data('user_meta', $opts);
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
    if (user_access('user_meta_view') || $opts['cid'] == user_id()) {
        if (array_key_exists('join', $opts) && in_array('contact', $opts['join'])) {
            $table['columns'][] = array("title"=>'Name', 'class'=>'', 'id'=>'');
        }
        $table['columns'][] = array("title"=>'MetaTag', 'class'=>'', 'id'=>'');
        $table['columns'][] = array("title"=>'Start', 'class'=>'', 'id'=>'');
        $table['columns'][] = array("title"=>'End', 'class'=>'', 'id'=>'');
    }
    // Add ops column
    if (!$export && (user_access('user_meta_edit') || user_access('user_meta_delete'))) {
        $table['columns'][] = array('title'=>'Ops','class'=>'');
    }
    
    // Add rows
    foreach ($data as $user_meta) {
        
        // Add meta data
        $row = array();
        if (user_access('user_meta_view') || $opts['cid'] == user_id()) {
            
            // Add cells
            if (array_key_exists('join', $opts) && in_array('contact', $opts['join'])) {
                $row[] = theme('contact_name', $cid_to_contact[$user_meta['cid']], true);
            }
            if (array_key_exists('join', $opts) && in_array('member', $opts['join'])) {
                // Construct membership info
                $member = $user_meta['member'];
                $plan = '';
                if (!empty($member)) {
                    $recentMembership = end($member['membership']);
                    if (!empty($recentMembership) && empty($recentMembership['end'])) {
                        $plan = $recentMembership['plan']['name'];
                    }
                }
                $row[] = $plan;
            }
            $row[] = $user_meta['tagstr'];
            $row[] = $user_meta['start'];
            $row[] = $user_meta['end'];
        }
        if (!$export && (user_access('user_meta_edit') || user_access('user_meta_delete'))) {
            // Construct ops array
            $ops = array();
            // Add edit op
            if (user_access('user_meta_edit')) {
                $ops[] = '<a href=' . crm_url('user_meta&umid=' . $user_meta['umid'] . '#tab-edit') . '>edit</a> ';
            }
            // Add delete op
            if (user_access('user_meta_delete')) {
                $ops[] = '<a href=' . crm_url('delete&type=user_meta&id=' . $user_meta['umid']) . '>delete</a>';
            }
            // Add ops row
            $row[] = join(' ', $ops);
        }
        $table['rows'][] = $row;
    }
    return $table;
}

// Autocomplete functions //////////////////////////////////////////////////////

/**
 * Return a list of contacts matching a text fragment.
 * @param $fragment
 */
function meta_tag_autocomplete ($fragment) {
    $data = array();
    $sql = "SELECT DISTINCT(`tagstr`) FROM `user_meta`";
    $userMeta = mysql_query($sql);
    if (!$userMeta) return $data;
    $mysqlRow = mysql_fetch_assoc($userMeta);
    while (!empty($mysqlRow)) {
            $row = array();
            $row['value'] = $mysqlRow['tagstr'];
            $row['label'] = $mysqlRow['tagstr'];
            $data[] = $row;
            $mysqlRow = mysql_fetch_assoc($userMeta);
    }
    return $data;
}

// Forms ///////////////////////////////////////////////////////////////////////

/**
 * Return the form structure for the add meta assignment form.
 *
 * @param The cid of the contact to add a meta assignment for.
 * @return The form structure.
 */
function user_meta_add_form ($cid) {
    
    // Ensure user is allowed to edit metas
    if (!user_access('user_meta_edit')) {
        return NULL;
    }
    
    // Create form structure
    $form = array(
        'type' => 'form',
        'method' => 'post',
        'command' => 'user_meta_add',
        'hidden' => array(
            'cid' => $cid
        ),
        'fields' => array(
            array(
                'type' => 'fieldset',
                'label' => 'Add Meta-Tag Assignment',
                'fields' => array(
                    array(
                        'type' => 'text',
                        'label' => 'MetaTag',
                        'name' => 'tagstr',
                        'value' => '[please enter a meaningful metatag here]',
                        'suggestion' => 'meta_tag',
                        'defaultClear' => True
                    ),
                    array(
                        'type' => 'text',
                        'label' => 'Since',
                        'name' => 'start',
                        'value' => date("Y-m-d"),
                        'class' => 'date'
                    ),
                    array(
                        'type' => 'text',
                        'label' => 'Until',
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
 * Return the form structure for an edit meta assignment form.
 *
 * @param $umid The umid of the meta assignment to edit.
 * @return The form structure.
 */
function user_meta_edit_form ($umid) {
    
    // Ensure user is allowed to edit meta
    if (!user_access('user_meta_edit')) {
        return NULL;
    }
    
    // Get meta data
    $data = crm_get_data('user_meta', array('umid'=>$umid));
    $user_meta = $data[0];
    if (empty($user_meta) || count($user_meta) < 1) {
        return array();
    }
    
    // Get corresponding contact data
    $data = member_contact_data(array('cid'=>$user_meta['cid']));
    $contact = $data[0];
    
    // Construct member name
    $name = theme('contact_name', $contact, true);
    
    // Create form structure
    $form = array(
        'type' => 'form',
        'method' => 'post',
        'command' => 'user_meta_update',
        'hidden' => array(
            'umid' => $umid
        ),
        'fields' => array(
            array(
                'type' => 'fieldset',
                'label' => 'Edit meta Info',
                'fields' => array(
                    array(
                        'type' => 'readonly',
                        'label' => 'Name',
                        'value' => $name
                    ),
                    array(
                        'type' => 'text',
                        'class' => 'date',
                        'label' => 'Since',
                        'name' => 'start',
                        'value' => $user_meta['start']
                    ),
                    array(
                        'type' => 'text',
                        'class' => 'date',
                        'label' => 'Until',
                        'name' => 'end',
                        'value' => $user_meta['end']
                    ),
                    array(
                        'type' => 'text',
                        'label' => 'Tag',
                        'name' => 'tagstr',
                        'value' => $user_meta['tagstr']
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
 * Return the delete meta assigment form structure.
 *
 * @param $umid The umid of the meta assignment to delete.
 * @return The form structure.
 */
function user_meta_delete_form ($umid) {
    
    // Ensure user is allowed to delete metas
    if (!user_access('user_meta_delete')) {
        return NULL;
    }
    
    // Get meta data
    $data = crm_get_data('user_meta',array('umid'=>$umid));
    $user_meta = $data[0];
    
    // Construct meta name
    $user_meta_name = "meta:$user_meta[umid] tagstr:$user_meta[tagstr] $user_meta[start] -- $user_meta[end]";
    
    // Create form structure
    $form = array(
        'type' => 'form',
        'method' => 'post',
        'command' => 'user_meta_delete',
        'hidden' => array(
            'umid' => $user_meta['umid']
        ),
        'fields' => array(
            array(
                'type' => 'fieldset',
                'label' => 'Delete Meta',
                'fields' => array(
                    array(
                        'type' => 'message',
                        'value' => '<p>Are you sure you want to delete the meta assignment "' . $user_meta_name . '"? This cannot be undone.',
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
function user_meta_command ($command, &$url, &$params) {
    switch ($command) {
        case 'member_add':
            $params['tab'] = 'user_metas';
            break;
    }
}

/**
 * Handle meta add request.
 *
 * @return The url to display on completion.
 */
function command_user_meta_add() {
    global $esc_post;
    
    // Verify permissions
    if (!user_access('user_meta_edit')) {
        error_register('Permission denied: user_meta_edit');
        return crm_url('user_meta&umid=' . $esc_post['umid']);
    }
    user_meta_save($_POST);
    return crm_url('contact&cid=' . $_POST['cid'] . '&tab=metas');
}

/**
 * Handle meta update request.
 *
 * @return The url to display on completion.
 */
function command_user_meta_update() {
    global $esc_post;
    
    // Verify permissions
    if (!user_access('user_meta_edit')) {
        error_register('Permission denied: user_meta_edit');
        return crm_url('user_meta&umid=' . $_POST['umid']);
    }
    user_meta_save($_POST);
    return crm_url('user_meta&umid=' . $esc_post['umid'] . '&tab=edit');
}

/**
 * Handle meta delete request.
 *
 * @return The url to display on completion.
 */
function command_user_meta_delete() {
    global $esc_post;
    // Verify permissions
    if (!user_access('user_meta_delete')) {
        error_register('Permission denied: user_meta_delete');
        return crm_url('user_meta&umid=' . $esc_post['umid']);
    }
    user_meta_delete($_POST);
    return crm_url('members');
}

// Pages ///////////////////////////////////////////////////////////////////////

/**
 * @return An array of pages provided by this module.
 */
function user_meta_page_list () {
    $pages = array();
    if (user_access('user_meta_view')) {
        $pages[] = 'user_metas';
    }
    return $pages;
}

/**
 * Page hook. Adds module content to a page before it is rendered.
 *
 * @param &$page_data Reference to data about the page being rendered.
 * @param $page_name The name of the page being rendered.
 * @param $options The array of options passed to theme('page').
 */
function user_meta_page (&$page_data, $page_name, $options) {
    
    switch ($page_name) {
        
        case 'contact':
            
            // Capture member cid
            $cid = $options['cid'];
            if (empty($cid)) {
                return;
            }
            
            // Add metas tab
            if (user_access('user_meta_view') || user_access('user_meta_edit') || user_access('user_meta_delete') || $cid == user_id()) {
                $user_metas = theme('table', crm_get_table('user_meta', array('cid' => $cid)));
                $user_metas .= theme('form', crm_get_form('user_meta_add', $cid)); // this is where we put the "Add Meta-Tag Assignment" form on the page
                page_add_content_bottom($page_data, $user_metas, 'Meta-Tags');
            }
            
            break;
        
        case 'user_metas':
            page_set_title($page_data, 'Meta-Tags');
            if (user_access('user_meta_view')) {
                // meta_cross_table ( displays tags across the screen, not down )
                $user_metas = theme('table', crm_get_table('user_meta_cross', array('join'=>array('contact', 'member'), 'show_export'=>true)));
                page_add_content_top($page_data, $user_metas, 'View');
            }
            break;
        
        case 'user_meta':
            
            // Capture meta id
            $umid = $options['umid'];
            if (empty($umid)) {
                return;
            }
            
            // Set page title
            page_set_title($page_data, user_meta_description($umid));
            
            // Add edit tab
            if (user_access('user_meta_view') || user_access('user_meta_edit') || user_access('user_meta_delete')) {
                page_add_content_top($page_data, theme('form', crm_get_form('user_meta_edit', $umid)), 'Edit');
            }
            
            break;
    }
}

// Themeing ////////////////////////////////////////////////////////////////////

/**
 * Return the themed html for an add meta assignment form.
 *
 * @param $cid The id of the contact to add a meta assignment for.
 * @return The themed html string.
 */
function theme_user_meta_add_form ($cid) {
    return theme('form', crm_get_form('user_meta_add', $cid));
}

/**
 * Return themed html for an edit meta assignment form.
 *
 * @param $umid The id of the meta assignment to edit.
 * @return The themed html string.
 */
function theme_user_meta_edit_form ($umid) {
    return theme('form', crm_get_form('user_meta_edit', $umid));
}

?>
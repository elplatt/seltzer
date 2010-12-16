<?php 

/*
    Copyright 2009-2010 Edward L. Platt <elplatt@alum.mit.edu>
    
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

// Utility functions ///////////////////////////////////////////////////////////

// DB to Object mapping ////////////////////////////////////////////////////////

/**
 * Return data tree representing key cards
 *
 * @param $opts An associative array of options, possible keys are:
 *   'kid' If specified, returns a single memeber with the matching key id;
 *   'cid' If specified, returns all keys assigned to the contact with specified id;
 *   'filter' An array of filters of the form (<filter name>, <filter param>)
*/ 
function key_data ($opts) {
    
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
        $sql .= " AND `kid`=$opts[kid]";
    }
    if (!empty($opts['cid'])) {
        $sql .= " AND `cid`=$opts[cid]";
    }
    if (!empty($opts['filter'])) {
        foreach ($opts['filter'] as $filter) {
            $name = $filter[0];
            $param = $filter[1];
            switch ($name) {
                case 'active':
                    $sql .= " AND (`start` IS NOT NULL AND `end` IS NULL)";
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
        
        $keys[] = $key;
        $row = mysql_fetch_assoc($res);
    }
    
    // Return data
    return $keys;
}

// Table data structures ///////////////////////////////////////////////////////

/**
 * Return key table structure
 *
 * @param $opts Associative array of options
*/
function key_table ($opts) {
    
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
    if (user_access('key_view')) {
        $table['columns'][] = array("title"=>'Serial', 'class'=>'', 'id'=>'');
        $table['columns'][] = array("title"=>'Slot', 'class'=>'', 'id'=>'');
        $table['columns'][] = array("title"=>'Start', 'class'=>'', 'id'=>'');
        $table['columns'][] = array("title"=>'End', 'class'=>'', 'id'=>'');
    }
    // Add ops column
    if (user_access('key_edit') || user_access('key_delete')) {
        $table['columns'][] = array('title'=>'Ops','class'=>'');
    }
    
    // Add rows
    foreach ($data as $key) {
        
        // Add key data
        $row = array();
        if (user_access('key_view')) {
            
            // Add cells
            $row[] = $key['serial'];
            $row[] = $key['slot'];
            $row[] = $key['start'];
            $row[] = $key['end'];
        }
        
        // Construct ops array
        $ops = array();
        
        // Add edit op
        if (user_access('key_edit')) {
            $ops[] = '<a href="key.php?kid=' . $key['kid'] . '">edit</a> ';
        }
        
        // Add delete op
        if (user_access('key_delete')) {
            $ops[] = '<a href="delete.php?type=key&id=' . $key['kid'] . '">delete</a>';
        }
        
        // Add ops row
        $row[] = join(' ', $ops);
        
        $table['rows'][] = $row;
    }
    
    return $table;
}

/**
 * Return key report table structure
*/
function key_report_table () {
    
    // Ensure user is allowed to view keys
    if (!user_access('key_view')) {
        return NULL;
    }
    
    // Get contact data
    $data = key_data($opts);
    if (count($data) < 1) {
        return array();
    }
    
    $highest = 0;
    foreach ($data as $key) {
        $highest = max($highest, $key['slot']);
    }
    
    // Initialize table
    $table = array(
        "id" => '',
        "class" => '',
        "rows" => array(),
        "columns" => array()
    );
    
    // Add columns
    if (user_access('key_view')) {
        $table['columns'][] = array("title"=>'Next Available Key Slot', 'class'=>'', 'id'=>'');
    }
    
    // Add cell
    $table['rows'][] = array($highest + 1);
    
    return $table;
}

// Forms ///////////////////////////////////////////////////////////////////////

/**
 * Return add key form structure
 *
 * @param cid of the contact to add a key for
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
 * Return edit key form structure
 *
 * @param $kid id of the key to edit
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
 * Return delete key form structure
 *
 * @param $kid id of the key to delete
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
 * Handle key add request.
 */
function command_key_add() {
    global $esc_post;
    
    // Verify permissions
    if (!user_access('key_edit')) {
        error_register('Permission denied: key_edit');
        return 'key.php?kid=' . $esc_post['kid'];
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
    
    return 'member.php?mid=' . member_contact_member_id($esc_post['cid']);
}

/**
 * Handle key update request.
 */
function command_key_update() {
    global $esc_post;
    
    // Verify permissions
    if (!user_access('key_edit')) {
        error_register('Permission denied: key_edit');
        return 'key.php?kid=' . $esc_post['kid'];
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
    
    return 'key.php?kid=' . $esc_post['kid'];
}

/**
 * Handle key delete request.
 */
function command_key_delete() {
    global $esc_post;
    
    // Verify permissions
    if (!user_access('key_delete')) {
        error_register('Permission denied: key_delete');
        return 'key.php?kid=' . $esc_post['kid'];
    }
    
    // Query database
    $sql = "
        DELETE FROM `key`
        WHERE `kid`='$esc_post[kid]'";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    
    return 'members.php';
}

// Pages ///////////////////////////////////////////////////////////////////////

/**
 * Page hook
*/
function key_page(&$data, $page, $options) {
    
    switch ($page) {
        
        case 'member':
            
            // Capture member id
            $mid = $options['mid'];
            if (empty($mid)) {
                return;
            }
            
            // Add keys tab
            if (user_access('key_view') || user_access('key_edit') || user_access('key_delete')) {
                if (!isset($data['Keys'])) {
                    $data['Keys'] = array();
                }
                $keys = theme('key_table', array('cid' => member_contact_id($mid)));
                $keys .= theme('key_add_form', member_contact_id($mid));
                array_push($data['Keys'], $keys);
            }
            
            break;
        
        case 'key':
            
            // Capture key id
            $kid = $options['kid'];
            if (empty($kid)) {
                return;
            }
            
            // Add edit tab
            if (user_access('key_view') || user_access('key_edit') || user_access('key_delete')) {
                if (!isset($data['Edit'])) {
                    $data['Edit'] = array();
                }
                array_unshift($data['Edit'], theme('key_edit_form', $kid));
            }
            
            break;
    }
}

// Themeing ////////////////////////////////////////////////////////////////////

/**
 * Return themed html for a key table
*/
function theme_key_table ($opts = NULL) {
    return theme_table(key_table($opts));
}

/**
 * Return themed html for key add form
 *
 * @param $cid The id of the contact to add a key for
 */
function theme_key_add_form ($cid) {
    return theme_form(key_add_form($cid));
}

/**
 * Return themed html for key edit form
 *
 * @param $kid The id of the key to edit
 */
function theme_key_edit_form ($kid) {
    return theme_form(key_edit_form($kid));
}

/**
 * Return themed html for key report
 */
function theme_key_report () {
    return theme_table(key_report_table());
}

?>
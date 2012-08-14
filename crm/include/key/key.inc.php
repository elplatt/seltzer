<?php 

/*
    Copyright 2009-2011 Edward L. Platt <elplatt@alum.mit.edu>
    
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
 *   'filter' An array of filters of the form (<filter name>, <filter param>)
 * @return An array with each element representing a single key card assignment.
*/ 
function key_data ($opts = array()) {
    
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
 * Return a table structure for a table of key assignments.
 *
 * @param $opts The options to pass to key_data().
 * @return The table structure.
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
            $ops[] = '<a href="key.php?kid=' . $key['kid'] . '#tab-edit">edit</a> ';
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
 * @return The table structure for a key report.
*/
function key_report_table () {
    
    // Ensure user is allowed to view keys
    if (!user_access('key_view')) {
        return NULL;
    }
    
    // Get contact data
    $data = key_data(array('filter'=>array('active')));
    if (count($data) < 1) {
        return array();
    }
    
    // Create list of taken slots, in ascending order
    $taken = array();
    foreach ($data as $key) {
        $taken[] = $key['slot'];
    }
    sort($taken);
    
    // Start at slot 0
    $next = 0;
    foreach ($taken as $n) {
        // Jump to next highest if current is taken
        if ($next == $n) {
            $next++;
        }
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
    $table['rows'][] = array($next);
    
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
 * Handle key add request.
 *
 * @return The url to display on completion.
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
    
    return 'member.php?cid=' . $esc_post['cid'];
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
    
    return 'key.php?kid=' . $esc_post['kid'] . '&tab=edit';
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
 * Page hook.  Adds module content to a page before it is rendered.
 *
 * @param &$data Reference to data about the page being rendered.
 * @param $page The name of the page being rendered.
 * @param $options The array of options passed to theme('page').
*/
function key_page (&$data, $page, $options) {
    
    switch ($page) {
        
        case 'member':
            
            // Capture member cid
            $cid = $options['cid'];
            if (empty($cid)) {
                return;
            }
            
            // Add keys tab
            if (user_access('key_view') || user_access('key_edit') || user_access('key_delete')) {
                if (!isset($data['Keys'])) {
                    $data['Keys'] = array();
                }
                $keys = theme('key_table', array('cid' => $cid));
                $keys .= theme('key_add_form', $cid);
                array_push($data['Keys'], $keys);
            }
            
            break;
        
        case 'key':
            
            // Capture key id
            $kid = $options['kid'];
            if (empty($kid)) {
                return;
            }
            
            // Set page title
            $data['#title'] = key_description($kid);
            
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
 * Return the themed html for a key assignment table.
 *
 * @param $opts The options to pass to key_table().
 * @return The themed html.
*/
function theme_key_table ($opts = NULL) {
    return theme('table', key_table($opts));
}

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

/**
 * Return the themed html for the key assignment report.
 *
 * @return The themed html.
 */
function theme_key_report () {
    return theme('table', key_report_table());
}

?>
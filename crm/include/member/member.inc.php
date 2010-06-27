<?php 

/*
    Copyright 2009-2010 Edward L. Platt <elplatt@alum.mit.edu>
    
    This file is part of the Seltzer CRM Project
    member.inc.php - Member module

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

// DB to Object mapping ////////////////////////////////////////////////////////

/**
 * Return data tree representing members
 *
 * @param $opts An associative array of options, possible keys are:
 *   'mid' If specified, returns a single memeber with the matching member id,
 *   'filter' An array of filters of the form (<filter name>, <filter param>)
*/ 
function member_data ($opts) {
    
    // Query database
    $sql = "
        SELECT
        `member`.`mid`,
        `member`.`cid`, `firstName`, `middleName`, `lastName`, `email`, `phone`, `emergencyName`, `emergencyPhone`,
        `user`.`uid`, `username`, `hash`
        FROM `member`
        LEFT JOIN `contact` ON `member`.`cid`=`contact`.`cid`
        LEFT JOIN `user` ON `member`.`cid`=`user`.`cid`
        LEFT JOIN `membership` ON (`member`.`mid`=`membership`.`mid` AND `membership`.`end` IS NULL)
        LEFT JOIN `plan` ON `plan`.`pid`=`membership`.`pid`
        WHERE 1";
    if (!empty($opts['mid'])) {
        $sql .= " AND `member`.`mid`=$opts[mid]";
    }
    if (!empty($opts['filter'])) {
        foreach ($opts['filter'] as $filter) {
            $name = $filter[0];
            $param = $filter[1];
            switch ($name) {
                case 'active':
                    $sql .= " AND (`membership`.`start` IS NOT NULL AND `membership`.`end` IS NULL)";
                    break;
                case 'voting':
                    $sql .= " AND (`membership`.`start` IS NOT NULL AND `membership`.`end` IS NULL AND `plan`.`voting` <> 0)";
                    break;
            }
        }
    }
    $sql .= "
        ORDER BY `lastName`, `firstName`, `middleName` ASC";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    
    // Store data
    $members = array();
    $row = mysql_fetch_assoc($res);
    while (!empty($row)) {
        $member = array(
            'mid' => $row['mid'],
            'active' => $row['memberActive'],
            'contact' => array(
                'cid' => $row['cid'],
                'firstName' => $row['firstName'],
                'middleName' => $row['middleName'],
                'lastName' => $row['lastName'],
                'email' => $row['email'],
                'phone' => $row['phone'],
                'emergencyName' => $row['emergencyName'],
                'emergencyPhone' => $row['emergencyPhone']
            ),
            'user' => array(
                'uid' => $row['uid'],
                'username' => $row['username'],
                'hash' => $row['hash']
            ),
            'membership' => array()
        );
        
        $members[] = $member;
        $row = mysql_fetch_assoc($res);
    }
    
    // Get list of memberships associated with each member
    // This is slow, should be combined into above query, but works for now -Ed
    foreach ($members as $index => $member) {
        
        // Query all memberships for current member
        $sql = "
            SELECT
            `membership`.`sid`, `membership`.`mid`, `membership`.`start`, `membership`.`end`,
            `plan`.`pid`, `plan`.`name`, `plan`.`price`, `plan`.`active`, `plan`.`voting`
            FROM `membership`
            INNER JOIN `plan` ON `plan`.`pid` = `membership`.`pid`
            WHERE `membership`.`mid`='$member[mid]'
            ORDER BY `membership`.`start` ASC
        ";
        $res = mysql_query($sql);
        if (!$res) die(mysql_error());
        
        // Add each membership
        $row = mysql_fetch_assoc($res);
        while (!empty($row)) {
            $membership = array(
                'sid' => $row['sid'],
                'mid' => $row['mid'],
                'pid' => $row['pid'],
                'start' => $row['start'],
                'end' => $row['end'],
                'plan' => array(
                    'pid' => $row['pid'],
                    'name' => $row['name'],
                    'price' => $row['price'],
                    'active' => $row['active'],
                    'voting' => $row['voting']
                )
            );
            $members[$index]['membership'][] = $membership;
            $row = mysql_fetch_assoc($res);
        }
    }
    
    // Return data
    return $members;
}

/**
 * Return data tree representing membership plans
 * 
 * @param $opts An associative array of options, possible keys are:
 *   'pid' If specified, returns a single plan with the matching id,
 *   'filter' An array of filters of the form (<filter name>, <filter param>)
*/
function member_plan_data ($opts) {
    
    // Construct query for plans
    $sql = "SELECT * FROM `plan` WHERE 1";
    if (!empty($opts['filter'])) {
        foreach ($opts['filter'] as $filter) {
            $name = $filter[0];
            $params = $filter[1];
            switch ($name) {
                case 'active':
                    $sql .= " AND `plan`.`active` <> 0";
                    break;
            }
        }
    }

    // Query database for plans
    $res = mysql_query($sql);
    if (!$res) { die(mysql_error()); }
    
    // Store plans
    $plans = array();
    $row = mysql_fetch_assoc($res);
    while ($row) {
        $plans[] = $row;
        $row = mysql_fetch_assoc($res);
    }
    
    return $plans;
}

/**
 * Return data tree representing memberships
 *
 * @param $opts An associative array of options, possible keys are:
 *   'mid' If specified, returns memberships for the member with the matching id,
 *   'filter' An array of filters of the form (<filter name>, <filter param>)
*/ 
function membership_data ($opts) {
    
    // Query database
    $sql = "
        SELECT *
        FROM `membership`
        INNER JOIN `plan`
        ON `membership`.`pid` = `plan`.`pid`
        WHERE 1";
        
    // Add member id
    if (!empty($opts['mid'])) {
        $sql .= " AND `mid`=$opts[mid]";
    }
    
    // Add filters
    if (!empty($opts['filter'])) {
        foreach ($opts['filter'] as $filter) {
            $name = $filter[0];
            $params = $filter[1];
            switch ($name) {
                default:
                break;
            }
        }
    }
    
    $sql .= "
        ORDER BY `start` DESC";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    
    // Store data
    $memberships = array();
    $row = mysql_fetch_assoc($res);
    while (!empty($row)) {
        $memberships[] = array(
            'mid' => $row['mid'],
            'sid' => $row['mid'],
            'pid' => $row['pid'],
            'start' => $row['start'],
            'end' => $row['end'],
            'plan' => array(
                'pid' => $row['pid'],
                'name' => $row['name'],
                'price' => $row['price'],
                'active' => $row['active'],
                'voting' => $row['voting']
            )
        );
        $row = mysql_fetch_assoc($res);
    }
    
    // Return data
    return $memberships;
}

/**
 * Return options array for membership plans
 */
function member_plan_options($opts = NULL) {
    
    // Get plan data
    $plans = member_plan_data($opts);
    
    // Add option for each member plan
    $options = array();
    foreach ($plans as $plan) {
        $options[$plan['pid']] = "$plan[name] - $plan[price]";
    }
    
    return $options;
}

/**
 * Return data structure representing contacts
 * 
 * @param $opts An associative array of options, possible keys are:
 *   'cid' If specified, returns a single memeber with the matching member id,
 *   'filter' An array of filters of the form (<filter name>, <filter param>)
*/ 
function member_contact_data ($opts) {
    
    // Query database
    $sql = "
        SELECT * FROM `contact`
        WHERE 1";
        
    // Add contact id
    if ($opts['cid']) {
        $sql .= " AND `cid`=$opts[cid]";
    }
    
    // Add filters
    if (!empty($opts['filter'])) {
        foreach ($opts['filter'] as $filter) {
            $name = $filter[0];
            $params = $filter[1];
            switch ($name) {
                default:
                break;
            }
        }
    }

    $sql .= "
        ORDER BY `lastName`, `firstName`, `middleName` ASC";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    
    // Store data
    $contactss = array();
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

// Table data structures ///////////////////////////////////////////////////////

/**
 * Return table structure representing members
 *
*/
function member_table ($opts = NULL) {
    
    // Ensure user is allowed to view members
    if (!permission_check('member_view')) {
        return NULL;
    }
    
    // Get member data
    $members = member_data($opts);
    
    // Create table structure
    $table = array(
        'id' => '',
        'class' => '',
        'rows' => array()
    );
    
    // Add columns
    $table['columns'] = array();
    
    if (permission_check('member_view')) {
        $table['columns'][] = array('title'=>'Name','class'=>'');
        $table['columns'][] = array('title'=>'Membership','class'=>'');
        $table['columns'][] = array('title'=>'E-Mail','class'=>'');
        $table['columns'][] = array('title'=>'Phone','class'=>'');
        $table['columns'][] = array('title'=>'Emergency Contact','class'=>'');
        $table['columns'][] = array('title'=>'Emergency Phone','class'=>'');
    }
    // Add edit column
    if (permission_check('member_edit') || permission_check('member_delete')) {
        $table['columns'][] = array('title'=>'Ops','class'=>'');
    }

    // Loop through member data
    foreach ($members as $member) {
        
        // Add user data
        $row = array();
        if (permission_check('member_view')) {
            
            // Construct name
            $name = $member['contact']['lastName'] . ", ";
            $name .= $member['contact']['firstName'];
            if (!empty($member['contact']['middleName'])) {
                $name .= ' ' . $member['contact']['middleName'];
            }
            
            // Construct membership info
            $recentMembership = end($member['membership']);
            $plan = '';
            if (!empty($recentMembership) && empty($recentMembership['end'])) {
                $plan = $recentMembership['plan']['name'];
            }
            
            // Add cells
            $row[] = $name;
            $row[] = $plan;
            $row[] = $member['contact']['email'];
            $row[] = $member['contact']['phone'];
            $row[] = $member['contact']['emergencyName'];
            $row[] = $member['contact']['emergencyPhone'];
        }
        
        // Construct ops array
        $ops = array();
        
        // Add edit op
        if (permission_check('member_edit')) {
            $ops[] = '<a href="member.php?mid=' . $member['mid'] . '">edit</a> ';
        }
        
        // Add delete op
        if (permission_check('member_delete')) {
            $ops[] = '<a href="delete.php?type=member&id=' . $member['mid'] . '">delete</a>';
        }
        
        // Add ops row
        $row[] = join(' ', $ops);
        
        // Add row to table
        $table['rows'][] = $row;
    }
    
    // Return table
    return $table;
}

/**
 * Return voting member report table
*/
function member_voting_report_table () {
    
    // Ensure user is allowed to view members
    if (!permission_check('member_view')) {
        return NULL;
    }
    
    // Get member data
    $members = member_data(array('filter'=>array(array('voting'))));
    
    // Create table structure
    $table = array(
        'id' => '',
        'class' => 'member-voting-report',
        'rows' => array()
    );
    
    // Add columns
    $table['columns'] = array();
    
    if (permission_check('member_view')) {
        $table['columns'][] = array('title'=>'Name','class'=>'name');
        $table['columns'][] = array('title'=>'Present','class'=>'check');
        $table['columns'][] = array('title'=>'A','class'=>'');
        $table['columns'][] = array('title'=>'B','class'=>'');
        $table['columns'][] = array('title'=>'C','class'=>'');
        $table['columns'][] = array('title'=>'D','class'=>'');
        $table['columns'][] = array('title'=>'E','class'=>'');
    }

    // Loop through member data
    foreach ($members as $member) {
        
        // Add user data
        $row = array();
        if (permission_check('member_view')) {
            $name = $member['contact']['lastName']
                . ', ' . $member['contact']['firstName'];
            if (!empty($member['contact']['middleName'])) {
                $name .= ' ' . $member['contact']['middleName'];
            }
            $row[] = $name;
            $row[] = ' ';
            $row[] = ' ';
            $row[] = ' ';
            $row[] = ' ';
            $row[] = ' ';
            $row[] = ' ';
        }
        
        // Add row to table
        $table['rows'][] = $row;
    }
    
    // Return table
    return $table;
}

/**
 * Return table structure representing memberships
 *
*/
function member_membership_table ($opts = NULL) {
    
    // Ensure user is allowed to view members
    if (!permission_check('member_membership_view')) {
        return NULL;
    }
    
    // Get member data
    $memberships = membership_data($opts);
    
    // Create table structure
    $table = array(
        'id' => '',
        'class' => '',
        'rows' => array()
    );
    
    // Add columns
    $table['columns'] = array();
    
    if (permission_check('member_membership_view')) {
        $table['columns'][] = array('title'=>'Start','class'=>'');
        $table['columns'][] = array('title'=>'End','class'=>'');
        $table['columns'][] = array('title'=>'Plan','class'=>'');
        $table['columns'][] = array('title'=>'Price','class'=>'');
    }

    // Check if there are any results
    if (empty($memberships)) {
        return $table;
    }

    // Loop through membership data
    foreach ($memberships as $membership) {
        
        // Add user data
        $row = array();
        if (permission_check('member_membership_view')) {
            $row[] = $membership['start'];
            $row[] = $membership['end'];
            $row[] = $membership['plan']['name'];
            $row[] = $membership['plan']['price'];
        }
        
        // Construct ops array
        $ops = array();
        
        // Add row to table
        $table['rows'][] = $row;
    }
    
    // Return table
    return $table;
}

// Forms ///////////////////////////////////////////////////////////////////////

/**
 * Return add member form structure
*/
function member_add_form () {
    
    // Generate default start date, first of current month
    $start = date("Y-m-01");
    
    // Create form structure
    $form = array(
        'type' => 'form',
        'method' => 'post',
        'command' => 'member_add',
        'fields' => array(
            array(
                'type' => 'fieldset',
                'label' => 'Add Member',
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
                    ),
                    array(
                        'type' => 'text',
                        'label' => 'Username',
                        'name' => 'username'
                    ),
                    array(
                        'type' => 'select',
                        'label' => 'Plan',
                        'name' => 'pid',
                        'selected' => $member['plan']['pid'],
                        'options' => member_plan_options(array('filter'=>array(array('active'))))
                    ),
                    array(
                        'type' => 'text',
                        'label' => 'Start Date',
                        'name' => 'start',
                        'value' => $start,
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
 * Return add membership form structure
 *
 * @param mid the member id of the member to add a membership for
*/
function member_membership_add_form ($mid) {
    
    // Create form structure
    $form = array(
        'type' => 'form',
        'method' => 'post',
        'command' => 'member_membership_add',
        'hidden' => array(
            'mid' => $mid
        ),
        'fields' => array(
            array(
                'type' => 'fieldset',
                'label' => 'Add Membership',
                'fields' => array(
                    array(
                        'type' => 'select',
                        'label' => 'Plan',
                        'name' => 'pid',
                        'options' => member_plan_options()
                    ),
                    array(
                        'type' => 'text',
                        'label' => 'Start',
                        'name' => 'start',
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
 * Return delete member form structure
 *
 * @param $mid id of the member to delete
*/
function member_delete_form ($mid) {
    
    // Get member data
    $data = member_data(array('mid'=>$mid));
    $member = $data[0];
    
    // Construct member name
    if (empty($member) || count($member) < 1) {
        return array();
    }
    $member_name = $member['contact']['firstName'];
    if (!empty($member['contact']['middleName'])) {
        $member_name .= ' ' . $member['contact']['middleName'];
    }
    $member_name .= ' ' . $member['contact']['lastName'];
    
    // Create form structure
    $form = array(
        'type' => 'form',
        'method' => 'post',
        'command' => 'member_delete',
        'hidden' => array(
            'mid' => $mid,
            'cid' => $member['contact']['cid'],
            'uid' => $member['user']['uid']
        ),
        'fields' => array(
            array(
                'type' => 'fieldset',
                'label' => 'Delete Member',
                'fields' => array(
                    array(
                        'type' => 'message',
                        'value' => '<p>Are you sure you want to delete the member "' . $member_name . '"? This cannot be undone.',
                    ),
                    array(
                        'type' => 'checkbox',
                        'label' => 'Also delete user?',
                        'name' => 'deleteUser'
                    ),
                    array(
                        'type' => 'checkbox',
                        'label' => 'Also delete contact info?',
                        'name' => 'deleteContact'
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

/**
 * Return edit contact form structure
 *
 * @param $cid id of the member to edit
*/
function member_contact_edit_form ($cid) {
    
    // Get contact data
    $data = member_contact_data(array('cid'=>$cid));
    $contact = $data[0];
    if (empty($contact) || count($contact) < 1) {
        return array();
    }
    
    // Create form structure
    $form = array(
        'type' => 'form',
        'method' => 'post',
        'command' => 'contact_update',
        'hidden' => array(
            'cid' => $cid
        ),
        'fields' => array(
            array(
                'type' => 'fieldset',
                'label' => 'Edit Contact Info',
                'fields' => array(
                    array(
                        'type' => 'text',
                        'label' => 'First Name',
                        'name' => 'firstName',
                        'value' => $contact['firstName'],
                    ),
                    array(
                        'type' => 'text',
                        'label' => 'Middle Name',
                        'name' => 'middleName',
                        'value' => $contact['middleName'],
                    ),
                    array(
                        'type' => 'text',
                        'label' => 'Last Name',
                        'name' => 'lastName',
                        'value' => $contact['lastName'],
                    ),
                    array(
                        'type' => 'text',
                        'label' => 'Email',
                        'name' => 'email',
                        'value' => $contact['email'],
                    ),
                    array(
                        'type' => 'text',
                        'label' => 'Phone',
                        'name' => 'phone',
                        'value' => $contact['phone'],
                    ),
                    array(
                        'type' => 'text',
                        'label' => 'Emergency Contact',
                        'name' => 'emergencyName',
                        'value' => $contact['emergencyName']
                    ),
                    array(
                        'type' => 'text',
                        'label' => 'Emergency Phone',
                        'name' => 'emergencyPhone',
                        'value' => $contact['emergencyPhone']
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

// Filters

/**
 * Return form structure for member filter
*/
function member_filter_form () {

    // Available filters    
    $filters = array(
        'all' => 'All',
        'voting' => 'Voting',
        'active' => 'Active'
    );
    
    // Default filter
    $selected = empty($_SESSION['member_filter_option']) ? 'all' : $_SESSION['member_filter_option'];
    
    // Construct hidden fields to pass GET params
    $hidden = array();
    foreach ($_GET as $key => $val) {
        $hidden[$key] = $val;
    }
    
    $form = array(
        'type' => 'form',
        'method' => 'get',
        'command' => 'member_filter',
        'hidden' => $hidden,
        'fields' => array(
            array(
                'type' => 'fieldset',
                'label' => 'Filter',
                'fields' => array(
                    array(
                        'type' => 'select',
                        'name' => 'filter',
                        'options' => $filters,
                        'selected' => $selected
                    ),
                    array(
                        'type' => 'submit',
                        'value' => 'Filter'
                    )
                )
            )
        )
    );
    
    return $form;
}

// Request Handlers ////////////////////////////////////////////////////////////

/**
 * Handle member add request.
 */
function command_member_add() {
    global $esc_post;
    
    // Verify permissions
    if (!permission_check('member_add')) {
        error_register('Permission denied: member_add');
        return 'members.php';
    }
    if (!permission_check('contact_add')) {
        error_register('Permission denied: contact_add');
        return 'members.php';
    }
    if (!permission_check('member_add')) {
        error_register('Permission denied: member_add');
        return 'members.php';
    }
    
    // Add contact
    $sql = "
        INSERT INTO `contact`
        (`firstName`,`middleName`,`lastName`,`email`,`phone`,`emergencyName`,`emergencyPhone`)
        VALUES
        ('$esc_post[firstName]','$esc_post[middleName]','$esc_post[lastName]','$esc_post[email]','$esc_post[phone]','$esc_post[emergencyName]','$esc_post[emergencyPhone]')";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    $cid = mysql_insert_id();
    
    // Add user
    $sql = "
        INSERT INTO `user`
        (`username`, `cid`)
        VALUES
        ('$esc_post[username]', '$cid')";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    $uid = mysql_insert_id();
    
    // Add role entry
    $sql = "
        INSERT INTO `role`
        (`uid`, `member`)
        VALUES
        ('$uid', 1)";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    
    // Add member
    $sql = "
        INSERT INTO `member`
        (`pid`,`cid`)
        VALUES
        ('$esc_post[pid]','$cid')";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    $mid = mysql_insert_id();
    
    // Add membership
    $sql = "
        INSERT INTO `membership`
        (`mid`, `pid`, `start`)
        VALUES
        ('$mid', '$esc_post[pid]', '$esc_post[start]')
    ";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    
    return 'members.php';
}

/**
 * Handle membership add request.
 */
function command_member_membership_add() {
    global $esc_post;
    
    // Verify permissions
    if (!permission_check('member_edit')) {
        error_register('Permission denied: member_edit');
        return 'members.php';
    }
    if (!permission_check('member_membership_edit')) {
        error_register('Permission denied: member_membership_edit');
        return 'members.php';
    }
    
    // Add membership
    $sql = "
        INSERT INTO `membership`
        (`mid`,`pid`,`start`";
    if (!empty($esc_post['end'])) {
        $sql .= ", `end`";
    }
    $sql .= ")
        VALUES
        ('$esc_post[mid]','$esc_post[pid]','$esc_post[start]'";
        
    if (!empty($esc_post['end'])) {
        $sql .= ",'$esc_post[end]'";
    }
    $sql .= ")";
    
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    
    return "member.php?mid=$_POST[mid]";
}

/**
 * Handle member filter request.
 */
function command_member_filter() {
    
    // Set filter in session
    $_SESSION['member_filter_option'] = $_GET['filter'];
    
    // Set filter
    if ($_GET['filter'] == 'all') {
        $_SESSION['member_filter'] = array();
    }
    if ($_GET['filter'] == 'active') {
        $_SESSION['member_filter'] = array(array('active'));
    }
    if ($_GET['filter'] == 'voting') {
        $_SESSION['member_filter'] = array(array('voting'));
    }
    
    // Construct query string
    $params = array();
    foreach ($_GET as $k=>$v) {
        if ($k == 'command' || $k == 'filter') {
            continue;
        }
        $params[] = urlencode($k) . '=' . urlencode($v);
    }
    if (!empty($params)) {
        $query = '?' . join('&', $params);
    }
    
    return 'members.php' . $query;
}

/**
 * Handle member delete request.
 */
function command_member_delete() {
    global $esc_post;
    
    // Verify permissions
    if (!permission_check('member_delete')) {
        error_register('Permission denied: member_delete');
        return 'members.php';
    }
    if ($_POST['deleteUser'] && !permission_check('user_delete')) {
        error_register('Permission denied: user_delete');
        return 'members.php';
    }
    if ($_POST['deleteContact'] && !permission_check('contact_delete')) {
        error_register('Permission denied: contact_delete');
        return 'members.php';
    }

    // Delete member
    $sql = "DELETE FROM `member` WHERE `mid`='$esc_post[mid]'";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    
    // Delete user
    if ($_POST['deleteUser']) {
        $sql = "DELETE FROM `user` WHERE `uid`='$esc_post[uid]'";
        $res = mysql_query($sql);
        if (!$res) die(mysql_error());
    }
    
    // Delete contact info
    if ($_POST['deleteContact']) {
        $sql = "DELETE FROM `contact` WHERE `cid`='$esc_post[cid]'";
        $res = mysql_query($sql);
        if (!$res) die(mysql_error());
    }

    return 'members.php';
}

/**
 * Handle contact update request.
 */
function command_contact_update() {
    global $esc_post;
    
    // Verify permissions
    if (!permission_check('contact_edit')) {
        error_register('Permission denied: contact_edit');
        return 'members.php';
    }
    
    // Query database
    $sql = "
        UPDATE `contact`
        SET
        `firstName`='$esc_post[firstName]',
        `middleName`='$esc_post[middleName]',
        `lastName`='$esc_post[lastName]',
        `email`='$esc_post[email]',
        `phone`='$esc_post[phone]',
        `emergencyName`='$esc_post[emergencyName]',
        `emergencyPhone`='$esc_post[emergencyPhone]'
        WHERE `cid`='$esc_post[cid]'";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    
    return 'members.php';
}

// Theme functions

/**
 * Return themed html for a table of members
*/
function theme_member_table ($opts = NULL) {
    return theme_table(member_table($opts));
}

/**
 * Returned themed html for add member form.
*/
function theme_member_add_form () {
    return theme_form(member_add_form());
}

/**
 * Returned themed html for edit member form.
 *
 * @param $mid The id of the member to edit
*/
function theme_member_edit_form ($mid) {
    return theme_form(member_edit_form($mid));
}

/**
 * Returned themed html for edit contact form.
 *
 * @param $cid The id of the contact to edit
*/
function theme_member_contact_edit_form ($cid) {
    return theme_form(member_contact_edit_form($cid));
}

/**
 * Return themed html for a member filter form
*/
function theme_member_filter_form () {
    return theme_form(member_filter_form());
}

/**
 * Return themed html for a member votin report
*/
function theme_member_voting_report () {
    return theme_table(member_voting_report_table());
}

/**
 * Return themed html for a membership table
 */
function theme_member_membership_table ($opts = NULL) {
    return theme_table(member_membership_table($opts));
}

/**
 * Return themed html for a membership add form
 *
 * @param $mid the member id of the member who owns the membership
 */
function theme_member_membership_add_form ($mid) {
    return theme_form(member_membership_add_form($mid));
}
?>
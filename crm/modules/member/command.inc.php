<?php 

/*
    Copyright 2009-2013 Edward L. Platt <ed@elplatt.com>
    
    This file is part of the Seltzer CRM Project
    command.inc.php - Member module - request handlers

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
 * Handle member add request.
 *
 * @return The url to display when complete.
 */
function command_member_add () {
    global $esc_post;
    global $config_email_to;
    global $config_email_from;
    global $config_org_name;
    
    // Verify permissions
    if (!user_access('member_add')) {
        error_register('Permission denied: member_add');
        return 'index.php?q=members';
    }
    if (!user_access('contact_add')) {
        error_register('Permission denied: contact_add');
        return 'index.php?q=members.php';
    }
    if (!user_access('member_add')) {
        error_register('Permission denied: member_add');
        return 'index.php?q=members.php';
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
    $esc_cid = mysql_real_escape_string($cid);
    
    // Find Username
    $username = $_POST['username'];
    $n = 0;
    while (empty($username) && $n < 100) {
        
        // Contruct test username
        $test_username = strtolower($_POST[firstName]{0} . $_POST[lastName]);
        if ($n > 0) {
            $test_username .= $n;
        }
        
        // Check whether username is taken
        $esc_test_name = mysql_real_escape_string($test_username);
        $sql = "SELECT * FROM `user` WHERE `username`='$esc_test_name'";
        $res = mysql_query($sql);
        if (!$res) die(mysql_error());
        $row = mysql_fetch_assoc($res);
        if (!$row) {
            $username = $test_username;
        }
        $n++;
    }
    if (empty($username)) {
        error_register('Please specify a username');
        return 'index.php?q=members&tab=add';
    }
    
    // Add user

    $user = array();
    $user['username'] = $username;
    $user['cid'] = $cid;
    user_save($user);
     
    // Add role entry
    $sql = "SELECT `rid` FROM `role` WHERE `name`='member'";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    $row = mysql_fetch_assoc($res);
    $esc_rid = mysql_real_escape_string($row['rid']);
    
    if ($row) {
        $sql = "
            INSERT INTO `user_role`
            (`cid`, `rid`)
            VALUES
            ('$esc_cid', '$esc_rid')";
        $res = mysql_query($sql);
        if (!$res) die(mysql_error());
    }
    
    // Add member
    $sql = "
        INSERT INTO `member`
        (`cid`)
        VALUES
        ('$esc_cid')";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    
    // Add membership
    $sql = "
        INSERT INTO `membership`
        (`cid`, `pid`, `start`)
        VALUES
        ('$cid', '$esc_post[pid]', '$esc_post[start]')
    ";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    
    // Notify admins
    $from = "\"$config_org_name\" <$config_email_from>";
    $headers = "From: $from\r\nContent-Type: text/html; charset=ISO-8859-1\r\n";
    if (!empty($config_email_to)) {
        $name = member_name($_POST['firstName'], $_POST['middleName'], $_POST['lastName']);
        $content = theme('member_created_email', $user['cid']);
        mail($config_email_to, "New Member: $name", $content, $headers);
    }
    
    // Notify user
    $confirm_url = user_reset_password_url($user['username']);
    $content = theme('member_welcome_email', $user['cid'], $confirm_url);
    mail($_POST['email'], "Welcome to $config_org_name", $content, $headers);
    
    return "index.php?q=member&cid=$esc_cid";
}

/**
 * Handle membership plan add request.
 *
 * @return The url to display on completion.
 */
function command_member_plan_add () {
    global $esc_post;
    
    // Verify permissions
    if (!user_access('member_plan_edit')) {
        error_register('Permission denied: member_plan_edit');
        return 'index.php?q=plans';
    }
    
    // Add plan
    $sql = "
        INSERT INTO `plan`
        (`name`,`price`, `voting`, `active`)
        VALUES
        ('$esc_post[name]', '$esc_post[price]', '$esc_post[voting]', '$esc_post[active]')
    ";
    
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    
    return "index.php?q=plans";
}

/**
 * Handle membership plan update request.
 *
 * @return The url to display on completion.
 */
function command_member_plan_update () {
    global $esc_post;
    
    // Verify permissions
    if (!user_access('member_plan_edit')) {
        error_register('Permission denied: member_plan_edit');
        return 'index.php?q=plans';
    }
    
    // Update plan
    $sql = "
        UPDATE `plan`
        SET
            `name`='$esc_post[name]',
            `price`='$esc_post[price]',
            `active`='$esc_post[active]',
            `voting`='$esc_post[voting]'
        WHERE `pid`='$esc_post[pid]'
    ";
    
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    
    return "index.php?q=plans";
}

/**
 * Handle delete membership plan request.
 *
 * @return The url to display on completion.
 */
function command_member_plan_delete () {
    global $esc_post;
    
    // Verify permissions
    if (!user_access('member_plan_edit')) {
        error_register('Permission denied: member_plan_edit');
        return 'index.php?q=members';
    }

    // Delete plan
    $sql = "DELETE FROM `plan` WHERE `pid`='$esc_post[pid]'";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());

    return 'index.php?q=plans';
}

/**
 * Handle membership add request.
 *
 * @return The url to display on completion.
 */
function command_member_membership_add () {
    global $esc_post;
    
    // Verify permissions
    if (!user_access('member_edit')) {
        error_register('Permission denied: member_edit');
        return 'index.php?q=members';
    }
    if (!user_access('member_membership_edit')) {
        error_register('Permission denied: member_membership_edit');
        return 'index.php?q=members';
    }
    
    // Add membership
    $sql = "
        INSERT INTO `membership`
        (`cid`,`pid`,`start`";
    if (!empty($esc_post['end'])) {
        $sql .= ", `end`";
    }
    $sql .= ")
        VALUES
        ('$esc_post[cid]','$esc_post[pid]','$esc_post[start]'";
        
    if (!empty($esc_post['end'])) {
        $sql .= ",'$esc_post[end]'";
    }
    $sql .= ")";
    
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    
    return "index.php?q=member&cid=$_POST[cid]";
}

/**
 * Handle membership update request.
 *
 * @param $sid The sid of the membership to update.
 * @return The url to display on completion.
 */
function command_member_membership_update () {
    global $esc_post;
    
    // Verify permissions
    if (!user_access('member_edit')) {
        error_register('Permission denied: member_edit');
        return 'index.php?q=members';
    }
    if (!user_access('member_membership_edit')) {
        error_register('Permission denied: member_membership_edit');
        return 'index.php?q=members';
    }
    
    // Update membership
    $sql = "
        UPDATE `membership`
        SET
            `pid`='$esc_post[pid]'
    ";
    if (!empty($esc_post['start'])) {
        $sql .= ", `start`='$esc_post[start]'";
    } else {
        $sql .= ", `start`=NULL";
    }
    if (!empty($esc_post['end'])) {
        $sql .= ", `end`='$esc_post[end]'";
    } else {
        $sql .= ", `end`=NULL";
    }
    $sql .= "
        WHERE `sid`='$esc_post[sid]'
    ";
    
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    
    return "index.php?q=member&cid=$_POST[cid]&tab=plan";
}

/**
 * Handle member filter request.
 *
 * @return The url to display on completion.
 */
function command_member_filter () {
    
    // Set filter in session
    $_SESSION['member_filter_option'] = $_GET['filter'];
    
    // Set filter
    if ($_GET['filter'] == 'all') {
        $_SESSION['member_filter'] = array();
    }
    if ($_GET['filter'] == 'active') {
        $_SESSION['member_filter'] = array('active'=>true);
    }
    if ($_GET['filter'] == 'voting') {
        $_SESSION['member_filter'] = array('voting'=>true);
    }
    
    // Construct query string
    $params = array();
    foreach ($_GET as $k=>$v) {
        if ($k == 'command' || $k == 'filter' || $k == 'q') {
            continue;
        }
        $params[] = urlencode($k) . '=' . urlencode($v);
    }
    if (!empty($params)) {
        $query = '&' . join('&', $params);
    }
    
    return 'index.php?q=members' . $query;
}

/**
 * Handle member delete request.
 *
 * @return The url to display on completion.
 */
function command_member_delete () {
    global $esc_post;
    
    // Verify permissions
    if (!user_access('member_delete')) {
        error_register('Permission denied: member_delete');
        return 'index.php?q=members';
    }
    if ($_POST['deleteUser'] && !user_access('user_delete')) {
        error_register('Permission denied: user_delete');
        return 'index.php?q=members';
    }
    if ($_POST['deleteContact'] && !user_access('contact_delete')) {
        error_register('Permission denied: contact_delete');
        return 'index.php?q=members';
    }

    // Delete user and roles
    if ($_POST['deleteUser']) {
        $sql = "DELETE FROM `user` WHERE `cid`='$esc_post[cid]'";
        $res = mysql_query($sql);
        if (!$res) die(mysql_error());
        $sql = "DELETE FROM `user_role` WHERE `cid`='$esc_post[cid]'";
        $res = mysql_query($sql);
        if (!$res) die(mysql_error());
    }
    
    // Delete member
    $sql = "DELETE FROM `member` WHERE `cid`='$esc_post[cid]'";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    
    // Delete contact info
    if ($_POST['deleteContact']) {
        $sql = "DELETE FROM `contact` WHERE `cid`='$esc_post[cid]'";
        $res = mysql_query($sql);
        if (!$res) die(mysql_error());
    }

    return 'index.php?q=members';
}

/**
 * Handle membership delete request.
 *
 * @return The url to display on completion.
 */
function command_member_membership_delete () {
    global $esc_post;
    
    // Verify permissions
    if (!user_access('member_membership_edit')) {
        error_register('Permission denied: member_membership_edit');
        return 'index.php?q=members';
    }

    // Delete membership
    $sql = "DELETE FROM `membership` WHERE `sid`='$esc_post[sid]'";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());

    return 'index.php?q=members';
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
        return 'index.php?q=members';
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
    
    return 'index.php?q=members';
}

/**
 * Handle member import request.
 *
 * @return The url to display on completion.
 */
function command_member_import () {
    global $config_org_name;
    
    if (!user_access('contact_edit')) {
        error_register('User does not have permission: contact_edit');
        return 'index.php';
    }
    if (!user_access('member_edit')) {
        error_register('User does not have permission: member_edit');
        return 'index.php';
    }
    
    if (!array_key_exists('member-file', $_FILES)) {
        error_register('No member file uploaded');
        return 'index.php?q=members&tab=import';
    }
    
    $csv = file_get_contents($_FILES['member-file']['tmp_name']);
    
    $data = csv_parse($csv);
    
    foreach ($data as $row) {
        
        // Convert row keys to lowercase and remove spaces
        foreach ($row as $key => $value) {
            $new_key = str_replace(' ', '', strtolower($key));
            unset($row[$key]);
            $row[$new_key] = $value;
        }
        
        // Add contact
        $firstName = mysql_real_escape_string($row['firstname']);
        $middleName = mysql_real_escape_string($row['middlename']);
        $lastName = mysql_real_escape_string($row['lastname']);
        $email = mysql_real_escape_string($row['email']);
        $phone = mysql_real_escape_string($row['phone']);
        $emergencyName = mysql_real_escape_string($row['emergencyname']);
        $emergencyPhone = mysql_real_escape_string($row['emergencyphone']);
        $sql = "
            INSERT INTO `contact`
            (`firstName`,`middleName`,`lastName`,`email`,`phone`,`emergencyName`,`emergencyPhone`)
            VALUES
            ('$firstName','$middleName','$lastName','$email','$phone','$emergencyName','$emergencyPhone')";
        $res = mysql_query($sql);
        if (!$res) die(mysql_error());
        $cid = mysql_insert_id();
        $esc_cid = mysql_real_escape_string($cid);
        
        // Add member
        $sql = "
            INSERT INTO `member`
            (`cid`)
            VALUES
            ('$esc_cid')";
        $res = mysql_query($sql);
        if (!$res) die(mysql_error());
            
        // Find Username
 
        $username = $row['username'];
        $n = 0;
        while (empty($username) && $n < 100) {
            
            // Contruct test username
            $test_username = strtolower($row['firstname']{0} . $row['lastName']);
            if ($n > 0) {
                $test_username .= $n;
            }
            
            // Check whether username is taken
            $esc_test_name = mysql_real_escape_string($test_username);
            $sql = "SELECT * FROM `user` WHERE `username`='$esc_test_name'";
            $res = mysql_query($sql);
            if (!$res) die(mysql_error());
            $user_row = mysql_fetch_assoc($res);
            if (!$user_row) {
                $username = $test_username;
            }
            $n++;
        }
        if (empty($username)) {
            error_register('Please specify a username');
            return 'index.php?q=members&tab=import';
        }
        
        // Add user
        $user = array();
        $user['username'] = $username;
        $user['cid'] = $cid;
        user_save($user);
         
        // Add role entry
        $sql = "SELECT `rid` FROM `role` WHERE `name`='member'";
        $res = mysql_query($sql);
        if (!$res) die(mysql_error());
        $role_row = mysql_fetch_assoc($res);
        $esc_rid = mysql_real_escape_string($role_row['rid']);
        
        if ($role_row) {
            $sql = "
                INSERT INTO `user_role`
                (`cid`, `rid`)
                VALUES
                ('$esc_cid', '$esc_rid')";
            $res = mysql_query($sql);
            if (!$res) die(mysql_error());
        }
        
        // Add plan if necessary
        $esc_plan_name = mysql_real_escape_string($row['plan']);
        $sql = "SELECT `pid` FROM `plan` WHERE `name`='$esc_plan_name'";
        $res = mysql_query($sql);
        if (!$res) die(mysql_error());
        if (mysql_num_rows($res) < 1) {
            $sql = "
                INSERT INTO `plan`
                (`name`, `active`)
                VALUES
                ('$esc_plan_name', '1')
            ";
            $res = mysql_query($sql);
            if (!$res) die(mysql_error());
            $pid = mysql_insert_id();
        } else {
            $plan_row = mysql_fetch_assoc($res);
            $pid = $plan_row['pid'];
        }
        
        // Add membership
        $esc_start = mysql_real_escape_string($row['startdate']);
        $esc_pid = mysql_real_escape_string($pid);
        
        $sql = "
            INSERT INTO `membership`
            (`cid`, `pid`, `start`)
            VALUES
            ('$esc_cid', '$esc_pid', '$esc_start')
        ";
        $res = mysql_query($sql);
        if (!$res) die(mysql_error());
        
        // Notify admins
        $from = "\"$config_org_name\" <$config_email_from>";
        $headers = "From: $from\r\nContent-Type: text/html; charset=ISO-8859-1\r\n";
        if (!empty($config_email_to)) {
            $name = member_name($_POST['firstName'], $_POST['middleName'], $_POST['lastName']);
            $content = theme('member_created_email', $user['cid']);
            mail($config_email_to, "New Member: $name", $content, $headers);
        }
        
        // Notify user
        $confirm_url = user_reset_password_url($user['username']);
        $content = theme('member_welcome_email', $user['cid'], $confirm_url);
        mail($email, "Welcome to $config_org_name", $content, $headers);
    }
    
    return 'index.php?q=members';
}

/**
 * Handle plan import request.
 *
 * @return The url to display on completion.
 */
function command_member_plan_import () {
    
    if (!user_access('member_plan_edit')) {
        error_register('User does not have permission: member_plan_edit');
        return 'index.php';
    }
    
    if (!array_key_exists('plan-file', $_FILES)) {
        error_register('No plan file uploaded');
        return 'index.php?q=plans&tab=import';
    }
    
    $csv = file_get_contents($_FILES['plan-file']['tmp_name']);
    
    $data = csv_parse($csv);
    
    foreach ($data as $row) {
        
        // Convert row keys to lowercase and remove spaces
        foreach ($row as $key => $value) {
            $new_key = str_replace(' ', '', strtolower($key));
            unset($row[$key]);
            $row[$new_key] = $value;
        }
        
        // Add plan
        $name = mysql_real_escape_string($row['planname']);
        $price = mysql_real_escape_string($row['price']);
        $active = mysql_real_escape_string($row['active']);
        $voting = mysql_real_escape_string($row['voting']);
        $sql = "
            INSERT INTO `plan`
            (`name`,`price`,`active`,`voting`)
            VALUES
            ('$name','$price','$active','$voting')";
        $res = mysql_query($sql);
        if (!$res) die(mysql_error());
        $pid = mysql_insert_id();
    }
    
    return 'index.php?q=plans';
}
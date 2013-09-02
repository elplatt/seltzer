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
        return crm_url('members');
    }
    if (!user_access('contact_add')) {
        error_register('Permission denied: contact_add');
        return crm_url('members');
    }
    if (!user_access('user_add')) {
        error_register('Permission denied: user_add');
        return crm_url('members');
    }
    
    // Find username or create a new one
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
        if (!$res) crm_error(mysql_error());
        $row = mysql_fetch_assoc($res);
        if (!$row) {
            $username = $test_username;
        }
        $n++;
    }
    if (empty($username)) {
        error_register('Please specify a username');
        return crm_url('members&tab=add');
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
    // Add user fields
    $user = array('username' => $username);
    $contact['user'] = $user;
    // Add member fields
    $membership = array(
        array(
            'pid' => $_POST['pid']
            , 'start' => $_POST['start']
        )
    );
    $member = array('membership' => $membership);
    $contact['member'] = $member;
    // Add user fields
    $user = array('username' => $username);
    $contact['user'] = $user;
    // Save to database
    $contact = contact_save($contact);
    
    // Add role entry
    $sql = "SELECT `rid` FROM `role` WHERE `name`='member'";
    $res = mysql_query($sql);
    if (!$res) crm_error(mysql_error());
    $row = mysql_fetch_assoc($res);
    $esc_cid = mysql_real_escape_string($contact['cid']);
    $esc_rid = mysql_real_escape_string($row['rid']);
    
    if ($row) {
        $sql = "
            INSERT INTO `user_role`
            (`cid`, `rid`)
            VALUES
            ('$esc_cid', '$esc_rid')";
        $res = mysql_query($sql);
        if (!$res) crm_error(mysql_error());
    }
    
    if (function_exists('paypal_payment_revision')) {
        $esc_create_paypal_contact = $_POST['create_paypal_contact'] ? '1' : '0';
        $esc_paypal_email = $_POST['email'];
        if ($esc_create_paypal_contact === '1') {
            if (!empty($esc_paypal_email)) {
                $sql = "
                    INSERT INTO `contact_paypal`
                    (`paypal_email`, `cid`)
                    VALUES
                    ('$esc_paypal_email', '$esc_cid')
                ";
                $res = mysql_query($sql);
                if (!$res) crm_error(mysql_error());
            }
        }
    }
    
    // Notify admins
    $from = "\"$config_org_name\" <$config_email_from>";
    $headers = "From: $from\r\nContent-Type: text/html; charset=ISO-8859-1\r\n";
    if (!empty($config_email_to)) {
        $name = theme_contact_name($contact['cid']);
        $content = theme('member_created_email', $contact['cid']);
        mail($config_email_to, "New Member: $name", $content, $headers);
    }
    
    // Notify user
    $confirm_url = user_reset_password_url($contact['user']['username']);
    $content = theme('member_welcome_email', $contact['user']['cid'], $confirm_url);
    mail($_POST['email'], "Welcome to $config_org_name", $content, $headers);
    
    return crm_url("contact&cid=$esc_cid");
}

/**
 * Handle membership plan add request.
 *
 * @return The url to display on completion.
 */
function command_member_plan_add () {
    $esc_name = mysql_real_escape_string($_POST['name']);
    $esc_price = mysql_real_escape_string($_POST['price']);
    $esc_voting = $_POST['voting'] ? '1' : '0';
    $esc_active = $_POST['active'] ? '1' : '0';
    
    // Verify permissions
    if (!user_access('member_plan_edit')) {
        error_register('Permission denied: member_plan_edit');
        return crm_url('plans');
    }
    
    // Add plan
    $sql = "
        INSERT INTO `plan`
        (`name`,`price`, `voting`, `active`)
        VALUES
        ('$esc_name', '$esc_price', '$esc_voting', '$esc_active')
    ";
    
    $res = mysql_query($sql);
    if (!$res) crm_error(mysql_error());
    
    return crm_url('plans');
}

/**
 * Handle membership plan update request.
 *
 * @return The url to display on completion.
 */
function command_member_plan_update () {
    $esc_name = mysql_real_escape_string($_POST['name']);
    $esc_price = mysql_real_escape_string($_POST['price']);
    $esc_active = $_POST['active'] ? '1' : '0';
    $esc_voting = $_POST['voting'] ? '1' : '0';
    $esc_pid = mysql_real_escape_string($_POST['pid']);
    
    // Verify permissions
    if (!user_access('member_plan_edit')) {
        error_register('Permission denied: member_plan_edit');
        return crm_url('plans');
    }
    
    // Update plan
    $sql = "
        UPDATE `plan`
        SET
            `name`='$esc_name',
            `price`='$esc_price',
            `active`='$esc_active',
            `voting`='$esc_voting'
        WHERE `pid`='$esc_pid'
    ";
    
    $res = mysql_query($sql);
    if (!$res) crm_error(mysql_error());
    
    return crm_url('plans');
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
        return crm_url('members');
    }
    
    // Delete plan
    $sql = "DELETE FROM `plan` WHERE `pid`='$esc_post[pid]'";
    $res = mysql_query($sql);
    if (!$res) crm_error(mysql_error());
    
    return crm_url('plans');
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
        return crm_url('members');
    }
    if (!user_access('member_membership_edit')) {
        error_register('Permission denied: member_membership_edit');
        return crm_url('members');
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
    if (!$res) crm_error(mysql_error());
    
    return crm_url("contact&cid=$_POST[cid]");
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
        return crm_url('members');
    }
    if (!user_access('member_membership_edit')) {
        error_register('Permission denied: member_membership_edit');
        return crm_url('members');
    }
    // Construct membership object and save
    $membership = array(
        'sid' => $_POST['sid']
        , 'cid' => $_POST['cid']
        , 'pid' => $_POST['pid']
        , 'start' => $_POST['start']
        , 'end' => $_POST['end']
    );
    member_membership_save($membership);
    return crm_url("contact&cid=$_POST[cid]&tab=plan");
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
    
    return crm_url('members') . $query;
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
        return crm_url('members');
    }
    
    // Delete membership
    $sql = "DELETE FROM `membership` WHERE `sid`='$esc_post[sid]'";
    $res = mysql_query($sql);
    if (!$res) crm_error(mysql_error());
    
    return crm_url('members');
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
        return crm_url('members');
    }
    if (!user_access('member_edit')) {
        error_register('User does not have permission: member_edit');
        return crm_url('members');
    }
    
    if (!array_key_exists('member-file', $_FILES)) {
        error_register('No member file uploaded');
        return crm_url('members&tab=import');
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
        if (!$res) crm_error(mysql_error());
        $cid = mysql_insert_id();
        $esc_cid = mysql_real_escape_string($cid);
        
        // Add member
        $sql = "
            INSERT INTO `member`
            (`cid`)
            VALUES
            ('$esc_cid')";
        $res = mysql_query($sql);
        if (!$res) crm_error(mysql_error());
        
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
            if (!$res) crm_error(mysql_error());
            $user_row = mysql_fetch_assoc($res);
            if (!$user_row) {
                $username = $test_username;
            }
            $n++;
        }
        if (empty($username)) {
            error_register('Please specify a username');
            return crm_url('members&tab=import');
        }
        
        // Add user
        $user = array();
        $user['username'] = $username;
        $user['cid'] = $cid;
        user_save($user);
        
        // Add role entry
        $sql = "SELECT `rid` FROM `role` WHERE `name`='member'";
        $res = mysql_query($sql);
        if (!$res) crm_error(mysql_error());
        $role_row = mysql_fetch_assoc($res);
        $esc_rid = mysql_real_escape_string($role_row['rid']);
        
        if ($role_row) {
            $sql = "
                INSERT INTO `user_role`
                (`cid`, `rid`)
                VALUES
                ('$esc_cid', '$esc_rid')";
            $res = mysql_query($sql);
            if (!$res) crm_error(mysql_error());
        }
        
        // Add plan if necessary
        $esc_plan_name = mysql_real_escape_string($row['plan']);
        $sql = "SELECT `pid` FROM `plan` WHERE `name`='$esc_plan_name'";
        $res = mysql_query($sql);
        if (!$res) crm_error(mysql_error());
        if (mysql_num_rows($res) < 1) {
            $sql = "
                INSERT INTO `plan`
                (`name`, `active`)
                VALUES
                ('$esc_plan_name', '1')
            ";
            $res = mysql_query($sql);
            if (!$res) crm_error(mysql_error());
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
        if (!$res) crm_error(mysql_error());
        
        if (function_exists('paypal_payment_revision')) {
            if (!empty($email)) {
                $sql = "
                    INSERT INTO `contact_paypal`
                    (`paypal_email`, `cid`)
                    VALUES
                    ('$email', '$esc_cid')
                ";
                $res = mysql_query($sql);
                if (!$res) crm_error(mysql_error());
            }
        }
        
        // Notify admins
        $from = "\"$config_org_name\" <$config_email_from>";
        $headers = "From: $from\r\nContent-Type: text/html; charset=ISO-8859-1\r\n";
        if (!empty($config_email_to)) {
            $name = theme_contact_name($_POST['cid']);
            $content = theme('member_created_email', $user['cid']);
            mail($config_email_to, "New Member: $name", $content, $headers);
        }
        
        // Notify user
        $confirm_url = user_reset_password_url($user['username']);
        $content = theme('member_welcome_email', $user['cid'], $confirm_url);
        mail($email, "Welcome to $config_org_name", $content, $headers);
    }
    
    return crm_url('members');
}

/**
 * Handle plan import request.
 *
 * @return The url to display on completion.
 */
function command_member_plan_import () {
    
    if (!user_access('member_plan_edit')) {
        error_register('User does not have permission: member_plan_edit');
        return crm_url('members');
    }
    
    if (!array_key_exists('plan-file', $_FILES)) {
        error_register('No plan file uploaded');
        return crm_url('plans&tab=import');
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
        if (!$res) crm_error(mysql_error());
        $pid = mysql_insert_id();
    }
    
    return crm_url('plans');
}
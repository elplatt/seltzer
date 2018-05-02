<?php

/*
    Copyright 2009-2017 Edward L. Platt <ed@elplatt.com>
    Copyright 2013-2017 Chris Murray <chris.f.murray@hotmail.co.uk>
    
    This file is part of the Seltzer CRM Project
    register.inc.php - registration module

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
function register_revision () {
    return 1;
}

/**
 * Install or upgrade this module.
 * @param $old_revision The last installed revision of this module, or 0 if the
 *   module has never been installed.
 */
function register_install($old_revision = 0) {
    if ($old_revision < 1) {
        // There is nothing to install. Do nothing
    }
}

/**
 * @return The themed html string for a registration form.
*/
function theme_register_form () {
    return theme('form', crm_get_form('register'));
}

/**
 * @return The form structure for registering a member.
*/
function register_form () {
    
    // Start with contact form
    $form = crm_get_form('member_add');
    
    // Generate default start date, first of current month
    $start = date("Y-m-d");
    
    // Change form command
    $form['command'] = 'register';
    $form['submit'] = 'Register';
    
    return $form;
}

/**
 * Handle member registration request.
 *
 * @return The url to display when complete.
 */
function command_register () {
    global $db_connect;
    global $esc_post;
    global $config_org_name;
    global $config_email_to;
    global $config_email_from;
    
    // Find username or create a new one
    $username = $_POST['username'];
    $n = 0;
    while (empty($username) && $n < 100) {
        
        // Construct test username
        $test_username = strtolower($_POST['firstName']{0} . $_POST['lastName']);
        if ($n > 0) {
            $test_username .= $n;
        }
        
        // Check whether username is taken
        $esc_test_name = mysqli_real_escape_string($db_connect, $test_username);
        $sql = "SELECT * FROM `user` WHERE `username`='$esc_test_name'";
        $res = mysqli_query($db_connect, $sql);
        if (!$res) crm_error(mysqli_error($res));
        $user_row = mysqli_fetch_assoc($res);
        if (!$user_row) {
            $username = $test_username;
        }
        $n++;
    }
    if (empty($username)) {
        error_register('Please specify a username');
        return crm_url('register');
    }
    
    // Check for duplicate usernames
    if (!empty($username)) {
        
        // Check whether username is in use
        $test_username = $username;
        $esc_test_username = mysqli_real_escape_string($db_connect, $test_username);
        $sql = "SELECT * FROM `user` WHERE `username`='$esc_test_username'";
        $res = mysqli_query($db_connect, $sql);
        if (!$res) crm_error(mysqli_error($res));
        $username_row = mysqli_fetch_assoc($res);
        if (!$username_row) {
            $username = $test_username;
        } else {
            error_register('Username already in use, please specify a different username');
            return crm_url('register');
        }
    }
    
    // Check for duplicate email addresses
    $email = $_POST['email'];
    if (!empty($email)) {
        
        // Check whether email address is in use
        $test_email = $email;
        $esc_test_email = mysqli_real_escape_string($db_connect, $test_email);
        $sql = "SELECT * FROM `contact` WHERE `email`='$esc_test_email'";
        $res = mysqli_query($db_connect, $sql);
        if (!$res) crm_error(mysqli_error($res));
        $email_row = mysqli_fetch_assoc($res);
        if (!$email_row) {
            $email = $test_email;
        } else {
            error_register('Email address already in use');
            error_register('Please specify a different email address');
            return crm_url('register');
        }
    }
    
    // Build contact object
    $contact = array(
        'firstName' => $_POST['firstName']
        , 'middleName' => $_POST['middleName']
        , 'lastName' => $_POST['lastName']
        , 'email' => $email
        , 'phone' => $_POST['phone']
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
    $member = array(
        'membership' => $membership
        , 'emergencyName' => $_POST['emergencyName']
        , 'emergencyPhone' => $_POST['emergencyPhone']
        , 'emergencyRelation' => $_POST['emergencyRelation']
        , 'address1' => $_POST['address1']
        , 'address2' => $_POST['address2']
        , 'address3' => $_POST['address3']
        , 'town_city' => $_POST['town_city']
        , 'zipcode' => $_POST['zipcode']
    );
    $contact['member'] = $member;
    
    // Save to database
    $contact = contact_save($contact);
    
    $esc_cid = mysqli_real_escape_string($db_connect, $contact['cid']);
    
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
    
    return crm_url('login');
}

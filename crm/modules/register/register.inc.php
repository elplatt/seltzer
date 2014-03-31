<?php

/*
    Copyright 2009-2014 Edward L. Platt <ed@elplatt.com>
    Copyright 2013-2014 Chris Murray <chris.f.murray@hotmail.co.uk>

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
    $form = crm_get_form('contact');
    
    // Generate default start date, first of current month
    $start = date("Y-m-d");
    
    // Change form command
    $form['command'] = 'register';
    $form['submit'] = 'Register';
    
    // Add member data
    $form['fields'][] = array(
        'type' => 'fieldset',
        'label' => 'User Info',
        'fields' => array(
            array(
                'type' => 'text',
                'label' => 'Username',
                'name' => 'username'
            )
        )
    );
    $form['fields'][] = array(
        'type' => 'fieldset',
        'label' => 'Membership Info',
        'fields' => array(
            array(
                'type' => 'select',
                'label' => 'Plan',
                'name' => 'pid',
                'selected' => '',
                'options' => member_plan_options(array('filter'=>array('active'=>true)))
            ),
            array(
                'type' => 'text',
                'label' => 'Start Date',
                'name' => 'start',
                'value' => $start,
                'class' => 'date'
            )
        )
    );
    
    return $form;
}

/**
 * Handle member registration request.
 *
 * @return The url to display when complete.
 */
function command_register () {
    global $esc_post;
    global $config_email_to;
    global $config_email_from;
    global $config_org_name;
    
    // Find username or create a new one
    $username = $_POST['username'];
    $n = 0;
    while (empty($username) && $n < 100) {
        
        // Construct test username
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
        return crm_url('register');
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
    
    $esc_cid = mysql_real_escape_string($contact['cid']);
    
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

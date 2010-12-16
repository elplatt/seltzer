<?php

/*
    Copyright 2009-2010 Edward L. Platt <elplatt@alum.mit.edu>
    
    This file is part of the Seltzer CRM Project
    user.inc.php - Core user functions

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
 * Return logged in user id
*/
function user_id() {
    return $_SESSION['userId'];
}

/**
 * Set logged in user
 *
 * @param $user_id The logged in user id
*/
function user_login($user_id) {
    $_SESSION['userId'] = $user_id;
}

/**
 * Return logged in user structure
 *
 * @param $uid The id of the user being queried, defaults to current user
*/
function user_get_user($uid = 0) {
    
    // Default to logged in user
    if ($uid == 0) {
        $uid = user_id();
    }
    
    // Query database
    $uid = mysql_real_escape_string($uid);
    $sql = "SELECT * FROM `user` WHERE `uid`='$uid'";
    $res = mysql_query($sql);
    if (!$res) { die(mysql_error()); }
    
    // Return user structure
    $user = mysql_fetch_assoc($res);
    return $user;
}

/**
 * Check if a user has the given role
 *
 * @param $role The role.
*/
function user_check_role ($role, $user_id = NULL) {
    
    // Choose logged in user if no user id
    if (!$user_id) {
        $user_id = user_id();
    }
    
    // Special case for authenticated user
    if ($user_id == user_id() && user_id()) {
        if ($role == 'authenticated') {
            return true;
        }
    }
    
    // Query for users roles
    $sql = "
        SELECT * FROM
        `user` LEFT JOIN `role` ON `user`.`uid`=`role`.`uid`
        WHERE `user`.`uid`='$user_id'";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    $row = mysql_fetch_array($res);
    
    // Check if user exists
    if (!$row) {
        return false;
    }
    
    // Check role
    if ($row[$role]) {
        return true;
    }
    
    // Default to false
    return false;
}

/**
 * Check if user has permissions for a given action
 *
 * @param $action The action
*/
function user_access ($action) {
    global $config_permissions;
    
    // Get user id
    $uid = user_id();
    
    // Query user's role info
    $sql = "SELECT * FROM `role` WHERE `uid`='$uid'";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    $roles = mysql_fetch_assoc($res);
    
    // Loop through allowed roles
    if (!empty($config_permissions[$action])) {
        foreach ($config_permissions[$action] as $role) {
            if ($roles[$role]) {
                return true;
            }
        }
    }
    
    // Default to no permission
    return false;
}

/**
 * Check reset password code
 *
 * @param $code The code
*/
function user_check_reset_code($code) {
    
    // Query database for code
    $esc_code = mysql_real_escape_string($code);
    $sql = "SELECT * FROM `resetPassword` WHERE `code`='$esc_code'";
    $res = mysql_query($sql);
    if (!$res) { die(mysql_error()); }
    
    // Fetch first row
    $row = mysql_fetch_assoc($res);
    
    // Return true if row is not empty
    return (boolean)$row;
}

/**
 * Return reset password form structure
*/
function user_reset_password_form () {
    $form = array(
        'type' => 'form',
        'method' => 'post',
        'command' => 'reset_password',
        'fields' => array(
            array(
                'type' => 'fieldset',
                'label' => 'Reset password',
                'fields' => array(
                    array(
                        'type' => 'text',
                        'label' => 'Username',
                        'name' => 'username'
                    ),
                    array(
                        'type' => 'submit',
                        'name' => 'submitted',
                        'value' => 'Send Email'
                    )
                )
            )
        )
    );
    return $form;
}

/**
 * Return reset password confirmation form structure
*/
function user_reset_password_confirm_form ($code) {
    
    $form = array(
        'type' => 'form',
        'method' => 'post',
        'command' => 'reset_password_confirm',
        'hidden' => array(
            'code' => $code
        ),
        'fields' => array(
            array(
                'type' => 'fieldset',
                'label' => 'Change password',
                'fields' => array(
                    array(
                        'type' => 'password',
                        'label' => 'Password',
                        'name' => 'password'
                    ),
                    array(
                        'type' => 'password',
                        'label' => 'Confirm',
                        'name' => 'confirm'
                    ),
                    array(
                        'type' => 'submit',
                        'name' => 'submitted',
                        'value' => 'Change password'
                    )
                )
            )
        )
    );
    return $form;
}

/**
 * Respond to reset password request
*/

function command_reset_password () {
    global $esc_post;
    global $config_host;
    global $config_base_path;
    global $config_email_from;
    
    // Get user info
    $sql = "
        SELECT * FROM `user`
        INNER JOIN `contact` ON `user`.`cid`=`contact`.`cid`
        WHERE `user`.`username`='$esc_post[username]'
        ";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    $row = mysql_fetch_assoc($res);
    
    // Make sure user exists
    if (empty($row)) {
        return false;
    }
    
    // Generate code
    $code = sha1(uniqid(time()));
    
    // Insert code into reminder table
    $sql = "
        REPLACE INTO `resetPassword`
        (`uid`, `code`)
        VALUES
        ('$row[uid]', '$code')";
    $res = mysql_query($sql);
    
    // Generate reset url
    $url = 'http://' . $config_host . $config_base_path . 'reset-confirm.php?v=' . $code;
    
    // Send code to user
    $to = $row['email'];
    $subject = '[i3 Detroit CRM] Reset Password';
    $from = $config_email_from;
    $message = "To reset your password, visit the following url: $url";
    $res = mail($to, $subject, $message);
    
    // Notify user to check their email
    message_register('Instructions for resetting your password have been sent to your e-mail.');
    
    return 'index.php';
}

/**
 * Respond to reset password confirmation
*/

function command_reset_password_confirm () {
    global $esc_post;
    
    // Check code
    if (!user_check_reset_code($_POST['code'])) {
        error_register('Invalid reset code');
        return 'index.php';
    }
    
    // Check that passwords match
    if ($_POST['password'] != $_POST['confirm']) {
        error_register('Passwords do not match');
        return 'index.php';
    }
    
    // Get user id
    $sql = "SELECT * FROM `resetPassword` WHERE `code`='$esc_post[code]'";
    $res = mysql_query($sql);
    if (!$res) { die(mysql_error()); }
    $row = mysql_fetch_assoc($res);
    $esc_uid = mysql_real_escape_string($row['uid']);
    
    // Calculate hash
    $esc_hash = mysql_real_escape_string(sha1($_POST['password']));
    
    // Update password
    $sql = "
        UPDATE `user`
        SET `hash`='$esc_hash'
        WHERE `uid`='$esc_uid'
        ";
    $res = mysql_query($sql);
    if (!$res) { die(mysql_error()); }
    
    // Notify user to check their email
    message_register('Your password has been reset, you may now log in');
    
    return 'login.php';
}

/* Returns themed html for a password reset form
*/
function theme_user_reset_password_form() {
    return theme_form(user_reset_password_form());
}

/**
 * Returns themed html for a password reset form
 *
 * @param $code The reset code
 */
function theme_user_reset_password_confirm_form($code) {
    
    if (!user_check_reset_code($code)) {
        return '<p>Invalid code</p>';
    }
    
    return theme_form(user_reset_password_confirm_form($code));
}

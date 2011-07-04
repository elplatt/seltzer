<?php

/*
    Copyright 2009-2011 Edward L. Platt <elplatt@alum.mit.edu>
    
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
 * @return the cid of the logged in user.
*/
function user_id () {
    return $_SESSION['userId'];
}

/**
 * Update the session variables to set a specified user as logged in.
 *
 * @param $cid The cid to set as the logged in user.
*/
function user_login ($cid) {
    $_SESSION['userId'] = $cid;
}

/**
 * @param $cid The cid of the user being queried, defaults to current user.
 * @return The data structure for the specified user.
*/
function user_get_user($cid = 0) {
    
    // Default to logged in user
    if ($cid == 0) {
        $cid = user_id();
    }
    
    // Query database
    $cid = mysql_real_escape_string($cid);
    $sql = "SELECT * FROM `user` WHERE `cid`='$cid'";
    $res = mysql_query($sql);
    if (!$res) { die(mysql_error()); }
    
    // Return user structure
    $user = mysql_fetch_assoc($res);
    return $user;
}

/**
 * Get role data for one or more users.
 *
 * @param $opts Options, possible keys are:
 *   cid Filter results to only those matching the given cid.
 * @return An array with each element representing one user's roles.
 */
function user_role_data ($opts = NULL) {
    
    // Construct query
    $sql = "SELECT * FROM `role` WHERE 1 ";
    
    // Add filter
    if (!empty($opts['cid'])) {
        $cid = mysql_real_escape_string($opts['cid']);
        $sql .= " AND `cid`='$cid'";
    }
    
    // Excecute query
    $res = mysql_query($sql);
    if (!$res) { die(mysql_error()); }

    // Construct array of role data
    $roles = array();
    $row = mysql_fetch_assoc($res);
    while ($row) {
        $roles[] = $row;
        $row = mysql_fetch_assoc($res);
    }
    
    return $roles;
}

/**
 * Check if a user has the given role.
 *
 * @param $role A string containing the role to check.
 * @return True if the user is assigned to the given role.
*/
function user_check_role ($role, $cid = NULL) {
    
    // Choose logged in user if no user id
    if (!$cid) {
        $cid = user_id();
    }
    
    // Special case for authenticated user
    if ($cid == user_id() && user_id()) {
        if ($role == 'authenticated') {
            return true;
        }
    }
    
    // Query for users roles
    $sql = "
        SELECT * FROM
        `user` LEFT JOIN `role` ON `user`.`cid`=`role`.`cid`
        WHERE `user`.`cid`='$cid'";
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
 * Check if the logged in user has permissions for a specified action.
 *
 * @param $action The action
 * @return True if the user has permission to perform $action.
*/
function user_access ($action) {
    global $config_permissions;
    
    // Get user id
    $cid = user_id();
    
    // Check whether role table exists (might not be installed yet)
    $sql = "SHOW TABLES LIKE 'role'";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    $row = mysql_fetch_assoc($res);
    if (!$row) {
        return false;
    }
    
    // Query user's role info
    $sql = "SELECT * FROM `role` WHERE `cid`='$cid'";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    $roles = mysql_fetch_assoc($res);
    if (count($roles) == 0) {
        return false;
    }
    
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
 * Check whether a specified password reset code exists.
 *
 * @param $code A string containing the code.
 * @return True if the specified reset code exists.
*/
function user_check_reset_code ($code) {
    
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
 * Handle login request.
 *
 * @return the url to display when complete.
 */
function command_login () {
    global $esc_post;
    
    // Calculate hash
    $esc_hash = sha1($_POST['password']);
    
    // Query database for given user
    $sql = "
        SELECT *
        FROM `user`
        WHERE `username`='$esc_post[username]' AND `hash`='$esc_hash'";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    $row = mysql_fetch_assoc($res);
    
    // Check for user
    if (!empty($row)) {
        user_login($row['cid']);
        $next = 'index.php';
    } else {
        error_register('Invalid username/password');
        $next = 'login.php';
    }
    
    // Redirect to index
    return $next;
}

/**
 * Handle logout request.
 *
 * @return The url to display when complete.
 */
function command_logout () {

    // Destroy session data
    session_destroy();
    
    // Redirect to index
    return 'index.php';
}

/**
 * @return login form structure.
*/
function login_form () {
    $form = array(
        'type' => 'form',
        'method' => 'post',
        'command' => 'login',
        'fields' => array(
            array(
                'type' => 'fieldset',
                'label' => 'Log in',
                'fields' => array(
                    array(
                        'type' => 'text',
                        'label' => 'Username',
                        'name' => 'username'
                    ),
                    array(
                        'type' => 'password',
                        'label' => 'Password',
                        'name' => 'password'
                    ),
                    array(
                        'type' => 'submit',
                        'name' => 'submitted',
                        'value' => 'Log in'
                    )
                )
            )
        )
    );
    return $form;
}

/**
 * @return password reset form structure
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
 * @param $code The password reset code.
 * @return The password reset confirmation form structure.
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
 * Respond to reset password request.
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
        error_register('No such username');
        return 'reset.php';
    }
    
    // Generate code
    $code = sha1(uniqid(time()));
    
    // Insert code into reminder table
    $sql = "
        REPLACE INTO `resetPassword`
        (`cid`, `code`)
        VALUES
        ('$row[cid]', '$code')";
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
 * Respond to password reset confirmation.
 * @return The url to display after the command is processed.
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
    $esc_cid = mysql_real_escape_string($row['cid']);
    
    // Calculate hash
    $esc_hash = mysql_real_escape_string(sha1($_POST['password']));
    
    // Update password
    $sql = "
        UPDATE `user`
        SET `hash`='$esc_hash'
        WHERE `cid`='$esc_cid'
        ";
    $res = mysql_query($sql);
    if (!$res) { die(mysql_error()); }
    
    // Notify user to check their email
    message_register('Your password has been reset, you may now log in');
    
    return 'login.php';
}

/**
 * @return The themed html string for a login form.
*/
function theme_login_form () {
    return theme_form(login_form());
}

/**
 * @return The themed html for a password reset form.
*/
function theme_user_reset_password_form () {
    return theme_form(user_reset_password_form());
}

/**
 * @param $code The pasword reset code.
 * @return The themed html for a password reset form.
 */
function theme_user_reset_password_confirm_form ($code) {
    
    if (!user_check_reset_code($code)) {
        return '<p>Invalid code</p>';
    }
    
    return theme_form(user_reset_password_confirm_form($code));
}

/**
 * Return the form structure for editing user roles.
 *
 * @param $cid The cid of the user.
 * @return The form structure.
*/
function user_role_edit_form ($cid) {
    
    // Get user data
    $data = user_role_data(array('cid'=>$cid));
    $role = $data[0];
    
    $form = array(
        'type' => 'form',
        'method' => 'post',
        'command' => 'user_role_update',
        'hidden' => array(
            'cid' => $cid
        ),
        'fields' => array(
            array(
                'type' => 'fieldset',
                'label' => 'Update Roles',
                'fields' => array(
                    array(
                        'type' => 'checkbox',
                        'label' => 'Director',
                        'name' => 'director',
                        'checked' => $role['director']
                    ),
                    array(
                        'type' => 'checkbox',
                        'label' => 'President',
                        'name' => 'president',
                        'checked' => $role['president']
                    ),
                    array(
                        'type' => 'checkbox',
                        'label' => 'VP',
                        'name' => 'vp',
                        'checked' => $role['vp']
                    ),
                    array(
                        'type' => 'checkbox',
                        'label' => 'Secretary',
                        'name' => 'secretary',
                        'checked' => $role['secretary']
                    ),
                    array(
                        'type' => 'checkbox',
                        'label' => 'Treasurer',
                        'name' => 'treasurer',
                        'checked' => $role['treasurer']
                    ),
                    array(
                        'type' => 'checkbox',
                        'label' => 'Web Admin',
                        'name' => 'webAdmin',
                        'checked' => $role['webAdmin']
                    ),
                    array(
                        'type' => 'submit',
                        'name' => 'submitted',
                        'value' => 'Update'
                    )
                )
            )
        )
    );
    return $form;
}

/**
 * Handle user role update request.
 *
 * @return The url to display on completion.
 */
function command_user_role_update () {
    global $esc_post;
    
    // Check permissions
    if (!user_access('user_edit')) {
        error_register('Current user does not have permission: user_edit');
        return 'members.php';
    }
    
    // Construct query
    $sql = "
        UPDATE `role`
        SET
            `director`='$esc_post[director]'
            , `president`='$esc_post[president]'
            , `vp`='$esc_post[vp]'
            , `secretary`='$esc_post[secretary]'
            , `treasurer`='$esc_post[treasurer]'
            , `webAdmin`='$esc_post[webAdmin]'
        WHERE `cid`='$esc_post[cid]'
    ";
    $res = mysql_query($sql);
    if (!$res) { die(mysql_error()); }
    
    return "member.php?cid=$_POST[cid]&tab=roles";
}


/**
 * Return themed html for a user role edit form.
 *
 * @param $cid The cid for the user to edit.
 * @return The themed html string.
 */
function theme_user_role_edit_form ($cid) {
    return theme('form', user_role_edit_form($cid));
}

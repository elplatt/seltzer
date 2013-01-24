<?php

/*
    Copyright 2009-2013 Edward L. Platt <ed@elplatt.com>
    
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
 * Array of all permissions.
 */
$user_permissions = array();

/**
 * Permission cache for current user.
 */
$user_permission_cache = array();

/**
 * Initialization code run after all modules are loaded.
 */
function user_init () {
    global $user_permissions;
    
    foreach (module_list() as $module) {
        $func = $module . '_permissions';
        if (function_exists($func)) {
            $permissions = call_user_func($func);
            $user_permissions = array_merge($user_permissions, $permissions);
        }
    }
}

/**
 * @return a list of all permissions.
 */
function user_permissions_list () {
    global $user_permissions;
    return $user_permissions;
}

/**
 * @return An array of the permissions provided by this module.
 */
function user_permissions () {
    return array(
        'user_add'
        , 'user_edit'
        , 'user_delete'
        , 'user_role_edit'
        , 'user_permissions_edit'
    );
}

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
 * Check user password.
 * @param $password The password to check.
 * @param $user The user data structure.
 */
function user_check_password($password, $user) {
    if (!empty($user['hash'])) {
        if (user_hash($password, $user['salt']) === $user['hash']) {
            return true;
        }
    }
    return false;
}

/**
 * Return data for one or more users.
 *
 * @param $opts An associative array of options, possible keys are:
 *   'cid' If specified, returns a single user with the matching cid,
 *   'filter' An array mapping filter names to filter values
 *   'join' Array of entities to be included in the results, options are:
 *     - role: adds 'roles' key with array of roles as a value.
 * @return An array with each element representing a user.
*/ 
function user_data ($opts) {
    // Create a map of user permissions if join was specified
    $join_perm = !array_key_exists('join', $opts) || in_array('permission', $opts['join']);
    if ($join_perm) {
        $sql = "
            SELECT `user`.`cid`, `role_permission`.`permission`
            FROM `user`
            INNER JOIN `user_role` ON `user`.`cid`=`user_role`.`cid`
            INNER JOIN `role_permission` ON `user_role`.`rid`=`role_permission`.`rid`
            WHERE 1
        ";
        if (array_key_exists('cid', $opts)) {
            $cid = mysql_real_escape_string($opts['cid']);
            $sql .= " AND `user`.`cid`='$cid' ";
        }
        $res = mysql_query($sql);
        if (!$res) { die(mysql_error()); }
        $permMap = array();
        $row = mysql_fetch_assoc($res);
        while ($row) {
            $cid = $row['cid'];
            if (!array_key_exists($cid, $permMap)) {
                $permMap[$cid] = array();
            }
            $permMap[$cid][] = $row['permission'];
            $row = mysql_fetch_assoc($res);
        }
    }
    
    // Create a map of user roles if role join was specified
    $join_role = !array_key_exists('join', $opts) || in_array('role', $opts['join']);
    if ($join_role) {
        $sql = "
            SELECT `user_role`.`cid`, `role`.`rid`, `role`.`name`
            FROM `user_role` INNER JOIN `role` ON `user_role`.`rid`=`role`.`rid`
        ";
        $res = mysql_query($sql);
        if (!$res) { die(mysql_error()); }
        $roles = array();
        $row = mysql_fetch_assoc($res);
        while ($row) {
            $cid = $row['cid'];
            if (!array_key_exists($cid, $roles)) {
                $roles[$cid] = array();
            }
            $roles[$cid][] = $row['name'];
            $row = mysql_fetch_assoc($res);
        }
    }
    
    // Construct query for users
    $sql = "
        SELECT `user`.`cid`, `user`.`username`, `user`.`hash`, `user`.`salt`
        FROM `user`
        INNER JOIN `contact` ON `contact`.`cid` = `user`.`cid`
        WHERE 1
    ";
    if (array_key_exists('cid', $opts)) {
        $esc_cid = mysql_real_escape_string($opts['cid']);
        $sql .= " AND `cid`='$esc_cid' ";
    }
    if (array_key_exists('filter', $opts)) {
        foreach ($opts['filter'] as $key => $value) {
            if ($key === 'username') {
                $sql .= " AND `username`='" . mysql_real_escape_string($value) . "' ";
            } else if ($key === 'email') {
                $sql .= " AND `contact`.`email`='" . mysql_real_escape_string($value) . "' ";
            }
        }
    }
    $res = mysql_query($sql);
    if (!$res) { die(mysql_error()); }
    
    // Create result array
    $users = array();    
    $row = mysql_fetch_assoc($res);
    while ($row) {
        $user = $row;
        if ($join_role) {
            if (array_key_exists($row['cid'], $roles)) {
                $user['roles'] = $roles[$row['cid']];
            } else {
                $user['roles'] = array();
            }
        }
        if ($join_perm) {
            if (array_key_exists($row['cid'], $permMap)) {
                $user['permissions'] = $permMap[$row['cid']];
            } else {
                $user['permissions'] = array();
            }
        }
        $users[] = $user;
        $row = mysql_fetch_assoc($res);
    }
    
    return $users;
}

/**
 * Saves a user into the database
 * 
 * @param $user the user to save.
 * @return an array representing the user that was saved in the database.
 */
function user_save ($user) {
    
    // first figure out wether the user is in the database or not
    $opts = array();
    $opts['cid'] = $user['cid'];
    $user_array = user_data($opts);
    
    if(empty($user_array)){
        // if not, insert it (code is int he command_member_add function)
        
        $esc_name = mysql_real_escape_string($user['username']);
        $esc_cid = mysql_real_escape_string($user['cid']);
        $esc_hash = mysql_real_escape_string($user['hash']);
        $esc_salt = mysql_real_escape_string($user['salt']);
        
        // Add user
        $sql = "
            INSERT INTO `user`
            (`username`, `cid`)
            VALUES
            ('$esc_name', '$esc_cid')";
        $res = mysql_query($sql);
        if (!$res) die(mysql_error());
        
    } else {
        // else that user already exists, update it
        $sql = "
            UPDATE `user`
            SET `username`='$esc_name',
            `hash`='$esc_hash',
            `salt`='$esc_salt'
            WHERE `cid`='$esc_cid'
            ";
        $res = mysql_query($sql);
        if (!$res) die(mysql_error());
    }
    
    return $user;
}

/**
 * Return data for one or more roles.
 *
 * @param $opts An associative array of options.
 * @return An array with each element representing a role.
*/ 
function user_role_data ($opts = NULL) {
    
    // Construct map from role ids to arrays of permissions granted
    $permissionMap = array();
    $sql = "SELECT `rid`, `permission` FROM `role_permission` WHERE 1";
    $res = mysql_query($sql);
    if (!$res) { die(mysql_error()); }
    $row = mysql_fetch_assoc($res);
    while ($row) {
        if (!array_key_exists($row['rid'], $permissionMap)) {
            $permissionMap[$row['rid']] = array();
        }
        $permissionMap[$row['rid']][] = $row['permission'];
        $row = mysql_fetch_assoc($res);
    }
    
    // Construct query for roles
    $sql = "SELECT `rid`, `name` FROM `role` WHERE 1 ";
    $res = mysql_query($sql);
    if (!$res) { die(mysql_error()); }
    $roles = array();
    $row = mysql_fetch_assoc($res);
    while ($row) {
        $role = $row;
        if (array_key_exists($row['rid'], $permissionMap)) {
            $role['permissions'] = $permissionMap[$row['rid']];
        } else {
            $role['permissions'] = array();
        }
        $roles[] = $role;
        $row = mysql_fetch_assoc($res);
    }
    return $roles;
}

/**
 * Generate a table structure for a given user's info.
 * @param $opts An associative array of options to be passed to user_data().
 * @return The table structure.
 */
function user_table ($opts) {
    $users = user_data($opts);
    
    $table = array(
        'id' => ''
        , 'class' => ''
        , 'columns' => array(
            array(
                'title' => 'Username'
                , 'id' => ''
                , 'class' => ''
            )
        )
        , 'rows' => array()
    );
    
    foreach ($users as $user) {
        $user_row = array();
        $user_row[] = $user['username'];
        $table['rows'][] = $user_row;
    }
    
    return $table;
}

/**
 * @param $cid The cid of the user being queried, defaults to current user.
 * @return The username for the specified user.
*/
function user_username($cid = NULL) {
    $opts = array();
    if ($cid) {
        $opts['cid'] = $cid;
    } else {
        $opts['cid'] = user_id();
    }
    if ($opts['cid'] == 1) {
        return 'admin';
    }
    $opts['join'] = array();
    $data = user_data($opts);
    return $data[0]['username'];
}

/**
 * @return An array of role names.
 */
function user_role_list () {
    $sql = "SELECT * FROM `role` WHERE 1";
    $res = mysql_query($sql);
    if (!$res) { die(mysql_error()); }
    
    $roles = array();
    $row = mysql_fetch_assoc($res);
    while ($row) {
        $roles[] = $row['name'];
        $row = mysql_fetch_assoc($res);
    }
    
    return $roles;
}

/**
 * Check if the logged in user has permissions for a specified action.
 *
 * @param $permission The permission to check for.
 * @return True if the user is granted $permission.
*/
function user_access ($permission) {
    global $user_permission_cache;
    
    // If a user is not logged in, they don't have access to anything
    if (!user_id()) {
        return false;
    }
    
    // The admin user has access to everything
    if (user_id() == 1) {
        return true;
    }
    
    // Check cache
    if (array_key_exists($permission, $user_permission_cache)) {
        return $user_permission_cache[$permission];
    }
    
    // Get list of the users roles and check each for the permission
    $data = user_data(array('cid'=>user_id()));
    $access = in_array($permission, $data[0]['permissions']);
    $user_permission_cache[$permission] = $access;
    return $access;
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
    
    //Check to see if there was an @ sign in the 'username'. This will signify that the user
    //probably entered their email, and not their username.
    if (strpos($_POST['username'], "@") === False){
        //there is not an "@" in the 'username', so we will assume the user entered their username
        $user_opts = array(
            'filter' => array(
                'username' => $_POST['username']
            )
        );
        $users = user_data($user_opts);
    } else {
        //There was an "@" in the 'username',so we will assume the user entered their email address
        $user_opts = array(
            'filter' => array(
                'email' => $_POST['username']
            )
        );
        $users = user_data($user_opts);
    }
    
    
    // Check for user
    if (sizeof($users) < 1) {
        //The user was not found, so assume they tried entering their email
        error_register('Invalid username/password');
        $next = 'index.php?q=login';
    }
    
    // Check password
    $user = $users[0];
    $valid = user_check_password($_POST['password'], $user);
    
    if ($valid) {
        user_login($user['cid']);
        $next = 'index.php';
    } else {
        error_register('Invalid username/password');
        $next = 'index.php?q=login';
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
                        'label' => 'Username or Email',
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
                        'label' => 'Username or Email',
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
    global $config_site_title;
    
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
        return 'index.php?q=reset';
    }
    
    // Generate code
    $code = sha1(uniqid(time()));
    
    // Insert code into reminder table
    $esc_cid = mysql_real_escape_string($row['cid']);
    $esc_code = msyql_real_escape_string($code);
    $sql = "
        REPLACE INTO `resetPassword`
        (`cid`, `code`)
        VALUES
        ('$esc_cid', '$esc_code')";
    $res = mysql_query($sql);
    
    // Generate reset url
    $url = 'http://' . $config_host . $config_base_path . 'index.php?q=reset-confirm&v=' . $code;
    
    // Send code to user
    $to = $row['email'];
    $subject = "[$config_site_title] Reset Password";
    $from = $config_email_from;
    $headers = "From: $from\r\n";
    $message = "To reset your password, visit the following url: $url";
    $res = mail($to, $subject, $message, $headers);
    
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
    $salt = user_salt();
    $esc_hash = mysql_real_escape_string(user_hash($_POST['password'], $salt));
    $esc_salt = mysql_real_escape_string($salt);
    
    // Update password
    $sql = "
        UPDATE `user`
        SET `hash`='$esc_hash'
        , `salt`='$esc_salt'
        WHERE `cid`='$esc_cid'
        ";
    $res = mysql_query($sql);
    if (!$res) { die(mysql_error()); }
    
    // Notify user to check their email
    message_register('Your password has been reset, you may now log in');
    
    return 'index.php?q=login';
}

/**
 * @return The themed html string for a login form.
*/
function theme_login_form () {
    return theme('form', login_form());
}

/**
 * @return The themed html for a password reset form.
*/
function theme_user_reset_password_form () {
    return theme('form', user_reset_password_form());
}

/**
 * @param $code The pasword reset code.
 * @return The themed html for a password reset form.
 */
function theme_user_reset_password_confirm_form ($code) {
    
    if (!user_check_reset_code($code)) {
        return '<p>Invalid code</p>';
    }
    
    return theme('form', user_reset_password_confirm_form($code));
}

/**
 * Return the form structure for editing user roles.
 *
 * @param $cid The cid of the user.
 * @return The form structure.
*/
function user_role_edit_form ($cid) {
    
    // Get user data
    $data = user_data(array('cid'=>$cid));
    $user = $data[0];
    
    // Get role data
    $roles = user_role_list();
    
    // Construct fields
    $fields = array();
    foreach ($roles as $role) {
        if ($role === 'authenticated') {
            continue;
        }
        $fields[] = array(
            'type' => 'checkbox',
            'label' => $role,
            'name' => $role,
            'checked' => in_array($role, $user['roles'])
        );
    }
    $fields[] = array(
        'type' => 'submit',
        'name' => 'submitted',
        'value' => 'Update'
    );
    
    $form = array(
        'type' => 'form',
        'method' => 'post',
        'command' => 'user_role_update',
        'hidden' => array(
            'cid' => $cid
        ),
        'fields' => $fields
    );
    return $form;
}

/**
 * @return Form structure for updating user permissions.
 */
function user_permissions_form () {
    
    // Form table rows and columns
    $columns = array();
    $rows = array();
    
    // Get role data
    $roles = user_role_data();
    
    // Add a column for permissions names, and each role
    $columns[] = '';
    foreach ($roles as $role) {
        $columns[] = array('title'=>$role['name']);
    }
    
    // Add a row for each permission
    foreach (user_permissions_list() as $permission) {
        $row = array();
        $row[] = array(
            'type' => 'message'
            , 'value' => $permission
        );
        foreach ($roles as $role) {
            $checked = in_array($permission, $role['permissions']);
            $row[] = array(
                'type' => 'checkbox',
                'name' => "$permission-$role[name]",
                'checked' => $checked
            );
        }
        $rows[] = $row;
    }
    
    $form = array(
        'type' => 'form'
        , 'method' => 'post'
        , 'command' => 'user_permissions_update'
        , 'fields' => array(
            array(
                'type' => 'table'
                , 'columns' => $columns
                , 'rows' => $rows
            )
            , array(
                'type' => 'submit',
                'name' => 'submitted',
                'value' => 'Update'
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
        return 'index.php?q=members';
    }
    
    // Check permissions
    if (!user_access('user_role_edit')) {
        error_register('Current user does not have permission: user_role_edit');
        return 'index.php?q=members';
    }
    
    // Delete all roles for specified user
    $sql = "DELETE FROM `user_role` WHERE `cid`='$esc_post[cid]'";
    $res = mysql_query($sql);
    if (!$res) { die(mysql_error()); }
    
    // Re-add each role
    $roles = user_role_data();
    foreach ($roles as $role) {
        if ($_POST[$role['name']]) {
            $esc_rid = mysql_real_escape_string($role['rid']);
            $sql = "
                INSERT INTO `user_role`
                (`cid`, `rid`)
                VALUES
                ('$esc_post[cid]', '$esc_rid')
            ";
            $res = mysql_query($sql);
            if (!$res) { die(mysql_error()); }
        }
    }
    
    return "index.php?q=member&cid=$_POST[cid]&tab=roles";
}

/**
 * Handle user permissions update request.
 *
 * @return The url to display on completion.
 */
function command_user_permissions_update () {
    global $esc_post;
    
    // Check permissions
    if (!user_access('user_edit')) {
        error_register('Current user does not have permission: user_edit');
        return 'index.php?q=permissions';
    }
    
    // Check status of each permission for each role
    $perms = user_permissions_list();
    $roles = user_role_data();
    foreach ($perms as $perm) {
        $esc_perm = mysql_real_escape_string($perm);
        foreach ($roles as $role) {
            $key = "$perm-$role[name]";
            $esc_rid = mysql_real_escape_string($role['rid']);
            if ($_POST[$key]) {
                // Ensure the role has this permission
                $sql = "
                    SELECT * FROM `role_permission`
                    WHERE `rid`='$esc_rid' AND `permission`='$esc_perm'
                ";
                $res = mysql_query($sql);
                if (!$res) { die(mysql_error()); }
                if (mysql_numrows($res) === 0) {
                    $sql = "
                        INSERT INTO `role_permission`
                        (`rid`, `permission`)
                        VALUES
                        ('$esc_rid', '$esc_perm')
                    ";
                }
                $res = mysql_query($sql);
                if (!$res) { die(mysql_error()); }
            } else {
                // Delete the permission for this role
                $sql = "
                    DELETE FROM `role_permission`
                    WHERE `rid`='$esc_rid' AND `permission`='$esc_perm'
                ";
                $res = mysql_query($sql);
                if (!$res) { die(mysql_error()); }
            }
        }
    }
    
    
    return "index.php?q=permissions";
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

/**
 * @return a random password salt.
 */
function user_salt () {
    $chars = 'abcdefghijklmnopqrstuvwxyz01234567890!@#$%^&*()-_=+[]{}\\|`~;:"\',./<>?';
    $char_count = strlen($chars);
    $salt_length = 16;
    $salt = '';
    for ($i = 0; $i < 16; $i++) {
        $salt .= $chars{rand(0, $char_count - 1)};
    }
    return $salt;
}

/**
 * Generate a salted password hash.
 * @param $password
 * @param $salt
 * @return The hash string.
 */
function user_hash ($password, $salt) {
    $input = empty($salt) ? $password : $salt . $password;
    return sha1($input);
}

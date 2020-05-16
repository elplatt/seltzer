<?php

/*
    Copyright 2009-2020 Edward L. Platt <ed@elplatt.com>
    
    This file is part of the Seltzer CRM Project
    user.inc.php - User module
    
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
function user_revision () {
    return 2;
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

// Install /////////////////////////////////////////////////////////////////////

/**
 * Install or upgrade this module.
 * @param $old_revision The last installed revision of this module, or 0 if the
 *   module has never been installed.
 */
function user_install ($old_revision = 0) {
    global $db_connect;
    if ($old_revision < 1) {
        // If user table exists, this code was already run when it was
        // part of the core module
        if (mysqli_num_rows(mysqli_query($db_connect, "SHOW TABLES LIKE 'user'")) == 0) {
            $sql = "
                CREATE TABLE IF NOT EXISTS `resetPassword` (
                    `cid` mediumint(8) unsigned NOT NULL
                    , `code` varchar(40) NOT NULL
                    , PRIMARY KEY (`cid`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
            ";
            $res = mysqli_query($db_connect, $sql);
            if (!$res) crm_error(mysqli_error($res));
            
            $sql = "
                CREATE TABLE IF NOT EXISTS `role` (
                    `rid` mediumint(9) NOT NULL AUTO_INCREMENT
                    , `name` varchar(255) NOT NULL
                    , PRIMARY KEY (`rid`)
                ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
            ";
            $res = mysqli_query($db_connect, $sql);
            if (!$res) crm_error(mysqli_error($res));
            
            $sql = "
                CREATE TABLE IF NOT EXISTS `role_permission` (
                    `rid` mediumint(8) unsigned NOT NULL
                    , `permission` varchar(255) NOT NULL
                    , PRIMARY KEY (`rid`,`permission`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
            ";
            $res = mysqli_query($db_connect, $sql);
            if (!$res) crm_error(mysqli_error($res));
            $sql = "
                CREATE TABLE IF NOT EXISTS `user` (
                    `cid` mediumint(11) unsigned NOT NULL
                    , `username` varchar(32) NOT NULL
                    , `hash` varchar(40) NOT NULL DEFAULT ''
                    , `salt` varchar(16) NOT NULL DEFAULT ''
                    , PRIMARY KEY (`cid`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
            ";
            $res = mysqli_query($db_connect, $sql);
            if (!$res) crm_error(mysqli_error($res));
            $sql = "
                CREATE TABLE IF NOT EXISTS `user_role` (
                    `cid` mediumint(8) unsigned NOT NULL
                    , `rid` mediumint(8) unsigned NOT NULL
                    , PRIMARY KEY (`cid`,`rid`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
            ";
            $res = mysqli_query($db_connect, $sql);
            if (!$res) crm_error(mysqli_error($res));
            // Create default roles
            $roles = array(
                '1' => 'authenticated'
                , '2' => 'member'
                , '3' => 'director'
                , '4' => 'president'
                , '5' => 'vp'
                , '6' => 'secretary'
                , '7' => 'treasurer'
                , '8' => 'webAdmin'
            );
            foreach ($roles as $rid => $role) {
                $sql = "
                    INSERT INTO `role` (`rid`, `name`)
                    VALUES ('$rid', '$role')
                ";
                $res = mysqli_query($db_connect, $sql);
                if (!$res) crm_error(mysqli_error($res));
            }
            $default_perms = array(
                'member' => array('report_view', 'contact_view')
                , 'director' => array('module_upgrade', 'report_view', 'contact_view', 'contact_add', 'contact_edit', 'contact_delete', 'user_add', 'user_edit', 'user_delete', 'user_role_edit', 'user_permissions_edit')
                , 'webAdmin' => array('module_upgrade', 'report_view', 'contact_view', 'contact_add', 'contact_edit', 'contact_delete', 'user_add', 'user_edit', 'user_delete', 'user_role_edit', 'user_permissions_edit')
            );
            foreach ($roles as $rid => $role) {
                if (array_key_exists($role, $default_perms)) {
                    foreach ($default_perms[$role] as $perm) {
                        $sql = "
                            INSERT INTO `role_permission` (`rid`, `permission`)
                            VALUES ('$rid', '$perm')
                        ";
                        $res = mysqli_query($db_connect, $sql);
                        if (!$res) crm_error(mysqli_error($res));
                    }
                }
            }
        }
    }
    if ($old_revision < 2) {
        // Set default permissions
        $roles = array(
            '1' => 'authenticated'
            , '2' => 'member'
            , '3' => 'director'
            , '4' => 'president'
            , '5' => 'vp'
            , '6' => 'secretary'
            , '7' => 'treasurer'
            , '8' => 'webAdmin'
        );
        $default_perms = array(
            'director' => array('contact_list')
            , 'webAdmin' => array('contact_list')
        );
        foreach ($roles as $rid => $role) {
            $esc_rid = mysqli_real_escape_string($db_connect, $rid);
            if (array_key_exists($role, $default_perms)) {
                foreach ($default_perms[$role] as $perm) {
                    $esc_perm = mysqli_real_escape_string($db_connect, $perm);
                    $sql = "
                        SELECT * FROM `role_permission`
                        WHERE `permission` LIKE '$esc_perm'
                    ";
                    $res = mysqli_query($db_connect, $sql);
                    if (!$res) {
                        $sql = "
                            INSERT INTO `role_permission` (`rid`, `permission`)
                            VALUES ('$esc_rid', '$esc_perm')
                        ";
                        $res = mysqli_query($db_connect, $sql);
                        if (!$res) crm_error(mysqli_error($res));
                    }
                }
            }
        }
    }
}

// Data Model //////////////////////////////////////////////////////////////////

/**
 * Return data for one or more users.
 * @param $opts An associative array of options, possible keys are:
 *   'cid' If specified, returns a single user with the matching cid,
 *   'filter' An array mapping filter names to filter values
 *   'join' Array of entities to be included in the results, options are:
 *     - role: adds 'roles' key with array of roles as a value.
 * @return An array with each element representing a user.
 */
function user_data ($opts) {
    global $db_connect;
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
            if (is_array($opts['cid'])) {
                $terms = array();
                foreach ($opts['cid'] as $cid) {
                    $terms[] = "'" . mysqli_real_escape_string($db_connect, $cid) . "'";
                }
                $sql .= "
                    AND `user`.`cid` IN (" . implode(',', $terms) . ")
                ";
            } else {
                $esc_cid = mysqli_real_escape_string($db_connect, $opts['cid']);
                $sql .= "
                    AND `user`.`cid`='$esc_cid'
                ";
            }
        }
        $res = mysqli_query($db_connect, $sql);
        if (!$res) { crm_error(mysqli_error($res)); }
        $permMap = array();
        $row = mysqli_fetch_assoc($res);
        while ($row) {
            $cid = $row['cid'];
            if (!array_key_exists($cid, $permMap)) {
                $permMap[$cid] = array();
            }
            $permMap[$cid][] = $row['permission'];
            $row = mysqli_fetch_assoc($res);
        }
    }
    // Create a map of user roles if role join was specified
    $join_role = !array_key_exists('join', $opts) || in_array('role', $opts['join']);
    if ($join_role) {
        $sql = "
            SELECT `user_role`.`cid`, `role`.`rid`, `role`.`name`
            FROM `user_role`
            INNER JOIN `role` ON `user_role`.`rid`=`role`.`rid`
        ";
        $res = mysqli_query($db_connect, $sql);
        if (!$res) { crm_error(mysqli_error($res)); }
        $roles = array();
        $row = mysqli_fetch_assoc($res);
        while ($row) {
            $cid = $row['cid'];
            if (!array_key_exists($cid, $roles)) {
                $roles[$cid] = array();
            }
            $roles[$cid][] = $row['name'];
            $row = mysqli_fetch_assoc($res);
        }
    }
    // Construct query for users
    $sql = "
        SELECT `user`.`cid`, `user`.`username`, `user`.`hash`, `user`.`salt`
        FROM `user`
        INNER JOIN `contact` ON `contact`.`cid` = `user`.`cid`
        WHERE 1
    ";
    if (array_key_exists('cid', $opts) && $opts['cid']) {
        $clauses = array();
        if (is_array($opts['cid'])) {
            $cids = $opts['cid'];
        } else {
            $cids = array($opts['cid']);
        }
        foreach ($cids as $cid) {
            $esc_cid = mysqli_real_escape_string($db_connect, $cid);
            $clauses[] = " `user`.`cid`='$esc_cid' ";
        }
        $sql .= "
            AND (" . implode(' OR ', $clauses) . ")
        ";
    }
    if (array_key_exists('filter', $opts)) {
        foreach ($opts['filter'] as $key => $value) {
            if ($key === 'username') {
                $sql .= "
                    AND `username`='" . mysqli_real_escape_string($db_connect, $value) . "'
                ";
            } else if ($key === 'email') {
                $sql .= "
                    AND `contact`.`email`='" . mysqli_real_escape_string($db_connect, $value) . "'
                ";
            }
        }
    }
    $res = mysqli_query($db_connect, $sql);
    if (!$res) { crm_error(mysqli_error($res)); }
    // Create result array
    $users = array();
    $row = mysqli_fetch_assoc($res);
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
        $row = mysqli_fetch_assoc($res);
    }
    return $users;
}

/**
 * Implementation of hook_data_alter().
 * @param $type The type of the data being altered.
 * @param $data An array of structures of the given $type.
 * @param $opts An associative array of options.
 * @return An array of modified structures.
 */
function user_data_alter ($type, $data = array(), $opts = array()) {
    switch ($type) {
        case 'contact':
            // Get cids of all contacts passed into $data
            $cids = array();
            foreach ($data as $contact) {
                $cids[] = $contact['cid'];
            }
            // Add the cids to the options
            $user_opts = $opts;
            $user_opts['cid'] = $cids;
            // Get an array of user structures for each cid
            $user_data = crm_get_data('user', $user_opts);
            // Create a map from cid to user structure
            $cid_to_user = array();
            foreach ($user_data as $user) {
                $cid_to_user[$user['cid']] = $user;
            }
            // Add user structures to the contact structures
            foreach ($data as $i => $contact) {
                $user = $cid_to_user[$contact['cid']];
                if ($user) {
                    $data[$i]['user'] = $user;
                }
            }
            break;
    }
    return $data;
}

/**
 * Return data for one or more roles.
 * @param $opts An associative array of options.
 * @return An array with each element representing a role.
 */
function user_role_data ($opts = null) {
    global $db_connect;
    // Construct map from role ids to arrays of permissions granted
    $permissionMap = array();
    $sql = "
        SELECT `rid`, `permission`
        FROM `role_permission`
        WHERE 1
    ";
    if (!empty($opts['rid'])) {
        $sql .= "
            AND `rid`='" . mysqli_real_escape_string($db_connect, $opts['rid']) . "'
        ";
    }
    $res = mysqli_query($db_connect, $sql);
    if (!$res) { crm_error(mysqli_error($res)); }
    $row = mysqli_fetch_assoc($res);
    while ($row) {
        if (!array_key_exists($row['rid'], $permissionMap)) {
            $permissionMap[$row['rid']] = array();
        }
        $permissionMap[$row['rid']][] = $row['permission'];
        $row = mysqli_fetch_assoc($res);
    }
    // Construct query for roles
    $sql = "
        SELECT `rid`, `name`
        FROM `role`
        WHERE 1
    ";
    $res = mysqli_query($db_connect, $sql);
    if (!$res) { crm_error(mysqli_error($res)); }
    $roles = array();
    $row = mysqli_fetch_assoc($res);
    while ($row) {
        $role = $row;
        if (array_key_exists($row['rid'], $permissionMap)) {
            $role['permissions'] = $permissionMap[$row['rid']];
        } else {
            $role['permissions'] = array();
        }
        $roles[] = $role;
        $row = mysqli_fetch_assoc($res);
    }
    return $roles;
}

// User adding and deleting ////////////////////////////////////////////////////

/**
 * Update user data when a contact is updated.
 * @param $contact The contact data array.
 * @param $op The operation being performed.
 */
function user_contact_api ($contact, $op) {
    if (!isset($contact['user'])) {
        return $contact;
    }
    switch ($op) {
        case 'create':
            $contact['user']['cid'] = $contact['cid'];
            user_save($contact['user']);
            break;
        case 'update':
            user_save($contact['user']);
            break;
        case 'delete':
            user_delete($contact['cid']);
            unset($contact['user']);
            break;
        default:
            crm_error('Unkown operation: ' . $op);
            break;
    }
    return $contact;
}

/**
 * Saves a user into the database
 * @param $user the user to save.
 * @return an array representing the user that was saved in the database.
 */
function user_save ($user) {
    global $db_connect;
    // First figure out whether the user is in the database or not
    $opts = array();
    $opts['cid'] = $user['cid'];
    $user_array = user_data($opts);
    if(empty($user_array)){
        // The user is not in the db, insert it
        $esc_name = mysqli_real_escape_string($db_connect, $user['username']);
        $esc_cid = mysqli_real_escape_string($db_connect, $user['cid']);
        // Add user
        $sql = "
            INSERT INTO `user`
            (`username`, `cid`)
            VALUES
            ('$esc_name', '$esc_cid')
        ";
        $res = mysqli_query($db_connect, $sql);
        if (!$res) crm_error(mysqli_error($res));
    } else {
        // The user already exists, update it
        $sql = "
            UPDATE `user`
            SET `username`='$esc_name'
            , `hash`='$esc_hash'
            , `salt`='$esc_salt'
            WHERE `cid`='$esc_cid'
        ";
        $res = mysqli_query($db_connect, $sql);
        if (!$res) crm_error(mysqli_error($res));
    }
    return $user;
}

/**
 * Delete user.
 * @param $cid The user's cid.
 */
function user_delete ($cid) {
    global $db_connect;
    $esc_cid = mysqli_real_escape_string($db_connect, $cid);
    $sql = "
        DELETE FROM `user`
        WHERE `cid`='$esc_cid'
    ";
    $res = mysqli_query($db_connect, $sql);
    if (!$res) crm_error(mysqli_error($res));
    $sql = "
        DELETE FROM `user_role`
        WHERE `cid`='$esc_cid'
    ";
    $res = mysqli_query($db_connect, $sql);
    if (!$res) crm_error(mysqli_error($res));
    message_register("Deleted user info for: " . theme('contact_name', $cid));
}

// Initalisation code //////////////////////////////////////////////////////////

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
 * @return the cid of the logged in user.
 */
function user_id () {
    if (isset($_SESSION['userId'])) {
        return $_SESSION['userId'];
    }
    return 0;
}

/**
 * Update the session variables to set a specified user as logged in.
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
 * @param $cid The cid of the user being queried, defaults to current user.
 * @return The username for the specified user.
 */
function user_username($cid = null) {
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
    global $db_connect;
    $sql = "
        SELECT *
        FROM `role`
        WHERE 1
    ";
    $res = mysqli_query($db_connect, $sql);
    if (!$res) { crm_error(mysqli_error($res)); }
    $roles = array();
    $row = mysqli_fetch_assoc($res);
    while ($row) {
        $roles[] = $row['name'];
        $row = mysqli_fetch_assoc($res);
    }
    return $roles;
}

/**
 * Check if the logged in user has permissions for a specified action.
 * @param $permission The permission to check for.
 * @return true if the user is granted $permission.
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
    if (!$access) {
        $role = crm_get_one('user_role', array('rid'=>1));
        $access = in_array($permission, $role['permissions']);
    }
    $user_permission_cache[$permission] = $access;
    return $access;
}

/**
 * Check if the user has a specific role
 * @param $cid The cid of the user being queried
 * @param $permission The permission to check for.
 * @return true if the user is granted $permission.
 */
function user_subject_access ($cid, $permission) {
    global $user_permission_cache;
    // The admin user has access to everything
    if ($cid == 1) {
        return true;
    }
    // Check cache
    if (array_key_exists($permission, $user_permission_cache)) {
        return $user_permission_cache[$permission];
    }
    // Get list of the users roles and check each for the permission
    $data = user_data(array('cid'=>$cid));
    $access = in_array($permission, $data[0]['permissions']);
    $user_permission_cache[$permission] = $access;
    return $access;
}

/**
 * Generate a password reset url.
 * @param $username
 * @return A string containing a password reset url.
 */
function user_reset_password_url ($username) {
    global $db_connect;
    global $config_host;
    global $config_base_path;
    global $config_protocol_security;
    // Get user info
    $esc_username = mysqli_real_escape_string($db_connect, $username);
    $sql = "
        SELECT * FROM `user`
        INNER JOIN `contact` ON `user`.`cid`=`contact`.`cid`
        WHERE `user`.`username`='$esc_username'
    ";
    $res = mysqli_query($db_connect, $sql);
    if (!$res) crm_error(mysqli_error($res));
    $row = mysqli_fetch_assoc($res);
    // Make sure user exists
    if (empty($row)) {
        error_register('No such username');
        return '';
    }
    // Generate code
    $code = sha1(uniqid(time()));
    // Insert code into reminder table
    $esc_cid = mysqli_real_escape_string($db_connect, $row['cid']);
    $esc_code = mysqli_real_escape_string($db_connect, $code);
    $sql = "
        REPLACE INTO `resetPassword`
        (`cid`, `code`)
        VALUES
        ('$esc_cid', '$esc_code')
    ";
    $res = mysqli_query($db_connect, $sql);
    // Generate reset url
    $url = $config_protocol_security . '://' . $config_host . crm_url("reset-confirm&v=" . $code);
    return $url;
}

/**
 * Check whether a specified password reset code exists.
 * @param $code A string containing the code.
 * @return true if the specified reset code exists.
 */
function user_check_reset_code ($code) {
    global $db_connect;
    // Query database for code
    $esc_code = mysqli_real_escape_string($db_connect, $code);
    $sql = "
        SELECT *
        FROM `resetPassword`
        WHERE `code`='$esc_code'
    ";
    $res = mysqli_query($db_connect, $sql);
    if (!$res) { crm_error(mysqli_error($res)); }
    // Fetch first row
    $row = mysqli_fetch_assoc($res);
    // Return true if row is not empty
    return (boolean)$row;
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

// Command Handlers ////////////////////////////////////////////////////////////

/**
 * Handle login request.
 * @return the url to display when complete.
 */
function command_login () {
    global $esc_post;
    //Check to see if there was an @ sign in the 'username'. This will signify that the user
    //probably entered their email, and not their username.
    if (strpos($_POST['username'], "@") === false){
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
        error_register('No user found');
        error_register('Invalid username/password');
        $next = crm_url('login');
        return;
    }
    // Check password
    $user = $users[0];
    $valid = user_check_password($_POST['password'], $user);
    if ($valid) {
        user_login($user['cid']);
        $next = crm_url();
    } else {
        error_register('Invalid username/password');
        $next = crm_url('login');
    }
    // Redirect to index
    return $next;
}

/**
 * Handle logout request.
 * @return The url to display when complete.
 */
function command_logout () {
    // Unset all of the session variables.
    $_SESSION = array();
    // If it's desired to kill the session, also delete the session cookie.
    // Note: This will destroy the session, and not just the session data!
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    // Finally, destroy the session.
    session_destroy();
    // Redirect to index
    return crm_url();
}

/**
 * Respond to reset password request.
 */
function command_reset_password () {
    global $config_host;
    global $config_base_path;
    global $config_email_from;
    global $config_site_title;
    // Send code to user by username
    $user = crm_get_one('user', array('filter'=>array('username'=>$_POST['username'])));
    if (empty($user)) {
        // Try email instead
        $user = crm_get_one('user', array('filter'=>array('email'=>$_POST['username'])));
    }
    if (empty($user)) {
        error_register('No such username/email.');
        return crm_url();
    }
    $contact = crm_get_one('contact', array('cid'=>$user['cid']));
    $url = user_reset_password_url($user['username']);
    if (!empty($url)) {
        $to = $contact['email'];
        $subject = "[$config_site_title] Reset Password";
        $from = $config_email_from;
        $headers = "From: $from\r\nContent-Type: text/html; charset=ISO-8859-1\r\n";
        $message = "To reset your password, visit the following url: $url";
        $res = mail($to, $subject, $message, $headers);
        // Notify user to check their email
        message_register('Instructions for resetting your password have been sent to your e-mail.');
    }
    return crm_url();
}

/**
 * Respond to password reset confirmation.
 * @return The url to display after the command is processed.
 */
function command_reset_password_confirm () {
    global $db_connect;
    global $esc_post;
    // Check code
    if (!user_check_reset_code($_POST['code'])) {
        error_register('Invalid reset code');
        return crm_url();
    }
    // Check that passwords match
    if ($_POST['password'] != $_POST['confirm']) {
        error_register('Passwords do not match');
        return crm_url();
    }
    // Get user id
    $sql = "
        SELECT *
        FROM `resetPassword`
        WHERE `code`='$esc_post[code]'
    ";
    $res = mysqli_query($db_connect, $sql);
    if (!$res) { crm_error(mysqli_error($res)); }
    $row = mysqli_fetch_assoc($res);
    $esc_cid = mysqli_real_escape_string($db_connect, $row['cid']);
    // Calculate hash
    $salt = user_salt();
    $esc_hash = mysqli_real_escape_string($db_connect, user_hash($_POST['password'], $salt));
    $esc_salt = mysqli_real_escape_string($db_connect, $salt);
    // Update password
    $sql = "
        UPDATE `user`
        SET `hash`='$esc_hash'
        , `salt`='$esc_salt'
        WHERE `cid`='$esc_cid'
    ";
    $res = mysqli_query($db_connect, $sql);
    if (!$res) { crm_error(mysqli_error($res)); }
    // Notify user to check their email
    message_register('Your password has been reset, you may now log in');
    return crm_url('login');
}

/**
 * Set password from user page.
 * @return The url to display after the command is processed.
 */
function command_set_password () {
    global $db_connect;
    global $esc_post;
    // Check permissions
    if ((user_id() != $esc_post['cid']) && !user_access('user_edit')) {
        error_register('Current user does not have permission: user_edit');
        return crm_url("contact&cid=$esc_cid");
    }
    // Check that passwords match
    if ($_POST['password'] != $_POST['confirm']) {
        error_register('Passwords do not match');
        return crm_url("contact&cid=$esc_cid");
    }
    // Get user id
    $sql = "
        SELECT *
        FROM `user`
        WHERE `cid`='$esc_post[cid]'
    ";
    $res = mysqli_query($db_connect, $sql);
    if (!$res) { crm_error(mysqli_error($res)); }
    $row = mysqli_fetch_assoc($res);
    $esc_cid = mysqli_real_escape_string($db_connect, $row['cid']);
    // Calculate hash
    $salt = user_salt();
    $esc_hash = mysqli_real_escape_string($db_connect, user_hash($_POST['password'], $salt));
    $esc_salt = mysqli_real_escape_string($db_connect, $salt);
    // Update password
    $sql = "
        UPDATE `user`
        SET `hash`='$esc_hash'
        , `salt`='$esc_salt'
        WHERE `cid`='$esc_cid'
    ";
    $res = mysqli_query($db_connect, $sql);
    if (!$res) { crm_error(mysqli_error($res)); }
    message_register("The user's password has been reset");
    return crm_url("contact&cid=$esc_cid");
}

/**
 * Handle user permissions update request.
 * @return The url to display on completion.
 */
function command_user_permissions_update () {
    global $db_connect;
    global $esc_post;
    // Check permissions
    if (!user_access('user_edit')) {
        error_register('Current user does not have permission: user_edit');
        return crm_url('permissions');
    }
    // Check status of each permission for each role
    $perms = user_permissions_list();
    $roles = user_role_data();
    foreach ($perms as $perm) {
        $esc_perm = mysqli_real_escape_string($db_connect, $perm);
        foreach ($roles as $role) {
            $key = "$perm-$role[name]";
            $esc_rid = mysqli_real_escape_string($db_connect, $role['rid']);
            if ($_POST[$key]) {
                // Ensure the role has this permission
                $sql = "
                    SELECT *
                    FROM `role_permission`
                    WHERE `rid`='$esc_rid' AND `permission`='$esc_perm'
                ";
                $res = mysqli_query($db_connect, $sql);
                if (!$res) { crm_error(mysqli_error($res)); }
                if (mysqli_num_rows($res) === 0) {
                    $sql = "
                        INSERT INTO `role_permission`
                        (`rid`, `permission`)
                        VALUES
                        ('$esc_rid', '$esc_perm')
                    ";
                }
                $res = mysqli_query($db_connect, $sql);
                if (!$res) { crm_error(mysqli_error($res)); }
            } else {
                // Delete the permission for this role
                $sql = "
                    DELETE FROM `role_permission`
                    WHERE `rid`='$esc_rid' AND `permission`='$esc_perm'
                ";
                $res = mysqli_query($db_connect, $sql);
                if (!$res) { crm_error(mysqli_error($res)); }
            }
        }
    }
    return crm_url('permissions');
}

/**
 * Handle user role update request.
 * @return The url to display on completion.
 */
function command_user_role_update () {
    global $db_connect;
    global $esc_post;
    // Check permissions
    if (!user_access('user_edit')) {
        error_register('Current user does not have permission: user_edit');
        return crm_url('members');
    }
    // Check permissions
    if (!user_access('user_role_edit')) {
        error_register('Current user does not have permission: user_role_edit');
        return crm_url('members');
    }
    // Delete all roles for specified user
    $sql = "
        DELETE FROM `user_role`
        WHERE `cid`='$esc_post[cid]'
    ";
    $res = mysqli_query($db_connect, $sql);
    if (!$res) { crm_error(mysqli_error($res)); }
    // Re-add each role
    $roles = user_role_data();
    foreach ($roles as $role) {
        if ($_POST[$role['name']]) {
            $esc_rid = mysqli_real_escape_string($db_connect, $role['rid']);
            $sql = "
                INSERT INTO `user_role`
                (`cid`, `rid`)
                VALUES
                ('$esc_post[cid]', '$esc_rid')
            ";
            $res = mysqli_query($db_connect, $sql);
            if (!$res) { crm_error(mysqli_error($res)); }
        }
    }
    return crm_url("contact&cid=$_POST[cid]&tab=roles");
}

// Forms ///////////////////////////////////////////////////////////////////////

/**
 * @return login form structure.
 */
function login_form () {
    $form = array(
        'type' => 'form'
        , 'method' => 'post'
        , 'command' => 'login'
        , 'fields' => array(
            array(
                'type' => 'fieldset'
                , 'label' => 'Log in'
                , 'fields' => array(
                    array(
                        'type' => 'text'
                        , 'label' => 'Username or Email'
                        , 'name' => 'username'
                        , 'class' => 'focus'
                    )
                    , array(
                        'type' => 'password'
                        , 'label' => 'Password'
                        , 'name' => 'password'
                    )
                    , array(
                        'type' => 'submit'
                        , 'name' => 'submitted'
                        , 'value' => 'Log in'
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
        'type' => 'form'
        , 'method' => 'post'
        , 'command' => 'reset_password'
        , 'fields' => array(
            array(
                'type' => 'fieldset'
                , 'label' => 'Reset password'
                , 'fields' => array(
                    array(
                        'type' => 'text'
                        , 'label' => 'Username or Email'
                        , 'name' => 'username'
                    )
                    , array(
                        'type' => 'submit'
                        , 'name' => 'submitted'
                        , 'value' => 'Send Email'
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
        'type' => 'form'
        , 'method' => 'post'
        , 'command' => 'reset_password_confirm'
        , 'hidden' => array(
            'code' => $code
        )
        , 'fields' => array(
            array(
                'type' => 'fieldset'
                , 'label' => 'Change password'
                , 'fields' => array(
                    array(
                        'type' => 'password'
                        , 'label' => 'Password'
                        , 'name' => 'password'
                    )
                    , array(
                        'type' => 'password'
                        , 'label' => 'Confirm'
                        , 'name' => 'confirm'
                    )
                    , array(
                        'type' => 'submit'
                        , 'name' => 'submitted'
                        , 'value' => 'Change password'
                    )
                )
            )
        )
    );
    return $form;
}

/**
 * @return The set password form structure.
 */
function user_set_password_form ($cid) {
    $form = array(
        'type' => 'form'
        , 'method' => 'post'
        , 'command' => 'set_password'
        , 'hidden' => array(
            'cid' => $cid
        )
        , 'fields' => array(
            array(
                'type' => 'fieldset'
                , 'label' => 'Change password'
                , 'fields' => array(
                    array(
                        'type' => 'password'
                        , 'label' => 'Password'
                        , 'name' => 'password'
                    )
                    , array(
                        'type' => 'password'
                        , 'label' => 'Confirm'
                        , 'name' => 'confirm'
                    )
                    , array(
                        'type' => 'submit'
                        , 'name' => 'submitted'
                        , 'value' => 'Change password'
                    )
                )
            )
        )
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
    $columns[] = array('title' => '');
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
                'type' => 'checkbox'
                , 'name' => "$permission-$role[name]"
                , 'checked' => $checked
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
                'type' => 'submit'
                , 'name' => 'submitted'
                , 'value' => 'Update'
            )
        )
    );
    return $form;
}

/**
 * Return the form structure for editing user roles.
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
            'type' => 'checkbox'
            , 'label' => $role
            , 'name' => $role
            , 'checked' => in_array($role, $user['roles'])
        );
    }
    $fields[] = array(
        'type' => 'submit'
        , 'name' => 'submitted'
        , 'value' => 'Update'
    );
    
    $form = array(
        'type' => 'form'
        , 'method' => 'post'
        , 'command' => 'user_role_update'
        , 'hidden' => array(
            'cid' => $cid
        )
        , 'fields' => $fields
    );
    return $form;
}

// Table, Theme & Page /////////////////////////////////////////////////////////

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
 * @return The themed html string for a login form.
 */
function theme_login_form () {
    return theme('form', crm_get_form('login'));
}

/**
 * @return The themed html for a password reset form.
 */
function theme_user_reset_password_form () {
    return theme('form', crm_get_form('user_reset_password_form'));
}

/**
 * @param $code The pasword reset code.
 * @return The themed html for a password reset form.
 */
function theme_user_reset_password_confirm_form ($code) {
    if (!user_check_reset_code($code)) {
        return '<p>Invalid code</p>';
    }
    return theme('form', crm_get_form('user_reset_password_confirm', $code));
}

/**
 * Return themed html for a user role edit form.
 * @param $cid The cid for the user to edit.
 * @return The themed html string.
 */
function theme_user_role_edit_form ($cid) {
    return theme('form', crm_get_form('user_role_edit', $cid));
}

/**
 * Page hook.  Adds user module content to a page before it is rendered.
 * @param &$page_data Reference to data about the page being rendered.
 * @param $page_name The name of the page being rendered.
 * @param $options The array of options passed to theme('page').
*/
function user_page (&$page_data, $page_name, $options) {
    switch ($page_name) {
        case 'contact':
            // Capture user id
            $cid = $_GET['cid'];
            if (empty($cid)) {
                return;
            }
            // Add view tab
            $view_content = '';
            if (user_id() == $_GET['cid'] || user_access('user_edit')) {
                $view_content .= '<h3>User Info</h3>';
                $view_content .= theme('table_vertical', crm_get_table('user', array('cid' => $cid)));
                $view_content .= '<h3>Change Password</h3>';
                $view_content .= theme('form', crm_get_form('user_set_password', $cid));
            }
            if (!empty($view_content)) {
                page_add_content_bottom($page_data, $view_content, 'View');
            }
            break;
    }
}

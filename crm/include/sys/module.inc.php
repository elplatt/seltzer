<?php
/*
    Copyright 2009-2014 Edward L. Platt <ed@elplatt.com>
    
    This file is part of the Seltzer CRM Project
    module.inc.php - Module installation and upgrade functions

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
 * @return An array of the permissions provided by this module.
 */
function module_permissions () {
    return array(
        'module_upgrade'
    );
}

/**
 * @return list of installed modules.
 */
function module_list () {
    global $config_modules;
    
    return $config_modules;
}

/**
 * @return The revision of an enabled module's current code, 0 if not enabled.
 * @param $module The module's name.
 */
function module_get_code_revision ($module) {
    $revision_func = $module . '_revision';
    if (!function_exists($revision_func)) {
        die('Function does not exist: ' . $revision_func);
    }
    return call_user_func($revision_func);
}

/**
 * Get a module's database schema revision.
 * @param $module The module's name.
 */
function module_get_schema_revision ($module) {
    $esc_module = mysql_real_escape_string($module);
    $sql = "SELECT `revision` FROM `module` WHERE `name`='$esc_module'";
    $res = mysql_query($sql);
    if (!$res) { die(mysql_error()); }
    if (mysql_num_rows($res) === 0) {
        return 0;
    }
    $row = mysql_fetch_assoc($res);
    return $row['revision'];
}

/**
 * Set a module's database schema revision.
 * @param $module The module's name.
 * @param $revision The new revision number.
 */
function module_set_schema_revision ($module, $revision) {
    $esc_module = mysql_real_escape_string($module);
    $esc_revision = mysql_real_escape_string($revision);
    $sql = "SELECT `revision` FROM `module` WHERE `name`='$esc_module'";
    $res = mysql_query($sql);
    if (!$res) { die(mysql_error()); }
    if (mysql_num_rows($res) === 0) {
        $sql = "INSERT INTO `module` (`name`, `revision`) VALUES ('$esc_module', '$esc_revision')";
    } else {
        $sql = "UPDATE `module` SET `revision`='$esc_revision' WHERE `name`='$esc_module'";
    }
    $res = mysql_query($sql);
    if (!$res) { die(mysql_error()); }
}

/**
 * @return true if core is installed.
 */
function module_core_installed () {
    $sql = "SHOW TABLES LIKE 'module'";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    if (mysql_num_rows($res) > 0) {
        return true;
    }
    return false;
}

/**
 * Installs all configured modules.
 */
function module_install () {
    
    // Check whether already installed
    if (module_core_installed()) {
        error_register('The database must be empty before you can install ' . title() . " " . crm_version() . '!');
        return false;
    }
    
    foreach (module_list() as $module) {
        
        // Call the module's installation function
        $installer = $module . '_install';
        if (function_exists($installer)) {
            call_user_func($installer, 0);
        }
        
        // Set the module's schema revision
        $revision = module_get_code_revision($module);
        module_set_schema_revision($module, $revision);
    }
    
    return true;
}

/**
 * Upgrade all configured modules.
 */
function module_upgrade () {
    
    // Make sure core is installed
    if (!module_core_installed()) {
        error_register('Please run the install script');
        return false;
    }
    foreach (module_list() as $module) {
        
        // Get current schema and code revisions
        $old_revision = module_get_schema_revision($module);
        $new_revision = module_get_code_revision($module);
        
        // Upgrade the module to the current revision
        $installer = $module . '_install';
        if (function_exists($installer)) {
            call_user_func($installer, $old_revision);
        }
        
        // Update the revision number in the database
        module_set_schema_revision($module, $new_revision);
    }
    return true;
}

/**
 * @return a table structure of modules and their upgrade status.
 */
function module_upgrade_table () {
    if (!user_access('module_upgrade')) {
        return '';
    }
    $table = array(
        'id' => ''
        , 'class' => ''
        , 'columns' => array(
            array('title'=>'Module')
            , array('title'=>'Old')
            , array('title'=>'New')
            , array('title'=>'Needs upgrade?')
            )
        , 'rows' => array()
        );
    $rows = array();
    foreach (module_list() as $module) {
        $old_rev = module_get_schema_revision($module);
        $new_rev = module_get_code_revision($module);
        $row = array();
        $row[] = $module;
        $row[] = $old_rev;
        $row[] = $new_rev;
        $row[] = ($new_rev > $old_rev) ? '<strong>Yes</strong>' : 'No';
        $table['rows'][] = $row;
    }
    return $table;
}

/**
 * @return The installation form structure.
 */
function module_install_form () {
    $form = array(
        'type' => 'form',
        'method' => 'post',
        'command' => 'module_install',
        'fields' => array(
            array(
                'type' => 'fieldset',
                'label' => 'Install ' . title() . " " . crm_version(),
                'fields' => array(
                    array(
                        'type' => 'text',
                        'label' => 'Admin E-mail',
                        'name' => 'email'
                    ),
                    array(
                        'type' => 'password',
                        'label' => 'Admin Password',
                        'name' => 'password'
                    ),
                    array(
                        'type' => 'submit',
                        'name' => 'submitted',
                        'value' => 'Install'
                    )
                )
            )
        )
    );
    return $form;
}

/**
 * @return The upgrade form structure.
 */
function module_upgrade_form () {
    if (!user_access('module_upgrade')) {
        return '';
    }
    $form = array(
        'type' => 'form',
        'method' => 'post',
        'command' => 'module_upgrade',
        'fields' => array(
            array(
                'type' => 'fieldset',
                'label' => 'Upgrade ' . title() . " " . crm_version() . ' Modules',
                'fields' => array(
                    array(
                        'type' => 'submit',
                        'name' => 'submitted',
                        'value' => 'Upgrade'
                    )
                )
            )
        )
    );
    return $form;
}


/**
 * Handle installation request.
 *
 * @return The url to redirect to on completion.
 */
function command_module_install () {
    global $esc_post;
    
    // Create tables
    $res = module_install();
    if (!$res) {
        return crm_url();
    }
    
    // Add admin contact and user
    $sql = "
        INSERT INTO `contact`
        (`firstName`, `lastName`, `email`)
        VALUES
        ('Admin', 'User', '$esc_post[email]')
    ";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    $cid = mysql_insert_id();
    $esc_cid = mysql_real_escape_string($cid);
    
    $salt = user_salt();
    $esc_hash = mysql_real_escape_string(user_hash($_POST['password'], $salt));
    $esc_salt = mysql_real_escape_string($salt);
    $sql = "
        INSERT INTO `user`
        (`cid`, `username`, `hash`, `salt`)
        VALUES
        ('$esc_cid', 'admin', '$esc_hash', '$esc_salt')
    ";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    
    message_register(title() . " " . crm_version() . ' has been installed.');
    message_register('You may log in as user "admin"');
    return crm_url('login');
}

/**
 * Handle upgrade request.
 *
 * @return The url to redirect to on completion.
 */
function command_module_upgrade () {
    global $esc_post;
    
    // Create tables
    $res = module_upgrade();
    if (!$res) {
        return crm_url();
    }
    
    message_register(title() . " " . crm_version() . ' has been upgraded.');
    return crm_url();
}

/**
 * Initialize the module system
 */
function module_init () {
    global $core_stylesheets;
    global $core_scripts;
    
    foreach (module_list() as $module) {
        $style_func = $module . '_stylesheets';
        if (function_exists($style_func)) {
            $stylesheets = call_user_func($style_func);
            foreach ($stylesheets as $sheet) {
                $core_stylesheets[] = "include/$module/$sheet";
            }
        }
        $style_func = $module . '_scripts';
        if (function_exists($style_func)) {
            $scripts = call_user_func($scripts_func);
            foreach ($scripts as $script) {
                $core_scripts[] = "include/$module/$script";
            }
        }
    }
}

/**
 * Invoke a hook in all modules.
 * @param $hook The hook name.
 * @return An array of results returned from each hook.  If hooks return an
 *   array, it will be merged with other results.
 */
function module_invoke_all ($hook) {
    $args = func_get_args();
    $hook_name = array_shift($args);
    // Call hook for each module
    $results = array();
    foreach (module_list() as $module) {
        $hook = $module . '_' . $hook_name;
        if (function_exists($hook)) {
            $result = call_user_func_array($hook, $args);
            if (is_array($result)) {
                $results = array_merge($results, $result);
            } else {
                $results[] = $result;
            }
        }
    }
    return $result;
}

/**
 * Invoke an entity api in all modules.
 */
function module_invoke_api ($type, $entity, $op) {
    $args = func_get_args();
    $type = array_shift($args);
    $modules = module_list();
    foreach ($modules as $module) {
        $hook = "${module}_${type}_api";
        if (function_exists($hook)) {
            $entity = call_user_func_array($hook, $args);
            if (empty($entity)) {
                crm_error("$hook returned empty entity");
            }
            $args[0] = $entity;
        }
    }
    return $entity;
}

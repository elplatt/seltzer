<?php

/*
    Copyright 2009-2025 Edward L. Platt <ed@elplatt.com>
    
    This file is part of the Seltzer CRM Project
    variable.inc.php - Module to save and retrieve strings
    
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
 * @return This module's revision number. Each new release should increment
 * this number.
 */
function variable_revision () {
    return 2;
}

/**
 * Install or upgrade this module.
 * @param $old_revision The last installed revision of this module, or 0 if the
 *   module has never been installed.
 */
function variable_install($old_revision = 0) {
    global $db_connect;
    global $config_host;
    global $config_base_path;
    global $config_site_title;
    global $config_github_username;
    global $config_github_repo;
    global $config_protocol_security;
    global $config_org_name;
    global $config_org_website;
    global $config_currency_code;
    global $config_email_from;
    global $config_email_to;
    global $config_address1;
    global $config_address2;
    global $config_address3;
    global $config_town_city;
    global $config_zipcode;
    global $config_theme;
    // Create initial database table
    if ($old_revision < 1) {
        $sql = "
            CREATE TABLE IF NOT EXISTS `variable` (
                `name` varchar(255) NOT NULL
                , `value` text NOT NULL
                , PRIMARY KEY (`name`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
        ";
        $res = mysqli_query($db_connect, $sql);
        if (!$res) crm_error(mysqli_error($db_connect));
    }
    if ($old_revision < 2) {
        variable_set('host', $config_host);
        variable_set('base_path', $config_base_path);
        variable_set('site_title', $config_site_title);
        variable_set('github_username', $config_github_username);
        variable_set('github_repo', $config_github_repo);
        variable_set('protocol_security', $config_protocol_security);
        variable_set('org_name', $config_org_name);
        variable_set('org_website', $config_org_website);
        variable_set('currency_code', $config_currency_code);
        variable_set('email_from', $config_email_from);
        variable_set('email_to', $config_email_to);
        variable_set('address1', $config_address1);
        variable_set('address2', $config_address2);
        variable_set('address3', $config_address3);
        variable_set('town_city', $config_town_city);
        variable_set('zipcode', $config_zipcode);
        variable_set('theme', $config_theme);
    }
}

// Utility functions ///////////////////////////////////////////////////////////

/**
 * Set a variable's value.
 * @param $name
 * @param $value
 */
function variable_set ($name, $value) {
    global $db_connect;
    $esc_name = mysqli_real_escape_string($db_connect, $name);
    $esc_value = mysqli_real_escape_string($db_connect, $value);
    // Check if variable exists
    $sql = "
        SELECT `value`
        FROM `variable`
        WHERE `name`='$esc_name'
    ";
    $res = mysqli_query($db_connect, $sql);
    if (!$res) crm_error(mysqli_error($db_connect));
    if (mysqli_num_rows($res) > 0) {
        // Update
        $sql = "
            UPDATE `variable`
            SET `value`='$esc_value'
            WHERE `name`='$esc_name'
        ";
        $res = mysqli_query($db_connect, $sql);
        if (!$res) crm_error(mysqli_error($db_connect));
    } else {
        // Insert
        $sql = "
            INSERT INTO `variable`
            (`name`, `value`)
            VALUES
            ('$esc_name', '$esc_value')
        ";
        $res = mysqli_query($db_connect, $sql);
        if (!$res) crm_error(mysqli_error($db_connect));
    }
}

/**
 * Get a variable's value.
 * @param $name
 * @param $default The value to return if no such variable exists.
 * @return The value of the variable named $name, or $default if not found.
 */
function variable_get ($name, $default = null) {
    global $db_connect;
    $esc_name = mysqli_real_escape_string($db_connect, $name);
    $sql = "
        SELECT `value`
        FROM `variable`
        WHERE `name`='$esc_name'
    ";
    try {
        $res = mysqli_query($db_connect, $sql);
    } catch (Exception $e) {
        return $default;
    }
    $variable = mysqli_fetch_assoc($res);
    if ($variable) {
        return $variable['value'];
    }
    return $default;
}

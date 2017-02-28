<?php

/*
    Copyright 2009-2016 Edward L. Platt <ed@elplatt.com>
    
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
 * @return This module's revision number.  Each new release should increment
 * this number.
 */
function variable_revision () {
    return 1;
}

/**
 * Install or upgrade this module.
 * @param $old_revision The last installed revision of this module, or 0 if the
 *   module has never been installed.
 */
function variable_install($old_revision = 0) {
    global $db_connect;
    // Create initial database table
    if ($old_revision < 1) {
        $sql = '
            CREATE TABLE IF NOT EXISTS `variable` (
              `name` varchar(255) NOT NULL,
              `value` text NOT NULL,
              PRIMARY KEY (`name`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
        ';
        $res = mysqli_query($db_connect, $sql);
        if (!$res) die(mysqli_error($res));
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
    $sql = "SELECT `value` FROM `variable` WHERE `name`='$esc_name'";
    $res = mysqli_query($db_connect, $sql);
    if (!$res) die(mysqli_error($res));
    
    if (mysqli_num_rows($res) > 0) {
        // Update
        $sql = "
            UPDATE `variable`
            SET `value`='$esc_value'
            WHERE `name`='$esc_name'
        ";
        $res = mysqli_query($db_connect, $sql);
        if (!$res) die(mysqli_error($res));
    } else {
        // Insert
        $sql = "
            INSERT INTO `variable`
            (`name`, `value`)
            VALUES ('$esc_name', '$esc_value')
        ";
        $res = mysqli_query($db_connect, $sql);
        if (!$res) die(mysqli_error($res));
    }
}

/**
 * Get a variable's value.
 * @param $name
 * @param $default The value to return if no such variable exists.
 * @return The value of the variable named $name, or $default if not found.
 */
function variable_get ($name, $default) {
    global $db_connect;
    $esc_name = mysqli_real_escape_string($db_connect, $name);
    
    $sql = "SELECT `value` FROM `variable` WHERE `name`='$esc_name'";
    $res = mysqli_query($db_connect, $sql);
    if (!$res) die(mysqli_error($res));
    
    $variable = mysqli_fetch_assoc($res);
    if ($variable) {
        return $variable['value'];
    }
    
    return $default;
}

<?php

/*
    Copyright 2009-2022 Edward L. Platt <ed@elplatt.com>
    
    This file is part of the Seltzer CRM Project
    core.inc.php - Core functions
    
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
function core_revision () {
    return 3;
}

/**
 * Install or upgrade this module.
 * @param $old_revision The last installed revision of this module, or 0 if the
 *   module has never been installed.
 */
function core_install ($old_revision = 0) {
    global $db_connect;
    if ($old_revision < 1) {
        $sql = "
            SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';
        ";
        $res = mysqli_query($db_connect, $sql);
        if (!$res) crm_error(mysqli_error($res));
        $sql = "
            CREATE TABLE IF NOT EXISTS `module` (
                `did` MEDIUMINT(8) unsigned NOT NULL AUTO_INCREMENT
                , `name` VARCHAR(255) NOT NULL
                , `revision` MEDIUMINT(8) unsigned NOT NULL
                , PRIMARY KEY (`did`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
        ";
        $res = mysqli_query($db_connect, $sql);
        if (!$res) crm_error(mysqli_error($res));
    }
}

/**
 * @return An array of the permissions provided by this module.
 */
function core_permissions () {
    $permissions = array_merge(
        module_permissions()
        , array(
            'report_view'
            , 'global_options_view'
            , 'global_options_edit'
        )
    );
    return $permissions;
}

/**
 * @return global options form structure.
 */
function global_options_form () {
    // Ensure user is allowed to view options
    if (!user_access('global_options_view')) {
        return null;
    }
    // Create form
    $form = array(
        'type' => 'form'
        , 'method' => 'post'
        , 'command' => 'global_options_update'
        , 'fields' => array()
        , 'submit' => 'Update'
    );
    // Edit options
    $form['fields'][] = array(
        'type' => 'fieldset'
        , 'label' => 'Edit Global Options'
        , 'fields' => array(
            array(
                'type' => 'text'
                , 'label' => 'Host'
                , 'name' => 'host'
                , 'value' => get_host()
            )
            , array(
                'type' => 'text'
                , 'label' => 'Base Path'
                , 'name' => 'base_path'
                , 'value' => base_path()
            )
            , array(
                'type' => 'text'
                , 'label' => 'Site Title'
                , 'name' => 'site_title'
                , 'value' => title()
            )
            , array(
                'type' => 'text'
                , 'label' => 'GitHub Username'
                , 'name' => 'github_username'
                , 'value' => github_username()
            )
            , array(
                'type' => 'text'
                , 'label' => 'GitHub Repo'
                , 'name' => 'github_repo'
                , 'value' => github_repo()
            )
            , array(
                'type' => 'text'
                , 'label' => 'Protocol Security'
                , 'name' => 'protocol_security'
                , 'value' => protocol_security()
            )
            , array(
                'type' => 'text'
                , 'label' => 'Org Name'
                , 'name' => 'org_name'
                , 'value' => get_org_name()
            )
            , array(
                'type' => 'text'
                , 'label' => 'Org Website'
                , 'name' => 'org_website'
                , 'value' => get_org_website()
            )
            , array(
                'type' => 'text'
                , 'label' => 'Currency Code'
                , 'name' => 'currency_code'
                , 'value' => get_currency_code()
            )
            , array(
                'type' => 'text'
                , 'label' => 'Email From'
                , 'name' => 'email_from'
                , 'value' => get_email_from()
            )
            , array(
                'type' => 'text'
                , 'label' => 'Email To'
                , 'name' => 'email_to'
                , 'value' => get_email_to()
            )
            , array(
                'type' => 'text'
                , 'label' => 'Address 1'
                , 'name' => 'address1'
                , 'value' => get_address1()
            )
            , array(
                'type' => 'text'
                , 'label' => 'Address 2'
                , 'name' => 'address2'
                , 'value' => get_address2()
            )
            , array(
                'type' => 'text'
                , 'label' => 'Address 3'
                , 'name' => 'address3'
                , 'value' => get_address3()
            )
            , array(
                'type' => 'text'
                , 'label' => 'Town/City'
                , 'name' => 'town_city'
                , 'value' => get_town_city()
            )
            , array(
                'type' => 'text'
                , 'label' => 'Postal/Zip Code'
                , 'name' => 'zipcode'
                , 'value' => get_zipcode()
            )
            , array(
                'type' => 'text'
                , 'label' => 'Theme'
                , 'name' => 'theme'
                , 'value' => get_theme()
            )
        )
    );
    return $form;
}

/**
 * Handle global options update request.
 * @return The url to display on completion.
 */
function command_global_options_update () {
    global $db_connect;
    global $esc_post;
    // Check permissions
    if (!user_access('global_options_edit')) {
        error_register('Current user does not have permission: global_options_edit');
        return crm_url('global_options');
    }
    $options = array(
        'host', 'base_path', 'site_title', 'github_username', 'github_repo', 'protocol_security'
        , 'org_name', 'org_website', 'currency_code', 'email_from', 'email_to'
        , 'address1', 'address2', 'address3', 'town_city', 'zipcode', 'theme'
    );
    foreach ($options as $option) {
        $esc_option = mysqli_real_escape_string($db_connect, $option);
        variable_set($esc_option, $esc_post[$esc_option]);
    }
    return crm_url('global_options');
}

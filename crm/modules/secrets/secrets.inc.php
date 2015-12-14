<?php
/*
    Copyright 2014 Edward L. Platt <ed@elplatt.com>
    
    This file is part of the Seltzer CRM Project
    secrets.inc.php - access variables table for secrets
    Created by Matt Huber <unixmonky@gmail.com>

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

// Installation functions //////////////////////////////////////////////////////
/* - add this to config.inc.php:
-----
// Secrets Keeper
$config_modules[] = "secrets";
-----
also update $config_links with:
, 'secrets' => 'Secrets'
*/


/**
 * @return This module's revision number.  Each new release should increment
 * this number.
 */
function secrets_revision () {
    return 1;
}

/**
 * @return An array of the permissions provided by this module.
 */
function secrets_permissions () {
    return array(
        'secrets_view'
        , 'secrets_edit'
        , 'secrets_delete'
    );
}

/**
 * Install or upgrade this module.
 * @param $old_revision The last installed revision of this module, or 0 if the
 *   module has never been installed.
 */
function secrets_install($old_revision = 0) {
    if ($old_revision < 1) {
        // No unique databases needed
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
            'webAdmin' => array('secrets_view', 'secrets_edit', 'secrets_delete')
        );
        foreach ($roles as $rid => $role) {
            $esc_rid = mysql_real_escape_string($rid);
            if (array_key_exists($role, $default_perms)) {
                foreach ($default_perms[$role] as $perm) {
                    $esc_perm = mysql_real_escape_string($perm);
                    $sql = "INSERT INTO `role_permission` (`rid`, `permission`) VALUES ('$esc_rid', '$esc_perm')";
                    $sql .= " ON DUPLICATE KEY UPDATE rid=rid";
                    $res = mysql_query($sql);
                    if (!$res) die(mysql_error());
                }
            }
        }
    }
}

// Utility functions ///////////////////////////////////////////////////////////

// DB to Object mapping ////////////////////////////////////////////////////////

/**
 * Return data for one or more secrets.
 *
 * @param $opts An associative array of options, possible keys are:
 *   'kid' If specified, returns a single memeber with the matching key id;
 *   'cid' If specified, returns all keys assigned to the contact with specified id;
 *   'filter' An array mapping filter names to filter values;
 *   'join' A list of tables to join to the key table.
 * @return An array with each element representing a single key card assignment.
*/ 
function secrets_data ($opts = array()) {
    // Query database
    $sql = "
        SELECT
        `name`
        , `value`
        FROM `variable`
        WHERE 1";
    if (!empty($opts['name'])) {
        $esc_name = mysql_real_escape_string($opts['name']);
        $sql .= " AND `name`='" . $esc_name . "'";
    }
    $sql .= "
        ORDER BY `name` ASC";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    // Store data
    $secrets = array();
    $row = mysql_fetch_assoc($res);
    while (!empty($row)) {
        // Contents of row are name, value
        $secrets[] = $row;
        $row = mysql_fetch_assoc($res);
    }
    return $secrets;
}

/**
 * Create a new secret 
 * @param $secret The secret name,value
 * @return The secret structure with as it now exists in the database.
 */
function secrets_add ($secret) {
    // Escape values
    $fields = array('name', 'value');
    if (isset($secret['name'])) {
        // Add key if nonexists, otherise Update existing key
        $name = $secret['name'];
        $esc_name = mysql_real_escape_string($secret['name']);
        $esc_value = mysql_real_escape_string($secret['value']);
        if ( preg_match('/\s/',$esc_name) || preg_match('/\s/',$esc_value) ) {
            message_register('Whitespace not allowed in Name or Value.');
            return array();
        }
        $sql = "INSERT INTO variable (name, value) ";
        $sql .="VALUES ('" . $esc_name . "', '" . $esc_value . "') ";
        $res = mysql_query($sql);
        if (!$res) {
            message_register('ERROR: ' . mysql_error());
        } else {
            message_register('Secret Added');
        }
        return crm_get_one('secrets', array('name'=>$name));
    }
}

/**
 * Update an existing secret 
 * @param $secret The secret name,value
 * @return The secret structure with as it now exists in the database.
 */
function secrets_edit ($secret) {
    // Escape values
    if (isset($secret['name'])) {
        // Add key if nonexists, otherise Update existing key
        $name = $secret['name'];
        $esc_name = mysql_real_escape_string($secret['name']);
        $esc_value = mysql_real_escape_string($secret['value']);
        $sql = "UPDATE variable ";
        $sql .="SET value = '" . $esc_value . "' ";
        $sql .= "WHERE name = '" . $esc_name . "' ";

        $res = mysql_query($sql);
        // if (!$res) die(mysql_error());
        // message_register('Secret updated');
        if (!$res) {
            message_register('SQL: ' . $sql . '<br>ERROR: ' . mysql_error());
        } else {
            message_register('Secret updated');
        }
        return crm_get_one('secrets', array('name'=>$name));
    }
}

/**
 * Delete an existing secret 
 * @param $secret The secret name
 */
function secrets_delete ($secret) {
    if (isset($secret['name'])) {
        $esc_name = mysql_real_escape_string($secret['name']);
        $sql = "DELETE FROM variable WHERE name = '" . $esc_name . "'";
        $res = mysql_query($sql);
        if (!$res) die(mysql_error());
        if (mysql_affected_rows() > 0) {
            message_register('Secret deleted.');
        }
    } else {
        message_register('No such secret');
        var_dump_pre($secret);
    }
}

// Tables //////////////////////////////////////////////////////////////////////
// Put table generators here
function secrets_table ($opts) {
    // Determine settings
    $export = false;
    $show_ops = isset($opts['ops']) ? $opts['ops'] : true;
    foreach ($opts as $option => $value) {
        switch ($option) {
            case 'export':
                $export = $value;
                break;
        }
    }
    // Get secrets data
    $secrets = crm_get_data('secrets');
    if (count($secrets) < 1) {
        return array();
    }

    // Initialize table
    $table = array(
        'columns' => array(
            array('title' => 'Name')
            , array('title' => 'Value')
        )
        , 'rows' => array()
    );

    // Add ops column
    if (user_access('secrets_edit') || user_access('secrets_delete')) {
        $table['columns'][] = array('title'=>'Ops','class'=>'');
    }
    // Add rows
    foreach ($secrets as $key) {
        // Add secrets data
        $row = array();
        if (user_access('secrets_view')) {
            // Add cells
            $row[] = $key['name'];
            $row[] = $key['value'];
        }
        // Construct ops array
        $ops = array();
        // Add edit op
        if (user_access('secrets_edit')) {
            $ops[] = '<a href=' . crm_url('secrets_edit&name=' . $key['name']) . '>edit</a> ';
            // $ops[] = '<a href=' . crm_url('secrets_edit&name=' . $key['name'] . '#tab-edit') . '>edit</a> ';
        }
        // Add delete op
        if (user_access('secrets_delete')) {
            $ops[] = '<a href=' . crm_url('delete&type=secrets&id=' . $key['name']) . '>delete</a>';
        }
        // Add ops row
        $row[] = join(' ', $ops);
        $table['rows'][] = $row;  
    }
  return $table;
}

// function secrets_edit_table ($opts) {
//     // Get secret data
//     if (empty($opts['name'])) {
//         return;
//     }
//     $name = $opts['name'];
//     $secrets = crm_get_one('secrets', array('name'=>$name));
//     if (count($secrets) < 1) {
//         return array();
//     }
    
//     // Initialize table
//     $table = array(
//         'columns' => array(
//             array('title' => 'Name')
//             , array('title' => 'Value')
//         )
//         , 'rows' => array()
//     );

//     // Add columns
//     // if (user_access('secrets_view')) {
//     //     $table['columns'][] = array("title"=>'Name', 'class'=>'', 'id'=>'');
//     //     $table['columns'][] = array("title"=>'Value', 'class'=>'', 'id'=>'');
//     // }
//     // Add ops column
//     if (user_access('secrets_edit')) {
//         $table['columns'][] = array('title'=>'Ops','class'=>'');
//     }
//     // Add row to edit; assumption is we only ever edit one secret at a time
//     $row = array();
//     if (user_access('secrets_view')) {
//         // Add cells
//         $row[] = $secrets['name'];
//         $row[] = $secrets['value'];
//     }
//     // Construct ops array
//     $ops = array();
//     // Add edit op
//     if (user_access('secrets_edit')) {
//         $ops[] = '<a href=' . crm_url('secrets_edit&name=' . $secrets['name']) . '>save</a> ';
//     }
//     // // Add delete op
//     // if (user_access('secrets_delete')) {
//     //     $ops[] = '<a href=' . crm_url('delete&type=secret&secret=' . $key['name']) . '>delete</a>';
//     // }
//     // Add ops row
//     $row[] = join(' ', $ops);
//     $table['rows'][] = $row;  
//     return $table;
// }

// Forms ///////////////////////////////////////////////////////////////////////
// Put form generators here

function secrets_add_form () {
    
    // Ensure user is allowed to edit keys
    if (!user_access('secrets_edit')) {
        return NULL;
    }
    
    // Create form structure
    $form = array(
        'type' => 'form',
        'method' => 'post',
        'command' => 'secrets_add',
        // 'hidden' => array(
        //     'name' => $name
        // ),
        'fields' => array(
            array(
                'type' => 'fieldset',
                'label' => 'Add Secret',
                'fields' => array(
                    array(
                        'type' => 'text',
                        'label' => 'Name',
                        'name' => 'name'
                    ),
                    array(
                        'type' => 'text',
                        'label' => 'Value',
                        'name' => 'value'
                    ),
                    array(
                        'type' => 'submit',
                        'value' => 'Add'
                    )
                )
            )
        )
    );
    
    return $form;
}

/**
 * Return the secrets edit form structure.
 *
 * @param $name The name of the secret to edit.
 * @return The form structure.
*/
function secrets_edit_form ($name) {
    
    // Ensure user is allowed to edit keys
    if (!user_access('secrets_edit')) {
        return NULL;
    }

    // Get secret
    $data = crm_get_one('secrets', array('name'=>$name));
    if (empty($data['name']) || count($data['name']) < 1) {
        return array();
    }

    
    // Create form structure
    $form = array(
        'type' => 'form',
        'method' => 'post',
        'command' => 'secrets_edit',
        // 'hidden' => array(
        //     'name' => $name
        // ),
        'fields' => array(
            array(
                'type' => 'fieldset',
                'label' => 'Add Secret',
                'fields' => array(
                    array(
                        'type' => 'readonly',
                        'label' => 'Name',
                        'name' => 'name',
                        'value' => $data['name']
                    ),
                    array(
                        'type' => 'text',
                        'label' => 'Value',
                        'name' => 'value',
                        'value' => $data['value'],
                    ),
                    array(
                        'type' => 'submit',
                        'value' => 'Save'
                    )
                )
            )
        )
    );
    
    return $form;
}

/**
 * Return the delete key assigment form structure.
 *
 * @param $kid The kid of the key assignment to delete.
 * @return The form structure.
*/
function secrets_delete_form ($name) {
    
    // Ensure user is allowed to delete keys
    if (!user_access('secrets_delete')) {
        return NULL;
    }
    
    // Get secret data
    $secrets = crm_get_one('secrets', array('name'=>$name));

    // Construct secret name
    $secret_name = $secrets['name'];
    // Create form structure
    $form = array(
        'type' => 'form',
        'method' => 'post',
        'command' => 'secrets_delete',
        'submit' => 'Delete',
        'hidden' => array(
            'name' => $secrets['name']
        ),
        'fields' => array(
            array(
                'type' => 'fieldset',
                'label' => 'Delete Secret',
                'fields' => array(
                    array(
                        'type' => 'message',
                        'value' => '<p>Are you sure you want to delete the secret  "' . $secret_name . '"? This cannot be undone.',
                    // ),
                    // array(
                    //     'type' => 'submit',
                    //     'value' => 'Delete'
                    )
                )
            )
        )
    );
    
    return $form;
}

// Themeing ////////////////////////////////////////////////////////////////////

/**
 * Return the themed html for an add secret assignment form.
 *
 * @param $name The name of the secret to add a value assignment for.
 * @return The themed html string.
 */
function theme_secrets_add_form ($name) {
    return theme('form', crm_get_form('secrets_add', $name));
}

/**
 * Return themed html for an edit secret form.
 *
 * @param $name The name of the key assignment to edit.
 * @return The themed html string.
 */
function theme_secrets_edit_form ($name) {
    return theme('form', crm_get_form('secrets_edit', $name));
}

// Pages ///////////////////////////////////////////////////////////////////////

/**
 * @return An array of pages provided by this module.
 */
function secrets_page_list () {
    $pages = array();
    if (user_access('secrets_view')) {
        $pages[] = 'secrets';
        $pages[] = 'secrets_edit';
    }
    return $pages;
}

/**
 * Page hook.  Adds module content to a page before it is rendered.
 *
 * @param &$page_data Reference to data about the page being rendered.
 * @param $page_name The name of the page being rendered.
 * @param $options The array of options passed to theme('page').
*/
function secrets_page (&$page_data, $page_name, $options) {
    switch ($page_name) {

        case 'secrets':
            page_set_title($page_data, 'Secret Keeper');
            if (user_access('secrets_view')) {
                $secrets = theme('table', 'secrets', array('show_export'=>true));
            }
            if (user_access('secrets_edit')) {
                $secrets .= theme('secrets_add_form', '');
            }
            page_add_content_top($page_data, $secrets);
            break;

        case 'secrets_edit':
            // Capture secret to edit
            $name = $options['name'];
            if (empty($name)) {
                return;
            }           
            page_set_title($page_data, 'Edit a Secret');
            if (user_access('secrets_edit')) {
                page_add_content_top($page_data, theme('secrets_edit_form', $name));
            }
            break;
    }
}
// Request Handlers ////////////////////////////////////////////////////////////
// Put request handlers here

/**
 * Handle key secret request.
 *
 * @return The url to display on completion.
 */
function command_secrets_add() {
    // Verify permissions
    if (!user_access('secrets_edit')) {
        error_register('Permission denied: secrets_edit');
        return crm_url('secrets');
    }
    secrets_add($_POST);
    return crm_url('secrets');
}

/**
 * Handle key secret edit.
 *
 * @return The url to display on completion.
 */
function command_secrets_edit() {
    // Verify permissions
    if (!user_access('secrets_edit')) {
        error_register('Permission denied: secrets_edit');
        return crm_url('secrets');
    }
    secrets_edit($_POST);
    return crm_url('secrets');
}

/**
 * Handle secret delete request.
 *
 * @return The url to display on completion.
 */
function command_secrets_delete() {
    global $esc_post;
    // Verify permissions
    if (!user_access('secrets_delete')) {
        error_register('Permission denied: secrets_delete');
        return crm_url('secrets');
    }
    secrets_delete($_POST);
    return crm_url('secrets');
}

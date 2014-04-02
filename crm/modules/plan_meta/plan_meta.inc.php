<?php

/*
    Copyright 2009-2013 Edward L. Platt <ed@elplatt.com>
    Copyright 2013 David "Buzz" Bussenschutt <davidbuzz@gmail.com>

    This file is part of the Seltzer CRM Project
    plan_meta.inc.php - Meta-Tag tracking module
    This module is for associating arbitrary "meta data" with member/s.
    This can be useful for making arbitrary groupings of users that have special meaning to you.
    Examples:
    We have one called "Respected", which entitles a member to get a physical key to the building....
    Or one called "Machinist", which means they have passed basic safety assessment to permit them to use our Mill/Lathe.
    It's kinda like an extension to the Permissions system, but for managing things external to Seltzer.

    Seltzer is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    any later version.

    Seltzer is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Seltzer. If not, see <http://www.gnu.org/licenses/>.
*/

// Installation functions //////////////////////////////////////////////////////

/**
 * @return This module's revision number. Each new release should increment
 * this number.
 */
function plan_meta_revision () {
    return 1;
}

/**
 * @return An array of the permissions provided by this module.
 */
function plan_meta_permissions () {
    return array(
        'plan_meta_view'
        , 'plan_meta_edit'
        , 'plan_meta_delete'
    );
}

/**
 * Install or upgrade this module.
 * @param $old_revision The last installed revision of this module, or 0 if the
 * module has never been installed.
 */
function plan_meta_install($old_revision = 0) {
    if ($old_revision < 1) {
        $sql = '
            CREATE TABLE IF NOT EXISTS `plan_meta` (
            `pmid` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
            `pid` mediumint(8) unsigned NOT NULL,
            `start` date DEFAULT NULL,
            `end` date DEFAULT NULL,
            `tagstr` varchar(255) NOT NULL,
            PRIMARY KEY (`pmid`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
            ';
        $res = mysql_query($sql);
        if (!$res) die(mysql_error());
        
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
            'director' => array('plan_meta_view', 'plan_meta_edit', 'plan_meta_delete'),
            'webAdmin' => array('plan_meta_view', 'plan_meta_edit', 'plan_meta_delete'),
            'member' => array('plan_meta_view')
        );
        foreach ($roles as $rid => $role) {
            $esc_rid = mysql_real_escape_string($rid);
            if (array_key_exists($role, $default_perms)) {
                foreach ($default_perms[$role] as $perm) {
                    $esc_perm = mysql_real_escape_string($perm);
                    $sql = "INSERT INTO `role_permission` (`rid`, `permission`) VALUES ('$esc_rid', '$esc_perm')";
                    $res = mysql_query($sql);
                    if (!$res) die(mysql_error());
                }
            }
        }
    }
}

// Utility functions ///////////////////////////////////////////////////////////

/**
 * Generate a descriptive string for a single Meta-Tag .
 *
 * @param $pmid The id of the meta-tag to describe.
 * @return The description string.
 */
function plan_meta_description ($pmid) {
    
    // Get meta data
    $data = crm_get_data('plan_meta', array('pmid' => $pmid));
    if (empty($data)) {
        return '';
    }
    $plan_meta = $data[0];
    
    // Construct description
    $description = 'Meta ';
    $description .= $plan_meta['tagstr'];
    
    return $description;
}

// DB to Object mapping ////////////////////////////////////////////////////////

/**
 * Return data for one or more meta-tag assignments.
 *
 * @param $opts An associative array of options, possible metas are:
 * 'pmid' If specified, returns a single plan with the matching meta id;
 * 'pid' If specified, returns all metas assigned to the plan with specified id;
 * 'filter' An array mapping filter names to filter values;
 * 'join' A list of tables to join to the meta table.
 * @return An array with each element representing a single meta-tag assignment.
 */
function plan_meta_data ($opts = array()) {
    
    // Determine joins
    $join_plan = false;
    if (array_key_exists('join', $opts)) {
        foreach ($opts['join'] as $table) {
            if ($table === 'plan') {
                $join_plan = true;
            }
        }
    }
    
    // Create map from pids to plan names if necessary
    // TODO: Add filters for speed
    if ($join_plan) {
        $plans = member_plan_data();
        $pidToplan = array();
        foreach ($plans as $plan) {
            $pidToPlan[$plan['pid']] = $plan;
        }
    }
    
    // Query database
    $sql = "
        SELECT
        `pmid`
        , `pid`
        , `start`
        , `end`
        , `tagstr`
        FROM `plan_meta`
        WHERE 1";
    if (!empty($opts['pmid'])) {
        $esc_pmid = mysql_real_escape_string($opts['pmid']);
        $sql .= " AND `pmid`='$esc_pmid'";
    }
    if (!empty($opts['pid'])) {
        $esc_pid = mysql_real_escape_string($opts['pid']);
        $sql .= " AND `pid`='$esc_pid'";
    }
    if (!empty($opts['filter'])) {
        foreach ($opts['filter'] as $name => $param) {
            switch ($name) {
                case 'active':
                    if ($param) {
                        $sql .= " AND (`start` IS NOT NULL AND `end` IS NULL)";
                    } else {
                        $sql .= " AND (`start` IS NULL OR `end` IS NOT NULL)";
                    }
                    break;
            }
        }
    }
    $sql .= "
        ORDER BY `tagstr` ASC";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    
    // Store data
    $plan_metas = array();
    $row = mysql_fetch_assoc($res);
    while (!empty($row)) {
        $plan_meta = array(
            'pmid' => $row['pmid'],
            'pid' => $row['pid'],
            'start' => $row['start'],
            'end' => $row['end'],
            'tagstr' => $row['tagstr'],
        );
        if ($join_plan) {
            if (array_key_exists($row['pid'], $pidToPlan)) {
                $plan_meta['member_plan'] = $pidToPlan[$row['pid']];
            }
        }
        $plan_metas[] = $plan_meta;
        $row = mysql_fetch_assoc($res);
    }
    
    // Return data
    return $plan_metas;
}

/**
 * Implementation of hook_data_alter().
 * @param $type The type of the data being altered.
 * @param $data An array of structures of the given $type.
 * @param $opts An associative array of options.
 * @return An array of modified structures.
 */
function plan_meta_data_alter ($type, $data = array(), $opts = array()) {
    switch ($type) {
        case 'plan':
            // Get pids of all plans passed into $data
            $pids = array();
            foreach ($data as $plan) {
                $pids[] = $plan['pid'];
            }
            // Add the pids to the options
            $plan_meta_opts = $opts;
            $plan_meta_opts['pid'] = $pids;
            // Get an array of plan meta data structures for each pid
            $plan_meta_data = crm_get_data('plan_meta', $plan_meta_opts);
            // Create a map from pid to an array of plan meta data structures
            $pid_to_plan_metas = array();
            foreach ($plan_meta_data as $plan_meta) {
                $pid_to_plan_metas[$plan_meta['pid']][] = $plan_meta;
            }
            // Add plan meta data structures to the plan structures
            foreach ($data as $i => $plan) {
                if (array_key_exists($plan['pid'], $pid_to_plan_metas)) {
                    $plan_metas = $pid_to_plan_metas[$plan['pid']];
                    $data[$i]['plan_metas'] = $plan_metas;
                }
            }
            break;
    }
    return $data;
}

/**
 * Save a user meta data structure.  If $plan_meta has a 'pmid' element, an existing user meta data will
 * be updated, otherwise a new user meta data will be created.
 * @param $pmid The user meta data structure
 * @return The user meta data structure with as it now exists in the database.
 */
function plan_meta_save ($plan_meta) {
    // Escape values
    $fields = array('pmid', 'pid', 'tagstr', 'start', 'end');
    if (isset($plan_meta['pmid'])) {
        // Update existing plan meta data
        $pmid = $plan_meta['pmid'];
        $esc_pmid = mysql_real_escape_string($pmid);
        $clauses = array();
        foreach ($fields as $k) {
            if ($k == 'end' && empty($plan_meta[$k])) {
                continue;
            }
            if (isset($plan_meta[$k]) && $k != 'pmid') {
                $clauses[] = "`$k`='" . mysql_real_escape_string($plan_meta[$k]) . "' ";
            }
        }
        $sql = "UPDATE `plan_meta` SET " . implode(', ', $clauses) . " ";
        $sql .= "WHERE `pmid`='$esc_pmid'";
        $res = mysql_query($sql);
        if (!$res) die(mysql_error());
        message_register('Plan Meta  Data updated');
    } else {
        // Insert new plan meta data
        $cols = array();
        $values = array();
        foreach ($fields as $k) {
            if (isset($plan_meta[$k])) {
                if ($k == 'end' && empty($plan_meta[$k])) {
                    continue;
                }
                $cols[] = "`$k`";
                $values[] = "'" . mysql_real_escape_string($plan_meta[$k]) . "'";
            }
        }
        $sql = "INSERT INTO `plan_meta` (" . implode(', ', $cols) . ") ";
        $sql .= " VALUES (" . implode(', ', $values) . ")";
        $res = mysql_query($sql);
        if (!$res) die(mysql_error());
        $pmid = mysql_insert_id();
        message_register('Plan Meta Data added');
    }
    return crm_get_one('plan_meta', array('pmid'=>$pmid));
}

/**
 * Delete a piece of plan meta data.
 * @param $plan_meta The plan meta data data structure to delete, must have a 'pmid' element.
 */
function plan_meta_delete ($plan_meta) {
    $esc_pmid = mysql_real_escape_string($plan_meta['pmid']);
    $sql = "DELETE FROM `plan_meta` WHERE `pmid`='$esc_pmid'";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    if (mysql_affected_rows() > 0) {
        message_register('Plan Meta Data deleted.');
    }
}

// Table data structures ///////////////////////////////////////////////////////

/**
 * Return a table structure for a table of meta tag assignments.
 * displays the tag data all in one row ( a column each )
 *
 * @param $opts The options to pass to plan_meta_data().
 * @return The table structure.
 */
function plan_meta_cross_table ($opts) {
    
    // Determine settings
    $export = false;
    foreach ($opts as $option => $value) {
        switch ($option) {
            case 'export':
                $export = $value;
                break;
        }
    }
    
    // Get plan data
    $data = plan_meta_data($opts);
    if (count($data) < 1) {
        return array();
    }
    
    // Initialize table
    $table = array(
        "id" => '',
        "class" => '',
        "rows" => array(),
        "columns" => array()
    );
    $tableid = 0 ;
    $uniq = array();
    
    // determine max/total number of tags, as we'll use one column for each:
    $sql = "SELECT distinct tagstr from plan_meta order by tagstr asc";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    $count = mysql_num_rows($res); // just one row.
        $tags = array();
        while ($row = mysql_fetch_array($res, MYSQL_NUM))
        {
            $tags[] = $row[0];
        }
    
    // Add column headers
    if (user_access('plan_meta_view')) {
        $table['columns'][] = array("title"=>'Name', 'class'=>'', 'id'=>''); // column 1
        for ( $i = 0 ; $i < $count; $i++) {
            $table['columns'][] = array("title"=>$tags[$i], 'class'=>'', 'id'=>''); // column 2 -> almost end
        }
    }
    // Add ops column
    if (!$export && (user_access('plan_meta_edit'))) {
        $table['columns'][] = array('title'=>'Ops','class'=>''); // last column.
    }
    
    // Add row data
    foreach ($data as $plan_meta) {
        
        // Add meta data
        $row = array();
        
        // plan not already on screen, add them, and all details, and first tag.
        if ( ! array_key_exists($plan_meta['pid'], $uniq) ) {
            
            $uniq[$plan_meta['pid']] = $tableid;
            
            if (user_access('plan_meta_view')) {
                
                // Add cells
                $row[] = theme('member_plan_name', $plan_meta['pid'], true);
                
                // insert new tag in new row at a fixed offset.
                for ( $i = 0 ; $i < $count+1; $i++) {
                    if ( $table['columns'][$i]['title'] == $plan_meta['tagstr'] ) {
                        $row[$i] = '<input type="checkbox" name="'.$plan_meta['tagstr'].'" value="1" checked="checked" disabled=true/>';
                    } else {
                        if ( ! array_key_exists($i, $row) ) { $row[$i] = ''; }
                    }
                }
            }
            if (!$export && (user_access('plan_meta_edit') || user_access('plan_meta_delete'))) {
                // Construct ops array
                $ops = array();
                // Add edit op
                if (user_access('plan_meta_edit')) {
                    $ops[] = '<a href=' . crm_url('plan&pid=' . $plan_meta['pid'] . '#tab-meta-tags') . '>edit</a>';
                }
                // Add ops row
                $row[] = join(' ', $ops);
            }
            $table['rows'][$tableid] = $row;
            $tableid++;
        }
            else {
                //print "burp<br>\n";
                // user alresdy, just add additional tag for them ...
                
                $previd = $uniq[$plan_meta['pid']];
                $row = $table['rows'][$previd];
                
                // insert new tag to existing row:
                for ( $i = 1 ; $i < $count+1; $i++) {
                    if ( $table['columns'][$i]['title'] == $plan_meta['tagstr'] ) {
                        $row[$i] = '<input type="checkbox" name="'.$plan_meta['tagstr'].'" value="1" checked="checked" disabled=true/>';
                    }
                }
                $table['rows'][$previd] = $row;
            }
    }
    
    //var_dump($uniq);
    
    return $table;
}

/**
 * Return a normal table structure for a table of meta assignments.
 *
 * @param $opts The options to pass to plan_meta_data().
 * @return The table structure.
 */
function plan_meta_table ($opts) {
    
    // Determine settings
    $export = false;
    foreach ($opts as $option => $value) {
        switch ($option) {
            case 'export':
                $export = $value;
                break;
        }
    }
    
    // Get plan data
    $data = crm_get_data('plan_meta', $opts);
    if (count($data) < 1) {
        return array();
    }
    
    // Initialize table
    $table = array(
        "id" => '',
        "class" => '',
        "rows" => array(),
        "columns" => array()
    );
    
    // Add columns
    if (user_access('plan_meta_view')) {
        if (array_key_exists('join', $opts) && in_array('plan', $opts['join'])) {
            $table['columns'][] = array("title"=>'Name', 'class'=>'', 'id'=>'');
        }
        $table['columns'][] = array("title"=>'MetaTag', 'class'=>'', 'id'=>'');
        $table['columns'][] = array("title"=>'Start', 'class'=>'', 'id'=>'');
        $table['columns'][] = array("title"=>'End', 'class'=>'', 'id'=>'');
    }
    // Add ops column
    if (!$export && (user_access('plan_meta_edit') || user_access('plan_meta_delete'))) {
        $table['columns'][] = array('title'=>'Ops','class'=>'');
    }
    
    // Add rows
    foreach ($data as $plan_meta) {
        
        // Add meta data
        $row = array();
        if (user_access('plan_meta_view')) {
            
            // Add cells
            if (array_key_exists('join', $opts) && in_array('plan', $opts['join'])) {
                $row[] = theme('member_plan_name', $pid_to_plan[$plan_meta['pid']], true);
            }
            
            $row[] = $plan_meta['tagstr'];
            $row[] = $plan_meta['start'];
            $row[] = $plan_meta['end'];
        }
        if (!$export && (user_access('plan_meta_edit') || user_access('plan_meta_delete'))) {
            // Construct ops array
            $ops = array();
            // Add edit op
            if (user_access('plan_meta_edit')) {
                $ops[] = '<a href=' . crm_url('plan_meta&pmid=' . $plan_meta['pmid'] . '#tab-edit') . '>edit</a> ';
            }
            // Add delete op
            if (user_access('plan_meta_delete')) {
                $ops[] = '<a href=' . crm_url('delete&type=plan_meta&id=' . $plan_meta['pmid']) . '>delete</a>';
            }
            // Add ops row
            $row[] = join(' ', $ops);
        }
        $table['rows'][] = $row;
    }
    return $table;
}

// Forms ///////////////////////////////////////////////////////////////////////

/**
 * Return the form structure for the add meta assignment form.
 *
 * @param The pid of the plan to add a meta assignment for.
 * @return The form structure.
 */
function plan_meta_add_form ($pid) {
    
    // Ensure user is allowed to edit metas
    if (!user_access('plan_meta_edit')) {
        return NULL;
    }
    
    // Create form structure
    $form = array(
        'type' => 'form',
        'method' => 'post',
        'command' => 'plan_meta_add',
        'hidden' => array(
            'pid' => $pid
        ),
        'fields' => array(
            array(
                'type' => 'fieldset',
                'label' => 'Add Meta-Tag Assignment',
                'fields' => array(
                    array(
                        'type' => 'text',
                        'label' => 'MetaTag',
                        'name' => 'tagstr',
                         'value' => '[please enter a meaningful metatag here]',
                    ),
                    array(
                        'type' => 'text',
                        'label' => 'Since',
                        'name' => 'start',
                        'value' => date("Y-m-d"),
                        'class' => 'date'
                    ),
                    array(
                        'type' => 'text',
                        'label' => 'Until',
                        'name' => 'end',
                        'class' => 'date'
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
 * Return the form structure for an edit meta assignment form.
 *
 * @param $pmid The id of the meta assignment to edit.
 * @return The form structure.
 */
function plan_meta_edit_form ($pmid) {
    
    // Ensure user is allowed to edit meta
    if (!user_access('plan_meta_edit')) {
        return NULL;
    }
    
    // Get meta data
    $data = crm_get_data('plan_meta', array('pmid'=>$pmid));
    $plan_meta = $data[0];
    if (empty($plan_meta) || count($plan_meta) < 1) {
        return array();
    }
    
    // Get corresponding plan data
    $data = member_plan_data(array('pid'=>$plan_meta['pid']));
    $plan = $data[0];
    
    // Construct plan name
    $name = theme('member_plan_name', $plan, true);
    
    // Create form structure
    $form = array(
        'type' => 'form',
        'method' => 'post',
        'command' => 'plan_meta_update',
        'hidden' => array(
            'pmid' => $pmid
        ),
        'fields' => array(
            array(
                'type' => 'fieldset',
                'label' => 'Edit meta Info',
                'fields' => array(
                    array(
                        'type' => 'readonly',
                        'label' => 'Name',
                        'value' => $name
                    ),
                    array(
                        'type' => 'text',
                        'class' => 'date',
                        'label' => 'Since',
                        'name' => 'start',
                        'value' => $plan_meta['start']
                    ),
                    array(
                        'type' => 'text',
                        'class' => 'date',
                        'label' => 'Until',
                        'name' => 'end',
                        'value' => $plan_meta['end']
                    ),
                    array(
                        'type' => 'text',
                        'label' => 'Tag',
                        'name' => 'tagstr',
                        'value' => $plan_meta['tagstr']
                    ),
                   array(
                        'type' => 'submit',
                        'value' => 'Update'
                    )
                )
            )
        )
    );
    return $form;
}

/**
 * Return the delete meta assigment form structure.
 *
 * @param $pmid The pmid of the meta assignment to delete.
 * @return The form structure.
 */
function plan_meta_delete_form ($pmid) {
    
    // Ensure user is allowed to delete metas
    if (!user_access('plan_meta_delete')) {
        return NULL;
    }
    
    // Get meta data
    $data = crm_get_data('plan_meta',array('pmid'=>$pmid));
    $plan_meta = $data[0];
    
    // Construct meta name
    $plan_meta_name = "meta:$plan_meta[pmid] tagstr:$plan_meta[tagstr] $plan_meta[start] -- $plan_meta[end]";
    
    // Create form structure
    $form = array(
        'type' => 'form',
        'method' => 'post',
        'command' => 'plan_meta_delete',
        'hidden' => array(
            'pmid' => $plan_meta['pmid']
        ),
        'fields' => array(
            array(
                'type' => 'fieldset',
                'label' => 'Delete Meta',
                'fields' => array(
                    array(
                        'type' => 'message',
                        'value' => '<p>Are you sure you want to delete the meta assignment "' . $plan_meta_name . '"? This cannot be undone.',
                    ),
                    array(
                        'type' => 'submit',
                        'value' => 'Delete'
                    )
                )
            )
        )
    );
    return $form;
}

// Request Handlers ////////////////////////////////////////////////////////////

/**
 * Command handler.
 * @param $command The name of the command to handle.
 * @param &$url A reference to the url to be loaded after completion.
 * @param &$params An associative array of query parameters for &$url.
 */
function plan_meta_command ($command, &$url, &$params) {
    switch ($command) {
        case 'plan_add':
            $params['tab'] = 'plan_metas';
            break;
    }
}

/**
 * Handle meta add request.
 *
 * @return The url to display on completion.
 */
function command_plan_meta_add() {
    global $esc_post;
    
    // Verify permissions
    if (!user_access('plan_meta_edit')) {
        error_register('Permission denied: plan_meta_edit');
        return crm_url('plan_meta&pmid=' . $esc_post['pmid']);
    }
    plan_meta_save($_POST);
    return crm_url('plan&pid=' . $_POST['pid'] . '&tab=metas');
}

/**
 * Handle meta update request.
 *
 * @return The url to display on completion.
 */
function command_plan_meta_update() {
    global $esc_post;
    
    // Verify permissions
    if (!user_access('plan_meta_edit')) {
        error_register('Permission denied: plan_meta_edit');
        return crm_url('plan_meta&pmid=' . $_POST['pmid']);
    }
    plan_meta_save($_POST);
    return crm_url('plan_meta&pmid=' . $esc_post['pmid'] . '&tab=edit');
}

/**
 * Handle meta delete request.
 *
 * @return The url to display on completion.
 */
function command_plan_meta_delete() {
    global $esc_post;
    // Verify permissions
    if (!user_access('plan_meta_delete')) {
        error_register('Permission denied: plan_meta_delete');
        return crm_url('plan_meta&pmid=' . $esc_post['pmid']);
    }
    plan_meta_delete($_POST);
    return crm_url('plans');
}

// Pages ///////////////////////////////////////////////////////////////////////

/**
 * @return An array of pages provided by this module.
 */
function plan_meta_page_list () {
    $pages = array();
    if (user_access('plan_meta_view')) {
        $pages[] = 'plan_metas';
    }
    return $pages;
}

/**
 * Page hook. Adds module content to a page before it is rendered.
 *
 * @param &$page_data Reference to data about the page being rendered.
 * @param $page_name The name of the page being rendered.
 * @param $options The array of options passed to theme('page').
 */
function plan_meta_page (&$page_data, $page_name, $options) {
    
    switch ($page_name) {
        
        case 'plan':
            
            // Capture plan id
            $pid = $options['pid'];
            if (empty($pid)) {
                return;
            }
            
            // Add metas tab
            if (user_access('plan_meta_view') || user_access('plan_meta_edit') || user_access('plan_meta_delete')) {
                $plan_metas = theme('table', crm_get_table('plan_meta', array('pid' => $pid)));
                $plan_metas .= theme('form', crm_get_form('plan_meta_add', $pid)); // this is where we put the "Add Meta-Tag Assignment" form on the page
                page_add_content_bottom($page_data, $plan_metas, 'Meta-Tags');
            }
            
            break;
        
        case 'plan_metas':
            page_set_title($page_data, 'Meta-Tags');
            if (user_access('plan_meta_view')) {
                // meta_cross_table ( displays tags across the screen, not down )
                $plan_metas = theme('table', crm_get_table('plan_meta_cross', array('join'=>array('contact', 'member'), 'show_export'=>true)));
                page_add_content_top($page_data, $plan_metas, 'View');
            }
            break;
        
        case 'plan_meta':
            
            // Capture meta id
            $pmid = $options['pmid'];
            if (empty($pmid)) {
                return;
            }
            
            // Set page title
            page_set_title($page_data, plan_meta_description($pmid));
            
            // Add edit tab
            if (user_access('plan_meta_view') || user_access('plan_meta_edit') || user_access('plan_meta_delete')) {
                page_add_content_top($page_data, theme('form', crm_get_form('plan_meta_edit_form', $pmid)), 'Edit');
            }
            
            break;
    }
}

// Themeing ////////////////////////////////////////////////////////////////////

/**
 * Return the themed html for an add meta assignment form.
 *
 * @param $pid The id of the plan to add a meta assignment for.
 * @return The themed html string.
 */
function theme_plan_meta_add_form ($pid) {
    return theme('form', crm_get_form('plan_meta_add', $pid));
}

/**
 * Return themed html for an edit meta assignment form.
 *
 * @param $pmid The id of the meta assignment to edit.
 * @return The themed html string.
 */
function theme_plan_meta_edit_form ($pmid) {
    return theme('form', crm_get_form('plan_meta_edit', $pmid));
}

?>

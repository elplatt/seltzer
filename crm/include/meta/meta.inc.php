<?php 

/*
    Copyright 2009-2013 Edward L. Platt <ed@elplatt.com>
    Copyright 2013 David "Buzz" Bussenschutt <davidbuzz@gmail.com>
    
    This file is part of the Seltzer CRM Project
    meta.inc.php - Meta-Tag tracking module
    
    This module is for associating arbitrary "meta data" with member/s.    
    This can be useful for making arbitrary groupings of users that have special meaning to you. 
    Examples:    
    We have one called "Respected", which entitles a member to get a physical key to the building....
    Or one called "Machinist", which means they have passed basic safety assessment to permit them to use our Mill/Lathe.
    It's kinda like an extension to the Permissions system, but for managing things external to SeltzerCRM.

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

/**
 * @return This module's revision number.  Each new release should increment
 * this number.
 */
function meta_revision () {
    return 2;
}

/**
 * @return An array of the permissions provided by this module.
 */
function meta_permissions () {
    return array(
        'meta_view'
        , 'meta_edit'
        , 'meta_delete'
    );
}

/**
 * Install or upgrade this module.
 * @param $old_revision The last installed revision of this module, or 0 if the
 *   module has never been installed.
 */
function meta_install($old_revision = 0) {
    if ($old_revision < 1) {
        $sql = '
            CREATE TABLE IF NOT EXISTS `meta` (
              `kid` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
              `cid` mediumint(8) unsigned NOT NULL,
              `start` date DEFAULT NULL,
              `end` date DEFAULT NULL,
              `tagstr` varchar(255) NOT NULL,
              PRIMARY KEY (`kid`)
            ) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
        ';
        $res = mysql_query($sql);
        if (!$res) die(mysql_error());
    }
    // Permissions moved to DB, set defaults on install/upgrade
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
            'director' => array('meta_view', 'meta_edit', 'meta_delete')
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
 * @param $kid The kid of the meta-tag to describe.
 * @return The description string.
 */
function meta_description ($kid) {
    
    // Get meta data
    $data = meta_data(array('kid' => $kid));
    if (empty($data)) {
        return '';
    }
    $meta = $data[0];
    
    // Construct description
    $description = 'Meta ';
    $description .= $meta['tagstr'];
    
    return $description;
}

// DB to Object mapping ////////////////////////////////////////////////////////

/**
 * Return data for one or more meta-tag assignments.
 *
 * @param $opts An associative array of options, possible metas are:
 *   'kid' If specified, returns a single memeber with the matching meta id;
 *   'cid' If specified, returns all metas assigned to the contact with specified id;
 *   'filter' An array mapping filter names to filter values;
 *   'join' A list of tables to join to the meta table.
 * @return An array with each element representing a single meta-tag assignment.
*/ 
function meta_data ($opts = array()) {
    
    // Determine joins
    $join_contact = false;
    $join_member = false;
    if (array_key_exists('join', $opts)) {
        foreach ($opts['join'] as $table) {
            if ($table === 'contact') {
                $join_contact = true;
            }
            if ($table === 'member') {
                $join_member = true;
            }
        }
    }
    
    // Create map from cids to contact names if necessary
    // TODO: Add filters for speed
    if ($join_contact) {
        $contacts = member_contact_data();
        $cidToContact = array();
        foreach ($contacts as $contact) {
            $cidToContact[$contact['cid']] = $contact;
        }
    }
    
    if ($join_member) {
        $members = member_data();
        $cidToMember = array();
        foreach ($members as $member) {
            $cidToMember[$member['cid']] = $member;
        }
    }
    
    // Query database
    $sql = "
        SELECT
        `kid`
        , `cid`
        , `start`
        , `end`
        , `tagstr`
        FROM `meta`
        WHERE 1";
    if (!empty($opts['kid'])) {
        $esc_kid = mysql_real_escape_string($opts['kid']);
        $sql .= " AND `kid`='$esc_kid'";
    }
    if (!empty($opts['cid'])) {
        $esc_cid = mysql_real_escape_string($opts['cid']);
        $sql .= " AND `cid`='$esc_cid'";
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
    $metas = array();
    $row = mysql_fetch_assoc($res);
    while (!empty($row)) {
        $meta = array(
            'kid' => $row['kid'],
            'cid' => $row['cid'],
            'start' => $row['start'],
            'end' => $row['end'],
            'tagstr' => $row['tagstr'],
        );
        if ($join_contact) {
            if (array_key_exists($row['cid'], $cidToContact)) {
                $meta['contact'] = $cidToContact[$row['cid']];
            }
        }
        if ($join_contact) {
            if (array_key_exists($row['cid'], $cidToMember)) {
                $meta['member'] = $cidToMember[$row['cid']];
            }
        }
        $metas[] = $meta;
        $row = mysql_fetch_assoc($res);
    }
    
    // Return data
    return $metas;
}

// Table data structures ///////////////////////////////////////////////////////

/**
 * Return a table structure for a table of meta tag assignments.  
 *   displays the tag data all in one row ( a column each ) 
 *
 * @param $opts The options to pass to meta_data().
 * @return The table structure.
*/
function meta_cross_table ($opts) {
    
    // Determine settings
    $export = false;
    foreach ($opts as $option => $value) {
        switch ($option) {
            case 'export':
                $export = $value;
                break;
        }
    }
    
    // Get contact data
    $data = meta_data($opts);
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
    $sql = "SELECT distinct tagstr from meta order by tagstr asc"; 
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    $count = mysql_num_rows($res); // just one row.   
        $tags = array();
        while ($row = mysql_fetch_array($res, MYSQL_NUM))
        {
            $tags[] = $row[0];
        }
    
    // Add column headers 
    if (user_access('meta_view') || $opts['cid'] == user_id()) {
        if (array_key_exists('join', $opts) && in_array('contact', $opts['join'])) {
            $table['columns'][] = array("title"=>'Last Name', 'class'=>'', 'id'=>''); // column 1 
            $table['columns'][] = array("title"=>'First Name', 'class'=>'', 'id'=>''); // column 2 
         //   $table['columns'][] = array("title"=>'Middle Name', 'class'=>'', 'id'=>'');
        }
        if (array_key_exists('join', $opts) && in_array('member', $opts['join'])) {
     //       $table['columns'][] = array("title"=>'Membership', 'class'=>'', 'id'=>'');
        }
        
         for ( $i = 0 ; $i < $count; $i++) { 
            $table['columns'][] = array("title"=>$tags[$i], 'class'=>'', 'id'=>''); // column 3 -> almost end
        }  
    //    $table['columns'][] = array("title"=>'Since', 'class'=>'', 'id'=>'');
    //    $table['columns'][] = array("title"=>'Until', 'class'=>'', 'id'=>'');
    }
    // Add ops column
    if (!$export && (user_access('meta_edit') || user_access('meta_delete'))) {
        $table['columns'][] = array('title'=>'Ops','class'=>''); // last column. 
    }
    
    // Add row data
    foreach ($data as $meta) {
        
        // Add meta data
        $row = array();
        
       // user not already on screen, add them, and all details, and first tag.
        if ( ! array_key_exists( $meta['contact']['lastName'].$meta['contact']['firstName'], $uniq) ) { 
            
            $uniq[$meta['contact']['lastName'].$meta['contact']['firstName']] = $tableid; 
        
            if (user_access('meta_view') || $opts['cid'] == user_id()) {
                
                // Add cells
                if (array_key_exists('join', $opts) && in_array('contact', $opts['join'])) {
                    $row[] = $meta['contact']['lastName'];
                    $row[] = $meta['contact']['firstName'];
           //         $row[] = $meta['contact']['middleName'];
                }
                if (array_key_exists('join', $opts) && in_array('member', $opts['join'])) {
                    // Construct membership info
                    $member = $meta['member'];
                    $plan = '';
                    if (!empty($member)) {
                        $recentMembership = end($member['membership']);
                        if (!empty($recentMembership) && empty($recentMembership['end'])) {
                            $plan = $recentMembership['plan']['name'];
                        }
                    }

                }
                
                // insert new tag in new row at a fixed offset.
                  for ( $i = 2 ; $i < $count+2; $i++) { 
                      if ( $table['columns'][$i]['title'] == $meta['tagstr'] ) { 
                          $row[$i] = '<input type="checkbox" name="'.$meta['tagstr'].'" value="1" checked="checked" disabled=true/>';
                      }  else { 
                          if ( ! array_key_exists($i, $row) ) { $row[$i] = ''; } 
                      }
                  }  
                          
 
            }
        
           if (!$export && (user_access('meta_edit') || user_access('meta_delete'))) {
               // Construct ops array
               $ops = array();
               
               // Add edit op
               if (user_access('meta_edit')) {
                   $ops[] = '<a href="index.php?q=member&cid=' . $meta['cid'] . '#tab-meta-tags">edit</a> ';
               }
               
               // Add delete op
               if (user_access('meta_delete')) {
                   $ops[] = '<a href="index.php?q=delete&type=meta&id=' . $meta['kid'] . '">delete</a>';
               }
               
               // Add ops row
               $row[] = join(' ', $ops);
           }
           
            $table['rows'][$tableid] = $row;
            $tableid++;

        
        } else { 
            
            //print "burp<br>\n"; 
            // user alresdy, just add additional tag for them ...
            
            $previd = $uniq[$meta['contact']['lastName'].$meta['contact']['firstName']];
            $row = $table['rows'][$previd]; 
            
            // insert new tag to existing row:
            for ( $i = 2 ; $i < $count+2; $i++) { 
                if ( $table['columns'][$i]['title'] == $meta['tagstr'] ) { 
                    $row[$i] = '<input type="checkbox" name="'.$meta['tagstr'].'" value="1" checked="checked" disabled=true/>';
                }  //else { 
                   // if ( ! array_key_exists($i, $row) ) { $row[$i] = ''; } 
                //}
            }  
            
            $table['rows'][$previd] = $row;
          
        } 

        
     }
    
    //var_dump($uniq);
    
    return $table;
}


// Table data structures ///////////////////////////////////////////////////////

/**
 * Return a normal table structure for a table of key assignments.
 *
 * @param $opts The options to pass to meta_data().
 * @return The table structure.
*/
function meta_table ($opts) {
    
    // Determine settings
    $export = false;
    foreach ($opts as $option => $value) {
        switch ($option) {
            case 'export':
                $export = $value;
                break;
        }
    }
    
    // Get contact data
    $data = meta_data($opts);
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
    if (user_access('meta_view') || $opts['cid'] == user_id()) {
        if (array_key_exists('join', $opts) && in_array('contact', $opts['join'])) {
            $table['columns'][] = array("title"=>'Last Name', 'class'=>'', 'id'=>'');
            $table['columns'][] = array("title"=>'First Name', 'class'=>'', 'id'=>'');
     //       $table['columns'][] = array("title"=>'Middle Name', 'class'=>'', 'id'=>'');
        }
        if (array_key_exists('join', $opts) && in_array('member', $opts['join'])) {
   //         $table['columns'][] = array("title"=>'Membership', 'class'=>'', 'id'=>'');
        }
        $table['columns'][] = array("title"=>'MetaTag', 'class'=>'', 'id'=>'');
   //     $table['columns'][] = array("title"=>'Slot', 'class'=>'', 'id'=>'');
        $table['columns'][] = array("title"=>'Start', 'class'=>'', 'id'=>'');
        $table['columns'][] = array("title"=>'End', 'class'=>'', 'id'=>'');
    }
    // Add ops column
    if (!$export && (user_access('meta_edit') || user_access('meta_delete'))) {
        $table['columns'][] = array('title'=>'Ops','class'=>'');
    }
    
    // Add rows
    foreach ($data as $meta) {
        
        // Add meta data
        $row = array();
        if (user_access('meta_view') || $opts['cid'] == user_id()) {
            
            // Add cells
            if (array_key_exists('join', $opts) && in_array('contact', $opts['join'])) {
                $row[] = $meta['contact']['lastName'];
                $row[] = $meta['contact']['firstName'];
        //        $row[] = $meta['contact']['middleName'];
            }
            if (array_key_exists('join', $opts) && in_array('member', $opts['join'])) {
                // Construct membership info
                $member = $meta['member'];
                $plan = '';
                if (!empty($member)) {
                    $recentMembership = end($member['membership']);
                    if (!empty($recentMembership) && empty($recentMembership['end'])) {
                        $plan = $recentMembership['plan']['name'];
                    }
                }
                $row[] = $plan;
            }
            $row[] = $meta['tagstr'];
    //        $row[] = $meta['slot'];
            $row[] = $meta['start'];
            $row[] = $meta['end'];
        }
        
        if (!$export && (user_access('meta_edit') || user_access('meta_delete'))) {
            // Construct ops array
            $ops = array();
            
            // Add edit op
            if (user_access('meta_edit')) {
                $ops[] = '<a href="index.php?q=meta&kid=' . $meta['kid'] . '#tab-edit">edit</a> ';
            }
            
            // Add delete op
            if (user_access('meta_delete')) {
                $ops[] = '<a href="index.php?q=delete&type=meta&id=' . $meta['kid'] . '">delete</a>';
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
 * @param The cid of the contact to add a meta assignment for.
 * @return The form structure.
*/
function meta_add_form ($cid) {
    
    // Ensure user is allowed to edit metas
    if (!user_access('meta_edit')) {
        return NULL;
    }
    
    // Create form structure
    $form = array(
        'type' => 'form',
        'method' => 'post',
        'command' => 'meta_add',
        'hidden' => array(
            'cid' => $cid
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
    
    //var_dump(debug_backtrace());
    
    return $form;
}

//http://localhost/seltzer/crm/index.php?q=member&cid=6#tab-meta-tags

/**
 * Return the form structure for an edit meta assignment form.
 *
 * @param $kid The kid of the meta assignment to edit.
 * @return The form structure.
*/
function meta_edit_form ($kid) {
    
    // Ensure user is allowed to edit meta
    if (!user_access('meta_edit')) {
        return NULL;
    }
    
    // Get meta data
    $data = meta_data(array('kid'=>$kid));
    $meta = $data[0];
    if (empty($meta) || count($meta) < 1) {
        return array();
    }
    
    // Get corresponding contact data
    $data = member_contact_data(array('cid'=>$meta['cid']));
    $contact = $data[0];
    
    // Construct member name
    $name = member_name($contact['firstName'], $contact['middleName'], $contact['lastName']);
    
    // Create form structure
    $form = array(
        'type' => 'form',
        'method' => 'post',
        'command' => 'meta_update',
        'hidden' => array(
            'kid' => $kid
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
                        'value' => $meta['start']
                    ),
                    array(
                        'type' => 'text',
                        'class' => 'date',
                        'label' => 'Until',
                        'name' => 'end',
                        'value' => $meta['end']
                    ),
                    array(
                        'type' => 'text',
                        'label' => 'Tag',
                        'name' => 'tagstr',
                        'value' => $meta['tagstr']
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
 * @param $kid The kid of the meta assignment to delete.
 * @return The form structure.
*/
function meta_delete_form ($kid) {
    
    // Ensure user is allowed to delete metas
    if (!user_access('meta_delete')) {
        return NULL;
    }
    
    // Get meta data
    $data = meta_data(array('kid'=>$kid));
    $meta = $data[0];
    
    // Construct meta name
    $meta_name = "meta:$meta[kid] tagstr:$meta[tagstr] $meta[start] -- $meta[end]";
    
    // Create form structure
    $form = array(
        'type' => 'form',
        'method' => 'post',
        'command' => 'meta_delete',
        'hidden' => array(
            'kid' => $meta['kid']
        ),
        'fields' => array(
            array(
                'type' => 'fieldset',
                'label' => 'Delete Meta',
                'fields' => array(
                    array(
                        'type' => 'message',
                        'value' => '<p>Are you sure you want to delete the meta assignment "' . $meta_name . '"? This cannot be undone.',
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
function meta_command ($command, &$url, &$params) {
    switch ($command) {
        case 'member_add':
            $params['tab'] = 'metas';
            break;
    }
}

/**
 * Handle meta add request.
 *
 * @return The url to display on completion.
 */
function command_meta_add() {
    global $esc_post;
    
    // Verify permissions
    if (!user_access('meta_edit')) {
        error_register('Permission denied: meta_edit');
        return 'index.php?q=meta&kid=' . $esc_post['kid'];
    }
    
    // Query database
    $sql = "
        INSERT INTO `meta`
        (`cid`, `tagstr`, `start`";
    if (!empty($esc_post['end'])) {
        $sql .= ", `end`";
    }
    $sql .= "
        )
        VALUES
        ('$esc_post[cid]', '$esc_post[tagstr]', '$esc_post[start]'";
    if (!empty($esc_post['end'])) {
        $sql .= ", '$esc_post[end]'";
    }
    $sql .= ")";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    
    return 'index.php?q=member&cid=' . $_POST['cid'] . '&tab=metas';
}

/**
 * Handle meta update request.
 *
 * @return The url to display on completion.
 */
function command_meta_update() {
    global $esc_post;
    
    // Verify permissions
    if (!user_access('meta_edit')) {
        error_register('Permission denied: meta_edit');
        return 'index.php?q=meta&kid=' . $_POST['kid'];
    }
    
    // Query database
    $sql = "
        UPDATE `meta`
        SET
        `start`='$esc_post[start]',";
    if (!empty($esc_post[end])) {
        $sql .= "`end`='$esc_post[end]',";
    } else {
        $sql .= "`end`=NULL,";
    }
    $sql .= "
        `tagstr`='$esc_post[tagstr]' 
        WHERE `kid`='$esc_post[kid]'";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    
    return 'index.php?q=meta&kid=' . $esc_post['kid'] . '&tab=edit';
}

/**
 * Handle meta delete request.
 *
 * @return The url to display on completion.
 */
function command_meta_delete() {
    global $esc_post;
    
    // Verify permissions
    if (!user_access('meta_delete')) {
        error_register('Permission denied: meta_delete');
        return 'index.php?q=meta&kid=' . $esc_post['kid'];
    }
    
    // Query database
    $sql = "
        DELETE FROM `meta`
        WHERE `kid`='$esc_post[kid]'";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    
    return 'index.php?q=members';
}

// Pages ///////////////////////////////////////////////////////////////////////

/**
 * @return An array of pages provided by this module.
 */
function meta_page_list () {
    $pages = array();
    if (user_access('meta_view')) {
        $pages[] = 'metas';
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
function meta_page (&$page_data, $page_name, $options) {
    
    switch ($page_name) {
        
        case 'member':
            
            // Capture member cid
            $cid = $options['cid'];
            if (empty($cid)) {
                return;
            }
            
            // Add metas tab
            if (user_access('meta_view') || user_access('meta_edit') || user_access('meta_delete') || $cid == user_id()) {
                $metas = theme('table', 'meta', array('cid' => $cid));
                $metas .= theme('meta_add_form', $cid); // this is where we put the "Add Meta-Tag Assignment" form on the page
                page_add_content_bottom($page_data, $metas, 'Meta-Tags');
            }
            
            break;
        
        case 'metas':
            page_set_title($page_data, 'Meta-Tags');
            if (user_access('meta_view')) {
                // meta_cross_table  ( displays tags across the screen, not down ) 
                $metas = theme('table', 'meta_cross', array('join'=>array('contact', 'member'), 'show_export'=>true));
                page_add_content_top($page_data, $metas, 'View');
            }
            break;
        
        case 'meta':
            
            // Capture meta id
            $kid = $options['kid'];
            if (empty($kid)) {
                return;
            }
            
            // Set page title
            page_set_title($page_data, meta_description($kid));
            
            // Add edit tab
            if (user_access('meta_view') || user_access('meta_edit') || user_access('meta_delete')) {
                page_add_content_top($page_data, theme('meta_edit_form', $kid), 'Edit');
            }
            
            break;
    }
}

// Themeing ////////////////////////////////////////////////////////////////////

/**
 * Return the themed html for an add meta assignment form.
 *
 * @param $cid The id of the contact to add a meta assignment for.
 * @return The themed html string.
 */
function theme_meta_add_form ($cid) {
    return theme('form', meta_add_form($cid));
}

/**
 * Return themed html for an edit meta assignment form.
 *
 * @param $kid The kid of the meta assignment to edit.
 * @return The themed html string.
 */
function theme_meta_edit_form ($kid) {
    return theme('form', meta_edit_form($kid));
}

?>
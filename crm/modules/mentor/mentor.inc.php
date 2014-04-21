<?php 

/*
    Copyright 2009-2014 Edward L. Platt <ed@elplatt.com>
    
    This file is part of the Seltzer CRM Project
    mentor.inc.php - Mentor module 

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
function mentor_revision () {
    return 1;
}

/**
 * @return An array of the permissions provided by this module.
 */
function mentor_permissions () {
    return array(
        'mentor_view'
        , 'mentor_edit'
        , 'mentor_delete'
    );
}

/**
 * Install or upgrade this module.
 * @param $old_revision The last installed revision of this module, or 0 if the
 *   module has never been installed.
 */
function mentor_install($old_revision = 0) {
    if ($old_revision < 1) {
        $sql = '
            CREATE TABLE IF NOT EXISTS `mentor` (
              `cid` mediumint(8) unsigned NOT NULL,
              `mentor_cid` mediumint(8) unsigned NOT NULL,
              PRIMARY KEY (`cid`,`mentor_cid`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
        ';
        $res = mysql_query($sql);
        if (!$res) die(mysql_error());
        // Set default permissions on install/upgrade
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
        //Set director (rid 3) as the default role that can administer mentors.
        $sql = "INSERT INTO `role_permission` (`rid`, `permission`) VALUES
        ('3', 'mentor_view'),
        ('3', 'mentor_edit'),
        ('3', 'mentor_delete')";
        $res = mysql_query($sql);
        if (!$res) die(mysql_error());
        
    }
}

// Pages ///////////////////////////////////////////////////////////////////////

/**
 * @return An array of pages provided by this module.
 */
function mentor_page_list () {
    $pages = array();
    if (user_access('mentor_view')) {
        $pages[] = 'mentor';
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
function mentor_page (&$page_data, $page_name, $options) {
    
    switch ($page_name) {
        
        case 'contact':
            
            // Capture member cid
            $cid = $options['cid'];
            if (empty($cid)) {
                return;
            }
            
            // Add mentors tab
            if (user_access('mentor_view') || user_access('mentor_edit') || user_access('mentor_delete') || $cid == user_id()) {
                $mentorships = theme('table', crm_get_table('mentor', array('cid' => $cid)));
                $mentorships .= theme('form', crm_get_form('mentor_add', $cid));
                page_add_content_bottom($page_data, $mentorships, 'Mentor');
            }
            
            break;
        
       
    }
}

// Themeing ////////////////////////////////////////////////////////////////////

/**
 * Return the themed html for an add mentor assignment form.
 *
 * @param $cid The id of the contact to add a mentor assignment for.
 * @return The themed html string.
 */
function theme_mentor_add_form ($cid) {
    return theme('form', crm_get_form('mentor_add', $cid));
}

/**
 * Return themed html for an edit mentor assignment form.
 *
 * @param $cid The cid of the mentor assignment to edit.
 * @return The themed html string.
 */
function theme_mentor_edit_form ($cid) {
    return theme('form', crm_get_form('mentor_edit', $cid));
}

// DB to Object mapping ////////////////////////////////////////////////////////

/**
 * Return data for one or more mentor assignments.
 *
 * @param $opts An associative array of options, possible keys are:
 *   'cid' If specified, returns mentor contacts assigned to this cid,
 *   and the proteges assigned to this cid;
 * @return An array with each element representing a mentor assignment.
*/ 
function mentor_data ($opts = array()) {
    
   
    // Query database
    $sql = "
        SELECT
        `cid`
        , `mentor_cid`
        FROM `mentor`
        WHERE 1";
    if (!empty($opts['cid'])) {
        if (is_array($opts['cid'])) {
            $terms = array();
            foreach ($opts['cid'] as $cid) {
                $esc_cid = mysql_real_escape_string($cid);
                $terms[] = "'$cid'";
            }
            $sql .= " AND `cid` IN (" . implode(', ', $terms) . ") ";
            $sql .= " OR `mentor_cid` IN (" . implode(', ', $terms) . ") ";
        } else {
            $esc_cid = mysql_real_escape_string($opts['cid']);
            $sql .= " AND `cid`='$esc_cid'";
            $sql .= " OR `mentor_cid`='$esc_cid'";
        }
    }
    if (!empty($opts['mentor_cid'])) {
        $esc_cid = mysql_real_escape_string($opts['mentor_cid']);
        $sql .= " AND `mentor_cid`='$esc_cid'";
    }

    //TODO: specify an order? (ORDER BY... ASC)
    
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());    
    
    // Store data in mentorships array
    $mentorships = array();
    $row = mysql_fetch_assoc($res);
    
    
    while (!empty($row)) {
        $mentorship = array(
            'cid' => $row['cid'],
            'mentor_cid' => $row['mentor_cid']
        );
        $mentorships[] = $mentorship;
        $row = mysql_fetch_assoc($res);
    }
    // At this point, the mentorships might not be in unique rows.
    // in other words, there might be multiple entries with the same cid
    // we should match up multiple mentors/proteges that are related to
    // the same cid
    $mentor_data = array();
    foreach ($mentorships as $mentorship){
        if (empty($mentor_data[$mentorship['cid']])){
            //this is a new cid. Create an array.
            $mentor_data[$mentorship['cid']] = array('mentor_cids' => array(),
                                                     'protege_cids' => array());
        }
        //populate array with mentor_cid (it should be created by now if it previously
        // didn't exist.)
        $mentor_data[$mentorship['cid']]['mentor_cids'][] = $mentorship['mentor_cid'];
        
        //now do the opposite. that is to say, assign the protege to the mentor_cid
        //of course, this involves creating the mentor_cid if it doesn't exist yet
        if (empty($mentor_data[$mentorship['mentor_cid']])){
            //this is a new cid. Create an array.
            $mentor_data[$mentorship['mentor_cid']] = array('mentor_cids' => array(),
                                                     'protege_cids' => array());
        }
        //populate the mentor's array with protege cid.
        $mentor_data[$mentorship['mentor_cid']]['protege_cids'][] = $mentorship['cid'];
    }  
    // Return data
    
    return $mentor_data;
}

// Table data structures ///////////////////////////////////////////////////////

/**
 * Return a table structure for a table of mentor assignments.
 *
 * @param $opts The options to pass to mentor_data().
 * @return The table structure.
*/
function mentor_table ($opts) {
    
    // Determine settings
    $export = false;
    foreach ($opts as $option => $value) {
        switch ($option) {
            case 'export':
                $export = $value;
                break;
        }
    }
    
    
    // Get the current contact's data
    $contacts = crm_get_data('contact', $opts);
    // Dont display anything if no mentorships exist.
    if (empty($contacts[0]['member']['mentorships'])) {
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
    if (user_access('mentor_view') || $opts['cid'] == user_id()) {
        $table['columns'][] = array("title"=>'Mentor Name', 'class'=>'', 'id'=>'');
        $table['columns'][] = array("title"=>'Protege Name', 'class'=>'', 'id'=>'');

    }
    // Add ops column
    if (!$export && (user_access('mentor_edit') || user_access('mentor_delete'))) {
        $table['columns'][] = array('title'=>'Ops','class'=>'');
    }
    
    // Add rows
    foreach ($contacts as $contact) {
        //get the mentor info
        $mentors_cids = $contact['member']['mentorships']['mentor_cids'];
        $get_mentor_opts = array(
            'cid' => $mentors_cids
        );
        //Print out the mentors only if there actually are some mentor cids.
        if(!empty($mentors_cids)){
            $mentors = crm_get_data('contact',$get_mentor_opts);
            
            foreach ($mentors as $mentor){
                // Add mentor data
                $row = array();
                if (user_access('mentor_view') || $opts['cid'] == user_id()) {
                    // Add the mentor's name
                    $row[] = theme('contact_name', $mentor, true);
                    // Add the contact's name
                    $row[] = theme('contact_name', $contact, true);
                }
                
                if (!$export && (user_access('mentor_edit') || user_access('mentor_delete'))) {
                    // Construct ops array
                    $ops = array();
                    
                    // Add edit op
                    //if (user_access('mentor_edit')) {
                    //    $ops[] = '<a href=' . crm_url('contact&cid=' . $mentor['cid'] . '#tab-mentor') . '>edit</a> ';
                    //}
                    
                    // Add delete op
                    if (user_access('mentor_delete')) {
                        $ops[] = '<a href=' . crm_url('delete&type=mentor&id=' . $contact['cid'] . '&mentorcid=' . $mentor['cid']) . '>delete</a>';
                    }
                    // Add ops row
                    $row[] = join(' ', $ops);
                } 
                $table['rows'][] = $row;
            }
        }
        //get the protege info
        $protege_cids = $contact['member']['mentorships']['protege_cids'];
        $get_protege_opts = array(
            'cid' => $protege_cids           
        );
        //Print out the proteges only if there actually are some protege cids.
        if(!empty($protege_cids)){
            $proteges = crm_get_data('contact',$get_protege_opts);
            
            foreach($proteges as $protege){
                //Add Protege Data
                $row = array();
                if (user_access('mentor_view') || $opts['cid'] == user_id()) {
                    // Add the mentor's name (actually the contact)
                    $row[] = theme('contact_name', $contact, true);
                    // Add the protege's name
                    $row[] = theme('contact_name', $protege, true);
                }
                if (!$export && (user_access('mentor_edit') || user_access('mentor_delete'))) {
                    // Construct ops array
                    $ops = array();
                    
                    // Add edit op
                    //if (user_access('mentor_edit')) {
                    //    $ops[] = '<a href=' . crm_url('contact&cid=' . $contact['cid'] . '#tab-mentor') . '>edit</a>';
                    //}
                    
                    // Add delete op
                    if (user_access('mentor_delete')) {
                        $ops[] = '<a href=' . crm_url('delete&type=mentor&id=' . $protege['cid'] . '&mentorcid=' . $contact['cid']) . '>delete</a>';
                    }
                    // Add ops row
                    $row[] = join(' ', $ops);
                }
                $table['rows'][] = $row;
            }
        }
    }
    
    return $table;
}


// Forms ///////////////////////////////////////////////////////////////////////

/**
 * Return the form structure for the add mentor assignment form.
 *
 * @param The cid of the contact to add a mentor assignment form.
 * @return The form structure.
*/
function mentor_add_form ($cid) {
    
    // Ensure user is allowed to edit mentors
    if (!user_access('mentor_edit')) {
        return NULL;
    }
    
    // Create form structure
    $form = array(
        'type' => 'form',
        'method' => 'post',
        'command' => 'mentor_add',
        'hidden' => array(
            'cid' => $cid
        ),
        'fields' => array(
            array(
                'type' => 'fieldset',
                'label' => 'Add Mentor Assignment',
                'fields' => array(
                    array(
                        'type' => 'text',
                        'label' => 'Mentor Name',
                        'name' => 'mentor_cid',
                        'autocomplete' => 'contact_name',
                        'class' => 'focus'
                        
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
 * Return the form structure for an edit mentor assignment form.
 *
 * @param $cid The cid of the protege to edit.
 * @return The form structure.
*/
function mentor_edit_form ($cid) {
    
    // Ensure user is allowed to edit mentor
    if (!user_access('mentor_edit')) {
        return NULL;
    }
        
    // Get corresponding contact data
    $data = crm_get_data ('contact', $opts = array('cid' => $cid));
    $contact = $data[0];
    
    // Construct member name
    $name = member_name($contact['firstName'], $contact['middleName'], $contact['lastName']);
    
    // Get list of current mentor cids.
    $mentor_cids = $contact['member']['mentorships']['mentor_cids'];
    
    // Construct mentor name (from member/protege)
    $mentor_name = crm_get_data('contact', $opts = array('cid' => $mentor_cids[0]));
    
    // Get list of current protege cids.
    $protege_cids = $contact['member']['mentorships']['protege_cids'];
    
    // Construct protege name (from member/protege) TODO: Change this to an array of protege's
    $protege_name = crm_get_data('contact', $opts = array('cid' => $protege_cids[0]));
    
    // Create form structure
    $form = array(
        'type' => 'form',
        'method' => 'post',
        'command' => 'mentor_update',
        'hidden' => array(
            'cid' => $cid
        ),
        'fields' => array(
            array(
                'type' => 'fieldset',
                'label' => 'Edit Mentor Info',
                'fields' => array(
                    array(
                        'type' => 'readonly',
                        'label' => 'Name',
                        'value' => $name
                    ),
                    array(
                        'type' => 'text',
                        'class' => 'date',
                        'label' => 'Mentor',
                        'name' => 'mentor',
                        'value' => $mentor_name
                    ),
                    array(
                        'type' => 'text',
                        'class' => 'date',
                        'label' => 'Protege',
                        'name' => 'protege',
                        'value' => $protege_name
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
 * Return the delete mentor assigment form structure.
 *
 * @param $cid The cid of the protege to delete the mentor from.
 * @return The form structure.
*/
function mentor_delete_form ($cid) {
    
    // Ensure user is allowed to delete mentors
    if (!user_access('mentor_delete')) {
        return NULL;
    }
    
    // Get corresponding contact data
    $data = crm_get_data ('contact', $opts = array('cid' => $cid));
    $contact = $data[0];
    
    // Construct member name
    $name = member_name($contact['firstName'], $contact['middleName'], $contact['lastName']);
    
    // Get list of current mentor cids.
    $mentor_cid = $contact['member']['mentorships']['mentor_cids'][0];
    // Construct mentor name (from member/protege)
    $mentor_contact = crm_get_one('contact', $opts = array('cid' => $mentor_cid));
    $mentor_name = theme('contact_name', $mentor_contact);

    // Create form structure
    $form = array(
        'type' => 'form',
        'method' => 'post',
        'command' => 'mentor_delete',
        'hidden' => array(
            'cid' => $cid,
            'mentor_cid' => $mentor_cid
        ),
        'fields' => array(
            array(
                'type' => 'fieldset',
                'label' => 'Delete Mentor',
                'fields' => array(
                    array(
                        'type' => 'message',
                        'value' => '<p>Are you sure you want to delete the member assignment "' . $mentor_name . '"? This cannot be undone.'
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
function mentor_command ($command, &$url, &$params) {
    switch ($command) {
        case 'member_add':
            $params['tab'] = 'mentor';
            break;
    }
}

/**
 * Handle mentor add request.
 *
 * @return The url to display on completion.
 */
function command_mentor_add() {
    global $esc_post;
    
    // Verify permissions
    if (!user_access('mentor_edit')) {
        error_register('Permission denied: mentor_edit');
        return crm_url('');
    }
    
    // Query database
    $sql = "
        INSERT INTO `mentor`
        (`cid`, `mentor_cid`)
        VALUES
        ('$esc_post[cid]', '$esc_post[mentor_cid]')";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    
    return crm_url('contact&cid=' . $_POST['cid'] . '#tab-mentor');
}

/**
 * Handle mentor update request.
 *
 * @return The url to display on completion. TODO: This function is not completely
 * implemented yet, it still has some copied parts from the key module that have
 * not been translated
 */
function command_mentor_update() {
    global $esc_post;
    
    // Verify permissions
    if (!user_access('mentor_edit')) {
        error_register('Permission denied: mentor_edit');
        return crm_url('contact&cid=' . $_POST['cid']);
    }
    
    // Query database
    $sql = "
        UPDATE `mentor`
        SET
        `start`='$esc_post[start]',";
    if (!empty($esc_post[end])) {
        $sql .= "`end`='$esc_post[end]',";
    } else {
        $sql .= "`end`=NULL,";
    }
    $sql .= "
        `serial`='$esc_post[serial]',
        `slot`='$esc_post[slot]'
        WHERE `kid`='$esc_post[kid]'";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    
    return crm_url('contact&cid=' . $esc_post['cid'] . '#tab-mentor');
}

/**
 * Handle mentor delete request.
 *
 * @return The url to display on completion.
 */
function command_mentor_delete() {
    global $esc_post;
    
    // Verify permissions
    if (!user_access('mentor_delete')) {
        error_register('Permission denied: mentor_delete');
        return crm_url('');
    }
    
    // Query database
    $sql = "
        DELETE FROM `mentor`
        WHERE `cid`='$esc_post[cid]' AND `mentor_cid`='$esc_post[mentor_cid]'";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    
    return crm_url('members');
}

// Data Alter Function /////////////////////////////////////////////////////
function mentor_data_alter ($type, $data = array(), $opts = array()){
    switch($type){
        case 'member':
            
            //Get cids of all members passed into $data
            $cids = array();
            foreach ($data as $member){
                $cids[] = $member['cid'];
            }
            // Add the cids to the options
            $mentor_opts = $opts;
            $mentor_opts['cid'] = $cids;
            // Get an array of member structures for each cid
            $mentor_data = crm_get_data('mentor', $mentor_opts);
            // Add mentorship data to member array
            foreach ($data as $i=> $member) {
                $data[$i]['mentorships'] = $mentor_data[$member['cid']];
            }
            break;
    }
    
    return $data;
}

?>
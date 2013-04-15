<?php 

/*
    Copyright 2009-2013 Edward L. Platt <ed@elplatt.com>
    
    This file is part of the Seltzer CRM Project
    data.inc.php - Member module - database to object mapping

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
 * Return data for one or more members.
 *
 * @param $opts An associative array of options, possible keys are:
 *   'cid' If specified, return a member (or members if array) with the given id,
 *   'filter' An array mapping filter names to filter values
 * @return An array with each element representing a member.
*/ 
function member_data ($opts = array()) {
    
    // Query database
    $sql = "
        SELECT
        `member`.`cid`, `firstName`, `middleName`, `lastName`, `email`, `phone`, `emergencyName`, `emergencyPhone`,
        `username`, `hash`
        FROM `member`
        LEFT JOIN `contact` ON `member`.`cid`=`contact`.`cid`
        LEFT JOIN `user` ON `member`.`cid`=`user`.`cid`
        LEFT JOIN `membership` ON (`member`.`cid`=`membership`.`cid` AND `membership`.`end` IS NULL)
        LEFT JOIN `plan` ON `plan`.`pid`=`membership`.`pid`
        WHERE 1
    ";
    if (isset($opts['cid']) and !empty($opts['cid'])) {
        if (is_array($opts['cid'])) {
            $terms = array();
            foreach ($opts['cid'] as $cid) {
                $term = "'" . mysql_real_escape_string($cid) . "'";
                $terms[] = $term;
            }
            $esc_list = "(" . implode(',', $terms) .")";
            $sql .= " AND `member`.`cid` IN $esc_list ";
        } else {
            $esc_cid = mysql_real_escape_string($opts['cid']);
            $sql .= " AND `member`.`cid`='$esc_cid'";
        }
    }
    if (isset($opts['filter'])) {
        $filter = $opts['filter'];
        if (isset($filter['active'])) {
            if ($filter['active']) {
                $sql .= " AND (`membership`.`start` IS NOT NULL AND `membership`.`end` IS NULL)";
            } else {
                $sql .= " AND (`membership`.`start` IS NULL OR `membership`.`end` IS NOT NULL)";
            }
        }
        if (isset($filter['voting'])) {
            $sql .= " AND (`membership`.`start` IS NOT NULL AND `membership`.`end` IS NULL AND `plan`.`voting` <> 0)";
        }
    }
    $sql .= " GROUP BY `member`.`cid` ";
    $sql .= " ORDER BY `lastName`, `firstName`, `middleName` ASC ";

    $res = mysql_query($sql);
    if (!$res) crm_error(mysql_error());
    
    // Store data
    $members = array();
    $row = mysql_fetch_assoc($res);
    while (!empty($row)) {
        $member = array(
            'cid' => $row['cid'],
            'active' => $row['memberActive'],
            'contact' => array(
                'cid' => $row['cid'],
                'firstName' => $row['firstName'],
                'middleName' => $row['middleName'],
                'lastName' => $row['lastName'],
                'email' => $row['email'],
                'phone' => $row['phone'],
                'emergencyName' => $row['emergencyName'],
                'emergencyPhone' => $row['emergencyPhone']
            ),
            'user' => array(
                'cid' => $row['cid'],
                'username' => $row['username'],
                'hash' => $row['hash']
            ),
            'membership' => array()
        );
        
        $members[] = $member;
        $row = mysql_fetch_assoc($res);
    }
    
    // Get list of memberships associated with each member
    // This is slow, should be combined into a single query
    foreach ($members as $index => $member) {
        
        // Query all memberships for current member
        $esc_cid = mysql_real_escape_string($member['cid']);
        $sql = "
            SELECT
            `membership`.`sid`, `membership`.`cid`, `membership`.`start`, `membership`.`end`,
            `plan`.`pid`, `plan`.`name`, `plan`.`price`, `plan`.`active`, `plan`.`voting`
            FROM `membership`
            INNER JOIN `plan` ON `plan`.`pid` = `membership`.`pid`
            WHERE `membership`.`cid`='$esc_cid'
            ORDER BY `membership`.`start` ASC
        ";
        $res = mysql_query($sql);
        if (!$res) crm_error(mysql_error());
        
        // Add each membership
        $row = mysql_fetch_assoc($res);
        while (!empty($row)) {
            $membership = array(
                'sid' => $row['sid'],
                'cid' => $row['cid'],
                'pid' => $row['pid'],
                'start' => $row['start'],
                'end' => $row['end'],
                'plan' => array(
                    'pid' => $row['pid'],
                    'name' => $row['name'],
                    'price' => $row['price'],
                    'active' => $row['active'],
                    'voting' => $row['voting']
                )
            );
            $members[$index]['membership'][] = $membership;
            $row = mysql_fetch_assoc($res);
        }
    }
    
    // Return data
    return $members;
}

/**
 * Implementation of hook_data_alter().
 * @param $type The type of the data being altered.
 * @param $data An array of structures of the given $type.
 * @param $opts An associative array of options.
 * @return An array of modified structures.
 */
function member_data_alter ($type, $data = array(), $opts = array()) {
    switch ($type) {
        case 'contact':
            // Get cids of all contacts passed into $data
            $cids = array();
            foreach ($data as $contact) {
                $cids[] = $contact['cid'];
            }
            // Add the cids to the options
            $member_opts = $opts;
            $member_opts['cid'] = $cids;
            // Get an array of member structures for each cid
            $member_data = crm_get_data('member', $member_opts);
            // Create a map from cid to member structure
            $cid_to_member = array();
            foreach ($member_data as $member) {
                $cid_to_member[$member['cid']] = $member;
            }
            // Add member structures to the contact structures
            foreach ($data as $i => $contact) {
                $member = $cid_to_member[$contact['cid']];
                if ($member) {
                    $data[$i]['member'] = $member;
                }
            }
            break;
    }
    return $data;
}

/**
 * Update member data when a contact is updated.
 * @param $contact The contact data array.
 * @param $op The operation being performed.
 */
function member_contact_api ($contact, $op) {
    // Check whether the contact is a member
    if (!isset($contact['member'])) {
        return $contact;
    }
    $member = $contact['member'];
    $esc_cid = mysql_real_escape_string($contact['cid']);
    switch ($op) {
        case 'create':
            // Add member
            $sql = "
                INSERT INTO `member`
                (`cid`)
                VALUES
                ('$esc_cid')";
            $res = mysql_query($sql);
            if (!$res) crm_error(mysql_error());
            $contact['member']['cid'] = $contact['cid'];
            // Save memberships
            if (isset($member['membership'])) {
                foreach ($member['membership'] as $i => $membership) {
                    $membership = member_membership_save($membership);
                    $member['membership'][$i] = $membership;
                }
            }
            break;
        case 'update':
            // TODO
            break;
    }
    $contact['member'] = $member;
    // Save the user object
    // TODO Move this to the user module once it is created
    $contact['user']['cid'] = $contact['cid'];
    user_save($contact['user']);
    return $contact;
}

/**
 * Delete membership data for a contact.
 * @param $cid - The contact id.
 */
function member_delete ($cid) {
    // Store name
    $contact_data = crm_get_data('contact', array('cid'=>$cid));
    $contact = $contact_data[0];
    $name = theme('contact_name', $contact);
    // Delete member
    $esc_cid = mysql_real_escape_string($cid);
    $sql = "DELETE FROM `member` WHERE `cid`='$esc_cid'";
    $res = mysql_query($sql);
    if (!$res) crm_error(mysql_error());
    $sql = "DELETE FROM `membership` WHERE `cid`='$esc_cid'";
    $res = mysql_query($sql);
    if (!$res) crm_error(mysql_error());
    message_register("Deleted Membership info for: $name");
}

/**
 * Return data for one or more membership plans.
 * 
 * @param $opts An associative array of options, possible keys are:
 *   'pid' If specified, returns a single plan with the matching id,
 *   'filter' An array mapping filter names to filter values
 * @return An array with each element representing a membership plan.
*/
function member_plan_data ($opts) {
    
    // Construct query for plans
    $sql = "SELECT * FROM `plan` WHERE 1";
    if (isset($opts['filter'])) {
        foreach ($opts['filter'] as $name => $param) {
            switch ($name) {
                case 'active':
                    if ($param) {
                        $sql .= " AND `plan`.`active` <> 0";
                    } else {
                        $sql .= " AND `plan`.`active` = 0";
                    }
                    break;
            }
        }
    }
    if (!empty($opts['pid'])) {
        $pid = mysql_real_escape_string($opts[pid]);
        $sql .= " AND `plan`.`pid`='$pid' ";
    }

    // Query database for plans
    $res = mysql_query($sql);
    if (!$res) { crm_error(mysql_error()); }
    
    // Store plans
    $plans = array();
    $row = mysql_fetch_assoc($res);
    while ($row) {
        $plans[] = $row;
        $row = mysql_fetch_assoc($res);
    }
    
    return $plans;
}

/**
 * Return data for one or more memberships.
 *
 * @param $opts An associative array of options, possible keys are:
 *   'cid' If specified, returns memberships for the member with the cid,
 *   'filter' An array mapping filter names to filter values
 * @return An array with each element representing a membership.
*/ 
function member_membership_data ($opts) {
    
    // Query database
    $sql = "
        SELECT *
        FROM `membership`
        INNER JOIN `plan`
        ON `membership`.`pid` = `plan`.`pid`
        WHERE 1";
        
    // Add member id
    if (!empty($opts['cid'])) {
        $esc_cid = mysql_real_escape_string($opts['cid']);
        $sql .= " AND `cid`='$esc_cid'";
    }
    
    // Add membership id
    if (!empty($opts['sid'])) {
        $esc_sid = mysql_real_escape_string($opts['sid']);
        $sql .= " AND `sid`='$esc_sid'";
    }
    
    // Add filters
    $esc_today = mysql_real_escape_string(date('Y-m-d'));
    if (isset($opts['filter'])) {
        foreach ($opts['filter'] as $name => $param) {
            $esc_param = mysql_real_escape_string($param);
            switch ($name) {
                case 'active':
                    if ($param) {
                        $sql .= " AND (`end` IS NULL OR `end` > '$esc_today') ";
                    } else {
                        $sql .= " AND (`end` IS NOT NULL) ";
                    }
                    break;
                case 'starts_after':
                    $sql .= "AND (`start` > '$esc_param') ";
                    break;
                default:
                    break;
            }
        }
    }
    
    $sql .= "
        ORDER BY `start` DESC";
    $res = mysql_query($sql);
    if (!$res) crm_error(mysql_error());
    
    // Store data
    $memberships = array();
    $row = mysql_fetch_assoc($res);
    while (!empty($row)) {
        $memberships[] = array(
            'cid' => $row['cid'],
            'sid' => $row['sid'],
            'pid' => $row['pid'],
            'start' => $row['start'],
            'end' => $row['end'],
            'plan' => array(
                'pid' => $row['pid'],
                'name' => $row['name'],
                'price' => $row['price'],
                'active' => $row['active'],
                'voting' => $row['voting']
            )
        );
        $row = mysql_fetch_assoc($res);
    }
    
    // Return data
    return $memberships;
}

/**
 * Save a membership.  A membership represents a specific member's plan at
 * for a specific time period.
 * @param $membership
 * @return $membership
 */
function member_membership_save ($membership) {
    if (isset($membership['sid'])) {
        // Update
        // TODO
    } else {
        // Insert
        $sql = "
            INSERT INTO `membership`
            (`cid`, `pid`, `start`)
            VALUES
            ('$esc_cid', '$esc_post[pid]', '$esc_post[start]')
        ";
        $res = mysql_query($sql);
        if (!$res) crm_error(mysql_error());
        $membership['sid'] = mysql_insert_id();
    }
    return $membership;
}

/**
 * Generates an associative array mapping membership plan pids to
 * strings describing those membership plan.
 * 
 * @param $opts Options to be passed to member_plan_data().
 * @return The associative array of membership plan descriptions.
 */
function member_plan_options ($opts = NULL) {
    
    // Get plan data
    $plans = member_plan_data($opts);
    
    // Add option for each member plan
    $options = array();
    foreach ($plans as $plan) {
        $options[$plan['pid']] = "$plan[name] - $plan[price]";
    }
    
    return $options;
}

/**
 * Return data for one or more contacts.  Use contact_data() instead.
 * 
 * @param $opts An associative array of options, possible keys are:
 *   'cid' If specified returns the corresponding member (or members for an array);
 *   'filter' An array mapping filter names to filter values
 * @return An array with each element representing a contact.
 * @deprecated
*/ 
function member_contact_data ($opts = array()) {
    return contact_data($opts);
}

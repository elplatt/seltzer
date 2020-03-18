<?php

/*
    Copyright 2009-2020 Edward L. Platt <ed@elplatt.com>
    
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
 * @param $opts An associative array of options, possible keys are:
 *   'cid' If specified, return a member (or members if array) with the given id,
 *   'filter' An array mapping filter names to filter values
 * @return An array with each element representing a member.
 */
function member_data ($opts = array()) {
    global $db_connect;
    // Query database
    $sql = "
        SELECT
        `member`.`cid`, `firstName`, `middleName`, `lastName`, `email`, `phone`,
        `emergencyName`, `emergencyPhone`, `emergencyRelation`,
        `address1`, `address2`, `address3`, `town_city`, `zipcode`,
        `username`, `hash`
        FROM `member`
        LEFT JOIN `contact` ON `member`.`cid`=`contact`.`cid`
        LEFT JOIN `user` ON `member`.`cid`=`user`.`cid`
        LEFT JOIN `membership` ON (`member`.`cid`=`membership`.`cid` AND (`membership`.`end` IS NULL OR `membership`.`end` > NOW()))
        LEFT JOIN `plan` ON `plan`.`pid`=`membership`.`pid`
        WHERE 1
    ";
    if (isset($opts['cid']) and !empty($opts['cid'])) {
        if (is_array($opts['cid'])) {
            $terms = array();
            foreach ($opts['cid'] as $cid) {
                $term = "'" . mysqli_real_escape_string($db_connect, $cid) . "'";
                $terms[] = $term;
            }
            $esc_list = "(" . implode(',', $terms) .")";
            $sql .= "
                AND `member`.`cid` IN $esc_list
            ";
        } else {
            $esc_cid = mysqli_real_escape_string($db_connect, $opts['cid']);
            $sql .= "
                AND `member`.`cid`='$esc_cid'
            ";
        }
    }
    if (isset($opts['filter'])) {
        $filter = $opts['filter'];
        if (isset($filter['active'])) {
            if ($filter['active']) {
                //get active members:
                $sql .= " AND
                    (`membership`.`start` IS NOT NULL AND `membership`.`start` < NOW() AND (`membership`.`end` IS NULL OR `membership`.`end` > NOW()))
                ";
            } else {
                //get NOT active members:
                $sql .= "
                    AND (`membership`.`start` IS NULL OR `membership`.`start` > NOW() OR (`membership`.`end` IS NOT NULL AND `membership`.`end` < NOW()))
                ";
            }
        }
        if (isset($filter['voting'])) {
            $sql .= "
                AND (`membership`.`start` IS NOT NULL AND `membership`.`start` < NOW() AND (`membership`.`end` IS NULL OR `membership`.`end` > NOW()) AND `plan`.`voting` <> 0)
            ";
        }
    }
    $sql .= "
        GROUP BY `member`.`cid`
    ";
    $sql .= "
        ORDER BY `lastName`, `firstName`, `middleName` ASC
    ";
    $res = mysqli_query($db_connect, $sql);
    if (!$res) crm_error(mysqli_error($res));
    // Store data
    $members = array();
    $row = mysqli_fetch_assoc($res);
    while (!empty($row)) {
        $member = array(
            'cid' => $row['cid'],
            'contact' => array(
                'cid' => $row['cid']
                , 'firstName' => $row['firstName']
                , 'middleName' => $row['middleName']
                , 'lastName' => $row['lastName']
                , 'email' => $row['email']
                , 'phone' => $row['phone']
            )
            , 'user' => array(
                'cid' => $row['cid']
                , 'username' => $row['username']
                , 'hash' => $row['hash']
            )
            , 'member' => array(
                'emergencyName' => $row['emergencyName']
                , 'emergencyPhone' => $row['emergencyPhone']
                , 'emergencyRelation' => $row['emergencyRelation']
                , 'address1' => $row['address1']
                , 'address2' => $row['address2']
                , 'address3' => $row['address3']
                , 'town_city' => $row['town_city']
                , 'zipcode' => $row['zipcode']
            )
            , 'membership' => array()
        );
        $members[] = $member;
        $row = mysqli_fetch_assoc($res);
    }
    // Get list of memberships associated with each member
    // This is slow, should be combined into a single query
    foreach ($members as $index => $member) {
        
        // Query all memberships for current member
        $esc_cid = mysqli_real_escape_string($db_connect, $member['cid']);
        $sql = "
            SELECT
            `membership`.`sid`, `membership`.`cid`, `membership`.`start`, `membership`.`end`,
            `plan`.`pid`, `plan`.`name`, `plan`.`price`, `plan`.`active`, `plan`.`voting`
            FROM `membership`
            INNER JOIN `plan` ON `plan`.`pid` = `membership`.`pid`
            WHERE `membership`.`cid`='$esc_cid'
            ORDER BY `membership`.`start` ASC
        ";
        $res = mysqli_query($db_connect, $sql);
        if (!$res) crm_error(mysqli_error($res));
        // Add each membership
        $row = mysqli_fetch_assoc($res);
        while (!empty($row)) {
            $membership = array(
                'sid' => $row['sid']
                , 'cid' => $row['cid']
                , 'pid' => $row['pid']
                , 'start' => $row['start']
                , 'end' => $row['end']
                , 'plan' => array(
                    'pid' => $row['pid']
                    , 'name' => $row['name']
                    , 'price' => $row['price']
                    , 'active' => $row['active']
                    , 'voting' => $row['voting']
                )
            );
            $members[$index]['membership'][] = $membership;
            $row = mysqli_fetch_assoc($res);
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
                if (array_key_exists($contact['cid'], $cid_to_member)) {
                    $member = $cid_to_member[$contact['cid']];
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
    global $db_connect;
    // Check whether the contact is a member
    if (!isset($contact['member'])) {
        return $contact;
    }
    $esc_cid = mysqli_real_escape_string($db_connect, $contact['cid']);
    $esc_emergencyName = mysqli_real_escape_string($db_connect, $contact['member']['emergencyName']);
    $esc_emergencyPhone = mysqli_real_escape_string($db_connect, $contact['member']['emergencyPhone']);
    $esc_emergencyRelation = mysqli_real_escape_string($db_connect, $contact['member']['emergencyRelation']);
    $esc_address1 = mysqli_real_escape_string($db_connect, $contact['member']['address1']);
    $esc_address2 = mysqli_real_escape_string($db_connect, $contact['member']['address2']);
    $esc_address3 = mysqli_real_escape_string($db_connect, $contact['member']['address3']);
    $esc_town_city = mysqli_real_escape_string($db_connect, $contact['member']['town_city']);
    $esc_zipcode = mysqli_real_escape_string($db_connect, $contact['member']['zipcode']);
    switch ($op) {
        case 'create':
            // Add member
            $member = $contact['member'];
            $sql = "
                INSERT INTO `member`
                (`cid`, `emergencyName`, `emergencyPhone`, `emergencyRelation`, `address1`, `address2`, `address3`, `town_city`, `zipcode`)
                VALUES
                ('$esc_cid', '$esc_emergencyName', '$esc_emergencyPhone', '$esc_emergencyRelation', '$esc_address1', '$esc_address2', '$esc_address3', '$esc_town_city', '$esc_zipcode')
            ";
            $res = mysqli_query($db_connect, $sql);
            if (!$res) crm_error(mysqli_error($res));
            $contact['member']['cid'] = $contact['cid'];
            // Save memberships
            if (isset($member['membership'])) {
                foreach ($member['membership'] as $i => $membership) {
                    $membership['cid'] = $contact['cid'];
                    $membership = member_membership_save($membership);
                    $contact['member']['membership'][$i] = $membership;
                }
            }
            // Add role entry
            $sql = "
                SELECT `rid`
                FROM `role`
                WHERE `name`='member'
            ";
            $res = mysqli_query($db_connect, $sql);
            if (!$res) crm_error(mysqli_error($res));
            $row = mysqli_fetch_assoc($res);
            $esc_rid = mysqli_real_escape_string($db_connect, $row['rid']);
            if ($row) {
                $sql = "
                    INSERT INTO `user_role`
                    (`cid`, `rid`)
                    VALUES
                    ('$esc_cid', '$esc_rid')
                ";
                $res = mysqli_query($db_connect, $sql);
                if (!$res) crm_error(mysqli_error($res));
            }
            break;
        case 'update':
            // TODO
            break;
        case 'delete':
            member_delete($esc_cid);
            break;
    }
    return $contact;
}

/**
 * Saves a member.
 */
function member_save ($member) {
    global $db_connect;
    $fields = array(
        'cid', 'emergencyName', 'emergencyPhone', 'emergencyRelation', 'address1', 'address2', 'address3', 'town_city', 'zipcode'
    );
    $escaped = array();
    foreach ($fields as $field) {
        $escaped[$field] = mysqli_real_escape_string($db_connect, $member[$field]);
    }
    if (isset($member['cid'])) {
        // Update member
        $sql = "
            UPDATE `member`
            SET `emergencyName`='$escaped[emergencyName]'
                , `emergencyPhone`='$escaped[emergencyPhone]'
                , `emergencyRelation`='$escaped[emergencyRelation]'
                , `address1`='$escaped[address1]'
                , `address2`='$escaped[address2]'
                , `address3`='$escaped[address3]'
                , `town_city`='$escaped[town_city]'
                , `zipcode`='$escaped[zipcode]'
            WHERE `cid`='$escaped[cid]'
        ";
        $res = mysqli_query($db_connect, $sql);
        if (!$res) crm_error(mysqli_error($res));
        if (mysqli_affected_rows() < 1) {
            return null;
        }
    }
}

/**
 * Delete membership data for a contact.
 * @param $cid - The contact id.
 */
function member_delete ($cid) {
    global $db_connect;
    $esc_cid = mysqli_real_escape_string($db_connect, $cid);
    $sql = "
        DELETE FROM `member`
        WHERE `cid`='$esc_cid'
    ";
    $res = mysqli_query($db_connect, $sql);
    if (!$res) crm_error(mysqli_error($res));
    $sql = "
        DELETE FROM `membership`
        WHERE `cid`='$esc_cid'
    ";
    $res = mysqli_query($db_connect, $sql);
    if (!$res) crm_error(mysqli_error($res));
    message_register("Deleted membership info for: " . theme('contact_name', $esc_cid));
}

/**
 * Return data for one or more membership plans.
 * @param $opts An associative array of options, possible keys are:
 *   'pid' If specified, returns a single plan with the matching id,
 *   'filter' An array mapping filter names to filter values
 * @return An array with each element representing a membership plan.
 */
function member_plan_data ($opts = array()) {
    global $db_connect;
    // Construct query for plans
    $sql = "
        SELECT *
        FROM `plan`
        WHERE 1
    ";
    if (isset($opts['filter'])) {
        foreach ($opts['filter'] as $name => $param) {
            switch ($name) {
                case 'active':
                    if ($param) {
                        $sql .= "
                            AND `plan`.`active` <> 0
                        ";
                    } else {
                        $sql .= "
                            AND `plan`.`active` = 0
                        ";
                    }
                    break;
            }
        }
    }
    if (!empty($opts['pid'])) {
        $pid = mysqli_real_escape_string($db_connect, $opts['pid']);
        $sql .= " AND `plan`.`pid`='$pid' ";
    }
    // Query database for plans
    $res = mysqli_query($db_connect, $sql);
    if (!$res) { crm_error(mysqli_error($res)); }
    // Store plans
    $plans = array();
    $row = mysqli_fetch_assoc($res);
    while ($row) {
        $plans[] = $row;
        $row = mysqli_fetch_assoc($res);
    }
    return $plans;
}

/**
 * Generates an associative array mapping membership plan pids to
 * strings describing those membership plans.
 * @param $opts Options to be passed to member_plan_data().
 * @return The associative array of membership plan descriptions.
 */
function member_plan_options ($opts = bull) {
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
 * Saves or updates a membership plan
 */
function member_plan_save ($plan) {
    global $db_connect;
    $esc_name = mysqli_real_escape_string($db_connect, $plan['name']);
    $esc_price = mysqli_real_escape_string($db_connect, $plan['price']);
    $esc_voting = mysqli_real_escape_string($db_connect, $plan['voting']);
    $esc_active = mysqli_real_escape_string($db_connect, $plan['active']);
    $esc_pid = mysqli_real_escape_string($db_connect, $plan['pid']);
    if (isset($plan['pid'])) {
        // Update
        $sql = "
            UPDATE `plan`
            SET
                `name`='$esc_name'
                , `price`='$esc_price'
                , `active`='$esc_active'
                , `voting`='$esc_voting'
            WHERE `pid`='$esc_pid'
        ";
        $res = mysqli_query($db_connect, $sql);
        if (!$res) crm_error(mysqli_error($res));
        $plan = module_invoke_api('member_plan', $plan, 'update');
    } else {
        // Insert
        $sql = "
            INSERT INTO `plan`
            (`name`,`price`, `voting`, `active`)
            VALUES
            ('$esc_name', '$esc_price', '$esc_voting', '$esc_active')
        ";
        $res = mysqli_query($db_connect, $sql);
        if (!$res) crm_error(mysqli_error($res));
        $plan['pid'] = mysqli_insert_id($db_connect);
        $plan = module_invoke_api('member_plan', $plan, 'create');
    }
    return $plan;
}

/**
 * Deletes a membership plan
 */
function member_plan_delete ($pid) {
    global $db_connect;
    $esc_pid = mysqli_real_escape_string($db_connect, $pid);
    $description = theme('member_plan_description', $esc_pid);
    $plan = crm_get_one('member_plan', array('pid'=>$pid));
    $plan = module_invoke_api('member_plan', $plan, 'delete');
    $sql = "
        DELETE FROM `plan`
        WHERE `pid`='$esc_pid'
    ";
    $res = mysqli_query($db_connect, $sql);
    if (!$res) crm_error(mysqli_error($res));
    message_register("Deleted plan: $description");
}

/**
 * Return data for one or more memberships.
 * @param $opts An associative array of options, possible keys are:
 *   'cid' If specified, returns memberships for the member with the cid,
 *   'filter' An array mapping filter names to filter values
 * @return An array with each element representing a membership.
 */ 
function member_membership_data ($opts) {
    global $db_connect;
    // Query database
    $sql = "
        SELECT *
        FROM `membership`
        INNER JOIN `plan`
        ON `membership`.`pid` = `plan`.`pid`
        WHERE 1
    ";
    // Add member id
    if (!empty($opts['cid'])) {
        $esc_cid = mysqli_real_escape_string($db_connect, $opts['cid']);
        $sql .= "
            AND `cid`='$esc_cid'
        ";
    }
    // Add membership id
    if (!empty($opts['sid'])) {
        $esc_sid = mysqli_real_escape_string($db_connect, $opts['sid']);
        $sql .= "
            AND `sid`='$esc_sid'
        ";
    }
    // Add filters
    $esc_today = mysqli_real_escape_string($db_connect, date('Y-m-d'));
    if (isset($opts['filter'])) {
        foreach ($opts['filter'] as $name => $param) {
            $esc_param = mysqli_real_escape_string($db_connect, $param);
            switch ($name) {
                case 'active':
                    if ($param) {
                        $sql .= "
                            AND (`end` IS NULL OR `end` > '$esc_today')
                        ";
                    } else {
                        $sql .= "
                            AND (`end` IS NOT NULL)
                        ";
                    }
                    break;
                case 'starts_after':
                    $sql .= "
                        AND (`start` > '$esc_param')
                    ";
                    break;
                case 'ends_after':
                    $sql .= "
                        AND (`end` IS NULL OR `end` > '$esc_param')
                    ";
                    break;
                default:
                    break;
            }
        }
    }
    $sql .= "
        ORDER BY `start` DESC
    ";
    $res = mysqli_query($db_connect, $sql);
    if (!$res) crm_error(mysqli_error($res));
    // Store data
    $memberships = array();
    $row = mysqli_fetch_assoc($res);
    while (!empty($row)) {
        $memberships[] = array(
            'cid' => $row['cid']
            , 'sid' => $row['sid']
            , 'pid' => $row['pid']
            , 'start' => $row['start']
            , 'end' => $row['end']
            , 'plan' => array(
                'pid' => $row['pid']
                , 'name' => $row['name']
                , 'price' => $row['price']
                , 'active' => $row['active']
                , 'voting' => $row['voting']
            )
        );
        $row = mysqli_fetch_assoc($res);
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
    global $db_connect;
    $esc_sid = mysqli_real_escape_string($db_connect, $membership['sid']);
    $esc_cid = mysqli_real_escape_string($db_connect, $membership['cid']);
    $esc_pid = mysqli_real_escape_string($db_connect, $membership['pid']);
    $esc_start = mysqli_real_escape_string($db_connect, $membership['start']);
    $esc_end = mysqli_real_escape_string($db_connect, $membership['end']);
    if (isset($membership['sid'])) {
        // Update
        $sql = "
            UPDATE `membership`
            SET `cid`='$esc_cid'
            , `pid`='$esc_pid'
        ";
        if ($esc_start) {
            $sql .= "
                , `start`='$esc_start'
            ";
        } else {
            $sql .= "
                , `start`=NULL
            ";
        }
        if ($esc_end) {
            $sql .= "
                , `end`='$esc_end'
            ";
        } else {
            $sql .= "
                , `end`=NULL
            ";
        }
        $sql .= "
            WHERE `sid`='$esc_sid'
        ";
        $res = mysqli_query($db_connect, $sql);
        if (!$res) crm_error(mysqli_error($res));
        $membership = module_invoke_api('member_membership', $membership, 'update');
    } else {
        // Insert
        $sql = "
            INSERT INTO `membership`
            (`cid`, `pid`, `start`)
            VALUES
            ('$esc_cid', '$esc_pid', '$esc_start')
        ";
        $res = mysqli_query($db_connect, $sql);
        if (!$res) crm_error(mysqli_error($res));
        $membership['sid'] = mysqli_insert_id($db_connect);
        $membership = module_invoke_api('member_membership', $membership, 'add');
    }
    return $membership;
}

/**
 * Deletes a membership
 */
function member_membership_delete ($sid) {
    global $db_connect;
    $esc_sid = mysqli_real_escape_string($db_connect, $sid);
    $membership = crm_get_one('member_membership', array('sid'=>$sid));
    $membership = module_invoke_api('member_membership', $membership, 'delete');
    $sql = "
        DELETE FROM `membership`
        WHERE `sid`='$esc_sid'
    ";
    $res = mysqli_query($db_connect, $sql);
    if (!$res) crm_error(mysqli_error($res));
}

/**
 * Return data for one or more contacts.  Use contact_data() instead.
 * @param $opts An associative array of options, possible keys are:
 *   'cid' If specified returns the corresponding member (or members for an array);
 *   'filter' An array mapping filter names to filter values
 * @return An array with each element representing a contact.
 * @deprecated
 */ 
function member_contact_data ($opts = array()) {
    return contact_data($opts);
}

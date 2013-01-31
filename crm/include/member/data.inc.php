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
    if (!empty($opts['cid'])) {
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
        if (isset($filter['nameLike'])) {
            
            // Split on first comma and create an array of name parts in "first middle last" order
            $parts = split(',', $filter['nameLike'], 2);
            $names = array();
            foreach (array_reverse($parts) as $part) {
                $nameParts = preg_split('/\s+/', $part);
                foreach ($nameParts as $name) {
                    if (!empty($name)) {
                       $names[] = mysql_real_escape_string($name);
                    }
                }
            }
            
            // Set where clauses based on number of name segments given
            if (sizeof($names) === 1) {
                $sql .= "AND (`firstName` LIKE '%$names[0]%' OR `middleName` LIKE '%$names[0]%' OR `lastName` LIKE '%$names[0]%') ";
            } else if (sizeof($names) === 2) {
                $sql .= "
                    AND (
                        (`firstName` LIKE '%$names[0]%' AND (`middleName` LIKE '%$names[1]%' OR `lastName` LIKE '%$names[1]%'))
                        OR (`middleName` LIKE '%$names[0]%' AND `lastName` LIKE '%$names[1]%')
                    )
                ";
            } else if (sizeof($names) === 3) {
                $sql .= "
                    AND `firstName` LIKE '%$names[0]%'
                    AND `middleName` LIKE '%$names[1]%'
                    AND `lastName` LIKE '%$names[2]%'
                ";
            }
        }
    }
    $sql .= " GROUP BY `member`.`cid` ";
    $sql .= " ORDER BY `lastName`, `firstName`, `middleName` ASC ";

    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    
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
        if (!$res) die(mysql_error());
        
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
    if (!$res) { die(mysql_error()); }
    
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
    if (!$res) die(mysql_error());
    
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
 * Return data for one or more contacts.
 * 
 * @param $opts An associative array of options, possible keys are:
 *   'cid' If specified returns the corresponding member (or members for an array);
 *   'filter' An array mapping filter names to filter values
 * @return An array with each element representing a contact.
*/ 
function member_contact_data ($opts = array()) {
    
    // Query database
    $sql = "
        SELECT * FROM `contact`
        WHERE 1";
        
    // Add contact id
    if ($opts['cid']) {
        if (is_array($opts['cid'])) {
            $terms = array();
            foreach ($opts['cid'] as $cid) {
                $terms[] = "'" . mysql_real_escape_string($cid) . "'";
            }
            $esc_list = '(' . implode(',', $terms) . ')';
            $sql .= " AND `cid` IN $esc_list";
        } else {
            $esc_cid = mysql_real_escape_string($opts['cid']);
            $sql .= " AND `cid`='$esc_cid'";
        }
    }
    
    // Add filters
    if (isset($opts['filter'])) {
        foreach ($opts['filter'] as $name => $param) {
            switch ($name) {
                default:
                break;
            }
        }
    }

    $sql .= "
        ORDER BY `lastName`, `firstName`, `middleName` ASC";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    
    // Store data
    $contacts = array();
    $row = mysql_fetch_assoc($res);
    while (!empty($row)) {
        $contacts[] = array(
            'cid' => $row['cid'],
            'firstName' => $row['firstName'],
            'middleName' => $row['middleName'],
            'lastName' => $row['lastName'],
            'email' => $row['email'],
            'phone' => $row['phone'],
            'emergencyName' => $row['emergencyName'],
            'emergencyPhone' => $row['emergencyPhone']
        );
        $row = mysql_fetch_assoc($res);
    }
    
    // Return data
    return $contacts;
}

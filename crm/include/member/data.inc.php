<?php 

/*
    Copyright 2009-2010 Edward L. Platt <elplatt@alum.mit.edu>
    
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
 * Return data tree representing members
 *
 * @param $opts An associative array of options, possible keys are:
 *   'mid' If specified, returns a single memeber with the matching member id,
 *   'filter' An array of filters of the form (<filter name>, <filter param>)
*/ 
function member_data ($opts) {
    
    // Query database
    $sql = "
        SELECT
        `member`.`mid`,
        `member`.`cid`, `firstName`, `middleName`, `lastName`, `email`, `phone`, `emergencyName`, `emergencyPhone`,
        `user`.`uid`, `username`, `hash`
        FROM `member`
        LEFT JOIN `contact` ON `member`.`cid`=`contact`.`cid`
        LEFT JOIN `user` ON `member`.`cid`=`user`.`cid`
        LEFT JOIN `membership` ON (`member`.`mid`=`membership`.`mid` AND `membership`.`end` IS NULL)
        LEFT JOIN `plan` ON `plan`.`pid`=`membership`.`pid`
        WHERE 1";
    if (!empty($opts['mid'])) {
        $sql .= " AND `member`.`mid`=$opts[mid]";
    }
    if (!empty($opts['cid'])) {
        $sql .= " AND `member`.`cid`=$opts[cid]";
    }
    if (!empty($opts['filter'])) {
        foreach ($opts['filter'] as $filter) {
            $name = $filter[0];
            $param = $filter[1];
            switch ($name) {
                case 'active':
                    $sql .= " AND (`membership`.`start` IS NOT NULL AND `membership`.`end` IS NULL)";
                    break;
                case 'voting':
                    $sql .= " AND (`membership`.`start` IS NOT NULL AND `membership`.`end` IS NULL AND `plan`.`voting` <> 0)";
                    break;
            }
        }
    }
    $sql .= "
        ORDER BY `lastName`, `firstName`, `middleName` ASC";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    
    // Store data
    $members = array();
    $row = mysql_fetch_assoc($res);
    while (!empty($row)) {
        $member = array(
            'mid' => $row['mid'],
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
                'uid' => $row['uid'],
                'username' => $row['username'],
                'hash' => $row['hash']
            ),
            'membership' => array()
        );
        
        $members[] = $member;
        $row = mysql_fetch_assoc($res);
    }
    
    // Get list of memberships associated with each member
    // This is slow, should be combined into above query, but works for now -Ed
    foreach ($members as $index => $member) {
        
        // Query all memberships for current member
        $sql = "
            SELECT
            `membership`.`sid`, `membership`.`mid`, `membership`.`start`, `membership`.`end`,
            `plan`.`pid`, `plan`.`name`, `plan`.`price`, `plan`.`active`, `plan`.`voting`
            FROM `membership`
            INNER JOIN `plan` ON `plan`.`pid` = `membership`.`pid`
            WHERE `membership`.`mid`='$member[mid]'
            ORDER BY `membership`.`start` ASC
        ";
        $res = mysql_query($sql);
        if (!$res) die(mysql_error());
        
        // Add each membership
        $row = mysql_fetch_assoc($res);
        while (!empty($row)) {
            $membership = array(
                'sid' => $row['sid'],
                'mid' => $row['mid'],
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
 * Return data tree representing membership plans
 * 
 * @param $opts An associative array of options, possible keys are:
 *   'pid' If specified, returns a single plan with the matching id,
 *   'filter' An array of filters of the form (<filter name>, <filter param>)
*/
function member_plan_data ($opts) {
    
    // Construct query for plans
    $sql = "SELECT * FROM `plan` WHERE 1";
    if (!empty($opts['filter'])) {
        foreach ($opts['filter'] as $filter) {
            $name = $filter[0];
            $params = $filter[1];
            switch ($name) {
                case 'active':
                    $sql .= " AND `plan`.`active` <> 0";
                    break;
            }
        }
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
 * Return data tree representing memberships
 *
 * @param $opts An associative array of options, possible keys are:
 *   'mid' If specified, returns memberships for the member with the matching id,
 *   'filter' An array of filters of the form (<filter name>, <filter param>)
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
    if (!empty($opts['mid'])) {
        $sql .= " AND `mid`=$opts[mid]";
    }
    
    // Add membership id
    if (!empty($opts['sid'])) {
        $sql .= " AND `sid`=$opts[sid]";
    }
    
    // Add filters
    if (!empty($opts['filter'])) {
        foreach ($opts['filter'] as $filter) {
            $name = $filter[0];
            $params = $filter[1];
            switch ($name) {
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
            'mid' => $row['mid'],
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
 * Return options array for membership plans
 */
function member_plan_options($opts = NULL) {
    
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
 * Return data structure representing contacts
 * 
 * @param $opts An associative array of options, possible keys are:
 *   'cid' If specified, returns a single memeber with the matching member id,
 *   'filter' An array of filters of the form (<filter name>, <filter param>)
*/ 
function member_contact_data ($opts) {
    
    // Query database
    $sql = "
        SELECT * FROM `contact`
        WHERE 1";
        
    // Add contact id
    if ($opts['cid']) {
        $sql .= " AND `cid`=$opts[cid]";
    }
    
    // Add filters
    if (!empty($opts['filter'])) {
        foreach ($opts['filter'] as $filter) {
            $name = $filter[0];
            $params = $filter[1];
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
    $contactss = array();
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

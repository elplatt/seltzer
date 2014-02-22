<?php

/*
    Copyright 2009-2014 Edward L. Platt <ed@elplatt.com>
    
    This file is part of the Seltzer CRM Project
    report.inc.php - Member module - reports

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
 * @return A comma-separated list of user emails.
 * @param $opts - Options to pass to member_data().
 */
function member_email_report ($opts) {
    $result = array();
    $data = member_data($opts);
    foreach ($data as $row) {
        $email = trim($row['contact']['email']);
        if (!empty($email)) {
            $result[] = $email;
        }
    }
    return join($result, ', ');
}

/**
 * @return the earliest date of any membership.
 */
function member_membership_earliest_date () {
    $sql = "SELECT `start` FROM `membership` ORDER BY `start` ASC LIMIT 1";
    $res = mysql_query($sql);
    $row = mysql_fetch_assoc($res);
    if (!$row) {
        return '';
    }
    return $row['start'];
}

/**
 * @return json structure containing membership statistics.
 */
function member_statistics () {
    // Get plans and earliest date
    $plans = crm_map(member_plan_data(), 'pid');
    $results = array();
    foreach ($plans as $pid => $plan) {
        $results[$pid] = array();
    }
    $earliest = member_membership_earliest_date();
    if (empty($earliest)) {
        message_register('No membership data available.');
        return '[]';
    }
    // Generate list of months
    $start = 12*(int)date('Y', strtotime($earliest)) + (int)date('m', strtotime($earliest)) - 1;
    $now = 12*(int)date('Y') + (int)date('m') - 1;
    $dates = array();
    for ($months = $start; $months <= $now; $months++) {
        $year = floor($months/12);
        $month = $months % 12 + 1;
        $dates[] = "('$year-$month-01')";
    }
    // Create temporary table with dates
    $sql = "DROP TEMPORARY TABLE IF EXISTS `temp_months`";
    $res = mysql_query($sql);
    if (!$res) { crm_error(mysql_error($res)); }
    $sql = "CREATE TEMPORARY TABLE `temp_months` (`month` date NOT NULL);";
    $res = mysql_query($sql);
    if (!$res) { crm_error(mysql_error($res)); }
    $sql = "INSERT INTO `temp_months` (`month`) VALUES " . implode(',', $dates) . ";";
    $res = mysql_query($sql);
    if (!$res) { crm_error(mysql_error($res)); }
    // Query number of active memberships for each month
    $sql = "
        SELECT
            `plan`.`pid`
            , `plan`.`name`
            , `temp_months`.`month`
            , UNIX_TIMESTAMP(`temp_months`.`month`) AS `month_timestamp`
            , count(`membership`.`sid`) AS `member_count`
        FROM `temp_months`
        JOIN `plan`
        LEFT JOIN `membership`
        ON `membership`.`pid`=`plan`.`pid`
        AND `membership`.`start` <= `month`
        AND (`membership`.`end` IS NULL OR `membership`.`end` > `month`)
        GROUP BY `plan`.`pid`, `month`;
    ";
    $res = mysql_query($sql);
    if (!$res) { crm_error(mysql_error($res)); }
    // Build results
    while ($row = mysql_fetch_assoc($res)) {
        $results[$row['pid']][] = array(
            'x' => (int)$row['month_timestamp']
            , 'label' => $row['month']
            , 'y' => (int)$row['member_count']
        );
    }
    // Convert from associative to indexed
    $indexed = array();
    foreach ($results as $pid => $v) {
        $indexed[] = array(
            'name' => $plans[$pid]['name'] . " ($pid)"
            , 'values' => $v
        );
    }
    return json_encode($indexed);
}
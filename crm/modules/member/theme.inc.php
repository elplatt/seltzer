<?php

/*
    Copyright 2009-2025 Edward L. Platt <ed@elplatt.com>
    
    This file is part of the Seltzer CRM Project
    theme.inc.php - Member module - theming
    
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
 * @return The themed html for an active member email report.
 */
function theme_member_email_report ($opts) {
    $output = '<div class="member-email-report">';
    $title = '';
    if (isset($opts['filter']) && isset($opts['filter']['active'])) {
        $title = $opts['filter']['active'] ? 'Active ' : 'Lapsed ';
    }
    $title .= 'Member ';
    $title .= 'Email Report';
    $output .= "<h2>$title</h2>";
    $output .= '<textarea rows="10" cols="80">';
    $output .= member_email_report($opts);
    $output .= '</textarea>';
    $output .= '</div>';
    return $output;
}

/**
 * @return The themed html for a membership report.
 */
function theme_member_membership_report () {
    $output = <<<HTML
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <h2>Membership Report</h2>
        <div style="width: 100%; margin: 0 auto;">
            <canvas id="memberships-monthly"></canvas>
            <canvas id="memberships-monthly-cumulative"></canvas>
        </div>
        <div style="width: 50%; margin: 0 auto;">
            <canvas id="plan-distribution"></canvas>
        </div>
        <script src="modules/member/charts.js"></script>
HTML;
    return $output;
}

/**
 * Return the themed html description for a plan.
 * @param $pid The pid of the plan.
 * @return The themed html string.
 */
function theme_member_plan_description ($pid) {
    // Get plan data
    $data = member_plan_data(array('pid' => $pid));
    if (count($data) < 1) {
        return '';
    }
    $output = $data[0]['name'] . ' : ' . $data[0]['price'];
    return $output;
}

/**
 * Return the text of an email notifying administrators that a user has been created.
 * @param $cid The contact id of the new member.
 */
function theme_member_created_email ($cid) {
    // Get info on the logged in user
    $data = contact_data(array('cid'=>user_id()));
    $admin = $data[0];
    $adminName = theme_contact_name($admin['cid']);
    // Get info on member
    $data = member_data(array('cid'=>$cid));
    $member = $data[0];
    $contact = $member['contact'];
    $name = theme_contact_name($contact['cid']);
    // Get info on member's plan
    $data = member_membership_data(array('cid'=>$cid, $filter=>array('active'=>true)));
    $date = $data[0]['start'];
    $plan = $data[0]['plan']['name'];
    $output = "<p>Contact info:<br/>\n";
    $output .= "Name: $name<br/>\n";
    $output .= "Email: $contact[email]<br/>\n";
    $output .= "Phone: $contact[phone]\n</p>\n";
    $output .= "<p>Membership info:<br/>\n";
    $output .= "Plan: $plan<br/>\n";
    $output .= "Start date: $date\n</p>\n";
    if (user_id()) {
        $output .= "<p>Entered by: $adminName</p>\n";
    } else {
        $output .= "<p>User self-registered</p>\n";
    }
    return $output;
}

/**
 * Return the text of an email welcoming a new member.
 * @param $cid The contact id of the new member.
 * @param $confirm_url The url for the new user to confirm their email.
 */
function theme_member_welcome_email ($cid, $confirm_url) {
    $contact = crm_get_one('contact', array('cid'=>$cid));
    $vars = array(
        'type' => 'welcome'
        , 'confirm_url' => $confirm_url
        , 'username' => $contact['user']['username']
    );
    return template_render('email', $vars);
}

/**
 * Theme a plan name.
 * @param $plan The plan data structure or pid.
 * @param $link true if the name should be a link (default: false).
 * @param $path The path that should be linked to. The pid will always be added
 *   as a parameter.
 * @return the name string.
 */
function theme_member_plan_name ($plan, $link = false, $path = 'plan') {
    if (!is_array($plan)) {
        $plan = crm_get_one('member_plan', array('pid'=>$plan));
    }
    $name = $plan['name'];
    if ($link) {
        $url_opts = array('query' => array('pid' => $plan['pid']));
        $name = crm_link($name, $path, $url_opts);
    }
    return $name;
}

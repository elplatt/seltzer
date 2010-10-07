<?php 

/*
    Copyright 2009-2010 Edward L. Platt <elplatt@alum.mit.edu>
    
    This file is part of the Seltzer CRM Project
    command.inc.php - Member module - request handlers

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
 * Handle member add request.
 */
function command_member_add() {
    global $esc_post;
    
    // Verify permissions
    if (!user_access('member_add')) {
        error_register('Permission denied: member_add');
        return 'members.php';
    }
    if (!user_access('contact_add')) {
        error_register('Permission denied: contact_add');
        return 'members.php';
    }
    if (!user_access('member_add')) {
        error_register('Permission denied: member_add');
        return 'members.php';
    }
    
    // Add contact
    $sql = "
        INSERT INTO `contact`
        (`firstName`,`middleName`,`lastName`,`email`,`phone`,`emergencyName`,`emergencyPhone`)
        VALUES
        ('$esc_post[firstName]','$esc_post[middleName]','$esc_post[lastName]','$esc_post[email]','$esc_post[phone]','$esc_post[emergencyName]','$esc_post[emergencyPhone]')";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    $cid = mysql_insert_id();
    
    // Add user
    $sql = "
        INSERT INTO `user`
        (`username`, `cid`)
        VALUES
        ('$esc_post[username]', '$cid')";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    $uid = mysql_insert_id();
    
    // Add role entry
    $sql = "
        INSERT INTO `role`
        (`uid`, `member`)
        VALUES
        ('$uid', 1)";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    
    // Add member
    $sql = "
        INSERT INTO `member`
        (`pid`,`cid`)
        VALUES
        ('$esc_post[pid]','$cid')";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    $mid = mysql_insert_id();
    
    // Add membership
    $sql = "
        INSERT INTO `membership`
        (`mid`, `pid`, `start`)
        VALUES
        ('$mid', '$esc_post[pid]', '$esc_post[start]')
    ";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    
    return 'members.php';
}

/**
 * Handle membership add request.
 */
function command_member_membership_add() {
    global $esc_post;
    
    // Verify permissions
    if (!user_access('member_edit')) {
        error_register('Permission denied: member_edit');
        return 'members.php';
    }
    if (!user_access('member_membership_edit')) {
        error_register('Permission denied: member_membership_edit');
        return 'members.php';
    }
    
    // Add membership
    $sql = "
        INSERT INTO `membership`
        (`mid`,`pid`,`start`";
    if (!empty($esc_post['end'])) {
        $sql .= ", `end`";
    }
    $sql .= ")
        VALUES
        ('$esc_post[mid]','$esc_post[pid]','$esc_post[start]'";
        
    if (!empty($esc_post['end'])) {
        $sql .= ",'$esc_post[end]'";
    }
    $sql .= ")";
    
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    
    return "member.php?mid=$_POST[mid]";
}

/**
 * Handle member filter request.
 */
function command_member_filter() {
    
    // Set filter in session
    $_SESSION['member_filter_option'] = $_GET['filter'];
    
    // Set filter
    if ($_GET['filter'] == 'all') {
        $_SESSION['member_filter'] = array();
    }
    if ($_GET['filter'] == 'active') {
        $_SESSION['member_filter'] = array(array('active'));
    }
    if ($_GET['filter'] == 'voting') {
        $_SESSION['member_filter'] = array(array('voting'));
    }
    
    // Construct query string
    $params = array();
    foreach ($_GET as $k=>$v) {
        if ($k == 'command' || $k == 'filter') {
            continue;
        }
        $params[] = urlencode($k) . '=' . urlencode($v);
    }
    if (!empty($params)) {
        $query = '?' . join('&', $params);
    }
    
    return 'members.php' . $query;
}

/**
 * Handle member delete request.
 */
function command_member_delete() {
    global $esc_post;
    
    // Verify permissions
    if (!user_access('member_delete')) {
        error_register('Permission denied: member_delete');
        return 'members.php';
    }
    if ($_POST['deleteUser'] && !user_access('user_delete')) {
        error_register('Permission denied: user_delete');
        return 'members.php';
    }
    if ($_POST['deleteContact'] && !user_access('contact_delete')) {
        error_register('Permission denied: contact_delete');
        return 'members.php';
    }

    // Delete member
    $sql = "DELETE FROM `member` WHERE `mid`='$esc_post[mid]'";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    
    // Delete user
    if ($_POST['deleteUser']) {
        $sql = "DELETE FROM `user` WHERE `uid`='$esc_post[uid]'";
        $res = mysql_query($sql);
        if (!$res) die(mysql_error());
    }
    
    // Delete contact info
    if ($_POST['deleteContact']) {
        $sql = "DELETE FROM `contact` WHERE `cid`='$esc_post[cid]'";
        $res = mysql_query($sql);
        if (!$res) die(mysql_error());
    }

    return 'members.php';
}

/**
 * Handle membership delete request.
 */
function command_member_membership_delete() {
    global $esc_post;
    
    // Verify permissions
    if (!user_access('member_membership_edit')) {
        error_register('Permission denied: member_membership_edit');
        return 'members.php';
    }

    // Delete membership
    $sql = "DELETE FROM `membership` WHERE `sid`='$esc_post[sid]'";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());

    return 'members.php';
}

/**
 * Handle contact update request.
 */
function command_contact_update() {
    global $esc_post;
    
    // Verify permissions
    if (!user_access('contact_edit')) {
        error_register('Permission denied: contact_edit');
        return 'members.php';
    }
    
    // Query database
    $sql = "
        UPDATE `contact`
        SET
        `firstName`='$esc_post[firstName]',
        `middleName`='$esc_post[middleName]',
        `lastName`='$esc_post[lastName]',
        `email`='$esc_post[email]',
        `phone`='$esc_post[phone]',
        `emergencyName`='$esc_post[emergencyName]',
        `emergencyPhone`='$esc_post[emergencyPhone]'
        WHERE `cid`='$esc_post[cid]'";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    
    return 'members.php';
}

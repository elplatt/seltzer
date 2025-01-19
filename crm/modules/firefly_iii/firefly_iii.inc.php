<?php

/*
    Copyright 2024 Ilias Daradimos <judgedrid@gmail.com>
    
    This file is part of the Seltzer CRM Project
    firefly_iii.inc.php - Firefly III webhook endpoints.
    
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
 * @return This module's revision number. Each new release should increment
 * this number.
 */
function firefly_iii_revision () {
    return 1;
}

/**
 * Install or upgrade this module.
 * @param $old_revision The last installed revision of this module, or 0 if the
 *   module has never been installed.
 */
function firefly_iii_install($old_revision = 0) {
    global $db_connect;
    if ($old_revision < 1) {
        $sql = "
            CREATE TABLE IF NOT EXISTS `firefly_iii_transactions` (
                `pmtid` mediumint(8) unsigned NOT NULL,
                `ffiii_tr_id` mediumint(8) unsigned NOT NULL
                , PRIMARY KEY (`pmtid`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
        ";
        $res = mysqli_query($db_connect, $sql);
        if (!$res) crm_error(mysqli_error($db_connect));

        $sql = "
            CREATE TABLE IF NOT EXISTS `firefly_iii_users` (
                `cid` mediumint(8) unsigned NOT NULL,
                `ffiii_uid` mediumint(8) unsigned NOT NULL
                , PRIMARY KEY (`cid`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
        ";
        $res = mysqli_query($db_connect, $sql);
        if (!$res) crm_error(mysqli_error($db_connect));
    }
}

/**
 * Return data for one or more Firefly III mappings.
 * @param $opts An associative array of options, possible keys are:
 *   'cid' If specified, returns all mappings assigned to the contact with specified id;
 *   'filter' An array mapping filter names to filter values;
 * @return An array with each element representing a single payment.
 */
function firefly_iii_users_data ($opts = array()) {
    global $db_connect;
    $sql = "
        SELECT `cid`, `username`, `ffiii_uid`
        FROM `user` LEFT JOIN `firefly_iii_users` USING(cid)
        WHERE 1
    ";
    if (isset($opts['filter'])) {
        foreach ($opts['filter'] as $filter => $value) {
            if ($filter === 'cid') {
                $esc_cid = mysqli_real_escape_string($db_connect, $value);
                $sql .= "
                    AND `cid`='$esc_cid'
                ";
            }
            $sql .= "
                AND $filter='$value'
            ";
        }
    }
    $res = mysqli_query($db_connect, $sql);
    if (!$res) crm_error(mysqli_error($db_connect));
    $uids = array();
    while ($row = mysqli_fetch_assoc($res)) {
        $uids[] = array('cid' => $row['cid'],
                        'ffiii_uid' => $row['ffiii_uid']);
    }
    return $uids;
}

// Contact & Payment addition, deletion, update ////////////////////////////////

/**
 * Update Firefly III mapping when a contact is updated.
 * @param $contact The contact data array.
 * @param $op The operation being performed.
 */
function firefly_iii_contact_api ($contact, $op) {
    switch ($op) {
        case 'delete':
            firefly_iii_contact_delete($contact['cid']);
            break;
    }
    return $contact;
}


// Table & Page rendering //////////////////////////////////////////////////////

/**
 * Generate payments contacts table.
 * @param $opts an array of options passed to the firefly_iii_contact_data function
 * @return a table (array) listing the contacts and Firefly III account IDs
 */
function firefly_iii_users_table ($opts) {
    global $config_ffiii;
    $export = false;
    $data = crm_get_data('firefly_iii_users', $opts);
    // Initialize table
    $table = array(
        "id" => ''
        , "class" => ''
        , "rows" => array()
        , "columns" => array()
    );
    // Check for permissions
    if (!user_access('payment_view')) {
        error_register('User does not have permission to view payments');
        return;
    }
    // Add columns
    $table['columns'][] = array("title"=>'Full Name');
    $table['columns'][] = array("title"=>'Firefly III UID');
    // Add ops column
    if (!$export && (user_access('user_edit'))) {
        $table['columns'][] = array('title'=>'Ops','class'=>'');
    }
    // Add rows
    foreach ($data as $union) {
        $row = array();
        $memberopts = array(
            'cid' => $union['cid'],
        );
        $contact = crm_get_one('contact', array('cid'=>$union['cid']));
        $contactName = '';
        if (!empty($contact)) {
            $contactName = theme('contact_name', $contact, true);
        }
        $row[] = $contactName;
        $row[] = "<a href='".variable_get('ffiii_url')."/accounts/show/{$union['ffiii_uid']}' target='_blank'>{$union['ffiii_uid']}</a>";
        if (!$export && (user_access('user_edit'))) {
            $ops = array();
            if (user_access('payment_delete')) {
                $ops[] = '<a href=' . crm_url('ffiii_user_edit&cid=' . $contact['cid']) . '>Edit</a>';
            }
            $row[] = join(' ', $ops);
        }
        // Save row array into the $table structure
        $table['rows'][] = $row;
    }
    return $table;
}

/**
 * Page hook. Adds module content to a page before it is rendered.
 * @param &$page_data Reference to data about the page being rendered.
 * @param $page_name The name of the page being rendered.
 * @param $options The array of options passed to theme('page').
 */
function firefly_iii_page (&$page_data, $page_name, $options) {
    switch ($page_name) {
        case 'payments':
            if (user_access('user_edit')) {
                $content = theme('table', crm_get_table('firefly_iii_users'), array('show_export'=>true));
                page_add_content_top($page_data, $content, 'Firefly III');

            }
            break;
        case 'ffiii_user_edit':
            $content = theme('form', crm_get_form('firefly_iii_user_edit', $options['cid']));
            page_add_content_top($page_data, $content);
            break;
        case 'global_options':
            $content = theme('form', crm_get_form('firefly_iii_settings'));
            page_add_content_bottom($page_data, $content);
    }
}

// Forms ///////////////////////////////////////////////////////////////////////

/**
 * @return a Firefly III account id form structure.
 */
function firefly_iii_user_edit_form ($cid) {
    $contact = crm_get_one('contact', array('cid'=>$cid));
    return array(
        'type' => 'form'
        , 'method' => 'post'
        , 'command' => 'firefly_iii_user_edit'
        , 'hidden' => array(
            'cid' => $cid
        )
        , 'fields' => array(
            array(
                'type' => 'fieldset'
                , 'label' => "Set Firefly III uid for {$contact['user']['username']} ({$contact['firstName']} {$contact['lastName']})"
                , 'fields' => array(
                    array(
                        'type' => 'text'
                        , 'label' => 'Firefly UID'
                        , 'name' => 'ffiii_uid'
                    )
                    , array(
                        'type' => 'submit'
                        , 'value' => 'Save'
                    )
                )
            )
        )
    );
}

function firefly_iii_settings_form () {
    if (!in_array('sha3-256', hash_algos())) {
        $hash_msg = array(
            'type' => 'message'
            , 'value' => '<b>Note:</b> SHA3-256 is not available, use "Skip signature verification" at your own risk'
        );
        $skip_sig_field = array(
            'type' => 'checkbox'
            , 'label' => 'Skip signature verification'
            , 'name' => 'ffiii_skip_signature'
            , 'checked' => variable_get('ffiii_skip_signature')
        );
    } else {
        $hash_msg = array();
        $skip_sig_field = array();
        variable_set('ffiii_skip_signature', false);
    }
    return array(
        'type' => 'form'
        , 'method' => 'post'
        , 'command' => 'firefly_iii_settings_save'
        , 'fields' => array(
            array(
                'type' => 'fieldset'
                , 'label' => "Firefly III settings"
                , 'fields' => array(
                    array(
                        'type' => 'text'
                        , 'label' => 'Firefly III URL'
                        , 'name' => 'ffiii_url'
                        , 'value' => variable_get('ffiii_url')
                    )
                    , array(
                        'type' => 'text'
                        , 'label' => 'Create token'
                        , 'name' => 'ffiii_create_token'
                        , 'value' => variable_get('ffiii_create_token')
                    )
                    , array(
                        'type' => 'text'
                        , 'label' => 'Update token'
                        , 'name' => 'ffiii_update_token'
                        , 'value' => variable_get('ffiii_update_token')
                    )
                    , array(
                        'type' => 'text'
                        , 'label' => 'Delete token'
                        , 'name' => 'ffiii_delete_token'
                        , 'value' => variable_get('ffiii_delete_token')
                        )
                    , array(
                        'type' => 'text'
                        , 'label' => 'Membership category'
                        , 'name' => 'ffiii_membership_category'
                        , 'value' => variable_get('ffiii_membership_category', 'Membership')
                    )
                    , array(
                        'type' => 'checkbox'
                        , 'label' => 'Match existing transactions'
                        , 'name' => 'ffiii_match_trx'
                        , 'checked' => variable_get('ffiii_match_trx')
                    )
                    , $hash_msg
                    , $skip_sig_field
                    , array(
                        'type' => 'submit'
                        , 'value' => 'Save'
                    )
                )
            )
        )
    );
}


function command_firefly_iii_user_edit() {
    global $db_connect;
    $sql = "
    INSERT INTO firefly_iii_users (cid, ffiii_uid)
    VALUES ({$_POST['cid']}, {$_POST['ffiii_uid']})
    ON DUPLICATE KEY UPDATE ffiii_uid = VALUES(ffiii_uid);
    ";
    $res = mysqli_query($db_connect, $sql);
    if (!$res) crm_error(mysqli_error($db_connect));
    message_register('Updated Firefly III id');
    return crm_url('payments#tab-firefly-iii');
}

function command_firefly_iii_settings_save() {
    global $esc_post;
    global $db_connect;
    $options = array(
        'ffiii_url', 'ffiii_create_token', 'ffiii_update_token', 'ffiii_delete_token', 'ffiii_match_trx',
        'ffiii_skip_signature', 'ffiii_membership_category');
    foreach ($options as $option) {
        $esc_option = mysqli_real_escape_string($db_connect, $option);
        variable_set($esc_option, $esc_post[$esc_option]);
    }
    return crm_url('global_options');
}

function firefly_iii_payment_api ($payment, $op) {
    switch ($op) {
        case 'delete':
            firefly_iii_trx_map_delete($payment['pmtid']);
            break;
    }
    return $payment;
}

function firefly_iii_trx_map_delete($pmtid) {
    global $db_connect;
    $sql = "DELETE FROM `firefly_iii_transactions`
            WHERE `pmtid`=$pmtid";
    $res = mysqli_query($db_connect, $sql);
    if (!$res) crm_error(mysqli_error($db_connect));
}

function firefly_iii_trx_create_service($url_params, $ffiii_trx) {
    global $db_connect;
    $signature = ($_SERVER['HTTP_SIGNATURE'] ?: false);
    firefly_iii_signature_check($signature, variable_get('ffiii_create_token'), 'create');
    foreach ($ffiii_trx['content']['transactions'] as $key => $transaction) {
        firefly_iii_trx_create($transaction, $ffiii_trx['content']['id']);
    }

}
function firefly_iii_get_cid($source_id) {
    global $db_connect;
    // Get CID
    $esc_source_id = mysqli_real_escape_string($db_connect, $source_id);
    $sql = "SELECT `cid` FROM `firefly_iii_users` WHERE `ffiii_uid`=$esc_source_id";
    $res = mysqli_query($db_connect, $sql);
    if (!$res) trigger_error(mysqli_error($db_connect));
    $row = mysqli_fetch_assoc($res) ?: false;
    if ($row === false) return false;
    return $row['cid'];
}

function firefly_iii_trx_create($transaction, $trx_id) {
    global $db_connect;
    if ($transaction['category_name'] != variable_get('ffiii_membership_category', 'Membership')) return;
    $cid = firefly_iii_get_cid($transaction['source_id']);
    // Check for existing transaction
    $sql = "SELECT `pmtid` FROM payment
            JOIN firefly_iii_transactions USING(`pmtid`)
            WHERE `ffiii_tr_id`={$transaction['transaction_journal_id']}";
    $res = mysqli_query($db_connect, $sql);
    if (mysqli_num_rows($res) > 0) return;
    // Locate matching transaction
    $pmtid = false;
    if (variable_get('ffiii_match_trx')) {
        $pmtid = firefly_iii_match_transaction($transaction);
    }
    // Create transaction
    $amount = $transaction['amount']*100;
    $url = variable_get('ffiii_url');
    $notes = "<a href=\"$url/transactions/show/$trx_id\" target=\"_blank\">Firefly III transaction $trx_id</a>";
    // skip if matched transaction
    mysqli_begin_transaction($db_connect);
    if ($pmtid == false) {
        $sql = "INSERT INTO `payment` (`date`, `description`, `code`, `value`, `credit`, `debit`, `method`, `confirmation`, `notes`)
                VALUES ('{$transaction['date']}', '{$transaction['category_name']}',
                '{$transaction['currency_code']}', {$amount}, {$cid}, 0, '{$transaction['destination_name']}', '', '$notes');
                ";
        $res = mysqli_query($db_connect, $sql);
        $pmtid = mysqli_insert_id($db_connect);
    } else {
        // Add note
        $sql = "UPDATE `payment`
                SET `notes`='$notes', `method`='{$transaction['destination_name']}'
                WHERE `pmtid`=$pmtid";
        $res = mysqli_query($db_connect, $sql);
    }
    $sql="INSERT INTO firefly_iii_transactions (`pmtid`, `ffiii_tr_id`)
            VALUES($pmtid, {$transaction['transaction_journal_id']})";
    $res = mysqli_query($db_connect, $sql);
    mysqli_commit($db_connect);
    if (!$res) trigger_error(mysqli_error($db_connect));
}

function firefly_iii_trx_update($transaction) {
    global $db_connect;
    $cid = firefly_iii_get_cid($transaction['source_id']);
    $amount = $transaction['amount']*100;
    $pmtid = firefly_iii_get_mptid($transaction['transaction_journal_id']);
    $sql = "UPDATE `payment`
            SET `value`={$amount}, `credit`={$cid}, `method`='{$transaction['destination_name']}'
            WHERE `pmtid`=$pmtid";
    $res = mysqli_query($db_connect, $sql);
}

function firefly_iii_trx_update_service($url_params, $ffiii_trx) {
    global $db_connect;
    $signature = ($_SERVER['HTTP_SIGNATURE'] ?: false);
    firefly_iii_signature_check($signature, variable_get('ffiii_update_token'), 'update');
    foreach ($ffiii_trx['content']['transactions'] as $key => $transaction) {
        $pmtid = firefly_iii_get_mptid($transaction['transaction_journal_id']);
        if ($transaction['category_name'] == 'Membership') {
            if ($pmtid) {
                // Update transaction
                firefly_iii_trx_update($transaction);

            } else {
                // No record, create transaction
                firefly_iii_trx_create($transaction, $ffiii_trx['content']['id']);
            }
        } else {
            $pmtid = firefly_iii_get_mptid($transaction['transaction_journal_id']);
            if ($pmtid) {
                // Not membership, delete transaction
                payment_delete($pmtid);
            }
        }
    }
}

function firefly_iii_trx_delete_service($url_params, $ffiii_trx) {
    global $db_connect;
    $signature = ($_SERVER['HTTP_SIGNATURE'] ?: false);
    firefly_iii_signature_check($signature, variable_get('ffiii_delete_token'), 'delete');
    
    foreach ($ffiii_trx['content']['transactions'] as $key => $transaction) {
        $sql = "DELETE `payment`, `firefly_iii_transactions`
                FROM `firefly_iii_transactions`
                JOIN `payment` USING (`pmtid`)
                WHERE `ffiii_tr_id` = ".$transaction['transaction_journal_id'];
        $res = mysqli_query($db_connect, $sql);
        if (!$res) trigger_error(mysqli_error($db_connect));
    }
}

function firefly_iii_signature_check($signature, $secret, $op) {
    if ($signature === false) {
        header('Status: 400 Bad Request');
        echo 'No valid "'.$op.'" signature in header';
        exit;
    }

    $entityBody = file_get_contents('php://input');

    /**
     * Explode the signature header to get the necessary data.
     *
     * I know this is terrible code but I'm lazy like that. I'm fairly sure there exists a PHP function like explode_key_value_pairs().
     */
    $parts = explode(',', $signature);
    $result = [];
    foreach ($parts as $part) {
        list($key, $value) = explode("=", $part);
        $result[$key] = $value;
    }
    $timestamp = $result['t'];
    $signatureHash = $result['v1'];
    if (null === $timestamp || null === $signatureHash) {
        header('Status: 400 Bad Request');
        echo 'Could not extract valid signature from header :(';
        exit;
    }

    if (variable_get('ffiii_skip_signature', false)) return;

    /**
     * Try to recalculate the signature based on the data. Steal this code.
     */
    $payload    = sprintf('%s.%s', $timestamp, $entityBody);
    $calculated = hash_hmac('sha3-256', $payload, $secret, false);
    $valid      = $calculated === $signatureHash;

    if (!$valid) {
        header('Status: 401 Unauthorized');
        echo 'Invalid signature';
        exit;
    }
}

function firefly_iii_contact_delete($cid) {
    global $db_connect;
    $sql = "DELETE FROM `firefly_iii_users`
            WHERE `cid` = $cid";
    $res = mysqli_query($db_connect, $sql);
    if (!$res) crm_error(mysqli_error($db_connect));
}

function firefly_iii_get_mptid($tjid) {
    global $db_connect;
    $sql = "SELECT `pmtid`
            FROM `firefly_iii_transactions`
            WHERE `ffiii_tr_id` = $tjid";
    $res = mysqli_query($db_connect, $sql);
    if (!$res) trigger_error(mysqli_error($db_connect));
    if (mysqli_num_rows($res) == 0) return false;
    $row = mysqli_fetch_assoc($res);
    return $row['pmtid'];
}

/**
 * Attempt to match existing transaction based on cid, date and amount
 */
function firefly_iii_match_transaction($transaction) {
    global $db_connect;
    // Check if transaction is matched in CRM
    $cid = firefly_iii_get_cid($transaction['source_id']);
    $amount = $transaction['amount'] * 100;
    $date = explode('T', $transaction['date'])[0];
    $sql="SELECT `pmtid`
          FROM `payment`
          LEFT JOIN firefly_iii_transactions USING(pmtid)
          WHERE `date`='$date'
          AND `value`=$amount
          AND `credit`=$cid
          AND ffiii_tr_id IS NULL
          LIMIT 1
          ";
    $res = mysqli_query($db_connect, $sql);
    if (!$res) trigger_error(mysqli_error($db_connect));
    if (mysqli_num_rows($res) == 0) return false;
    $row = mysqli_fetch_assoc($res);
    return $row['pmtid'];
}
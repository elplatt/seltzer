<p>
    <?php
    global $config_address1;
    global $config_address2;
    global $config_address3;
    global $config_town_city;
    global $config_zipcode;
    global $config_org_name;
    global $config_org_website;
    global $config_email_from;
    
    print 'Welcome to ' . "$config_org_name" . '!';
    print '<br /><br />';
    
    print 'You are receiving this email because you have recently been entered into the ' . "$config_org_name" . ' membership management system, Seltzer.';
    print '<br /><br />';
    
    print 'Your username is ' . "$username" . '. To confirm your email and set your password, visit <a href="' . "$confirm_url". '">' . "$confirm_url" . '</a>.';
    print '<br /><br />';
    
    print 'You may manage your contact info at: <a href="http://' . "$hostname$base_path" . '">http://' . "$hostname$base_path" . '</a>';
    print '<br /><br />';
    
    print 'Please ensure the information we have on file for you is complete and accurate.';
    print '<br /><br />';
    
    if((!empty($config_address1)) && (!empty($config_address2)) && (!empty($config_address3)) && (!empty($config_town_city)) && (!empty($config_zipcode))) {
        print 'Our address is ' . "$config_address1" . ", " . "$config_address2" . ", " . "$config_address3" . ", " . "$config_town_city" . ", " . "$config_zipcode";
    }
    print '<br /><br />';
    
    print 'If you have any additional questions, please contact: <a href="mailto:' . "$config_email_from" . '">' . "$config_email_from" . '</a> or visit the website at <a href="' . "$config_org_website" . '">' . "$config_org_website";
    ?>
</p>

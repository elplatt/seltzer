<p>Welcome to <?php global $config_org_name; print "$config_org_name"; ?>!</p>
<p>
    You are receiving this email because you have recently been entered into the <?php print "$config_org_name"; ?> membership management system, Seltzer.
    <br />
    <br />
    Your username is <?php print "$username"; ?>.  To confirm your email and set your password, visit <a href="<?php print "$confirm_url"; ?>"><?php print "$confirm_url"; ?></a>.
    <br />
    You may manage your contact info at:
    <a href="<?php print "http://$hostname$base_path"; ?>"><?php print "http://$hostname$base_path"; ?></a>
    <br/>
    <br/>
    Please ensure the information we have on file for you is complete and accurate.
    <br />
    <br />
    <?php
    global $config_address1;
    global $config_address2;
    global $config_address3;
    global $config_town_city;
    global $config_zipcode;
    if((!empty($config_address1)) && (!empty($config_address2)) && (!empty($config_address3)) && (!empty($config_town_city)) && (!empty($config_zipcode))) {
        print 'Our address is ' . "$config_address1" . ", " . "$config_address2" . ", " . "$config_address3" . ", " . "$config_town_city" . ", " . "$config_zipcode";
    }
    ?>
    <br />
    <br />
    If you have any additional questions, please contact: <?php global $config_email_from; print "$config_email_from"; ?> or visit the website at <?php global $config_org_website; print "$config_org_website"; ?>
</p>

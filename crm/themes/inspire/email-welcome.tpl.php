<p>
    <?php
    print '<html>';
    print 'Welcome to ' . get_org_name() . '!';
    print '<br /><br />';
    print 'You are receiving this email because you have recently been entered into the ' . get_org_name() . ' membership management system, ' . title() . '.';
    print '<br /><br />';
    print 'Your username is ' . "$username" . '. To confirm your email and set your password, visit <a href="' . "$confirm_url". '">' . "$confirm_url" . '</a>.';
    print '<br /><br />';
    print 'You may manage your contact info at: <a href="' . protocol_security() . '://' . get_host() . base_path() . '">' . protocol_security() . '://' . get_host() . base_path() . '</a>';
    print '<br /><br />';
    print 'Please ensure the information we have on file for you is complete and accurate.';
    print '<br /><br />';
    if((!empty(get_address1())) && (!empty(get_address2())) && (!empty(get_address3())) && (!empty(get_town_city())) && (!empty(get_zipcode()))) {
        print 'Our address is ' . get_address1() . ", " . get_address2() . ", " . get_address3() . ", " . get_town_city() . ", " . get_zipcode();
    }
    print '<br /><br />';
    print 'If you have any additional questions, please contact: <a href="mailto:' . get_email_from() . '">' . get_email_from() . '</a> or visit the website at <a href="' . protocol_security() . '://' . get_org_website() . '">' . protocol_security() . '://' . get_org_website();
    print '</html>';
    ?>
</p>

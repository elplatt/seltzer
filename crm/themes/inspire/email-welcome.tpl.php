<p>Welcome to <?php print $org_name; ?>.</p>
<p>
    Your username is <?php print $username; ?>.  To confirm your email and set your password, visit <a href="<?php print $confirm_url; ?>"><?php print $confirm_url; ?></a>.
</p>
<p>
    You may manage your contact info at:
    <a href="<?php print "http://$hostname$base_path"; ?>"><?php print "http://$hostname$base_path"; ?></a>
</p>

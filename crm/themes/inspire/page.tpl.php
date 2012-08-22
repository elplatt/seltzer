<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <link rel="stylesheet" type="text/css" href="style.css"/>
    <link rel="stylesheet" type="text/css" href="css/ui-lightness/jquery-ui-1.8.14.custom.css"/>
    <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>
    <script type="text/javascript" src="js/jquery-ui-1.8.14.custom.min.js"></script>
    <script type="text/javascript" src="script.js"></script>
    <title><?php print $title; ?></title>
</head>
<body>
    <div class="container">
        <div class="header">
            <?php print $header; ?>
        </div>
        <div class="messages">
            <?php print $errors; ?>
            <?php print $messages; ?>
        </div>
        <div class="content">
            <?php print $content; ?>
        </div>
        <div class="footer">
            <?php print $footer; ?>
        </div>
    </div>
</body>
</html>

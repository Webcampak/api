<!-- src/AppBundle/Resources/views/Security/Desktoprot.html.php -->
<?php
$lang="en_US.utf8";
if ($app->getEnvironment() == 'dev') {
    $senchaAppPath = "../extjs/Desktop/";
} elseif ($app->getEnvironment() == 'preprod' || $app->getEnvironment() == 'preprod') {
    $senchaAppPath = "../extjs/build/testing/WpakD/";
} else {
    $senchaAppPath = "../extjs/build/production/WpakD/";
}
?>
<!DOCTYPE HTML>
<html>
<head>
    <meta charset="UTF-8">
    <title>Webcampak 3.0 Desktop</title>

    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">

    <?php /* Loading Jquery library */ ?>
    <script type="text/javascript" src="../resources/lib/js/jquery/jquery-2.1.1.min.js"></script>
    <script>var $j = jQuery.noConflict();</script>

    <?php /* Loading JED related components (translation) */ ?>
    <script>var json_locale_data = {"": {"Content-Type":" text/plain; charset=UTF-8"},"Empty":[null,"Empty"]};</script>
    <script type="text/javascript" src="../../../resources/lib/js/jed/jed.js"></script>
    <script>var i18n = new Jed({locale_data : {"messages" : json_locale_data},"domain" : "messages"});</script>

    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css">

    <?php /* Setting up global sencha Environment variables */ ?>
    <?php if ($app->getEnvironment() == 'dev' || $app->getEnvironment() == 'preprod' || $app->getEnvironment() == 'testing') { ?>
        <script>var symfonyEnv = 'app_<?php echo $app->getEnvironment() ?>.php';</script>
    <?php } else { ?>
        <script>var symfonyEnv = 'app.php';</script>
    <?php } ?>

    <?php /* Loading Sencha Framework components */ ?>
    <?php if ($app->getEnvironment() == 'dev') { ?>
       <!-- <x-compile> -->
           <!-- <x-bootstrap> -->
               <link rel="stylesheet" href="<?php echo $senchaAppPath; ?>bootstrap.css">
               <!--<script src="../ext/build/bootstrap.js"></script> -->
               <script type="text/javascript" src="<?php echo $senchaAppPath; ?>bootstrap.js">
               <script src="<?php echo $senchaAppPath; ?>bootstrap.js"></script>
           <!-- </x-bootstrap> -->
           <script src="<?php echo $senchaAppPath; ?>app.js"></script>
       <!-- </x-compile> -->
    <?php } else { ?>
        <script type="text/javascript" src="<?php echo $senchaAppPath; ?>app.js?<?php echo $currentBuild ?>"></script>
    <?php } ?>

    <script type="text/javascript" src="api/methods/desktop"></script>
</head>
<body></body>
</html>

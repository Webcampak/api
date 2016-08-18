<?php
$lang="en_US.utf8";
if ($app->getEnvironment() == 'dev') {
    $senchaAppPath = "../extjs/Dashboard/";
} elseif ($app->getEnvironment() == 'preprod' || $app->getEnvironment() == 'test') {
    $senchaAppPath = "../extjs/build/testing/WPAKT/";
} else {
    $senchaAppPath = "../extjs/build/production/WPAKT/";
}
?>
<!DOCTYPE HTML>
<html>
<head>
    <meta charset="UTF-8">
    <title>Webcampak 3.0 Dashboard</title>

    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">

    <?php /* Loading Jquery library */ ?>
    <script type="text/javascript" src="../resources/lib/js/jquery/jquery-2.1.1.min.js"></script>
    <script>var $j = jQuery.noConflict();</script>

    <?php /* Loading JED related components (translation) */ ?>
    <script>var json_locale_data = {"": {"Content-Type":" text/plain; charset=UTF-8"},"Empty":[null,"Empty"]};</script>
    <script type="text/javascript" src="../../../resources/lib/js/jed/jed.js"></script>
    <script type="text/javascript" src="../locale/<?php echo $language; ?>/LC_MESSAGES/webcampak.js"></script>
    <script>var i18n = new Jed({locale_data : {"messages" : json_locale_data},"domain" : "messages"});</script>

    <?php /* Loading DateFormat library */ ?>
    <script type="text/javascript" src="../resources/lib/js/dateformat/date.format.js"></script>

    <?php /* Loading jqzoom library */ ?>
    <link rel="stylesheet" type="text/css" href="../resources/lib/js/jqzoom_ev-2.3/css/jquery.jqzoom.css" />
    <script type='text/javascript' src='../resources/lib/js/jqzoom_ev-2.3/js/jquery.jqzoom-core.js'></script>

    <?php /* Loading flowplayer library */ ?>
    <link rel="stylesheet" type="text/css" href="../resources/lib/js/flowplayer-6.0.3/skin/functional.css" />
    <script type='text/javascript' src='../resources/lib/js/flowplayer-6.0.3/flowplayer.min.js'></script>

    <link rel="stylesheet" href="../resources/lib/css/font-awesome-4.4.0/css/font-awesome.min.css">
    
    <script type="text/javascript" src="api/methods/desktop"></script>        
    
    <?php /* Setting up global sencha Environment variables */ ?>
    <?php if ($app->getEnvironment() == 'dev' || $app->getEnvironment() == 'preprod' || $app->getEnvironment() == 'test') { ?>
        <script>var symfonyEnv = 'dashboard_<?php echo $app->getEnvironment() ?>.php';</script>
    <?php } else { ?>
        <script>var symfonyEnv = 'dashboard.php';</script>
    <?php } ?>

    <!--
    <script type="text/javascript">
        var Ext = Ext || {}; // Ext namespace won't be defined yet...

        // This function is called by the Microloader after it has performed basic
        // device detection. The results are provided in the "tags" object. You can
        // use these tags here or even add custom tags. These can be used by platform
        // filters in your manifest or by platformConfig expressions in your app.
        //
        Ext.beforeLoad = function (tags) {
            var s = location.search,  // the query string (ex "?foo=1&bar")
                profile;

            // For testing look for "?classic" or "?modern" in the URL to override
            // device detection default.
            //
            if (s.match(/\bclassic\b/)) {
                profile = 'classic';
            }
            else if (s.match(/\bmodern\b/)) {
                profile = 'modern';
            }
            else {
                profile = tags.desktop ? 'classic' : 'modern';
                //profile = tags.phone ? 'modern' : 'classic';
            }

            Ext.manifest = profile; // this name must match a build profile name

            // This function is called once the manifest is available but before
            // any data is pulled from it.
            //
            //return function (manifest) {
                // peek at / modify the manifest object
            //};
        };
    </script>
    -->      
        
    <?php /* Loading Sencha Framework components */ ?>
    <?php if ($app->getEnvironment() == 'dev') { ?>
        <!-- The line below must be kept intact for Sencha Cmd to build your application -->
        <script id="microloader" data-app="7a090660-458e-451c-bf27-bff579f6b88e" type="text/javascript" src="<?php echo $senchaAppPath; ?>bootstrap.js"></script>
    <?php } else { ?>
        <link rel="stylesheet" href="<?php echo $senchaAppPath; ?>resources/WPAKT-all.css"/>
        <script id="microloader" src="<?php echo $senchaAppPath; ?>microloader.js"></script>        
    <?php } ?>

</head>
<body></body>
</html>

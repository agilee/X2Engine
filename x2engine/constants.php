<?php
if(file_exists($customContstants = __DIR__.DIRECTORY_SEPARATOR.'constants-custom.php'))
    require_once $customContstants;



// remove the following lines when in production mode
defined('YII_DEBUG') or define('YII_DEBUG',false);

if (!YII_DEBUG) {
    assert_options (ASSERT_ACTIVE, false);
}

// To view pages according to how they'd look in a given edition, set YII_DEBUG
// to true and PRO_VERSION to:
// 0 for opensource
// 1 for pro
// 2 for pla (superset)
defined('PRO_VERSION') or define('PRO_VERSION',0);

// specify how many levels of call stack should be shown in each log message
defined('YII_TRACE_LEVEL') or define('YII_TRACE_LEVEL',3);

// Enable all logging or bare minimum logging:
defined('YII_LOGGING') or define('YII_LOGGING',true);

// If true, adds debug toolbar route to array of debug log routes
defined('YII_DEBUG_TOOLBAR') or define('YII_DEBUG_TOOLBAR',false);

// Indicates that the application is being run as part of a unit test. 
defined('YII_UNIT_TESTING') or define('YII_UNIT_TESTING',false);

// ID of the default admin user
defined('X2_PRIMARY_ADMIN_ID') or define('X2_PRIMARY_ADMIN_ID',1);

?>

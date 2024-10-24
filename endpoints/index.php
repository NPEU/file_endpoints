<?php

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Event\Dispatcher as EventDispatcher;

require_once __DIR__ . '/vendor/autoload.php';

#echo "<pre>\n"; var_dump($_GET); echo "</pre>\n";
#echo "<pre>\n"; var_dump($_SERVER); echo "</pre>\n"; exit;
#echo "<pre>\n"; var_dump($_COOKIE); echo "</pre>\n";
switch ($_SERVER['SERVER_NAME']) {
    case 'dev.npeu.ox.ac.uk':
        $application_env = 'development';
        break;
    case 'test.npeu.ox.ac.uk':
    case 'sandbox.npeu.ox.ac.uk':
    case 'next.npeu.ox.ac.uk':
        $application_env = 'testing';
        break;
    default:
        $application_env = 'production';
}

$application_domain = str_replace('.npeu.ox.ac.uk', '', $_SERVER['SERVER_NAME']);

if ($application_env == 'development') {
    @define('DEV', true);
    ini_set('display_errors', 'on');
} else {
    @define('DEV', false);
}

if ($application_env == 'testing') {
    error_reporting(E_ALL ^ E_DEPRECATED);
    @define('TEST', true);
} else {
    @define('TEST', false);
}


#require_once __DIR__ . '/vendor/autoload.php';

//session_start();

// Note the following file contains database passwords and is .gitignored in the repo.
// It's PARAMOUNT that this file does not find it's way outside the server.
#require_once('_settings.php');

$params = [];

// Set up Joomla User stuff:
define('DS', DIRECTORY_SEPARATOR);
$base_path = realpath(dirname(__DIR__));
define('BASE_PATH', $base_path . DS);
#echo "<pre>"; var_dump(BASE_PATH); echo "</pre>"; #exit;
//define( 'JDATE', 'Y-m-d H:i:s A' );
//define( '_JEXEC', 1 );


switch ($application_domain) {
    case 'dev':
    case 'test':
    case 'sandbox':
    case 'next':
        //define( 'JPATH_BASE', BASE_PATH . 'jan_' . $application_domain . DS .'public' );
        define( 'TOP_DOMAIN', 'https://' . $_SERVER['SERVER_NAME']);
        define( 'JDB', 'jan_' . $application_domain);
        break;
    default:
        //define( 'JPATH_BASE', BASE_PATH . 'jan' . DS .'public' );
        define( 'TOP_DOMAIN', 'https://www.npeu.ox.ac.uk' );
        define( 'JDB', 'jan' );
}

define('_JEXEC', 1);

//If this file is not placed in the /root directory of a Joomla instance put the directory for Joomla libraries here.
$joomla_directory = BASE_PATH;

// From https://joomla.stackexchange.com/questions/33140/how-to-create-an-instance-of-the-joomla-cms-from-the-browser-or-the-command-line
// Via: https://joomla.stackexchange.com/questions/33389/standalone-php-script-to-get-username-in-joomla-4
/**---------------------------------------------------------------------------------
 * Part 1 - Load the Framework and set up up the environment properties
 * -------------------------------------------------------------------------------*/

/**
 *  Site - Front end application when called from Browser via URL.
*/                                                  // Remove this '*/' to comment out this block
define('JPATH_BASE', (isset($joomla_directory)) ? $joomla_directory : __DIR__ );
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';
$class_name             =  new \Joomla\CMS\Application\SiteApplication;
$session_alias          = 'session.web';
$session_suffix         = 'web.site';
/** end Site config */

/**---------------------------------------------------------------------------------
 * Part 2 - Start the application from the container ready to be used.
 * -------------------------------------------------------------------------------*/
// Boot the DI container
$container = \Joomla\CMS\Factory::getContainer();

// Alias the session service key to the web session service.
$container->alias($session_alias, 'session.' . $session_suffix)
          ->alias('JSession', 'session.' . $session_suffix)
          ->alias(\Joomla\CMS\Session\Session::class, 'session.' . $session_suffix)
          ->alias(\Joomla\Session\Session::class, 'session.' . $session_suffix)
          ->alias(\Joomla\Session\SessionInterface::class, 'session.' . $session_suffix);

// Instantiate the application.
$app = $container->get($class_name::class);
// Set the application as global app
Factory::$application = $app;

#echo "<pre>"; var_dump(get_class_methods($app)); echo "</pre>"; exit;


$session = Factory::getSession();
$user = Factory::getUser();
$user = Factory::getApplication()->getIdentity();

if (array_key_exists(10, $user->groups)) {
    $params['is_staff'] = true;
}

//
#echo "<pre>"; var_dump($user); echo "</pre>"; exit;
/*
#$json = json_encode(array('staff' => $is_staff_member));
$json = json_encode($user);
#$json = json_encode($_COOKIE);
#$json = json_encode($_SESSION);
#$json = json_encode(array('test', 'ing'));

header('Access-Control-Allow-Origin: *');
header('Content-type: application/json');
echo $json;
exit;
*/
//

/*
set_include_path(implode(PATH_SEPARATOR, array(
    'EndpointsService',
    get_include_path(),
    )));
spl_autoload_register(function($class) {
        @include str_replace('_', '/', $class) . '.php';
    }
);
*/
require_once 'server_vars.php';
#require_once 'EndpointHelpers.php';

require_once '../detect_server.php';

$pathinfo = pathinfo($_SERVER['SCRIPT_NAME']);
$path = trim($pathinfo['dirname'], '/');
$classname = trim(str_replace($path, '', $_SERVER['SCRIPT_URL']), '/');
$classname = preg_replace('/[^a-z0-9-]/', '', $classname);
$classname = ucwords(preg_replace('/-/', ' ', $classname));
$classname = trim(preg_replace('/\s/', '', $classname), '/');
$classname = '\\EndpointService\\' . $classname . '\\' . $classname;
#echo "<pre>"; var_dump( $classname ); echo "</pre>"; exit;

/* LOG ---------------------------------*/
/*
$log_host     = 'localhost';
$log_database = 'endpoints_service_log';

if (DEV) {
    $log_database = 'endpoints_service_log_dev';
}
if (TEST) {
    $log_database = 'endpoints_service_log_test';
}

$log_username = NPEU_DATABASE_USR;
$log_password = NPEU_DATABASE_PWD;

$log_db = new PDO("mysql:host=$log_host;dbname=$log_database", $log_username, $log_password, array(
    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8;'
));

$date           = $log_db->quote(date('c'));
$timestamp      = time();
$user_agent     = isset($_SERVER['HTTP_USER_AGENT']) ? $log_db->quote($_SERVER['HTTP_USER_AGENT']) : '""';
$remote_address = isset($_SERVER['REMOTE_ADDR']) ? $log_db->quote($_SERVER['REMOTE_ADDR']) : '""';
$request_uri    = isset($_SERVER['REQUEST_URI']) ? $log_db->quote($_SERVER['REQUEST_URI']) : '""';
$request_method = isset($_SERVER['REQUEST_METHOD']) ? $log_db->quote($_SERVER['REQUEST_METHOD']) : '""';
$post_data      = isset($_POST) ? $log_db->quote(stripslashes(json_encode($_POST))) : '""';
$post_body      = isset($_POST) ? $log_db->quote(file_get_contents('php://input')) : '""';

#echo "Body<pre>"; var_dump( $post_body ); echo "</pre>"; exit;

$sql = "INSERT INTO `log` (`date`,`timestamp`,`user_agent`,`remote_address`,`request_uri`,`request_method`,`post_data`,`post_body`) "
     . "VALUES ($date,$timestamp,$user_agent,$remote_address,$request_uri,$request_method,$post_data,$post_body);";
$log_db->exec($sql);
*/
/*-------------------------------------*/
#echo "<pre>"; var_dump($classname); echo "</pre>"; exit;

$service = new $classname();
#echo "<pre>"; var_dump($service); echo "</pre>"; exit;
$r = $service->run();

if (!$r) {
   echo '<h1>Error</h1><p>Something went wrong - we couldn\'t complete that request.</p>';
}

exit;

?>
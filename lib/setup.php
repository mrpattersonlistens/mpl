<?php
/**
 * Various set-up operations
 */
define('INTERNAL_SCRIPT', true);

unset($CFG);
global $CFG;
$CFG = new stdClass();
$CFG->dirroot    = dirname(dirname(__FILE__));
$CFG->wwwroot    = 'https://mpl-bmizepatterson.c9users.io';
$CFG->dataroot   = '/home/ubuntu/mpldata';
$CFG->postroot   = $CFG->wwwroot.'/posts';
$CFG->prefix     = 'mrpatt5_';
$CFG->dbhost     = 'localhost';
$CFG->dbname     = $CFG->prefix.'mpl';
$CFG->dbuser     = $CFG->prefix.'mpl_user';
$CFG->dbpass     = 'earsandheart';

// File permissions on created directories in the $CFG->dataroot
$CFG->directorypermissions = 02777;
if (!isset($CFG->filepermissions)) {
    $CFG->filepermissions = ($CFG->directorypermissions & 0666); // strip execute flags
}
if (!isset($CFG->umaskpermissions)) {
    $CFG->umaskpermissions = (($CFG->directorypermissions & 0777) ^ 0777);
}
umask($CFG->umaskpermissions);
if (!is_writable($CFG->dataroot)) {
    if (isset($_SERVER['REMOTE_ADDR'])) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 503 Service Unavailable');
    }
    echo('Fatal error: $CFG->dataroot is not writable, admin has to fix directory permissions! Exiting.'."\n");
    exit(1);
}

$CFG->sslproxy = true; // Cloud9 appears to tunnel an http connection through their own TLS cert, and forces the internal connection to use HTTP
$CFG->libdir = $CFG->dirroot .'/lib';

// sometimes default PHP settings are borked on shared hosting servers, I wonder why they have to do that??
ini_set('precision', 14);
ini_set('serialize_precision', 17); // Make float serialization consistent on all systems.

// Store settings from config.php in array in $CFG - we can use it later to detect problems and overrides.
if (!isset($CFG->config_php_settings)) {
    $CFG->config_php_settings = (array)$CFG;
}

// Debug settings
define('DEBUG_NONE', 0);                                            /** No warnings and errors at all */
define('DEBUG_MINIMAL', E_ERROR | E_PARSE);                         /** Fatal errors only */
define('DEBUG_NORMAL', E_ERROR | E_PARSE | E_WARNING | E_NOTICE);   /** Errors, warnings and notices */
define('DEBUG_ALL', E_ALL & ~E_STRICT);                             /** All problems except strict PHP warnings */
define('DEBUG_DEVELOPER', E_ALL | E_STRICT);                        /** DEBUG_ALL with all debug messages and strict warnings */
$CFG->debug = DEBUG_DEVELOPER;
$CFG->debugdisplay = true;
$CFG->debugdeveloper = (($CFG->debug & DEBUG_DEVELOPER) === DEBUG_DEVELOPER);
$CFG->debugvalidators = false;

// Define globals
global $DB;
global $OUTPUT;
global $PAGE;

require_once($CFG->libdir.'/setuplib.php');

$OUTPUT = new early_renderer();

// set handler for uncaught exceptions
set_exception_handler('default_exception_handler');
set_error_handler('default_error_handler', E_ALL | E_STRICT);

// Initialize performance info
init_performance_info();

// Load libraries
require_once($CFG->libdir.'/dblib.php');
require_once($CFG->libdir.'/mpllib.php');
require_once($CFG->libdir.'/outputlib.php');
require_once($CFG->libdir.'/pagelib.php');
require_once($CFG->libdir.'/weblib.php');

// Initialize database connection and the global $PAGE variable
$DB = new database_wrapper($CFG->dbhost, $CFG->dbname, $CFG->dbuser, $CFG->dbpass);
$PAGE = new mpl_page();

// enable circular reference collector
gc_enable();

$CFG->firstpost = $CFG->postroot.'/begin/';
$CFG->lastpost = $CFG->postroot.'/peccatum-illud-horribile/';
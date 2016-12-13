<?php

/*
 * SAF - Siege Application Framework
 * 
 * startup include
 *
 * by: CJ Niemira <siege (at) siege (dot) org>
 * (c) 2005
 * http://siege.org/projects/saf
 *
 * This code is licensed under the GNU General Public License
 * http://www.gnu.org/licenses/gpl.html
 */
 
 
// Setup some default headers
header("Expires: Sat, 01 Jan 2000 00:00:01 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Make sure my includes are going to work
ini_set('short_open_tag', 'On');

// Create the application object
set_include_path(dirname(__FILE__) . PATH_SEPARATOR . get_include_path());
require_once('safApplication.class.php');
$app = new safApplication;
header('X-Powered-By: ' . $app->signature());

// Include extra libraries
include(dirname(__FILE__) . '/extra/defines.lib.php');
include(dirname(__FILE__) . '/extra/functions.lib.php');

if (version_compare(phpversion(), '5.0.0', 'lt'))
	include(dirname(__FILE__) . '/extra/forward500.lib.php');

?>

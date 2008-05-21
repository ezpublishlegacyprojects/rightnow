<?php
/**
 * File module.php
 *
 * @package rightnow
 * @version //autogentag//
 * @copyright Copyright (C) 2007 xrow. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl.txt GPL License
 */
$Module = array( "name" => "RightNow",
                 "variable_params" => true,
                 "function" => array(
                 "script" => "logout.php",
                 "params" => array( ) ) );

$ViewList = array();
$ViewList['logout'] = array(
	'script' => 'logout.php',
	'default_navigation_part' => 'ezrightnow',
	"params" => array( ),
	"unordered_params" => array(  ) );
$ViewList['test'] = array(
	'script' => 'test.php',
	'functions' => array( 'debug' ),
	'default_navigation_part' => 'ezrightnow',
	'single_post_actions' => array( 'Cancel' => 'Cancel' ),
	'post_action_parameters' => array( 'Cancel' => array(  ) ),
	"params" => array( ),
	"unordered_params" => array(  ) );


$FunctionList['debug'] = array();

?>

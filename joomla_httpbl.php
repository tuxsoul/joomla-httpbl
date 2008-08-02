<?php
/*
*
* @package Joomla-Http:BL
* @copyright Copyright (C) 2008 Mario Oyorzabal Salgado
* @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
*/

// no direct access
defined( '_VALID_MOS' ) or die( 'Restricted access' );

// register function
$_MAMBOTS->registerFunction( 'onAfterStart', 'joomlahttpbl_bot' );

// joomla-httpbl_bot to redirect visitor, if this is evil :-)
function joomlahttpbl_bot () {
	global $params, $database;

	// search joomla-httpbl mambot
	$query = "SELECT id FROM #__mambots WHERE element = 'joomla_httpbl' AND folder = 'system'";
	$database->setQuery( $query );
	$id = $database->loadResult();

	// some error in request to database ?
	if( $database->getErrorNum() ) {
		return false;
	}

	// load joomla-httpbl mambot parameters
	$mambot = new mosMambot( $database );
	$mambot->load( $id );
	$mambotParams =& new mosParameters( $mambot->params );

	// load key, days activity and quicklink to joomla-httpbl
	$config_key       = $mambotParams->get( 'httpbl_key' );
	$config_quicklink = $mambotParams->get( 'httpbl_quicklink' );
	$config_days      = $mambotParams->get( 'httpbl_days' );

	$config_test      = $mambotParams->get( 'httpbl_test' );

	// have key ?
	if( !isset( $config_key ) || strlen( $config_key ) != 12 ) {
		return false;
	}

	// get ip visitor
	$ip = $_SERVER['REMOTE_ADDR'];

	// only for test
	if( (int)$config_test == 1) {
		$ip = '127.1.1.1';
	}

	// prepare data to search ip visitor in project honey pot
	$ip_reverse = implode( '.', array_reverse( explode( '.', $ip ) ) );
	$dnsbl = $config_key . '.' . $ip_reverse . '.dnsbl.httpbl.org';

	// search ip visitor in project honey pot
	$response = gethostbyname( $dnsbl );

	if( $response != $dnsbl ) {
		$result = explode( '.', $response );

		// is query ok
		if( (int)$result[0] == 127 ) {
			// is spammer ?
			if( (int)$result[3] > 0 ) {
				// last activity spammer
				if( (int)$result[1] <= (int)$config_days ) {
					// go to the trap
					header( 'Location:' . $config_quicklink );
					die();
				}
			}
		}
	}

	// all ok
	return true;	
}

?>

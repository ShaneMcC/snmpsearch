<?php

	/**
	 * Array of switches to check if none specified.
	 *
	 * Format for each line is: <switch name/ip>[=community string].
	 *
	 * If no community string is specified, then the value of
	 * $defaultcommunity is assumed.
	 */
	$defaultswitches = array('10.0.0.1=public',
	                         '10.0.0.2=public',
	                         '10.0.0.3',
	                         '10.0.0.4'
	                        );

	/**
	 * Default community string to use if one is not specified.
	 */
	$defaultcommunity = 'private';

	// Load in local config if it exists.
	if (file_exists(dirname(__FILE__) . '/config.local.php')) {
		require_once(dirname(__FILE__) . '/config.local.php');
	}

	if (!function_exists('findExtraSwitches')) {
		/**
		 * This function allows site-specific parsing of a MAC search result
		 * to see if there are other switches that should be looked at based on
		 * the ports that can see the mac.
		 *
		 * @param $result The result array from SNMPSwitch's findMac method.
		 * @return FALSE if there is nowhere else to look, else an array of
		 *         switches in the same format as $defaultswitches.
		 */
		function findExtraSwitches($result) {
			return FALSE;
		}
	}
?>

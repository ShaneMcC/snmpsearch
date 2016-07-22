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
	 * Array of routers to check for mapping IP address to MAC Address.
	 *
	 * Format for each line is: <router name/ip>[=community string].
	 *
	 * If no community string is specified, then the value of
	 * $defaultcommunity is assumed.
	 *
	 * If this array is empty, IP to MAC mapping is not enabled.
	 */
	$defaultrouters = array('defaultrouter@10.0.0.1=public',
	                       );

	/**
	 * Default community string to use if one is not specified.
	 */
	$defaultcommunity = 'private';

	/**
	 * If enabled, web search will ignore any further switches in it's list
	 * after finding a port that has an extra switch attached to it.
	 *
	 * This works best if the STP Root is the first switch in the list, and
	 * prevents un-needed searches on other default switches.
	 */
	$cleverFindExtra = false;

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

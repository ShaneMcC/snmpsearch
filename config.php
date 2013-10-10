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
?>

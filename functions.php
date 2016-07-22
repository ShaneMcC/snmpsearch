<?php
	require_once(dirname(__FILE__) . '/config.php');
	require_once(dirname(__FILE__) . '/classes/SNMPSwitch.php');

	function ip2mac($routers, $ip) {
		global $defaultcommunity;

		if (!is_array($routers)) { return; }

		foreach ($routers as $router) {
			$bits = explode('=', $router, 2);
			$router = $bits[0];
			$community = isset($bits[1]) ? $bits[1] : $defaultcommunity;

			$device = new SNMPSwitch($router, $community);
			$result = $device->ip2mac($ip);

			if ($result !== false) {
				return array($router, $result);
			}
		}

		return FALSE;
	}
?>

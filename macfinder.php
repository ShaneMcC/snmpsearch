#!/usr/bin/php
<?php
	require_once(dirname(__FILE__) . '/functions.php');

	if (isset($argv[1])) {
		$mac = $argv[1];
		if (!isValidMac($mac)) {
			die('"' . $mac . '" does not look like a valid MAC Address.'."\n");
		}
		$mac = str_replace(array(':', '-', '.'), '', $mac);
		$mac = join(':', str_split($mac, 2));
	} else {
		echo 'No MAC Address Specified.', "\n";
		echo 'Usage: ', $argv[0], ' <mac address> [switch1[=community]] [switch2[=community]] ... [switch#[=community]]';
		die("\n");
	}

	$switches = array();
	for ($i = 2; $i < count($argv); $i++) {
		$switches[] = $argv[$i];
	}

	if (count($switches) == 0) {
		$switches = $defaultswitches;
	}

	foreach ($switches as $switch) {
		$bits = explode('=', $switch, 2);
		$switch = $bits[0];
		$community = isset($bits[1]) ? $bits[1] : $defaultcommunity;
		print_r(findMacOnSwitch($switch, $community, $mac));
	}
?>

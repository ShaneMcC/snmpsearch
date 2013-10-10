#!/usr/bin/php
<?php
	require_once(dirname(__FILE__) . '/functions.php');

	if (isset($argv[1])) {
		$mac = $argv[1];
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

		$switch = new SNMPSwitch($switch, $community);

		print_r($switch->findMac($mac));
	}
?>

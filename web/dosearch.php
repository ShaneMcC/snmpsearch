<?php
	$mac = $_REQUEST['mac'];

	echo '<h2>Search Results</h2>';

	$switches = $defaultswitches;

	foreach ($switches as $switch) {
		$bits = explode('=', $switch, 2);
		$switch = $bits[0];
		$community = isset($bits[1]) ? $bits[1] : $defaultcommunity;

		$switch = new SNMPSwitch($switch, $community);
		$result = $switch->findMac($mac);

		if ($result === false) {
			echo 'There was an error with the mac address provided.';
			break;
		}

		if ($result['port'] === false) {
			echo '<span class="no">The requested MAC address <strong>', $mac, '</strong> was not found on <strong>', $result['switch']['name'], '</strong></span><br>';
		} else {
			echo '<span class="yes">The requested MAC address <strong>', $mac, '</strong> was found on <strong>', $result['switch']['name'], '</strong> on port <strong>', $result['port']['name'], ' (', $result['port']['calculateddesc'], ')</strong></span><br>';
		}
		/* echo '<pre>';
		var_dump($result);
		echo '</pre>'; */
	}
?>
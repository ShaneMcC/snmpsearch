<?php
	function displayResult($result) {
		if ($result['port'] === false) {
			echo '<span class="no">The requested MAC address <strong>', $result['mac'], '</strong> was not found on <strong>', $result['switch']['name'], '</strong></span><br>';
		} else {
			echo '<span class="yes">The requested MAC address <strong>', $result['mac'], '</strong> was found on <strong>', $result['switch']['name'], '</strong> on port <strong>', $result['port']['name'], ' (', $result['port']['calculateddesc'], ')</strong></span><br>';

			searchSwitches(findExtraSwitches($result), $result['mac']);
		}
	}

	function searchSwitches($switches, $mac) {
		global $defaultcommunity, $searchedSwitches;

		if (!is_array($switches)) { return; }
		if (!isset($searchedSwitches[$mac])) { $searchedSwitches[$mac] = array(); }

		foreach ($switches as $switch) {
			$bits = explode('=', $switch, 2);
			$switch = $bits[0];
			$community = isset($bits[1]) ? $bits[1] : $defaultcommunity;

			if (in_array($switch, $searchedSwitches[$mac])) { continue; } else { $searchedSwitches[$mac][] = $switch; }

			$switch = new SNMPSwitch($switch, $community);
			$result = $switch->findMac($mac);

			if ($result === false) {
				echo 'There was an error with the mac address provided.';
				break;
			}

			displayResult($result);
		}
	}

	echo '<h2>Search Results</h2>';
	searchSwitches($defaultswitches, $_REQUEST['mac']);
?>

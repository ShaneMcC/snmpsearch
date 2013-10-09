<?php
	require_once(dirname(__FILE__) . '/config.php');

	function mactosnmp($mac) {
		$bits = explode(':', $mac);

		foreach ($bits as &$bit) {
			$bit = hexdec($bit);
		}

		return implode('.', $bits);
	}

	function hex2ports($hex) {
		$ports = array();
		$baseport = 0;
		for ($i = 0; $i < strlen($hex); $i += 2) {
			$bit = substr($hex, $i, 2);
			$bin = base_convert($bit, 16, 2);
			foreach (str_split($bin) as $b) {
				if ($b == '1') { $ports[] = $baseport; }
				$baseport++;
			}
		}
		return $ports;
	}

	function getPortDetails($switch, $community, $portid) {
		$oldsnmp = snmp_get_valueretrieval();
		snmp_set_valueretrieval(SNMP_VALUE_PLAIN);
		snmp_set_oid_output_format(SNMP_OID_OUTPUT_NUMERIC);

		$result = array();
		$result['id'] = $portid;
		$result['name'] = @snmpget($switch, $community, '1.3.6.1.2.1.2.2.1.2.'.$portid);
		$result['desc'] = @snmpget($switch, $community, '1.3.6.1.2.1.31.1.1.1.18.'.$portid);
		$result['type'] = @snmpget($switch, $community, '1.3.6.1.2.1.2.2.1.3.'.$portid);

		if ($result['type'] == 161 || $result['type'] == 53 || $result['type'] == 54) {
			// Virtual Ports, Multiplexor ports or LACP Ports.
			// Get any data we can get about them.

			// Lets look for LACP member ports first.
			$result['lacpmembers'] = @snmpget($switch, $community, '1.2.840.10006.300.43.1.1.2.1.1.'.$portid);
			if (empty($result['lacpmembers'])) {
				unset($result['lacpmembers']);
			} else {
				$result['lacpmembers'] = bin2hex($result['lacpmembers']);
				$result['lacpmembers'] = hex2ports($result['lacpmembers']);
				foreach ($result['lacpmembers'] as &$member) {
					$member = getPortDetails($switch, $community, $member);
				}
			}

			// See if we have any stacked ports here...
			$result['stackmembers'] = array();
			$stackwalk = @snmprealwalk($switch, $community, '1.3.6.1.2.1.31.1.2.1.3.'.$portid);
			foreach ($stackwalk as $key => $val) {
				if ($val == 1) {
					$p = str_replace('.1.3.6.1.2.1.31.1.2.1.3.'.$portid.'.', '', $key);
					$result['stackmembers'][] = getPortDetails($switch, $community, $p);
				}
			}
			if (empty($result['stackmembers'])) {
				unset($result['stackmembers']);
			}

			// Cicso switches have yet another way...

		}

		snmp_set_valueretrieval($oldsnmp);
		return $result;
	}

	function getSwitchDetails($switch, $community) {
		$result = array();
		$result['hostname'] = $switch;
		$result['name'] = @snmpget($switch, $community, '1.3.6.1.2.1.1.5.0');
		$result['location'] = @snmpget($switch, $community, '1.3.6.1.2.1.1.6.0');
		return $result;
	}

	function findMacOnSwitch($switch, $community, $mac) {
		$result = array();
		$macsnmp = mactosnmp($mac);
		$oldsnmp = snmp_get_valueretrieval();
		snmp_set_valueretrieval(SNMP_VALUE_PLAIN);
		$result['port'] = @snmpget($switch, $community, '1.3.6.1.2.1.17.4.3.1.2.'.$macsnmp);
		if ($result['port'] !== false) {
			$result['port'] = getPortDetails($switch, $community, $result['port']);
		}

		$result['switch'] = getSwitchDetails($switch, $community);

		snmp_set_valueretrieval($oldsnmp);
		return $result;
	}

	function isValidMac($mac) {
		return preg_match('/([a-fA-F0-9]{2}[:|\-|\.]?){6}/', $mac) || preg_match('/([a-fA-F0-9]{4}[\.]?){6}/', $mac);
	}
?>

<?php

/**
 * Class representing a switch.
 */
class SNMPSwitch {

	/** Host for this switch (address or IP) */
	private $host = '';
	/** Community String for this switch. */
	private $community = 'public';

	/**
	 * Create a new switch.
	 *
	 * @param $host Host for this switch (Address or IP)
	 * @param $community (Default: 'public') Community string for read-access
	 *                   to this switch.
	 */
	function __construct($host, $community = 'public') {
		$this->host = $host;
		$this->community = $community;
	}

	/**
	 * Check if a given mac address looks valid.
	 *
	 * @param $mac mac to check
	 * @return True or False.
	 */
	private function isValidMac($mac) {
		return preg_match('/([a-fA-F0-9]{2}[:|\-|\.]?){6}/', $mac) || preg_match('/([a-fA-F0-9]{4}[\.]?){6}/', $mac);
	}

	/**
	 * Convert a mac address to an snmp identifier.
	 *
	 * @param $mac mac to convert
	 * @return Dot-Separated Decimal representation of the mac address.
	 */
	private function mactosnmp($mac) {
		$bits = explode(':', $mac);

		foreach ($bits as &$bit) {
			$bit = hexdec($bit);
		}

		return implode('.', $bits);
	}

	/**
	 * Convert a hexidecimal port list into an array of port numbers.
	 *
	 * @param $hex Hexidecimal port list.
	 * @return Array of port numbers.
	 */
	private function hex2ports($hex) {
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

	/**
	 * Get details of a given port.
	 *
	 * @param $portid ID Number of port.
	 * @return Array of port information.
	 */
	function getPortDetails($portid) {
		$oldsnmp = snmp_get_valueretrieval();
		snmp_set_valueretrieval(SNMP_VALUE_PLAIN);
		snmp_set_oid_output_format(SNMP_OID_OUTPUT_NUMERIC);

		$result = array();
		$result['id'] = $portid;
		$result['name'] = @snmpget($this->host, $this->community, '1.3.6.1.2.1.2.2.1.2.'.$portid);
		$result['desc'] = @snmpget($this->host, $this->community, '1.3.6.1.2.1.31.1.1.1.18.'.$portid);
		$result['type'] = @snmpget($this->host, $this->community, '1.3.6.1.2.1.2.2.1.3.'.$portid);

		$result['calculateddesc'] = $result['desc'];

		if ($result['type'] == 161 || $result['type'] == 53 || $result['type'] == 54) {
			// Virtual Ports, Multiplexor ports or LACP Ports.
			// Get any data we can get about them.

			// Lets look for LACP member ports first.
			$result['lacpmembers'] = @snmpget($this->host, $this->community, '1.2.840.10006.300.43.1.1.2.1.1.'.$portid);
			if (empty($result['lacpmembers'])) {
				unset($result['lacpmembers']);
			} else {
				$result['lacpmembers'] = bin2hex($result['lacpmembers']);
				$result['lacpmembers'] = $this->hex2ports($result['lacpmembers']);
				foreach ($result['lacpmembers'] as &$member) {
					$member = $this->getPortDetails($member);
					if (empty($result['calculateddesc'])) { $result['calculateddesc'] = $member['desc']; }
				}
			}

			// See if we have any stacked ports here...
			$result['stackmembers'] = array();
			$stackwalk = @snmprealwalk($this->host, $this->community, '1.3.6.1.2.1.31.1.2.1.3.'.$portid);
			foreach ($stackwalk as $key => $val) {
				if ($val == 1) {
					$p = str_replace('.1.3.6.1.2.1.31.1.2.1.3.'.$portid.'.', '', $key);
					$info = $this->getPortDetails($p);
					$result['stackmembers'][] = $info;
					if (empty($result['calculateddesc'])) { $result['calculateddesc'] = $info['desc']; }
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

	/**
	 * Get details of this switch
	 *
	 * @return Array of switch information.
	 */
	function getDetails() {
		$result = array();
		$result['hostname'] = $this->host;
		$result['name'] = @snmpget($this->host, $this->community, '1.3.6.1.2.1.1.5.0');
		$result['location'] = @snmpget($this->host, $this->community, '1.3.6.1.2.1.1.6.0');
		return $result;
	}

	/**
	 * Get LLDP port IDs
	 *
	 * @return Array of Port IDs that have LLDP Info.
	 */
	function getLLDPPorts() {
		$result = array();
		$oldsnmp = snmp_get_valueretrieval();
		@snmp_set_valueretrieval(SNMP_VALUE_PLAIN);
		@snmp_set_oid_output_format(SNMP_OID_OUTPUT_NUMERIC);
		$walk = @snmprealwalk($this->host, $this->community, '1.0.8802.1.1.2.1.4.1.1.5.0');
		foreach ($walk as $oid => $val) {
			if (preg_match('#\.8802\.1\.1\.2\.1\.4\.1\.1\.5\.0\.([0-9]+)\.1#', $oid, $m)) {
				$result[] = $m[1];
			}
		}

		@snmp_set_valueretrieval($oldsnmp);
		return $result;
	}

	/**
	 * Get LLDP info for a port.
	 *
	 * @return Array of switch information.
	 */
	function getRemoteLLDPDetails($portid) {
		$oldsnmp = snmp_get_valueretrieval();
		@snmp_set_valueretrieval(SNMP_VALUE_PLAIN);
		$result = array();
		$result['hostname'] = $this->host;

		$result['lldpLocPortDesc'] = @snmpget($this->host, $this->community, '1.0.8802.1.1.2.1.3.7.1.4.' . $portid);
		$result['lldpRemChassisIdSubtype'] = @snmpget($this->host, $this->community, '1.0.8802.1.1.2.1.4.1.1.4.0.' . $portid . '.1');
		$result['lldpRemChassisId'] = @snmpget($this->host, $this->community, '1.0.8802.1.1.2.1.4.1.1.5.0.' . $portid . '.1');
		$result['lldpRemPortIdSubtype'] = @snmpget($this->host, $this->community, '1.0.8802.1.1.2.1.4.1.1.6.0.' . $portid . '.1');
		$result['lldpRemPortId'] = @snmpget($this->host, $this->community, '1.0.8802.1.1.2.1.4.1.1.7.0.' . $portid . '.1');
		$result['lldpRemPortDesc'] = @snmpget($this->host, $this->community, '1.0.8802.1.1.2.1.4.1.1.8.0.' . $portid . '.1');
		$result['lldpRemSysName'] = @snmpget($this->host, $this->community, '1.0.8802.1.1.2.1.4.1.1.9.0.' . $portid . '.1');
		$result['lldpRemSysDesc'] = @snmpget($this->host, $this->community, '1.0.8802.1.1.2.1.4.1.1.10.0.' . $portid . '.1');
		$result['lldpRemSysCapSupported'] = @snmpget($this->host, $this->community, '1.0.8802.1.1.2.1.4.1.1.11.0.' . $portid . '.1');
		$result['lldpRemSysCapEnabled'] = @snmpget($this->host, $this->community, '1.0.8802.1.1.2.1.4.1.1.12.0.' . $portid . '.1');

		@snmp_set_valueretrieval($oldsnmp);
		return $result;
	}


	/**
	 * Get LLDP info for a port.
	 *
	 * @return Array of switch information.
	 */
	function getAllLLDPData() {
		$oldsnmp = snmp_get_valueretrieval();
		@snmp_set_valueretrieval(SNMP_VALUE_PLAIN);

		$result = array();
		$result['hostname'] = $this->host;
		$result['lldpLocChassisId'] = @snmpget($this->host, $this->community, '1.0.8802.1.1.2.1.3.2.0');
		$result['ports'] = array();

		$snmp_names = array('1' => 'lldpRemTimeMark',
		                    '2' => 'lldpRemLocalPortNum',
		                    '3' => 'lldpRemIndex',
		                    '4' => 'lldpRemChassisIdSubtype',
		                    '5' => 'lldpRemChassisId',
		                    '6' => 'lldpRemPortIdSubtype',
		                    '7' => 'lldpRemPortId',
		                    '8' => 'lldpRemPortDesc',
		                    '9' => 'lldpRemSysName',
		                    '10' => 'lldpRemSysDesc',
		                    '11' => 'lldpRemSysCapSupported',
		                    '12' => 'lldpRemSysCapEnabled');

		@snmp_set_oid_output_format(SNMP_OID_OUTPUT_NUMERIC);

		$walk_data = @snmprealwalk($this->host, $this->community, '1.0.8802.1.1.2.1.4.1.1');
		foreach ($walk_data as $oid => $val) {
			// TODO: Multiple devices can appear per port.
			//       the \.1 needs to be less-specific here.
			if (preg_match('#\.8802\.1\.1\.2\.1\.4\.1\.1\.([0-9]+)\.0\.([0-9]+)\.([0-9]+)#', $oid, $m)) {
				$memberID = $m[3];
				$portID = $m[2];
				$oidName = $snmp_names[$m[1]];

				if (!isset($result['ports'][$portID])) { $result['ports'][$portID] = array('devices' => array()); }
				if (!isset($result['ports'][$portID]['devices'][$memberID])) { $result['ports'][$portID]['devices'][$memberID] = array(); }

				$result['ports'][$portID]['devices'][$memberID][$oidName] = $val;
			}
		}

		$walk_desc = @snmprealwalk($this->host, $this->community, '1.0.8802.1.1.2.1.3.7.1.4');
		foreach ($walk_desc as $oid => $val) {
			if (preg_match('#\.8802\.1\.1\.2\.1\.3\.7\.1\.4\.([0-9]+)#', $oid, $m)) {
				$portID = $m[1];
				if (isset($result['ports'][$portID])) {
					$result['ports'][$portID]['lldpLocPortDesc'] = $val;
				}
			}
		}

		$walk_name = @snmprealwalk($this->host, $this->community, '1.3.6.1.2.1.31.1.1.1.18');
		foreach ($walk_name as $oid => $val) {
			if (preg_match('#1\.3\.6\.1\.2\.1\.31\.1\.1\.1\.18\.([0-9]+)#', $oid, $m)) {
				$portID = $m[1];
				if (isset($result['ports'][$portID])) {
					$result['ports'][$portID]['ifAlias'] = $val;
				}
			}
		}

		$walk_remaddr = @snmprealwalk($this->host, $this->community, '1.0.8802.1.1.2.1.4.2.1.3.0');
		foreach ($walk_remaddr as $oid => $val) {
			if (preg_match('#\.8802\.1\.1\.2\.1\.4\.2\.1\.3\.0\.([0-9]+)\.([0-9]+)\.1.4.([0-9.]+)#', $oid, $m)) {
				$portID = $m[1];
				$memberID = $m[2];

				if (isset($result['ports'][$portID])) {
					$result['ports'][$portID]['devices'][$memberID]['lldpRemManAddr'] = $m[3];
				}
			}
		}

		@snmp_set_valueretrieval($oldsnmp);
		return $result;
	}

	/**
	 * Find a given MAC address on this switch.
	 *
	 * @param $mac Mac address (In any industry-accepted format).
	 * @return Port details of where this mac is known to exist.
	 */
	function findMac($mac) {
		if (!$this->isValidMac($mac)) { return FALSE; }
		$mac = str_replace(array(':', '-', '.'), '', $mac);
		$mac = join(':', str_split($mac, 2));

		$result = array();
		$macsnmp = $this->mactosnmp($mac);
		$oldsnmp = snmp_get_valueretrieval();
		snmp_set_valueretrieval(SNMP_VALUE_PLAIN);
		$result['port'] = @snmpget($this->host, $this->community, '1.3.6.1.2.1.17.4.3.1.2.'.$macsnmp);
		if ($result['port'] !== false) {
			$result['port'] = $this->getPortDetails($result['port']);
		}

		// TODO: Some switches don't implement the above, so we need to use
		//       1.3.6.1.2.1.17.7.1.2.2.1.2 - but this is a per-vlan table
		//       which is going to be a bit horrible on larger devices...

		$result['switch'] = $this->getDetails();
		$result['mac'] = $mac;

		snmp_set_valueretrieval($oldsnmp);
		return $result;
	}

	/**
	 * Convert a given IP address to MAC if this device knows it.
	 *
	 * @param $ip IP addresss
	 * @return MAC Address if known.
	 */
	function ip2mac($ip) {
		if (!filter_var($ip, FILTER_VALIDATE_IP)) { return FALSE; }
		$result = FALSE;
		$oldsnmp = snmp_get_valueretrieval();
		snmp_set_valueretrieval(SNMP_VALUE_PLAIN);
		snmp_set_oid_output_format(SNMP_OID_OUTPUT_NUMERIC);

		// ntop then pton to compress v6
		$wantedIP = inet_ntop(inet_pton($ip));

		$walk = snmprealwalk($this->host, $this->community, '1.3.6.1.2.1.4.35.1.4');
		foreach ($walk as $oid => $val) {
			if (preg_match('#\.1\.3\.6\.1\.2\.1\.4\.35\.1\.4\.([0-9]+)\.([0-9]+)\.[0-9]+\.([0-9.]+)#', $oid, $m)) {
				$portID = $m[1];
				$ipVersion = $m[2];

				$ipAddr = '';
				foreach (explode('.', $m[3]) as $bit) { $ipAddr .= chr($bit); }
				$ipAddr = strtolower(inet_ntop($ipAddr));

				if ($ipAddr == $wantedIP && !empty($val)) {
					$result = bin2hex($val);
					break;
				}
			}
		}

		@snmp_set_valueretrieval($oldsnmp);
		return $result;
	}
}




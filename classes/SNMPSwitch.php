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

		$result['switch'] = $this->getDetails();
		$result['mac'] = $mac;

		snmp_set_valueretrieval($oldsnmp);
		return $result;
	}
}

?>

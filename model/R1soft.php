<?php

class r1soft extends Model {
	public $r1soft;

	public function __construct($db, $uid) {
		$this->db	= $db;

		$this->uid = $uid;

		$this->servers = array();
		$this->nodes = array();

		//$this->getServers();

		if (!$this->keyExists()) {
			$encryption_key = openssl_random_pseudo_bytes(256);
			file_put_contents(dirname(dirname(__DIR__))."/enckey", $encryption_key);
		}
		$this->encryption_key = file_get_contents(dirname(dirname(__DIR__))."/enckey");
	}

	/*
	 *
	 * Get the list of servers the user has on there acount
	 *
	 */
	public function getServers() {
		$stmt = $this->db->prepare("SELECT node_id, server_number FROM servers WHERE user_id = :uid");
		
		$stmt->bindParam(":uid", $this->uid);
		$stmt->execute();

		$this->servers = $stmt->fetchAll();
		$this->getNodeDetails();

		return $this->servers;
	}

	public function getNodeDetails() {
		$stmt = $this->db->prepare("SELECT host, user, pass, port FROM nodes WHERE id = :nodeid");
		
		$stmt->bindParam(":nodeid", $nodeid);

		foreach ($this->servers as $server) {
			$nodeid = $server["node_id"];
			$stmt->execute();

			$node = $stmt->fetch();
			
			array_push($this->nodes, $node);
		}

		if (!is_null($this->nodes)) {
			return true;
		}
	}

	public function enableProductFeatures($node) {
		$context = stream_context_create(array(
			'ssl' => array('verify_peer' => false, 'verify_peer_name'=>false, 'allow_self_signed' => true)
		));

		try {
			$configClient = new soapclient("https://{$node["host"]}:{$node["port"]}/Configuration?wsdl",
				array('login'	=> $node["user"],
				'password'	=> $node["pass"],
				'trace'		=> 1,
				'cache_wsdl'	=> WSDL_CACHE_NONE,
				'features'	=> SOAP_SINGLE_ELEMENT_ARRAYS,
				'stream_context' => $context
				)
			);
			
			$configResponse = $configClient->enableALLProductFeatures();
		} catch (SoapFault $exception) {
			echo "Problem..... : ";
			echo $exception;
		}
	}

	public function getPolicyStatus($node) {
		$policies = array();
		
		$context = stream_context_create(array(
			'ssl' => array('verify_peer' => false, 'verify_peer_name'=>false, 'allow_self_signed' => true)
		));


		try {
			$policyClient = new soapclient("https://{$node["host"]}:{$node["port"]}/Policy2?wsdl",
				array('login'	=> $node["user"],
				'password'	=> $node["pass"],
				'trace'		=> 1,
				'cache_wsdl'	=> WSDL_CACHE_NONE,
				'features'	=> SOAP_SINGLE_ELEMENT_ARRAYS,
				'stream_context' => $context
				)
			);

			$allPoliciesForUser = $policyClient->getPolicies();

			return $allPoliciesForUser->return;
		} catch (SoapFault $exception) {
			echo "Problem getting polices...";
			echo $exception;
		}
	}

	/*
	 *
	 * Filter policies down to only the policies the user has access to 
	 *
	 */
	public function filterPolicies() {
		;
	}

	function keyExists() {
		return file_exists(dirname(dirname(__DIR__))."/enckey");
	}

	private function encryptPassword($data) {
		$iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($cipher));
		$encrypted = openssl_encrypt($data, $cipher, $encryption_key, 0, $iv);
		$encryptediv = $encrypted . ':' . $iv;
		
		return $encryptediv;
	}

	private function decryptPassword($encryptediv) {
		list($encrypted, $iv) = explode(':', $encryptediv);

		$decrypted = openssl_decrypt($encrypted, $cipher, $encryption_key, 0, $iv);
	}
}

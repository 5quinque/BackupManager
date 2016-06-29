<?php

require 'model/User.php';
require 'model/R1soft.php';

Class Model {
	var $endAlerts;

	public function __construct() {
		$dotenv = new Dotenv\Dotenv(dirname(dirname(__DIR__)));
		$dotenv->load();
		
		$dbhost = getenv('DB_HOST');
		$dbname = getenv('DB_NAME');
		$dbuser = getenv('DB_USER');
		$dbpass = getenv('DB_PASS');

		try {
			$this->db = new PDO("mysql:host={$dbhost};dbname={$dbname}", $dbuser, $dbpass);
			$this->db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		} catch (PDOException $e) {
			throw new PDOException("Error connecting to database.");
		}

		// danger, warning, success
		$this->endAlerts = array();
	}

	function setAlert($type, $alert) {
		array_push($this->endAlerts, array($type, $alert));
	}

	public function loadR1soft() {
		$this->r1soft = new r1soft($this->db, $this->uid);
	}
}

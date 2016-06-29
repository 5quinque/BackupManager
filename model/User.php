<?php

class User extends Model {
	public $user;

	public function __construct($parent) {
		$this->parent	= $parent;
		$this->db	= $this->parent->db;

		$this->userLoggedin = false;

		session_start();
	}

	public function login($details) {
		$username = $details[0];
		$password = $details[1];

		$stmt = $this->db->prepare("SELECT id, password FROM user WHERE username = :username");

		$stmt->bindParam(":username", $username);
		$stmt->execute();

		$user = $stmt->fetch();

		if (!$user) {
			$this->parent->setAlert("danger", "Incorrect Login");
			
			//var_dump($this->endAlerts);
			return 3;
		}

		$hash = $user["password"];

		if (password_verify($password, $hash)) {
			$this->username = $username;
			$this->uid = $user["id"];
			$this->getIDs();
			
			header('Location: /dashboard');
			return true;
		} else {
			$this->parent->setAlert("danger", "Incorrect Login");
			return 3;
		}
	}

	public function getUserLevel() {
		$stmt = $this->db->prepare("SELECT user_type FROM user WHERE id = :uid");
	
		$stmt->bindParam(":uid", $this->uid);
		$stmt->execute();

		$user = $stmt->fetch();

		return $user["user_type"];
	}

	public function logout() {
		if (!isset($_SESSION["uid"])) {
			return 3;
		}
		
		$stmt = $this->db->prepare("DELETE FROM sessions WHERE sid = :sid");
		$stmt->bindParam(":sid", $_SESSION["sid"]);

		$stmt->execute();

		session_destroy();

		$this->parent->setAlert("success", "You have been logged out");

		return 3;
	}

	public function register($username, $password, $repeatpassword) {
		if ($password !== $repeatpassword) {
			return "Passwords don't match";
		}
		
		if ($this->userExists($username)) {
			return "User already exists";
		}

		$hash = password_hash($password, PASSWORD_BCRYPT);

		$stmt = $this->db->prepare("INSERT INTO user VALUES(NULL, :username, :hash, '', 1, 1)");
		
		$stmt->bindValue(":username", $username);
		$stmt->bindValue(":hash", $hash);

		$stmt->execute();

		return true;
	}
	
	public function userExists($username) {
		$stmt = $this->db->prepare("SELECT username FROM user WHERE username = :username");
		
		$stmt->bindParam(":username", $username);
		$stmt->execute();

		return count($stmt->fetchAll()) == 1;
	}

	public function isLogged() {
		if ($this->userLoggedin) {
			return true;
		}

		if (!isset($_SESSION["uid"])) {
			return false;
		}

		$stmt = $this->db->prepare("SELECT * FROM sessions WHERE uid = :uid AND sid = :sid ORDER BY timestamp DESC");

		$stmt->bindParam(":uid", $_SESSION["uid"]);
		$stmt->bindParam(":sid", $_SESSION["sid"]);

		$stmt->execute();

		$row = $stmt->fetch();

		if ($row["uid"] != $_SESSION["uid"]) {
			return false;
		}

		if ($row["sid"] != $_SESSION["sid"]) {
			return false;
		}

		if ($row["tid"] != $_SESSION["tid"]) {
			return false;
		}

		if ($row["ip"] != $_SERVER["REMOTE_ADDR"]) {
			return false;
		}

		$this->uid = $row["uid"];
		$this->updateIDs();

		$this->loadR1soft();
		
		$this->userLoggedin = true;

		return true;
	}

	function getIDs() {
		$sid = session_id();
		$tid = md5(microtime(true));
		$ip = $_SERVER["REMOTE_ADDR"];

		$stmt = $this->db->prepare("INSERT INTO sessions VALUES(NULL, :uid, :sid, :tid, :ip)");

		$stmt->bindParam(":uid", $this->uid);
		$stmt->bindParam(":sid", $sid);
		$stmt->bindParam(":tid", $tid);
		$stmt->bindParam(":ip", $ip);

		$stmt->execute();

		$_SESSION["uid"] = $this->uid;
		$_SESSION["sid"] = $sid;
		$_SESSION["tid"] = $tid;
	}

	function updateIDs() {
		$sid = $_SESSION["sid"];
		$tid = md5(microtime(true));
		$ip = $_SERVER["REMOTE_ADDR"];
		
		$stmt = $this->db->prepare("UPDATE sessions SET tid = :tid WHERE sid = :sid");

		$stmt->bindParam(":sid", $sid);
		$stmt->bindParam(":tid", $tid);
		
		$stmt->execute();

		$_SESSION["tid"] = $tid;

		return true;
	}

	function getPagePerms($page) {
		$stmt = $this->db->prepare("SELECT user_level FROM pages WHERE pagename = :page");

		$stmt->bindParam(":page", $page);
		$stmt->execute();

		$page = $stmt->fetch();

		return $page["user_level"];
	}

}

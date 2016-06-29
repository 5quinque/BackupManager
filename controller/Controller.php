<?php

require 'model/Model.php';
require 'controller/View.php';
require 'controller/Dashboard.php';
require 'controller/Admin.php';

Class Controller {
	public function __construct() {
		$this->model		= new Model();
		$this->user		= new User($this->model);
		$this->view		= new View($this->model, $this->user);
		$this->dashboard	= new Dashboard($this->model, $this->user, $this->view);
		$this->admin		= new Admin($this->model, $this->user, $this->view);
	}

	/**
	 *
	 * All possible requests reside in this function
	 * 
	 */
	public function invoke() {
		$this->request("/logout", "user/logout");
		$this->request("/login", "user/login", array("POST@login", "POST@password"));

		$this->request("/", "dashboard/render");
		$this->request("/dashboard", "dashboard/render");
		//$this->request("/|/dashboard", "dashboard/render");
		//$this->request("/admin", "admin/render");

		// [TODO]
		$this->request("/admin/?{page}/?{action}", "admin/render/{page}");


		// [TODO]
		$this->request("/recoverypoints", "view/render", array("recoverypoints"));
		$this->request("/servers", "view/render", array("servers"));
		$this->request("/taskhistory", "view/render", array("taskhistory"));


		// [TODO]
		$this->request("404", "view/render/404");
		$this->request("403", "view/render/403");
	}

	public function request($request, $action, $args = null) {
		$definedURI = explode('/', $request);
		$requestURI = explode('/', $_SERVER["REQUEST_URI"]);

		// Remove the empty element
		array_shift($definedURI);
		array_shift($requestURI);

		// Make sure the request meets the minimum number of stuff
		$notRequired = substr_count($request, '?');
		$required = count($definedURI) - $notRequired;
		if (count($requestURI) < $required) {
			return false;
		}

		// Check if the URI matches
		list($actions, $vars) = $this->uriMatch($definedURI, $requestURI);

		if (!$actions) {
			//echo "no actions";
			return false;
		}

		//var_dump($vars);

		$split_action = explode('/', $action);	

		$obj = $split_action[0];
		$func = $split_action[1];

		if (!is_null($args)) {
			$funcArgs = $this->getArgs($args);

			// If the requested action returns '3' user doesn't have permission
			//  they will be shown the log in page
			if ($this->$obj->$func($funcArgs) == 3) {
				$this->view->render('login');
			}
		} else {
			if ($this->$obj->$func($vars) == 3) {
				$this->view->render('login');
			}
		}

		//var_dump($actions);
		//var_dump($vars);
		//var_dump($uri);
	}

	function uriMatch($definedURI, $requestURI) {
		$actions = array();
		$vars = array();

		for ($i = 0; $i < count($definedURI); $i++) {
			// Check if a variable
			
			if (preg_match('/(\?)?{(.*)}/', $definedURI[$i], $match)) {

				// Is the arg. required
				if ($match[1] == "?") {
					if (isset($requestURI[$i])) {
						$vars[$match[2]] = $requestURI[$i];
					} else {
						$vars[$match[2]] = null;
					}
				} else {
					// arg. is required, return false if isn't set
					if (!isset($requestURI[$i])) {
						return false;
					}

					$vars[$match[2]] = $requestURI[$i];
				}

			} elseif ($definedURI[$i] != $requestURI[$i]) {
				return false;
			} else {
				// Not a variable, add to actions
				array_push($actions, $definedURI[$i]);
			}
		}

		return array($actions, $vars);
	}

	public function getArgs($args) {
		if (!is_null($args)) {
			$funcArgs = array();
			foreach($args as $a) {
				$arg = explode('@', $a);

				if ($arg[0] == "POST") {
					// If a POST variable should be set, but isn't, redirect to /
					if (!isset($_POST[$arg[1]])) {
						header('Location: /');
					}
					array_push($funcArgs, $_POST[$arg[1]]);
				} elseif ($arg[0] == "GET") { 
					// If a GET variable should be set, but isn't, redirect to /
					if (!isset($_GET[$arg[1]])) {
						header('Location: /');
					}
					array_push($funcArgs, $_GET[$arg[1]]);
				} else {
					array_push($funcArgs, $arg);
				}
			}
		}

		return $funcArgs;
	}


	/**
	 *
	 * Handle the users request
	 *
	 * @param string $uri		User's requested URI
	 * @param string $action	The action to perform is the user requested above URI
	 * @param string $args		GET/POST variables set by the user
	 *
	 */
	public function requestOLD($uri, $action, $args = null) {
		// Multiple URIs can be assigned to one action
		//  These need to be split with |
		$possible_uri = explode('|', $uri);

		// This will need changing if we have variables with the URL
		//  E.g /servers/vps52898327
		if (in_array($_SERVER["REQUEST_URI"], $possible_uri)) {
			$action = explode('/', $action);

			if (!is_null($args)) {
				$funcArgs = array();
				foreach($args as $a) {
					$arg = explode('@', $a);

					if ($arg[0] == "POST") {
						// If a POST variable should be set, but isn't, redirect to /
						if (!isset($_POST[$arg[1]])) {
							header('Location: /');
						}
						array_push($funcArgs, $_POST[$arg[1]]);
					} elseif ($arg[0] == "GET") { 
						// If a GET variable should be set, but isn't, redirect to /
						if (!isset($_GET[$arg[1]])) {
							header('Location: /');
						}
						array_push($funcArgs, $_GET[$arg[1]]);
					} else {
						array_push($funcArgs, $arg);
					}
				}
			}

			$obj = $action[0];
			$func = $action[1];

			if (!is_null($args)) {
				// If the requested action returns '3' user doesn't have permission
				//  they will be shown the log in page
				if ($this->$obj->$func($funcArgs) == 3) {
					$this->view->render('login');
				}
			} else {
				if ($this->$obj->$func() == 3) {
					$this->view->render('login');
				}
			}

		}
	}

}


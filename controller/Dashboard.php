<?php

class Dashboard extends Controller {
	public function __construct($model, $user, $view) {
		$this->model = $model;
		$this->user = $user;
		$this->view = $view;
	}

	public function render() {
		$policies = null;
		if ($this->user->isLogged()) {

			$servers = $this->user->r1soft->getServers();

			foreach($this->user->r1soft->nodes as $node) {
				$this->user->r1soft->enableProductFeatures($node);
				$policies = $this->user->r1soft->getPolicyStatus($node);
			}

			// [TODO] Cross reference $servers and $policies
			
		} else {
			$servers = "";
		}

		$this->view->render(array(array("dashboard"), $policies));
	}


}

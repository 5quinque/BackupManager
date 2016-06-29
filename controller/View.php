<?php

class View extends Controller {
	public function __construct($model, $user) {
		$loader = new Twig_Loader_Filesystem('view');
		$this->twig = new Twig_Environment($loader, array(
			//'cache'	=> 'cache'
		));
		$this->model = $model;

		$this->user = $user;
	}

	public function render($args) {
		if (is_array($args)) {
			$page = $args[0][0];
		} else {
			$page = $args;
		}

		if (isset($args[1])) {
			$pageContent = $args[1];
		} else {
			$pageContent = "";
		}

		// Check if user has access
		if ($this->user->isLogged()) {

			$pagePerm = $this->user->getPagePerms($page);
			$userLevel = $this->user->getUserLevel();

			if ($userLevel <= $pagePerm) {
				echo $this->twig->render("$page.html", array('page' => $page, 'stuff' => $pageContent));
			} else {
				echo $this->twig->render("404.html");
			}
		} else {
			//echo $this->twig->render('login.html', array('alerts' => $this->model::$endAlerts, 'stuff' => $pageContent));
			echo $this->twig->render('login.html', array('alerts' => $this->model->endAlerts, 'stuff' => $pageContent));
		}
	}
}

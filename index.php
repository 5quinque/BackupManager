<?php

require_once 'vendor/autoload.php';
require_once 'lib/password.php';

require 'controller/Controller.php';

$controller = new Controller();
$controller->invoke();



<?php


// load application config (error reporting etc.)
require 'app/config/config.php';

// load application class
require 'app/core/application.php';
require 'app/core/controller.php';
set_time_limit(0);
// start the application
$app = new Application();

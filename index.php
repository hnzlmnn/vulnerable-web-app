<?php

//phpinfo();

// Kickstart the framework
$f3 = require('lib/base.php');

$f3->set('AUTOLOAD', 'src/classes/;src/controller/;src/services/');

//$f3->HOST = '172.28.1.173';
$f3->ERRORS = array();

$f3->DB = new \DB\SQL('sqlite:data/db.sqlite', null, null, array( \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION ));
$f3->VULNDB = new \DB\SQL('sqlite:data/vulndb.sqlite');

$f3->sess = new DB\SQL\Session($f3->DB,'sessions',true, null,'CSRF');
if ($f3->get('SESSION.csrf') == null) {
    $f3->set('SESSION.csrf', $f3->sess->csrf());
}
$f3->copy('SESSION.csrf', 'CSRF');

if (isset($f3->REQUEST['error'])) {
    $f3->ERRORS[] = $f3->REQUEST['error'];
}

$f3->set('page', is_numeric($f3->GET['page']) ? intval($f3->GET['page']) : 0);

$f3->validateCSRF = function($name = 'csrf', $die = false) {
    if (Base::instance()->SESSION[$name] === Base::instance()->POST[$name]) {
        return true;
    }
    if ($die) {
        die("CSRF validation failed!");
    }
    return false;
};

$f3->set('DEBUG',1);
if ((float)PCRE_VERSION<7.9)
	trigger_error('PCRE version is out of date');

// Load configuration
$f3->config('config.ini');

//var_dump($f3);

$f3->run();

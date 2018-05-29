<?php

class IndexController {

    public function index() {
        echo View::instance()->render('index.html');
    }

}
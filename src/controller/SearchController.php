<?php

class SearchController {

    public function index() {
        echo View::instance()->render('search.html');
    }
}
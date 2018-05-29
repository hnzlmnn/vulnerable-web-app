<?php

class ChallengeController {

    public function index() {
        echo View::instance()->render('challenges.html');
    }

    public function changeName() {
        $name = Base::instance()->POST['name'];
        if (Base::instance()->validateCSRF() && $name !== null && !empty($name)) {
            ChallengeService::instance()->rename($name);
        }
        Base::instance()->reroute('/challenges');
    }

    public function challenge() {
        echo View::instance()->render('challenge.html');
    }
}
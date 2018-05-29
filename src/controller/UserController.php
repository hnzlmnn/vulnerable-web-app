<?php

class UserController {

    public function me($f3) {
        if (!UserService::instance()->authenticated()) {
            $f3->reroute('/admin/login?redirect=/user');
        }
        $f3->reroute('/user/' . SessionService::instance()->uid());
    }

    public function user($f3) {
        $f3->set('uid', $f3->PARAMS['id']);
        $f3->set('me', $f3->PARAMS['id'] === SessionService::instance()->uid());
        if ($f3->VERB == 'POST') {
            if (!$f3->me) {
                $f3->reroute('/user');
            }
            if (!$f3->validateCSRF()) {
                $f3->ERRORS[] = "Invalid CSRF token!";
            } else {
                $result = UserService::instance()->changePassword($f3->POST['old'], $f3->POST['new'], $f3->POST['confirm']);
                if ($result === UserService::LOGIN_WRONG_USER) {
                    $f3->ERRORS[] = "Invalid user!";
                } else if ($result === UserService::LOGIN_WRONG_PASSWORD) {
                    $f3->ERRORS[] = "Incorrect old password!";
                } else if ($result === UserService::LOGIN_CONFIRM_MISMATCH) {
                    $f3->ERRORS[] = "Invalid password confirmation!";
                } else if ($result === UserService::LOGIN_OK) {
                    $f3->set('SUCCESS', 'Password changed successfully!');
                }
            }
        }
        echo View::instance()->render('user.html');
    }

}

<?php

class SessionService {

    private static $instance;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
    }

    public function uid($impersonateORid = true) {
        if (!is_bool($impersonateORid) && $impersonateORid !== null) {
            return $impersonateORid;
        }
        $uid = Base::instance()->SESSION['impersonate'];
        if ($impersonateORid === false || empty($uid)) {
            $uid = Base::instance()->SESSION['uid'];
        }
        return $uid;
    }

    public function login($id) {
        if (UserService::instance()->byId($id) !== null) {
            Base::instance()->SESSION['uid'] = $id;
            return true;
        }
        return false;
    }

    public function logout() {
        if (SessionService::instance()->isImpersonation()) {
            Base::instance()->SESSION['impersonate'] = null;
        } else {
            Base::instance()->set('COOKIE.PHPSESSID', '');
            // VULN: no session termination
            // $f3->clear('SESSION');
        }
    }

    public function impersonate($id) {
        if (UserService::instance()->byId($id) === null) {
            return false;
        }
        if ($this->uid() === $id) {
            return false;
        }
        Base::instance()->SESSION['impersonate'] = $id;
        return true;
    }

    public function isImpersonation() {
        return !empty(Base::instance()->SESSION['impersonate']);
    }

}

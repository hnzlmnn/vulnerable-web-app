<?php

class UserService {

    const LOGIN_OK = 0;
    const LOGIN_WRONG_USER = 1;
    const LOGIN_WRONG_PASSWORD = 2;
    const LOGIN_CONFIRM_MISMATCH = 3;

    const SIGNUP_OK = 0;
    const SIGNUP_EXISTS = 1;
    const SIGNUP_NO_USERNAME = 2;
    const SIGNUP_NO_PASSWORD = 3;
    const SIGNUP_PW_POLICY = 4;

    const RIGHT_SUPERUSER = 1;
    const RIGHT_EDITOR = 2;
    const RIGHT_USER = 3;

    private $rights = array(
        self::RIGHT_SUPERUSER => 'Superuser',
        self::RIGHT_EDITOR => 'Editor',
        self::RIGHT_USER => 'User'
    );

    private static $instance;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $f3 = Base::instance();
        $f3->DB->exec('CREATE TABLE IF NOT EXISTS users (
    id integer PRIMARY KEY AUTOINCREMENT,
	username text UNIQUE,
	hash varchar
);');

        $f3->DB->exec('CREATE TABLE IF NOT EXISTS user_rights (
    id integer PRIMARY KEY AUTOINCREMENT,
	user integer,
	right integer
);');

        $f3->DB->exec('CREATE TABLE IF NOT EXISTS rights (
    id integer UNIQUE,
	name text
);');
        $this->ensureRights();
    }

    private function ensureRights() {
        $result = Base::instance()->DB->exec('SELECT COUNT(*) AS rights FROM rights;')[0];
        if ($result['rights'] === '0') {
            foreach ($this->rights as $id => $name) {
                $this->createRight($id, $name);
            }
        }
    }

    private function createRight($id, $name) {
        Base::instance()->DB->exec(
            'INSERT INTO rights ("id", "name") VALUES (:id, :name)',
            array(':id' => $id, ':name' => $name)
        );
    }

    public function create($username, $password, &$id = null) {
        if (empty($username)) {
            return self::SIGNUP_NO_USERNAME;
        }
        if (empty($password)) {
            return self::SIGNUP_NO_PASSWORD;
        }
        if (strlen($password)< 4) {
            return self::SIGNUP_PW_POLICY;
        }
        try {
            $result = Base::instance()->DB->exec(
                array(
                    'INSERT INTO users ("username", "hash") VALUES (:username, :hash);',
                    'SELECT last_insert_rowid() as id;'
                ),
                array(
                    array(
                        ':username' => $username,
                        ':hash' => password_hash($password, PASSWORD_DEFAULT)
                    ),
                    array()
                )
            );
        } catch (\PDOException $e) {
            return self::SIGNUP_EXISTS;
        }
        $id = $result[0]['id'];
        return self::SIGNUP_OK;
    }

    public function getUsers($page = null) {
        if (is_numeric($page)) {
            return Base::instance()->DB->exec('SELECT * FROM users LIMIT :limit OFFSET :offset;',
                array(':limit' => PaginationService::instance()->limitUsers($page), ':offset' => PaginationService::instance()->offsetUsers($page))
            );
        }
        return Base::instance()->DB->exec('SELECT * FROM users;');
    }

    public function byId($uid) {
        if ($uid === null) {
            return null;
        }
        $result = Base::instance()->DB->exec(
            'SELECT * FROM users WHERE id = :id;',
            array(':id' => $uid)
        );
        if (count($result) === 0) {
            return null;
        }
        return $result[0];
    }

    public function byName($username) {
        $result = Base::instance()->DB->exec(
            'SELECT * FROM users WHERE username = :username;',
            array(':username' => $username)
        );
        if (count($result) === 0) {
            return null;
        }
        return $result[0];
    }

    public function count() {
        $result = Base::instance()->DB->exec('SELECT COUNT(*) AS users FROM users;')[0];
        return intval($result['users']);
    }

    public function getName($uid) {
        $result = $this->byId($uid);
        return $result === null ? '' : $result['username'];
    }

    public function exists($username) {
        return $this->byName($username) !== null;
    }

    public function login($username, $password) {
        $user = $this->byName($username);
        if ($user === null) {
            return self::LOGIN_WRONG_USER;
        }
        if (!password_verify($password, $user['hash'])) {
            return self::LOGIN_WRONG_PASSWORD;
        }
        // VULN: No session upgrade
        SessionService::instance()->login($user['id']);
        return self::LOGIN_OK;
    }

    public function hasRight($rights, $conjunction = null, $uid = null) {
        if ($rights === null) {
            $rights = array();
        }
        if ($uid === null) {
            $uid = SessionService::instance()->uid();
        }
        if ($uid === null) {
            return false;
        }
        if ($conjunction === null) {
            $conjunction = 'AND';
        }
        if (is_array($rights)) {
            $state = null;
            foreach($rights as $right) {
                $current = $this->hasRight($right, $conjunction, $uid);
                if ($state === null) {
                    $state = $current;
                    continue;
                }
                switch ($conjunction) {
                    case 'AND':
                        $state = $state && $current;
                        break;
                    case 'OR':
                        $state = $state || $current;
                        break;
                    default:
                        return false;
                }
            }
            if ($state === null) {
                return true;
            }
            return $state;
        }
        if (empty($rights)) {
            return true;
        }
        $result = Base::instance()->DB->exec(
            'SELECT * FROM user_rights WHERE user = :uid AND right = :right;',
            array(':uid' => $uid, ':right' => $rights)
        );
        return count($result) > 0;
    }

    public function requireRight($rights, $conjunction = null, $uid = null) {
        if ($this->hasRight($rights, $conjunction, $uid)) {
            return true;
        }
        Base::instance()->error(403);
        exit;
    }

    public function grantRight($uid, $right) {
        Base::instance()->DB->exec(
            'INSERT INTO user_rights ("user", "right") VALUES (:uid, :right);',
            array(':uid' => $uid, ':right' => $right)
        );
    }

    public function revokeRight($uid, $right) {
        Base::instance()->DB->exec(
            'DELETE FROM user_rights WHERE user = :uid AND right = :right;',
            array(':uid' => $uid, ':right' => $right)
        );
    }

    public function revokeRights($uid) {
        Base::instance()->DB->exec(
            'DELETE FROM user_rights WHERE user = :uid;',
            array(':uid' => $uid)
        );
        echo Base::instance()->DB->log();
    }

    public function getRights($uid = null) {
        if ($uid === null) {
            return Base::instance()->DB->exec(
                'SELECT * FROM rights;'
            );
        }
        return Base::instance()->DB->exec(
            'SELECT * FROM user_rights WHERE user = :id;',
            array(':id' => $uid)
        );
    }

    public function rightName($right) {
        $result = Base::instance()->DB->exec(
            'SELECT name FROM rights WHERE id = :right;',
            array(':right' => $right)
        );
        if (count($result) === 0) {
            return null;
        }
        return $result[0]['name'];
    }

    public function authenticated() {
        return $this->byId(SessionService::instance()->uid()) !== null;
    }

    public function changePassword($old, $new, $confirm, $uid = null) {
        // VULN: inconsistent pw policy
        if ($new !== $confirm) {
            return self::LOGIN_CONFIRM_MISMATCH;
        }
        $uid = SessionService::instance()->uid($uid);
        $user = $this->byId($uid);
        if ($user === null) {
            return self::LOGIN_WRONG_USER;
        }
        if (!password_verify($old, $user['hash'])) {
            return self::LOGIN_WRONG_PASSWORD;
        }
        Base::instance()->DB->exec(
            'UPDATE users SET hash = :hash WHERE id = :id;',
            array(':id' => $uid, ':hash' => password_hash($new, PASSWORD_DEFAULT))
        );
        return self::LOGIN_OK;
    }

    public function deleteUser($id) {
        if ($id == 1) {
            return false;
        }
        $this->revokeRights($id);
        Base::instance()->DB->exec(
            'DELETE FROM users WHERE id = :id;',
            array(':id' => $id)
        );
        return true;
    }
}
<?php

class ChallengeService {

    const COOKIE_NAME = '_';
    /* Challenges */
    const CHALLENGE_SQLI = 1;

    private $challenges = array(
        ChallengeService::CHALLENGE_SQLI => array(
            'name' => 'SQL Injection',
            'description' => 'An SQL injection vulnerability.'
        )
    );

    private static $instance;

    public static function instance() {
        if (ChallengeService::$instance === null) {
            ChallengeService::$instance = new ChallengeService();
        }
        return ChallengeService::$instance;
    }

    private function __construct() {
        $f3 = Base::instance();
        $f3->DB->exec('CREATE TABLE IF NOT EXISTS challenges (
    id integer,
	name text,
	description text
);');

        $f3->DB->exec('CREATE TABLE IF NOT EXISTS solves (
    id integer PRIMARY KEY AUTOINCREMENT,
	user integer,
	challenge integer,
	payload text
);');

        $f3->DB->exec('CREATE TABLE IF NOT EXISTS solvers (
    id integer PRIMARY KEY AUTOINCREMENT,
    token text UNIQUE,
	name text
);');
        $this->ensureChallenges();
    }

    private function ensureChallenges() {
        $result = Base::instance()->DB->exec('SELECT COUNT(*) AS challenges FROM challenges;')[0];
        if ($result['challenges'] === '0') {
            foreach ($this->challenges as $id => $challenge) {
                $this->createChallenge($id, $challenge);
            }
        }
    }

    private function createChallenge($id, $challenge) {
        Base::instance()->DB->exec(
            'INSERT INTO challenges ("id", "name", "description") VALUES (:id, :name, :description)',
            array(':id' => $id, ':name' => $challenge['name'], ':description' => $challenge['description'])
        );
    }

    public function getSolved($challenge) {
        return Base::instance()->DB->exec(
            'SELECT * FROM solves WHERE challenge = :challenge;',
            array(':challenge' => $challenge)
        );
    }

    public function getSolvedBy($token = null) {
        $token = $this->ensureSolver($token);
        $user = $this->solverId($token);
        return Base::instance()->DB->exec(
            'SELECT * FROM solves WHERE user = :user;',
            array(':user' => $user)
        );
    }

    public function countSolved($challenge) {
        return count($this->getSolved($challenge));
    }

    public function countSolvedBy($token = null) {
        return count($this->getSolvedBy($token));
    }

    public function isSolved($challenge) {
        return $this->countSolved($challenge) > 0;
    }

    public function hasSolved($challenge, $token = null) {
        $token = $this->ensureSolver($token);
        $user = $this->solverId($token);
        return count(Base::instance()->DB->exec(
            'SELECT * FROM solves WHERE user = :user AND challenge = :challenge;',
            array(':user' => $user, ':challenge' => $challenge)
        )) !== 0;
    }

    public function solve($challenge, $payload, $token = null) {
        $token = $this->ensureSolver($token);
        $user = $this->solverId($token);
        $payload = $payload === null ? '' : $payload;
        if ($this->hasSolved($challenge, $token)) {
            return false;
        }
        Base::instance()->DB->exec(
            'INSERT INTO solves ("user", "challenge", "payload") VALUES (:user, :challenge, :payload);',
            array(':user' => $user, ':challenge' => $challenge, ':payload' => $payload)
        );
    }

    public function unsolve($challenge, $token = null) {
        $token = $this->ensureSolver($token);
        $user = $this->solverId($token);
        Base::instance()->DB->exec(
            'DELETE FROM solves WHERE user = :user AND challenge = :challenge;',
            array(':user' => $user, ':challenge' => $challenge)
        );
    }

    public function getPayload($challenge, $token = null) {
        $token = $this->ensureSolver($token);
        $user = $this->solverId($token);
        $result = Base::instance()->DB->exec(
            'SELECT payload FROM solves WHERE user = :user AND challenge = :challenge;',
            array(':user' => $user, ':challenge' => $challenge)
        );
        if (count($result) === 0) {
            return '';
        }
        return $result[0]['payload'];
    }

    public function rename($name, $token = null) {
        $token = $this->ensureSolver($token);
        Base::instance()->DB->exec(
            'UPDATE solvers SET name = :name WHERE token = :token;',
            array(':name' => $name, ':token' => $token)
        );
    }

    public function getChallenge($id) {
        $result = Base::instance()->DB->exec(
            'SELECT * FROM challenges WHERE id = :id;',
            array(':id' => $id)
        );
        if (count($result) === 0) {
            return null;
        }
        return $result[0];
    }

    public function getChallenges() {
        return Base::instance()->DB->exec(
            'SELECT * FROM challenges;'
        );
    }

    public function challengeName($id) {
        $result = Base::instance()->DB->exec(
            'SELECT name FROM challenges WHERE id = :id;',
            array(':id' => $id)
        );
        if (count($result) === 0) {
            return null;
        }
        return $result[0]['name'];
    }

    public function getSolver($token) {
        if ($token === null) {
            return null;
        }
        $result = Base::instance()->DB->exec(
            'SELECT * FROM solvers WHERE token = :token;',
            array(':token' => $token)
        );
        if (count($result) === 0) {
            return null;
        }
        return $result[0];
    }

    public function solverId($token) {
        $result = $this->getSolver($token);
        if ($result === null) {
            return -1;
        }
        return $result['id'];
    }

    public function validSolver($token) {
        return $this->getSolver($token) !== null;
    }

    public function cookieSolver($token = null) {
        if ($token !== null) {
            Base::instance()->set('COOKIE.' . ChallengeService::COOKIE_NAME, $token);
        }
        return Base::instance()->COOKIE[ChallengeService::COOKIE_NAME];
    }

    public function createSolver() {
        $token = $this->cookieSolver();
        if ($token !== null && $this->validSolver($token)) {
            return $token;
        }
        $token = null;
        while ($token === null || $this->validSolver($token)) {
            $token = self::v4();
        }
        Base::instance()->DB->exec(
            'INSERT INTO solvers ("token", "name") VALUES (:token, :name);',
            array(':token' => $token, ':name' => 'Anonymous')
        );
        return $this->cookieSolver($token);
    }

    public function ensureSolver($token = null) {
        if ($this->validSolver($token)) {
            return $token;
        }
        return $this->createSolver();
    }

    public function challengeToken($challenge) {
        return 'challenge_' . $challenge;
    }

    public static function v4() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
}
<?php

class SanitizerService {

    const DEFAULT_SAFE = 'p;br;span;div;h1;h2;h3;h4;h5;img';

    private static $instance;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
    }

    public function clean($data, $tags = null) {
        if ($tags === null) {
            $tags = self::DEFAULT_SAFE;
        }
        return Base::instance()->clean($data, $tags);
    }

    public function scrub(&$data, $tags = null) {
        if ($tags === null) {
            $tags = self::DEFAULT_SAFE;
        }
        return $data = $this->clean($data, $tags);
    }

    public function getAllowed() {
        return explode(';', self::DEFAULT_SAFE);
    }

}

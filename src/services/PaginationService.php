<?php

class PaginationService {

    const DEFAULT_LIMIT = 10;

    private static $instance;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
    }

    public function limitArticles() {
        return self::DEFAULT_LIMIT;
    }

    public function offsetArticles($page = 0) {
        return $this->limitArticles() * $page;
    }

    public function pagesArticles($include_challenge = false) {
        return ceil(ArticleService::instance()->countArticles($include_challenge) / $this->limitArticles());
    }

    public function limitComments() {
        return self::DEFAULT_LIMIT;
    }

    public function offsetComments($page = 0) {
        return $this->limitComments() * $page;
    }

    public function pagesComments() {
        return ceil(ArticleService::instance()->countComments() / $this->limitComments());
    }

    public function limitUsers() {
        return self::DEFAULT_LIMIT;
    }

    public function offsetUsers($page = 0) {
        return $this->limitUsers() * $page;
    }

    public function pagesUsers() {
        return ceil(UserService::instance()->count() / $this->limitUsers());
    }

}

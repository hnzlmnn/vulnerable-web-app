<?php

class Comment {
    private $id, $article, $author, $content, $published;

    public function __construct($id, $article, $author, $content, $published) {
        $this->id = $id;
        $this->article = $article;
        $this->author = $author;
        $this->content = $content;
        $this->published = $published === null ? time() : $published;
    }

    public function id() {
        return $this->id;
    }

    public function article() {
        return $this->article;
    }

    public function author() {
        return $this->author;
    }

    public function authorName() {
        $user = UserService::instance()->byId($this->author);
        return $user === null ? '' : $user['username'];
    }

    public function content() {
        return SanitizerService::instance()->clean($this->content);
    }

    public function extract($length = 40, $dots = "...") {
        $content = $this->content();
        if (strlen($content) > $length) {
            $content = substr($content, 0, $length) . $dots;
        }
        return $content;
    }

    public function published($format = 'Y-m-d G:i') {
        if ($format === null) {
            return $this->published;
        }
        return date($format, $this->published);
    }
}
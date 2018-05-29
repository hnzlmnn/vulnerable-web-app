<?php

class Article {
    private $id, $title, $content, $keywords, $token, $author, $published;

    public function __construct($id, $title, $content = null, $keywords = null, $token = false, $author = null, $published = null) {
        $this->id = $id;
        $this->title = $title;
        $this->token = is_string($token) ? boolval($token) : $token;
        $this->author = SessionService::instance()->uid($author);
        $this->published = $published === null ? time() : $published;
        if ($content !== null && !empty($content)) {
            $this->content = $content;
        } elseif (!$this->token) {
            $this->content = (new LoremIpsum())->paragraph();
        }
        if ($keywords === null) {
            $keywords = $this->generateKeywords();
        } elseif (is_string($keywords)) {
            $keywords = array($keywords);
        }
        $this->keywords = $keywords;
    }

    public function id($id = null) {
        if ($id !== null) {
            $this->id= $id;
        }
        return $this->id;
    }

    public function title($title = null) {
        if ($title !== null) {
            $this->title = $title;
        }
        return $this->title === null ? '' : $this->title;
    }

    public function content($content = null) {
        if ($this->token === true) {
            return ChallengeService::instance()->challengeToken(ChallengeService::CHALLENGE_SQLI);
        }
        if ($content !== null) {
            $this->content = $content;
        }
        return SanitizerService::instance()->clean($this->content);
    }

    public function extract($length = 40, $dots = "...") {
        $content = $this->content();
        if (strlen($content) > $length) {
            $content = substr($content, 0, $length) . $dots;
        }
        return $content;
    }

    public function token() {
        return $this->token;
    }

    public function keywords() {
        return $this->keywords;
    }

    private function generateKeywords() {
        if ($this->token) {
            return array('');
        }
        $keywords = array();
        foreach(
            explode(
                ' ',
                preg_replace(
                    '/ +/',
                    ' ',
                    preg_replace(
                        '/[^a-z]/i',
                        ' ',
                        $this->title . ' ' . $this->content
                    )
                )
            ) as $word
        ) {
            $word = trim($word);
            if (!empty($word)) {
                $keywords[] = strtolower($word);
            }
        }
        return array_unique($keywords);
    }

    private function mark($search, $text) {
        if ($search === null) {
            return $text;
        }
        return preg_replace_callback(
            '/' . preg_quote($search) . '/i',
            function ($match) {
                return '<span class="highlight">' . $match[0] . '</span>';
            },
            $text
        );
    }

    public function markTitle($search) {
        return $this->mark($search, $this->title());
    }

    public function markContent($search) {
        return $this->mark($search, $this->content());
    }

    public function author() {
        return $this->author;
    }

    public function authorName() {
        $user = UserService::instance()->byId($this->author);
        return $user === null ? '' : $user['username'];
    }

    public function published($format = 'Y-m-d G:i') {
        if ($format === null) {
            return $this->published;
        }
        return date($format, $this->published);
    }
}

<?php

class ArticleService {

    private $articles = array();

    private static $instance;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->articles = array(
            // Generate "hidden" page which can be retrieved using a sql injection
            new Article(null, '', null, null, true, 1, 0),
            new Article(null, 'Example article', null, null, false, 1),
        );

        $f3 = Base::instance();
        $f3->VULNDB->exec('CREATE TABLE IF NOT EXISTS articles (
    id integer PRIMARY KEY AUTOINCREMENT,
	title text,
	content text,
	token boolean,
    published integer default (strftime(\'%s\', \'now\')),
    author integer
);');
        $f3->VULNDB->exec('CREATE TABLE IF NOT EXISTS search (
    id integer PRIMARY KEY AUTOINCREMENT,
	article integer,
	keyword text
);');
        $f3->VULNDB->exec('CREATE TABLE IF NOT EXISTS comments (
    id integer PRIMARY KEY AUTOINCREMENT,
	article integer,
	author integer,
	content text,
	published integer default (strftime(\'%s\', \'now\'))
);');
        $this->ensureContent();
    }

    private function ensureContent() {
        $result = Base::instance()->VULNDB->exec('SELECT COUNT(*) AS content FROM search;')[0];
        if ($result['content'] === '0') {
            foreach ($this->articles as $article) {
                $this->createArticle($article);
            }
        }
    }

    public function createArticle(Article $article) {
        $id = Base::instance()->VULNDB->exec(
            array(
                'INSERT INTO articles ("title", "content", "token", "author", "published") VALUES (:title, :content, :token, :author, :published);',
                'SELECT last_insert_rowid() as id;'
            ),
            array(
                array(
                    ':title' => $article->title(),
                    ':content' => $article->content(),
                    ':token' => $article->token(),
                    ':author' => $article->author(),
                    ':published' => $article->published(null)
                ),
                array()
            )
        )[0]['id'];
        if ($id === null) {
            return;
        }
        $article->id($id);
        foreach ($article->keywords() as $keyword) {
            Base::instance()->VULNDB->exec(
                'INSERT INTO search ("article", "keyword") VALUES (:article, :keyword);',
                array(
                    ':article' => $article->id(),
                    ':keyword' => $keyword
                )
            );
        }
    }

    public function getArticle($id, $challenge = false) {
        $result = Base::instance()->VULNDB->exec(
            'SELECT * FROM articles WHERE id = :id;',
            array(':id' => $id)
        );
        if (count($result) === 0) {
            return null;
        }
        $result = $result[0];
        $article = new Article(
            $result['id'],
            $result['title'],
            $result['content'],
            array(),
            $result['token'],
            $result['author'],
            $result['published']
        );
        if ($challenge && $article->token() === true) {
            ChallengeService::instance()->solve(ChallengeService::CHALLENGE_SQLI, Base::instance()->POST['search']);
        }
        return $article;
    }

    public function search($search) {
        if ($search === null || empty($search)) {
            return null;
        }
        // VULN: SQLi
        $results = array_unique(array_merge(
            array_column(Base::instance()->VULNDB->exec(
                "SELECT DISTINCT article as id FROM search WHERE instr(keyword, '" . $search . "');"
            ), 'id'),
            array_column(Base::instance()->VULNDB->exec(
                'SELECT id FROM articles WHERE instr(lower(title), lower(:search)) OR instr(lower(content), lower(:search)) > 0 COLLATE NOCASE;',
                array(':search' => $search)
            ), 'id')
        ));

        $articles = array();
        foreach ($results as $id) {
            $article = $this->getArticle($id, true);
            if ($article !== null) {
                $articles[] = $article;
            }
        }
        return $articles;
    }

    public function getLatestArticles($count = null) {
        if ($count === null || !is_numeric($count)) {
            $count = 3;
        }
        $results = Base::instance()->VULNDB->exec(
            'SELECT * FROM articles ORDER BY published DESC LIMIT :limit;',
            array(':limit' => $count)
        );

        $articles = array();
        foreach ($results as $result) {
            $article = new Article(
                $result['id'],
                $result['title'],
                $result['content'],
                array(),
                $result['token'],
                $result['author'],
                $result['published']
            );
            if ($article->token() === true) {
                continue;
            }
            $articles[] = $article;
        }
        return $articles;
    }

    public function getArticles($page = null) {
        if (is_numeric($page)) {
            $results = Base::instance()->VULNDB->exec(
                'SELECT * FROM articles ORDER BY published DESC LIMIT :limit OFFSET :offset;',
                array(':limit' => PaginationService::instance()->limitArticles($page), ':offset' => PaginationService::instance()->offsetArticles($page))
            );
        } else {
            $results = Base::instance()->VULNDB->exec(
                'SELECT * FROM articles ORDER BY published DESC;'
            );
        }
        $articles = array();
        foreach ($results as $result) {
            $article = new Article(
                $result['id'],
                $result['title'],
                $result['content'],
                array(),
                $result['token'],
                $result['author'],
                $result['published']
            );
            if ($article->token() === true) {
                continue;
            }
            $articles[] = $article;
        }
        return $articles;
    }

    public function editArticle($id, $title, $content) {
        $article = $this->getArticle($id);
        if ($article === null) {
            return false;
        }
        $article->title($title);
        $article->content($content);
        Base::instance()->VULNDB->exec(
            'UPDATE articles SET title = :title, content = :content, author = :author, published = :published WHERE id = :id;',
            array(
                ':id' => $article->id(),
                ':title' => $article->title(),
                ':content' => $article->content(),
                ':author' => SessionService::instance()->uid(),
                ':published' => time()
            )
        );
        return true;
    }

    public function deleteArticle($id) {
        Base::instance()->VULNDB->exec(
            'DELETE FROM articles WHERE id = :id;',
            array(':id' => $id)
        );
    }

    public function countArticlesById($id) {
        return intval(Base::instance()->VULNDB->exec(
            'SELECT COUNT(*) as count FROM articles WHERE author = :id;',
            array(':id' => $id)
        )[0]['count']);
    }

    public function countArticles($include_challenge = false) {
        if ($include_challenge) {
            return intval(Base::instance()->VULNDB->exec(
                'SELECT COUNT(*) as count FROM articles;'
            )[0]['count']);
        }
        return intval(Base::instance()->VULNDB->exec(
            'SELECT COUNT(*) as count FROM articles WHERE token = 0;'
        )[0]['count']);
    }

    public function countCommentsById($id) {
        return intval(Base::instance()->VULNDB->exec(
            'SELECT COUNT(*) as count FROM comments WHERE author = :id;',
            array(':id' => $id)
        )[0]['count']);
    }

    public function countComments() {
        return intval(Base::instance()->VULNDB->exec(
            'SELECT COUNT(*) as count FROM comments;'
        )[0]['count']);
    }

    public function getComment($id) {
        $result = Base::instance()->VULNDB->exec(
            'SELECT * FROM comments WHERE id = :id;',
            array(':id' => $id)
        );
        if (count($result) === 0) {
            return null;
        }
        $comment = $result[0];
        return new Comment($comment['id'], $comment['article'], $comment['author'], $comment['content'], $comment['published']);
    }

    public function getComments($article = null, $page =  null) {
        if ($article === null) {
            if (is_numeric($page)) {
                $results = Base::instance()->VULNDB->exec(
                    'SELECT * FROM comments ORDER BY published DESC LIMIT :limit OFFSET :offset;',
                    array(':limit' => PaginationService::instance()->limitComments($page), ':offset' => PaginationService::instance()->offsetComments($page))
                );
            } else {
                $results = Base::instance()->VULNDB->exec(
                    'SELECT * FROM comments ORDER BY published DESC;'
                );
            }
        } else {
            if (is_numeric($page)) {
                $results = Base::instance()->VULNDB->exec(
                    'SELECT * FROM comments WHERE article = :article ORDER BY published DESC LIMIT :limit OFFSET :offset;',
                    array(':article' => $article, ':limit' => PaginationService::instance()->limitComments($page), ':offset' => PaginationService::instance()->offsetComments($page))
                );
            } else {
                $results = Base::instance()->VULNDB->exec(
                    'SELECT * FROM comments WHERE article = :article ORDER BY published DESC;',
                    array(':article' => $article)
                );
            }
        }
        $comments = array();
        foreach ($results as $comment) {
            $comments[] = new Comment($comment['id'], $comment['article'], $comment['author'], $comment['content'], $comment['published']);;
        }
        return $comments;
    }

    public function addComment($article, $content, $author = null) {
        if ($this->getArticle($article) === null) {
            return false;
        }
        $author = SessionService::instance()->uid($author);
        Base::instance()->VULNDB->exec(
            'INSERT INTO comments ("article", "author", "content") VALUES (:article, :author, :content);',
            array(
                ':article' => $article,
                ':author' => $author,
                ':content' => $content
            )
        );
        return true;
    }

    public function deleteComment($id) {
        Base::instance()->VULNDB->exec(
            'DELETE FROM comments WHERE id = :id;',
            array(
                ':id' => $id
            )
        );
        return true;
    }

}

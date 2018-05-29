<?php

class ArticleController {

    public function index() {
        echo View::instance()->render('article.html');
    }

	public function comment($f3) {
		if (!$f3->validateCSRF()) {
            $f3->ERRORS[] = "Invalid CSRF token!";
        } else {
            $result = ArticleService::instance()->addComment($f3->PARAMS['id'], $f3->POST['content']);
            // VULN: Stored XSS
            if ($result === false) {
                $f3->ERRORS[] = "Invalid article!";
            } else {
                $f3->reroute('/article/' . $f3->PARAMS['id']);
            }
        }
	}

}

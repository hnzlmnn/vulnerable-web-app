<?php

class AdminController {

    public function index($f3) {
        if (!UserService::instance()->authenticated()) {
            $f3->reroute('/admin/login?redirect=/admin' . (isset($f3->GET['username']) ? '&username=' . $f3->GET['username'] : ''));
        }
        UserService::instance()->requireRight(array(UserService::RIGHT_SUPERUSER, UserService::RIGHT_EDITOR), 'OR');
        $f3->set('template', 'dashboard.html');
        // $colors = array("blue", "indigo", "purple", "pink", "red", "orange", "yellow", "green", "teal", "cyan", "white", "gray", "gray-dark");
        $f3->set('stats', array(
            array(
                'bgcolor' => 'red',
                'color' => 'white',
                'number' => ArticleService::instance()->countArticles(),
                'name' => 'Articles',
                'icon' => 'files-o',
                'link' => '/admin/articles'
            ),
            array(
                'bgcolor' => 'orange',
                'color' => 'white',
                'number' => ArticleService::instance()->countComments(),
                'name' => 'Comments',
                'icon' => 'comments-o',
                'link' => '/admin/comments'
            )
        ));
        if (UserService::instance()->hasRight(UserService::RIGHT_SUPERUSER)) {
            $f3->stats[] = array(
                'bgcolor' => 'purple',
                'color' => 'white',
                'number' => UserService::instance()->count(),
                'name' => 'Users',
                'icon' => 'users',
                'link' => '/admin/users'
            );
        }
        ToolbarService::instance()
            ->show()
            ->setTitle('Dashboard');
        echo View::instance()->render('admin.html');
    }

    public function articles($f3) {
        if (!UserService::instance()->authenticated()) {
            $f3->reroute('/admin/login?redirect=/admin/articles');
        }
        UserService::instance()->requireRight(array(UserService::RIGHT_SUPERUSER, UserService::RIGHT_EDITOR), 'OR');
        $f3->set('template', 'articles.html');
        $f3->set('pages', PaginationService::instance()->pagesArticles());
        ToolbarService::instance()
            ->show()
            ->setTitle('Articles')
            ->addButton(new Button('/admin/articles/new', 'Create', 'plus', array('btn-success')));
        echo View::instance()->render('admin.html');
    }

    public function newarticle($f3) {
        if (!UserService::instance()->authenticated()) {
            $f3->reroute('/admin/login?redirect=/admin/articles/new');
        }
        UserService::instance()->requireRight(array(UserService::RIGHT_SUPERUSER, UserService::RIGHT_EDITOR), 'OR');
        if ($f3->VERB == 'POST') {
            if (!$f3->validateCSRF()) {
                $f3->ERRORS[] = "Invalid CSRF token!";
            } else {
                if (empty($f3->POST['title']) || empty($f3->POST['content'])) {
                    $f3->ERRORS[] = "Fill out all required fields!";
                } else {
                    ArticleService::instance()->createArticle(
                        new Article(
                            null,
                            $f3->POST['title'],
                            $f3->POST['content']
                        )
                    );
                    $f3->reroute('/admin/articles');
                }
            }
        }
        $f3->set('template', 'articleform.html');
        ToolbarService::instance()
            ->show()
            ->setTitle('Create Article')
            ->addButton(
                new Button(
                    '#',
                    'Save',
                    'floppy-o',
                    array('btn-success'),
                    'document.getElementById(\'adminForm\').submit();return false;'
                )
            )
            ->addButton(
                new Button(
                    '/admin/articles',
                    'Cancel',
                    'times',
                    array('btn-danger')
                )
            );
        echo View::instance()->render('admin.html');
    }

    public function editarticle($f3) {
        if (!UserService::instance()->authenticated()) {
            $f3->reroute('/admin/login?redirect=/admin/articles/edit');
        }
        UserService::instance()->requireRight(array(UserService::RIGHT_SUPERUSER, UserService::RIGHT_EDITOR), 'OR');
        if ($f3->VERB == 'POST') {
            if (!$f3->validateCSRF()) {
                $f3->ERRORS[] = "Invalid CSRF token!";
            } else {
                if (empty($f3->POST['id']) || empty($f3->POST['title']) || empty($f3->POST['content'])) {
                    $f3->ERRORS[] = "Fill out all required fields!";
                } else {
                    ArticleService::instance()->editArticle(
                        $f3->POST['id'],
                        $f3->POST['title'],
                        $f3->POST['content']
                    );
                    $f3->reroute('/admin/articles');
                }
            }
        }
        $f3->set('template', 'articleform.html');
        $article = ArticleService::instance()->getArticle($f3->PARAMS['id']);
        if ($article === null || $article->token()) {
            $f3->error(404);
            exit;
        }
        $f3->set('article', $article);
        ToolbarService::instance()
            ->show()
            ->setTitle('Edit Article')
            ->addButton(
                new Button(
                    '#',
                    'Save',
                    'floppy-o',
                    array('btn-success'),
                    'document.getElementById(\'adminForm\').submit();return false;'
                )
            )
            ->addButton(
                new Button(
                    '/admin/articles',
                    'Cancel',
                    'times',
                    array('btn-danger')
                )
            );
        echo View::instance()->render('admin.html');
    }

    public function deletearticle($f3) {
        if (!UserService::instance()->authenticated()) {
            $f3->reroute('/admin/login?redirect=/admin/articles/delete/' . $f3->PARAMS['id']);
        }
        UserService::instance()->requireRight(array(UserService::RIGHT_SUPERUSER, UserService::RIGHT_EDITOR), 'OR');
        // VULN: csrf
        ArticleService::instance()->deleteArticle($f3->PARAMS['id']);
        $f3->reroute('/admin/articles');
    }

    public function comments($f3) {
        if (!UserService::instance()->authenticated()) {
            $f3->reroute('/admin/login?redirect=/admin/comments');
        }
        UserService::instance()->requireRight(array(UserService::RIGHT_SUPERUSER, UserService::RIGHT_EDITOR), 'OR');
        $f3->set('template', 'comments.html');
        $f3->set('pages', PaginationService::instance()->pagesComments());
        ToolbarService::instance()
            ->show()
            ->setTitle('Comments');
        echo View::instance()->render('admin.html');
    }

    public function deletecomment($f3) {
        if (!UserService::instance()->authenticated()) {
            $f3->reroute('/admin/login?redirect=/admin/comments/delete/' . $f3->PARAMS['id']);
        }
        UserService::instance()->requireRight(array(UserService::RIGHT_SUPERUSER, UserService::RIGHT_EDITOR), 'OR');
        // VULN: csrf
        ArticleService::instance()->deleteComment($f3->PARAMS['id']);
        $f3->reroute('/admin/comments');
    }

    public function users($f3) {
        if (!UserService::instance()->authenticated()) {
            $f3->reroute('/admin/login?redirect=/admin/users');
        }
        UserService::instance()->requireRight(UserService::RIGHT_SUPERUSER);
        $f3->set('template', 'users.html');
        $f3->set('pages', PaginationService::instance()->pagesUsers());
        ToolbarService::instance()
            ->show()
            ->setTitle('Users')
            ->addButton(new Button('/admin/users/new', 'Create', 'user-plus', array('btn-success')));
        echo View::instance()->render('admin.html');
    }

    public function newuser($f3) {
        if (!UserService::instance()->authenticated()) {
            $f3->reroute('/admin/login?redirect=/admin/users/new');
        }
        UserService::instance()->requireRight(UserService::RIGHT_SUPERUSER);
        if ($f3->VERB == 'POST') {
            if (!$f3->validateCSRF()) {
                $f3->ERRORS[] = "Invalid CSRF token!";
            } else {
                if (empty($f3->POST['username']) || empty($f3->POST['password'])) {
                    $f3->ERRORS[] = "Fill out all required fields!";
                } else {
                    $id = UserService::instance()->create(
                            $f3->POST['username'],
                            $f3->POST['password']
                    );
                    if (is_array($f3->POST['rights'])) {
                        foreach ($f3->POST['rights'] as $right) {
                            UserService::instance()->grantRight($id, $right);
                        }
                    }
                    $f3->reroute('/admin/users');
                }
            }
        }
        $f3->set('template', 'userform.html');
        ToolbarService::instance()
            ->show()
            ->setTitle('Add User')
            ->addButton(
                new Button(
                    '#',
                    'Save',
                    'floppy-o',
                    array('btn-success'),
                    'document.getElementById(\'adminForm\').submit();return false;'
                )
            )
            ->addButton(
                new Button(
                    '/admin/users',
                    'Cancel',
                    'times',
                    array('btn-danger')
                )
            );
        echo View::instance()->render('admin.html');
    }

    public function edituser($f3) {
        if (!UserService::instance()->authenticated()) {
            $f3->reroute('/admin/login?redirect=/admin/users/new');
        }
        UserService::instance()->requireRight(UserService::RIGHT_SUPERUSER);
        if ($f3->VERB == 'POST') {
            if (!$f3->validateCSRF()) {
                $f3->ERRORS[] = "Invalid CSRF token!";
            } else {
                if (empty($f3->POST['id'])) {
                    $f3->error(404);
                    exit;
                } else {
                    UserService::instance()->revokeRights($f3->POST['id']);
                    if (is_array($f3->POST['rights'])) {
                        foreach ($f3->POST['rights'] as $right) {
                            UserService::instance()->grantRight($f3->POST['id'], $right);
                        }
                    }
                    $f3->reroute('/admin/users');
                }
            }
        }
        $f3->set('template', 'userform.html');
        $user = UserService::instance()->byId($f3->PARAMS['id']);
        if ($user === null) {
            $f3->error(404);
            exit;
        }
        $f3->set('user', $user);
        ToolbarService::instance()
            ->show()
            ->setTitle('Edit User')
            ->addButton(
                new Button(
                    '#',
                    'Save',
                    'floppy-o',
                    array('btn-success'),
                    'document.getElementById(\'adminForm\').submit();return false;'
                )
            )
            ->addButton(
                new Button(
                    '/admin/users',
                    'Cancel',
                    'times',
                    array('btn-danger')
                )
            );
        echo View::instance()->render('admin.html');
    }

    public function deleteuser($f3) {
        if (!UserService::instance()->authenticated()) {
            $f3->reroute('/admin/login?redirect=/admin/users/delete/' . $f3->PARAMS['id']);
        }
        UserService::instance()->requireRight(UserService::RIGHT_SUPERUSER);
        // VULN: csrf
        if (UserService::instance()->deleteUser($f3->PARAMS['id']) === false) {
            $f3->reroute('/admin/users?error=' . urlencode('Superuser can\'t be deleted!'));
        }
        $f3->reroute('/admin/users');
    }

    public function login($f3) {
        if (UserService::instance()->count() === 0) {
            $f3->reroute('/admin/setup');
        }
        if ($f3->VERB == 'POST') {
            if (!$f3->validateCSRF()) {
                $f3->ERRORS[] = "Invalid CSRF token!";
            } else {
                $result = UserService::instance()->login($f3->POST['username'], $f3->POST['password']);
                // VULN: User enumeration
                if ($result === UserService::LOGIN_WRONG_USER) {
                    $f3->ERRORS[] = "Invalid credentials&#33; ";
                } else if ($result === UserService::LOGIN_WRONG_PASSWORD) {
                    $f3->ERRORS[] = "Invalid credentials!";
                } else if ($result === UserService::LOGIN_OK) {
                    $redirect = '/';
                    if (UserService::instance()->hasRight(array(UserService::RIGHT_SUPERUSER, UserService::RIGHT_EDITOR), 'OR')) {
                        $redirect = '/admin';
                    }
                    // VULN: open redirect
                    if (!empty($f3->REQUEST['redirect'])) {
                        $redirect = urldecode($f3->POST['redirect']);
                    }
                    $f3->reroute($redirect);
                    exit;
                }
            }
        }
        echo View::instance()->render('login.html');
    }

    public function signup($f3) {
        if ($f3->VERB == 'POST') {
            if (!$f3->validateCSRF()) {
                $f3->ERRORS[] = "Invalid CSRF token!";
            } else {
                if ($f3->POST['password'] !== $f3->POST['repeat']) {
                    $f3->ERRORS[] = "Passwords do not match!";
                } else {
                    $id = null;
                    $result = UserService::instance()->create($f3->POST['username'], $f3->POST['password'], $id);
                    // VULN: User enumeration
                    if ($result === UserService::SIGNUP_EXISTS) {
                        $f3->ERRORS[] = "Duplicate user!";
                    } else if ($result === UserService::SIGNUP_NO_USERNAME) {
                        $f3->ERRORS[] = "No username provided!";
                    } else if ($result === UserService::SIGNUP_NO_PASSWORD) {
                        $f3->ERRORS[] = "No password provided!";
                    } else if ($result === UserService::SIGNUP_PW_POLICY) {
                        $f3->ERRORS[] = "Password does not comply with the policy! (Minimum 4 characters)";
                    } else if ($result === UserService::SIGNUP_OK) {
                        if ($id !== null) {
                            UserService::instance()->grantRight($id, UserService::RIGHT_USER);
                        }
                        $f3->reroute('/admin/login?redirect=' . urlencode($f3->REQUEST['redirect']) . '&username=' . urlencode($f3->POST['username']) . '&success=' . urlencode('Successfully signed up!'));
                        exit;
                    }
                }
            }
        }
        echo View::instance()->render('signup.html');
    }

    public function logout($f3) {
        SessionService::instance()->logout();
        $f3->reroute('/');
    }

    public function setup($f3) {
        if (UserService::instance()->count() !== 0) {
            $f3->reroute('/admin');
        }
        if ($f3->VERB == 'POST' && $f3->validateCSRF()) {
            if ($f3->POST['username'] !== null && $f3->POST['password'] !== null) {
                $id = null;
                UserService::instance()->create($f3->POST['username'], $f3->POST['password'], $id);
                UserService::instance()->grantRight($id, UserService::RIGHT_SUPERUSER);
                $f3->reroute('/admin?username=' . urlencode($f3->POST['username']));
            }
        }
        echo View::instance()->render('setup.html');
    }

    public function impersonate($f3) {
        if (!UserService::instance()->authenticated()) {
            $f3->reroute('/admin/login?redirect=/admin/users/impersonate/' . $f3->PARAMS['id']);
        }
        // VULN: privilege escalation
        // UserService::instance()->requireRight(UserService::RIGHT_SUPERUSER);
        if (SessionService::instance()->impersonate($f3->PARAMS['id']) === false) {
            $f3->reroute('/admin/users?error=' . urlencode('Impersonation failed!'));
        }
        $f3->reroute('/');
    }
}
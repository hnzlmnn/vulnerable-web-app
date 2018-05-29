<?php

class Route {

    private $name, $url, $icon, $text, $rights, $conjunction;

    public function __construct($name, $url, $icon, $text, $rights = array(), $conjunction = null) {
        $this->name = $name;
        $this->url = $url;
        $this->icon = $icon;
        $this->text = $text;
        $this->rights = $rights;
        $this->conjunction = $conjunction;
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getUrl() {
        if (is_array($this->url)) {
            return $this->url[0];
        }
        return $this->url;
    }

    /**
     * @return string
     */
    public function getIcon() {
        return $this->icon;
    }

    /**
     * @return string
     */
    public function getText() {
        return $this->text;
    }

    public function isRoute($path) {
        if (is_array($this->url)) {
            return in_array($path, $this->url);
        }
        return $this->url === $path;
    }

    public function hasRights($uid = null) {
        return UserService::instance()->hasRight($this->rights, $this->conjunction, $uid);
    }

}
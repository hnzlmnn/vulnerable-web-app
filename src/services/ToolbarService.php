<?php

class ToolbarService {

    private static $instance;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new ToolbarService();
        }
        return self::$instance;
    }

    private $visible = false, $title, $buttons = array();

    public function __construct() {
    }

    public function hide() {
        $this->visible = false;
        return $this;
    }

    public function show() {
        $this->visible = true;
        return $this;
    }

    public function isVisible() {
        return $this->visible;
    }

    public function addButton($buttons) {
        if (is_array($buttons)) {
            foreach ($buttons as $button) {
                $this->addButton($button);
            }
            return $this;
        }
        $this->buttons[] = $buttons;
        return $this;
    }

    public function getButtons() {
        return $this->buttons;
    }

    public function getTitle() {
        return $this->title === null ? '' : $this->title;
    }

    public function setTitle($title) {
        $this->title = $title;
        return $this;
    }

}
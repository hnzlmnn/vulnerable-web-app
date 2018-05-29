<?php

class Button {

    private $url, $text, $icon, $classes, $onclick;

    public function __construct($url, $text, $icon = null, $classes = null, $onclick = null) {
        $this->url = $url;
        $this->text = $text;
        $this->icon = $icon;
        $this->classes = $classes;
        $this->onclick = $onclick;
    }

    /**
     * @return string
     */
    public function getUrl() {
        return $this->url;
    }

    /**
     * @return string
     */
    public function getText() {
        return $this->text;
    }

    /**
     * @return string
     */
    public function getIcon() {
        return $this->icon;
    }

    public function getClasses() {
        return is_array($this->classes) ? $this->classes : array();
    }

    public function getClassString() {
        $classes = '';
        foreach($this->getClasses() as $i => $class) {
            $classes .= ($i > 0 ? ' ' : '') . $class;
        }
        return $classes;
    }

    public function getOnClick() {
        return $this->onclick === null ? '' : $this->onclick;
    }

}
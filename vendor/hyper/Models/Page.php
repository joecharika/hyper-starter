<?php


namespace Hyper\Models;


class Page
{
    public $href;
    public $page;

    /**
     * @param mixed $href
     * @return Page
     */
    public function setHref($href)
    {
        $this->href = $href;
        return $this;
    }

    /**
     * @param mixed $page
     * @return Page
     */
    public function setPage($page)
    {
        $this->page = $page;
        return $this;
    }
}
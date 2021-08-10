<?php

namespace Gomicale;

use PHPHtmlParser\Dom;
use PHPHtmlParser\Options;

class DomLoader
{
    private $dom;

    /**
     * @param string $url Domのロード先URL
     */
    public function __construct(string $url)
    {
        $options = new Options();
        $options->setEnforceEncoding('utf8');
        $this->dom = new Dom();
        $this->dom->loadFromUrl($url, $options);
    }

    public function getDom(): Dom
    {
        return $this->dom;
    }
}

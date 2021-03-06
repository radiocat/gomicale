<?php

namespace Gomicale;

use PHPHtmlParser\Dom;
use PHPHtmlParser\Options;

class Sample
{

    public function hello(): string
    {
        return "Hello PHPUnit!";
    }

    public function exampleHttpParser(): string
    {

        $options = new Options();
        $options->setEnforceEncoding('utf8');
        $url = 'https://example.com/';
        $dom = new Dom();
        $dom->loadFromUrl($url, $options);
        $h1 = $dom->find('h1')[0];
        return $h1->text;
    }
}

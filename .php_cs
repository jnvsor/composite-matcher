<?php
return PhpCsFixer\Config::create()
    ->setRules([
        '@Symfony' => true,
        'array_syntax' => array('syntax' => 'short'),
    ])
    ->setFinder(PhpCsFixer\Finder::create()->in(__DIR__));

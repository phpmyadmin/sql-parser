<?php

// @see https://github.com/FriendsOfPHP/PHP-CS-Fixer

$finder = PhpCsFixer\Finder::create()
    ->in(array(__DIR__ . '/bin', __DIR__ . '/src', __DIR__ . '/tests', __DIR__ . '/tools'))
;

return PhpCsFixer\Config::create()
    ->setRules(array(
        '@PSR1' => true,
        '@PSR2' => true,
        '@Symfony' => true,
        'array_syntax' => array('syntax' => 'long'),
        'concat_space' => array('spacing' => true),
        'no_trailing_whitespace' => true,
        'phpdoc_order' => true,
    ))
    ->setFinder($finder)
;

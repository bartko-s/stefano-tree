<?php
$finder = PhpCsFixer\Finder::create()
    ->in('examples')
    ->in('src')
    ->in('tests')
    ->notPath('_files');

return PhpCsFixer\Config::create()
    ->setRules([
        '@PSR2' => true,
        '@Symfony' => true,
        'protected_to_private' => false,
        'declare_strict_types' => true,
    ])
    ->setFinder($finder);
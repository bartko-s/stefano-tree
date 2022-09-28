<?php
$finder = PhpCsFixer\Finder::create()
    ->in('examples')
    ->in('src')
    ->in('tests');

$config = new PhpCsFixer\Config();
return $config->setRules([
        '@PSR2' => true,
        '@Symfony' => true,
        '@PhpCsFixer' => true,
        'protected_to_private' => false,
        'declare_strict_types' => true,
        'return_assignment' => false,
        'ordered_class_elements' => false,
        'php_unit_test_class_requires_covers' => false,
        'no_superfluous_phpdoc_tags' => false,
        'no_useless_else' => false,
        'no_null_property_initialization' => false,
        'single_line_comment_style' => false,
        'multiline_comment_opening_closing' => false,
        'array_syntax' => array(
            'syntax' => 'long',
        ),
        'multiline_whitespace_before_semicolons' => array(
            'strategy' => 'no_multi_line',
        ),
    ])
    ->setFinder($finder);

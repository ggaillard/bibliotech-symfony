<?php

// Le style du code de BiblioTech : les règles de Symfony (@Symfony),
// avec deux adaptations pour le projet.
//   vendor/bin/php-cs-fixer check   liste les écarts
//   vendor/bin/php-cs-fixer fix     les corrige

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('var')
    ->notPath([
        'config/bundles.php',
        'config/reference.php',
    ])
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        // les noms de tests restent lisibles : test_m2_la_page_contact_existe
        'php_unit_method_casing' => ['case' => 'snake_case'],
        // les commentaires en français ne se terminent pas toujours par un point
        'phpdoc_summary' => false,
    ])
    ->setFinder($finder)
;

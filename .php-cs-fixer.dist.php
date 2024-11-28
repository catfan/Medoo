<?php
$config = new PhpCsFixer\Config();

$config
    ->setRules([
        '@PSR2' => true,
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'nullable_type_declaration_for_default_null_value' => true
    ])
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in(__DIR__ . '/src')
            ->in(__DIR__ . '/tests')
    )
;

return $config;

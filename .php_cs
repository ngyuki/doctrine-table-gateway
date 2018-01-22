<?php
$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->path('src/')
    ->path('tests/')
;

return PhpCsFixer\Config::create()
    ->setRules([
        '@PSR2' => true,

        // list の後のカッコまで消されるけど list はキーワードでは？
        'no_spaces_after_function_name' => false,

        // テストコードで public を省略している
        'visibility_required' => false,
    ])
    ->setFinder($finder)
;

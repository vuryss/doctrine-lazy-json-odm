<?php

$finder = new PhpCsFixer\Finder()
    ->in(__DIR__)
;

return new PhpCsFixer\Config()
    ->setRules([
        '@PER-CS' => true,
        '@PHP84Migration' => true,
        '@Symfony' => true,
        'single_line_throw' => false,
    ])
    ->setFinder($finder)
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
;

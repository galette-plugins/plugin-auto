<?php 

$finder = (new PhpCsFixer\Finder())
    ->in([
        __DIR__ . '/lib',
        __DIR__ . '/tests',
    ])
;

return (new PhpCsFixer\Config())
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
    ->setCacheFile(sys_get_temp_dir() . '/php-cs-fixer.plugin-galette-auto.cache')
    ->setRules([
        '@PSR12' => true,
        '@PER-CS' => true,
        '@PHP82Migration' => true,
        'trailing_comma_in_multiline' => false,
        'cast_spaces' => false,
        'single_line_empty_body' => false,
        'no_unused_imports' => true
    ])
    ->setFinder($finder)
;

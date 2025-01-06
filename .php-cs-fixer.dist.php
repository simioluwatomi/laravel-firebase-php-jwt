<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

/*
 * This document has been generated with
 * https://mlocati.github.io/php-cs-fixer-configurator/#version:2.15|configurator
 * you can change this configuration by importing this file.
 */

return (new Config())
    ->setRiskyAllowed(true)
    ->setLineEnding(PHP_EOL)
    ->setRules([
        '@Symfony' => true,
        '@PSR12' => true,
        '@PhpCsFixer' => true,
        'no_superfluous_phpdoc_tags' => false,
        'braces_position' => ['anonymous_classes_opening_brace' => 'next_line_unless_newline_at_signature_end'],
        'class_definition' => ['single_line' => true],
        'increment_style' => ['style' => 'post'],
        'logical_operators' => true,
        'not_operator_with_successor_space' => true,
        'multiline_whitespace_before_semicolons' => ['strategy' => 'no_multi_line'],
        'numeric_literal_separator' => ['strategy' => 'use_separator'],
        'blank_line_before_statement' => ['statements' => ['return', 'throw']],
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'php_unit_method_casing' => ['case' => 'snake_case'],
        'yoda_style' => ['equal' => false, 'identical' => false, 'less_and_greater' => false],
        'single_line_comment_style' => ['comment_types' => ['hash']],
        'declare_strict_types' => true,
        'php_unit_internal_class' => ['types' => []],
        'php_unit_test_class_requires_covers' => false,
    ])
    ->setCacheFile(__DIR__.'/.php_cs.cache')
    ->setFinder(
        Finder::create()
            ->exclude('vendor')
            ->exclude('bootstrap')
            ->exclude('storage')
            ->notPath('public/index.php')
            ->notPath('artisan')
            ->in(__DIR__)
    );

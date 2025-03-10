<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude([
        'dist'
    ]);

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        'braces_position' => [
            'anonymous_functions_opening_brace' => 'same_line',
            'anonymous_classes_opening_brace' => 'same_line',
            'classes_opening_brace' => 'same_line',
            'control_structures_opening_brace' => 'same_line',
            'functions_opening_brace' => 'same_line'
        ],
        'no_whitespace_in_blank_line' => false,
        'control_structure_braces' => false,
        'line_ending' => false,
        'visibility_required' => false,
    ])
    ->setFinder($finder);

<?php

$finder = PhpCsFixer\Finder::create()
    ->path('src')->name('*.php')
    ->path('tests')->name('*.php')
    ->exclude('tests/Fixtures')
    ->in(__DIR__)
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR2' => true,
        'array_syntax' => ['syntax' => 'short'],
        'no_unused_imports' => true,
        'blank_line_before_statement' => ['statements' => ['return']],
        'cast_spaces' => ['space' => 'single'],
        'concat_space' => ['spacing' => 'one'],
        'function_typehint_space' => true,
        'lowercase_cast' => true,
        'magic_constant_casing' => true,
        'class_attributes_separation' => ['elements' => ['method' => 'one']],
        'single_blank_line_before_namespace' => true,
        'no_singleline_whitespace_before_semicolons' => true,
        'no_spaces_around_offset' => true,
        'no_unused_imports' => true,
        'no_whitespace_before_comma_in_array' => true,
        'no_whitespace_in_blank_line' => true,
        'object_operator_without_whitespace' => true,
        'single_quote' => true,
        'no_blank_lines_after_phpdoc' => true,
        'no_extra_blank_lines' => true,
        'return_type_declaration' => ['space_before' => 'none'],
        'no_trailing_comma_in_list_call' => true,
        'no_trailing_comma_in_singleline_array' => true,
        'no_unneeded_control_parentheses' => true,
        'no_unneeded_curly_braces' => true,
        'short_scalar_cast' => true,
        'space_after_semicolon' => true,
        'ternary_operator_spaces' => true,
        'trailing_comma_in_multiline' => true,
        'trim_array_spaces' => true,

        'no_empty_phpdoc' => true,
        // 7.3 only 'no_superfluous_phpdoc_tags' => true,
        'phpdoc_align' => true,
        'general_phpdoc_tag_rename' => true,
        'phpdoc_inline_tag_normalizer' => true,
        'phpdoc_annotation_without_dot' => true,
        'phpdoc_tag_type' => true,
        'phpdoc_indent' => true,
        'phpdoc_var_without_name' => true,
        'phpdoc_types' => true,
        'phpdoc_trim' => true,
        'phpdoc_to_comment' => true,
        'phpdoc_summary' => true,
        'phpdoc_single_line_var_spacing' => true,
        'phpdoc_separation' => true,
        'phpdoc_scalar' => true,
        'phpdoc_no_useless_inheritdoc' => true,
        'phpdoc_no_empty_return' => true,
        'phpdoc_no_alias_tag' => true,
    ])
    ->setFinder($finder)
;

<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;
use PhpCsFixerCustomFixers\Fixer\CommentedOutFunctionFixer;
use PhpCsFixerCustomFixers\Fixer\CommentSurroundedBySpacesFixer;
use PhpCsFixerCustomFixers\Fixer\ConstructorEmptyBracesFixer;
use PhpCsFixerCustomFixers\Fixer\MultilinePromotedPropertiesFixer;
use PhpCsFixerCustomFixers\Fixer\NoCommentedOutCodeFixer;
use PhpCsFixerCustomFixers\Fixer\NoDuplicatedArrayKeyFixer;
use PhpCsFixerCustomFixers\Fixer\NoLeadingSlashInGlobalNamespaceFixer;
use PhpCsFixerCustomFixers\Fixer\NoPhpStormGeneratedCommentFixer;
use PhpCsFixerCustomFixers\Fixer\NoUselessCommentFixer;
use PhpCsFixerCustomFixers\Fixer\PhpdocArrayStyleFixer;
use PhpCsFixerCustomFixers\Fixer\PhpdocNoSuperfluousParamFixer;
use PhpCsFixerCustomFixers\Fixer\PhpdocParamTypeFixer;
use PhpCsFixerCustomFixers\Fixer\PhpdocSingleLineVarFixer;
use PhpCsFixerCustomFixers\Fixer\PhpdocTypesCommaSpacesFixer;
use PhpCsFixerCustomFixers\Fixer\PhpdocTypesTrimFixer;
use PhpCsFixerCustomFixers\Fixer\PromotedConstructorPropertyFixer;
use PhpCsFixerCustomFixers\Fixer\StringableInterfaceFixer;
use PhpCsFixerCustomFixers\Fixers;

$finder = Finder::create()
    ->ignoreDotFiles(true)
    ->ignoreVCSIgnored(true)
    ->in(['./src', './config', './tests']);

return (new Config())
    ->setParallelConfig(ParallelConfigFactory::detect())
    ->registerCustomFixers(new Fixers())
    ->setRiskyAllowed(true)
    ->setRules(
        [
            '@PHP82Migration'                            => true,
            '@PSR2'                                      => true,
            '@PSR12'                                     => true,
            '@PhpCsFixer'                                => true,
            '@Symfony:risky'                             => true,
            NoLeadingSlashInGlobalNamespaceFixer::name() => true,
            PhpdocNoSuperfluousParamFixer::name()        => true,
            CommentSurroundedBySpacesFixer::name()       => true,
            CommentedOutFunctionFixer::name()            => ['print_r', 'var_dump', 'var_export', 'dump', 'dd', 'ray'],
            ConstructorEmptyBracesFixer::name()          => true,
            MultilinePromotedPropertiesFixer::name()     => true,
            NoCommentedOutCodeFixer::name()              => true,
            NoDuplicatedArrayKeyFixer::name()            => true,
            NoPhpStormGeneratedCommentFixer::name()      => true,
            NoUselessCommentFixer::name()                => true,
            PhpdocArrayStyleFixer::name()                => true,
            PhpdocParamTypeFixer::name()                 => true,
            PhpdocSingleLineVarFixer::name()             => true,
            PhpdocTypesCommaSpacesFixer::name()          => true,
            PhpdocTypesTrimFixer::name()                 => true,
            PromotedConstructorPropertyFixer::name()     => true,
            StringableInterfaceFixer::name()             => true,
            'simplified_null_return'                     => false,
            'no_unused_imports'                          => true,
            'fully_qualified_strict_types'               => true,
            'align_multiline_comment'                    => true,
            'array_indentation'                          => true,
            'binary_operator_spaces'                     => [
                'default'   => 'single_space',
                'operators' => [
                    '=>' => 'align_single_space_minimal',
                    '='  => 'align_single_space_minimal',
                ],
            ],
            'modernize_types_casting'                => true,
            'modernize_strpos'                       => true,
            'get_class_to_class_keyword'             => true,
            'no_useless_else'                        => true,
            'simplified_if_return'                   => true,
            'ternary_to_elvis_operator'              => true,
            'statement_indentation'                  => true,
            'no_php4_constructor'                    => true,
            'no_superfluous_phpdoc_tags'             => true,
            'void_return'                            => true,
            'yoda_style'                             => false,
            'single_line_comment_style'              => ['comment_types' => ['hash']],
            'php_unit_test_class_requires_covers'    => false,
            'php_unit_internal_class'                => false,
            'return_assignment'                      => false,
            'octal_notation'                         => false,
            'non_printable_character'                => true,
            'multiline_whitespace_before_semicolons' => ['strategy' => 'no_multi_line'],
            'global_namespace_import'                => ['import_classes' => true, 'import_constants' => false, 'import_functions' => false],
            'single_line_after_imports'              => true,
            'native_function_invocation'             => false,
            'self_accessor'                          => false,
            'no_unneeded_final_method'               => true,
            'no_unset_on_property'                   => true,
            'cast_spaces'                            => true,
        ]
    )
    ->setFinder($finder);

<?php

declare(strict_types=1);

use NunoMaduro\PhpInsights\Domain\Insights\CyclomaticComplexityIsHigh;
use NunoMaduro\PhpInsights\Domain\Insights\ForbiddenDefineFunctions;
use NunoMaduro\PhpInsights\Domain\Insights\ForbiddenFinalClasses;
use NunoMaduro\PhpInsights\Domain\Insights\ForbiddenNormalClasses;
use NunoMaduro\PhpInsights\Domain\Insights\ForbiddenPrivateMethods;
use NunoMaduro\PhpInsights\Domain\Insights\ForbiddenTraits;
use NunoMaduro\PhpInsights\Domain\Metrics\Architecture\Classes;
use NunoMaduro\PhpInsights\Domain\Metrics\Architecture\Functions;
use NunoMaduro\PhpInsights\Domain\Metrics\Code\Globally;
use NunoMaduro\PhpInsights\Domain\Metrics\Complexity\Complexity;
use NunoMaduro\PhpInsights\Domain\Metrics\Style\Style;
use NunoMaduro\PhpInsights\Domain\Sniffs\ForbiddenSetterSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Arrays\DisallowLongArraySyntaxSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\CodeAnalysis\UnconditionalIfStatementSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\CodeAnalysis\UnnecessaryFinalModifierSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Commenting\TodoSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Debug\CSSLintSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Debug\JSHintSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Files\LineLengthSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Formatting\MultipleStatementAlignmentSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Formatting\SpaceAfterNotSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Metrics\NestingLevelSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\NamingConventions\AbstractClassNamePrefixSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\NamingConventions\CamelCapsFunctionNameSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\NamingConventions\UpperCaseConstantNameSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\PHP\ForbiddenFunctionsSniff;
use PHP_CodeSniffer\Standards\PEAR\Sniffs\WhiteSpace\ObjectOperatorIndentSniff;
use PHP_CodeSniffer\Standards\PSR1\Sniffs\Methods\CamelCapsMethodNameSniff;
use PHP_CodeSniffer\Standards\PSR12\Sniffs\Functions\ReturnTypeDeclarationSniff;
use PHP_CodeSniffer\Standards\PSR2\Sniffs\Classes\PropertyDeclarationSniff;
use PHP_CodeSniffer\Standards\Squiz\Sniffs\PHP\CommentedOutCodeSniff;
use PHP_CodeSniffer\Standards\Squiz\Sniffs\PHP\NonExecutableCodeSniff;
use PHP_CodeSniffer\Standards\Squiz\Sniffs\WhiteSpace\ScopeClosingBraceSniff;
use PhpCsFixer\Fixer\Basic\BracesFixer;
use PhpCsFixer\Fixer\ClassNotation\ClassDefinitionFixer;
use PhpCsFixer\Fixer\ClassNotation\ProtectedToPrivateFixer;
use PhpCsFixer\Fixer\Operator\BinaryOperatorSpacesFixer;
use PhpCsFixer\Fixer\ReturnNotation\ReturnAssignmentFixer;
use PhpCsFixer\Fixer\Whitespace\NoSpacesInsideParenthesisFixer;
use SlevomatCodingStandard\Sniffs\Classes\DisallowLateStaticBindingForConstantsSniff;
use SlevomatCodingStandard\Sniffs\Classes\ForbiddenPublicPropertySniff;
use SlevomatCodingStandard\Sniffs\Classes\SuperfluousAbstractClassNamingSniff;
use SlevomatCodingStandard\Sniffs\Classes\SuperfluousErrorNamingSniff;
use SlevomatCodingStandard\Sniffs\Classes\SuperfluousExceptionNamingSniff;
use SlevomatCodingStandard\Sniffs\Classes\SuperfluousInterfaceNamingSniff;
use SlevomatCodingStandard\Sniffs\Classes\SuperfluousTraitNamingSniff;
use SlevomatCodingStandard\Sniffs\ControlStructures\AssignmentInConditionSniff;
use SlevomatCodingStandard\Sniffs\ControlStructures\DisallowEmptySniff;
use SlevomatCodingStandard\Sniffs\ControlStructures\DisallowShortTernaryOperatorSniff;
use SlevomatCodingStandard\Sniffs\ControlStructures\DisallowYodaComparisonSniff;
use SlevomatCodingStandard\Sniffs\Functions\FunctionLengthSniff;
use SlevomatCodingStandard\Sniffs\Namespaces\AlphabeticallySortedUsesSniff;
use SlevomatCodingStandard\Sniffs\TypeHints\DeclareStrictTypesSniff;
use SlevomatCodingStandard\Sniffs\TypeHints\DisallowMixedTypeHintSniff;
use SlevomatCodingStandard\Sniffs\TypeHints\NullTypeHintOnLastPositionSniff;
use SlevomatCodingStandard\Sniffs\TypeHints\ParameterTypeHintSniff;
use SlevomatCodingStandard\Sniffs\TypeHints\ParameterTypeHintSpacingSniff;
use SlevomatCodingStandard\Sniffs\TypeHints\PropertyTypeHintSniff;
use SlevomatCodingStandard\Sniffs\TypeHints\ReturnTypeHintSniff;
use SlevomatCodingStandard\Sniffs\Whitespaces\DuplicateSpacesSniff;

return [
    'preset'  => 'laravel',
    'ide'     => 'phpstorm',
    'exclude' => [],
    'add'     => [
        Classes::class => [
            ForbiddenFinalClasses::class,
            UpperCaseConstantNameSniff::class,
        ],
        Functions::class => [
            ReturnTypeDeclarationSniff::class,
            CamelCapsMethodNameSniff::class,
            ForbiddenFunctionsSniff::class,
            NonExecutableCodeSniff::class,
            UnconditionalIfStatementSniff::class,
            CamelCapsFunctionNameSniff::class,
        ],
        Style::class => [
            CommentedOutCodeSniff::class,
            MultipleStatementAlignmentSniff::class,
            CSSLintSniff::class,
            JSHintSniff::class,
            AbstractClassNamePrefixSniff::class,
            TodoSniff::class,
        ],
        Complexity::class => [
            NestingLevelSniff::class,
        ],
        Globally::class => [
            UnnecessaryFinalModifierSniff::class,
            DisallowLongArraySyntaxSniff::class,
        ],
    ],
    'remove' => [
        ClassDefinitionFixer::class,
        PropertyDeclarationSniff::class,
        BracesFixer::class,
        NoSpacesInsideParenthesisFixer::class.
        ReturnAssignmentFixer::class,
        MultipleStatementAlignmentSniff::class,
        ProtectedToPrivateFixer::class,
        AlphabeticallySortedUsesSniff::class,
        DeclareStrictTypesSniff::class,
        DisallowMixedTypeHintSniff::class,
        ForbiddenDefineFunctions::class,
        ForbiddenNormalClasses::class,
        ForbiddenTraits::class,
        BinaryOperatorSpacesFixer::class,
        SpaceAfterNotSniff::class,
        DuplicateSpacesSniff::class,
        ParameterTypeHintSpacingSniff::class,
        SuperfluousAbstractClassNamingSniff::class,
        SuperfluousErrorNamingSniff::class,
        SuperfluousExceptionNamingSniff::class,
        SuperfluousInterfaceNamingSniff::class,
        SuperfluousTraitNamingSniff::class,
        ForbiddenSetterSniff::class,
        PropertyDeclarationSniff::class,
        ForbiddenPublicPropertySniff::class,
        DisallowEmptySniff::class,
        AssignmentInConditionSniff::class,
        NullTypeHintOnLastPositionSniff::class,
        ParameterTypeHintSniff::class,
        PropertyTypeHintSniff::class,
        ReturnTypeHintSniff::class,
        DisallowLateStaticBindingForConstantsSniff::class,
        DisallowShortTernaryOperatorSniff::class,
        DisallowYodaComparisonSniff::class,
        ScopeClosingBraceSniff::class,
        ObjectOperatorIndentSniff::class,
        PHP_CodeSniffer\Standards\PEAR\Sniffs\WhiteSpace\ScopeClosingBraceSniff::class,
    ],
    'config' => [
        ForbiddenPrivateMethods::class => [
            'title' => 'you wrote `protected` the wrong way',
        ],
        CyclomaticComplexityIsHigh::class => [
            'maxComplexity' => 10,
        ],
        FunctionLengthSniff::class => [
            'maxLinesLength' => 40,
        ],
        NestingLevelSniff::class => [
            'nestingLevel'         => 3,
            'absoluteNestingLevel' => 5,
        ],
        LineLengthSniff::class => [
            'lineLimit'         => 120,
            'absoluteLineLimit' => 140,
            'ignoreComments'    => true,
        ],
        ForbiddenFunctionsSniff::class => [
            'forbiddenFunctions' => [
                'sizeof'     => 'count',
                'dd'         => null,
                'ddd'        => null,
                'dump'       => null,
                'var_dump'   => null,
                'factory'    => null,
                'exit'       => null,
                'print'      => null,
                'print_r'    => null,
                'printf'     => null,
                'shell_exec' => null,
                'eval'       => null,
                'goto'       => null,
                'get_class'  => null,
                'mysql'      => null,
                'mysqli'     => null,
            ],
        ],
    ],
    'requirements' => [
        'min-quality'      => 80,
        'min-complexity'   => 70,
        'min-architecture' => 80,
        'min-style'        => 80,
    ],
    'threads' => null,
];

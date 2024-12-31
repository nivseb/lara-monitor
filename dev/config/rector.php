<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Assign\CombinedAssignRector;
use Rector\CodeQuality\Rector\ClassMethod\InlineArrayReturnAssignRector;
use Rector\CodeQuality\Rector\Equal\UseIdenticalOverEqualWithSameTypeRector;
use Rector\CodeQuality\Rector\FuncCall\SimplifyRegexPatternRector;
use Rector\CodeQuality\Rector\Identical\BooleanNotIdenticalToNotIdenticalRector;
use Rector\CodeQuality\Rector\Ternary\ArrayKeyExistsTernaryThenValueToCoalescingRector;
use Rector\CodingStyle\Rector\ClassMethod\MakeInheritedMethodVisibilitySameAsParentRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\Cast\RecastingRemovalRector;
use Rector\DeadCode\Rector\ClassConst\RemoveUnusedPrivateClassConstantRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveEmptyClassMethodRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessParamTagRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessReturnTagRector;
use Rector\DeadCode\Rector\If_\RemoveAlwaysTrueIfConditionRector;
use Rector\DeadCode\Rector\If_\RemoveDeadInstanceOfRector;
use Rector\DeadCode\Rector\Property\RemoveUselessVarTagRector;
use Rector\DeadCode\Rector\Return_\RemoveDeadConditionAboveReturnRector;
use Rector\EarlyReturn\Rector\Foreach_\ChangeNestedForeachIfsToEarlyContinueRector;
use Rector\EarlyReturn\Rector\If_\ChangeNestedIfsToEarlyReturnRector;
use Rector\EarlyReturn\Rector\If_\RemoveAlwaysElseRector;
use Rector\Php80\Rector\Catch_\RemoveUnusedVariableInCatchRector;
use Rector\Php80\Rector\Switch_\ChangeSwitchToMatchRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddParamTypeFromPropertyTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromReturnDirectArrayRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromReturnNewRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictConstantReturnRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictTypedPropertyRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths(['./src', './config', './tests']);
    $rectorConfig->skip(['./vendor']);

    $rectorConfig->importNames();
    $rectorConfig->removeUnusedImports();

    $rectorConfig->rule(ArrayKeyExistsTernaryThenValueToCoalescingRector::class);
    $rectorConfig->rule(BooleanNotIdenticalToNotIdenticalRector::class);
    $rectorConfig->rule(CombinedAssignRector::class);
    $rectorConfig->rule(InlineArrayReturnAssignRector::class);
    $rectorConfig->rule(SimplifyRegexPatternRector::class);
    $rectorConfig->rule(UseIdenticalOverEqualWithSameTypeRector::class);
    $rectorConfig->rule(MakeInheritedMethodVisibilitySameAsParentRector::class);
    $rectorConfig->rule(RecastingRemovalRector::class);
    $rectorConfig->rule(RemoveAlwaysTrueIfConditionRector::class);
    $rectorConfig->rule(RemoveDeadConditionAboveReturnRector::class);
    $rectorConfig->rule(RemoveDeadInstanceOfRector::class);
    $rectorConfig->rule(RemoveEmptyClassMethodRector::class);
    $rectorConfig->rule(RemoveUnusedPrivateClassConstantRector::class);
    $rectorConfig->rule(RemoveUnusedPrivateMethodRector::class);
    $rectorConfig->rule(RemoveUselessParamTagRector::class);
    $rectorConfig->rule(RemoveUselessReturnTagRector::class);
    $rectorConfig->rule(RemoveUselessVarTagRector::class);
    $rectorConfig->rule(ChangeNestedForeachIfsToEarlyContinueRector::class);
    $rectorConfig->rule(ChangeNestedIfsToEarlyReturnRector::class);
    $rectorConfig->rule(RemoveAlwaysElseRector::class);
    $rectorConfig->rule(ChangeSwitchToMatchRector::class);
    $rectorConfig->rule(RemoveUnusedVariableInCatchRector::class);
    $rectorConfig->rule(ReadOnlyPropertyRector::class);
    $rectorConfig->rule(AddParamTypeFromPropertyTypeRector::class);
    $rectorConfig->rule(ReturnTypeFromReturnDirectArrayRector::class);
    $rectorConfig->rule(ReturnTypeFromReturnNewRector::class);
    $rectorConfig->rule(ReturnTypeFromStrictConstantReturnRector::class);
    $rectorConfig->rule(ReturnTypeFromStrictTypedPropertyRector::class);
};

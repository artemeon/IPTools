<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
                   ->withPhpSets()
                   ->withAttributesSets(phpunit: true)
                   ->withRules([
                       Rector\CodeQuality\Rector\Ternary\ArrayKeyExistsTernaryThenValueToCoalescingRector::class,
                       Rector\CodeQuality\Rector\NullsafeMethodCall\CleanupUnneededNullsafeOperatorRector::class,
                       Rector\CodeQuality\Rector\ClassMethod\InlineArrayReturnAssignRector::class,
                       Rector\CodingStyle\Rector\Stmt\NewlineAfterStatementRector::class,
                       Rector\CodingStyle\Rector\ClassMethod\NewlineBeforeNewAssignSetRector::class,
                       Rector\Php71\Rector\BooleanOr\IsIterableRector::class,
                       Rector\Php71\Rector\FuncCall\RemoveExtraParametersRector::class,
                       Rector\Php73\Rector\FuncCall\JsonThrowOnErrorRector::class,
                       Rector\TypeDeclaration\Rector\FunctionLike\AddReturnTypeDeclarationFromYieldsRector::class,
                       Rector\TypeDeclaration\Rector\FunctionLike\AddParamTypeForFunctionLikeWithinCallLikeArgDeclarationRector::class,
                       Rector\TypeDeclaration\Rector\FunctionLike\AddParamTypeSplFixedArrayRector::class,
                       Rector\Php80\Rector\Catch_\RemoveUnusedVariableInCatchRector::class,
                       Rector\Php84\Rector\Param\ExplicitNullableParamTypeRector::class,
                       Rector\DeadCode\Rector\Foreach_\RemoveUnusedForeachKeyRector::class,
                       Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPromotedPropertyRector::class,
                       Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictFluentReturnRector::class,
                       Rector\Php80\Rector\Class_\StringableForToStringRector::class,
                       Rector\CodeQuality\Rector\Ternary\UnnecessaryTernaryExpressionRector::class,
                       Rector\CodingStyle\Rector\ArrowFunction\StaticArrowFunctionRector::class,
                       Rector\CodingStyle\Rector\Closure\StaticClosureRector::class,
                       Rector\DeadCode\Rector\Node\RemoveNonExistingVarAnnotationRector::class,
                       Rector\DeadCode\Rector\ClassMethod\RemoveUnusedConstructorParamRector::class,
                       Rector\DeadCode\Rector\Concat\RemoveConcatAutocastRector::class,
                       Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodParameterRector::class,
                       Rector\Php72\Rector\FuncCall\GetClassOnNullRector::class,
                       Rector\Php73\Rector\FuncCall\ArrayKeyFirstLastRector::class,
                       Rector\Php80\Rector\ClassMethod\AddParamBasedOnParentClassMethodRector::class,
                       Rector\Php80\Rector\NotIdentical\StrContainsRector::class,
                       Rector\Php80\Rector\Identical\StrEndsWithRector::class,
                       Rector\Php80\Rector\Identical\StrStartsWithRector::class,
                       Rector\TypeDeclaration\Rector\ClassMethod\BoolReturnTypeFromBooleanStrictReturnsRector::class,
                       Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromReturnNewRector::class,
                       Rector\TypeDeclaration\Rector\ClassMethod\ParamTypeByMethodCallTypeRector::class,
                       Rector\TypeDeclaration\Rector\ClassMethod\NumericReturnTypeFromStrictScalarReturnsRector::class,
                       Rector\TypeDeclaration\Rector\ClassMethod\AddMethodCallBasedStrictParamTypeRector::class,
                       Rector\CodeQuality\Rector\If_\ExplicitBoolCompareRector::class,
                       Rector\CodeQuality\Rector\Foreach_\ForeachItemsAssignToEmptyArrayToAssignRector::class,
                       Rector\CodeQuality\Rector\Foreach_\ForeachToInArrayRector::class,
                       Rector\CodeQuality\Rector\BooleanAnd\RemoveUselessIsObjectCheckRector::class,
                       Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector::class,
                   ])
                   ->withPaths([
                       __DIR__ . '/src',
                       __DIR__ . '/tests',
                   ])
                   ->withTypeCoverageLevel(3);
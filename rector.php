<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\BooleanAnd\RepeatedAndNotEqualToNotInArrayRector;
use Rector\CodeQuality\Rector\BooleanNot\NegatedAndsToPositiveOrsRector;
use Rector\CodeQuality\Rector\BooleanOr\RepeatedOrEqualToInArrayRector;
use Rector\CodeQuality\Rector\ClassMethod\LocallyCalledStaticMethodToNonStaticRector;
use Rector\CodingStyle\Rector\FuncCall\FunctionFirstClassCallableRector;
use Rector\Config\RectorConfig;
use Rector\EarlyReturn\Rector\If_\ChangeIfElseValueAssignToEarlyReturnRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\Php82\Rector\Class_\ReadOnlyClassRector;
use Rector\PHPUnit\PHPUnit100\Rector\Class_\ParentTestClassConstructorRector;
use Rector\PHPUnit\PHPUnit120\Rector\CallLike\CreateStubOverCreateMockArgRector;
use Rector\PHPUnit\PHPUnit120\Rector\ClassMethod\ExpressionCreateMockToCreateStubRector;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\ValueObject\PhpVersion;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->phpVersion(PhpVersion::PHP_83);

    $rectorConfig->paths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);

    $rectorConfig->sets([
        SetList::CODE_QUALITY,
        PHPUnitSetList::COMPOSER_BASED,
    ]);
    $rectorConfig->skip([
        ReadOnlyPropertyRector::class => [
            __DIR__ . '/src/SDK/Metrics/Stream/SynchronousMetricStream.php',
            __DIR__ . '/tests/Unit/Extension/Propagator',
        ],
        ReadOnlyClassRector::class, // a class may need a mutable property later
        LocallyCalledStaticMethodToNonStaticRector::class,
        RepeatedOrEqualToInArrayRector::class,
        RepeatedAndNotEqualToNotInArrayRector::class,
        NegatedAndsToPositiveOrsRector::class,
        ChangeIfElseValueAssignToEarlyReturnRector::class,
        ParentTestClassConstructorRector::class, // misfires on test classes, breaking phpunit
        FunctionFirstClassCallableRector::class => [
            __DIR__ . '/src/Trace/Sampler/TraceOptions.php',
        ],
        // misses expectations set on the doubles in another method
        CreateStubOverCreateMockArgRector::class => [
            __DIR__ . '/tests/Unit/Trace/Sampler/SamplerTest.php',
            __DIR__ . '/tests/Unit/Resource/Detectors/UamsTest.php',
            __DIR__ . '/tests/Unit/Trace/Sampler/SwoHttpSamplerTest.php',
            __DIR__ . '/tests/Unit/Trace/Sampler/CountersTest.php',
            __DIR__ . '/tests/Unit/Trace/Sampler/HttpSamplerTest.php',
            __DIR__ . '/tests/Unit/Trace/Sampler/SampleStateTest.php',
        ],
        // assertEquals() against createMock() doubles fails once these become stubs
        ExpressionCreateMockToCreateStubRector::class => [
            __DIR__ . '/tests/Unit/Trace/Sampler/SampleStateTest.php',
            __DIR__ . '/tests/Unit/Resource/Detectors/UamsTest.php',
            __DIR__ . '/tests/Unit/Trace/Sampler/SamplerTest.php',
        ],
    ]);
};

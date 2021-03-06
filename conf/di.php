<?php
use Interop\Container\ContainerInterface;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection as r;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;

$obj = function (array $parameters, string $class = '') {
    $obj = $class ? DI\object($class) : DI\object();

    foreach ($parameters as $key => $value) {
        if (is_int($key)) {
            $key = $value;
        }

        if (is_string($value)) {
            $value = $value[0] == '#' ? substr($value, 1) : DI\get($value);
        }

        $obj->constructorParameter($key, $value);
    }

    return $obj;
};

$parameters = [
    'tmpDir' => sys_get_temp_dir(),
    'bootstrap' => null,
    'bootstrapFile' => null,
    'ignorePathPatterns' => [],
    'fileExtensions' => [ 'php', ],
    'polluteScopeWithLoopInitialAssignments' => true,
    'polluteCatchScopeWithTryAssignments' => false,
    'defineVariablesWithoutDefaultBranch' => false,
    'ignoreErrors' => [],
    'reportUnmatchedIgnoredErrors' =>  true,
    'earlyTerminatingMethodCalls' => [],
    'customRulesetUsed' => false,
    'checkThisOnly' => true,
    'checkFunctionArgumentTypes' => true,
    'enableUnionTypes' => true,
    'cacheOptions' => [
        'path' => DI\string('{tmpDir}/phpstan'),
    ],
    'universalObjectCratesClasses' => [
        'stdClass',
        'SimpleXMLElement',
    ],
];

$services = [
    PhpParser\NodeTraverser::class => function (PhpParser\NodeVisitor\NameResolver $nameResolver) {
        $nodeTraverser = new PhpParser\NodeTraverser;
        $nodeTraverser->addVisitor($nameResolver);

        return $nodeTraverser;
    },

    PhpParser\Parser::class => DI\object(PhpParser\Parser\Php7::class),

    PHPStan\Analyser\Analyser::class => $obj([
        'ignoreErrors',
        'reportUnmatchedIgnoredErrors',
        'bootstrapFile',
    ]),

    PHPStan\Analyser\NodeScopeResolver::class => $obj([
        'polluteScopeWithLoopInitialAssignments',
        'polluteCatchScopeWithTryAssignments',
        'defineVariablesWithoutDefaultBranch',
        'earlyTerminatingMethodCalls',
    ]),

    PHPStan\Command\AnalyseApplication::class => $obj([
        'fileExtensions',
        'ignorePathPatterns',
    ]),

    PHPStan\Parser\CachedParser::class => $obj([
        'originalParser' => PHPStan\Parser\Parser::class,
    ]),

    r\Php\UniversalObjectCratesClassReflectionExtension::class => $obj([
        'classes' => 'universalObjectCratesClasses',
    ]),

    r\Php\PhpMethodReflectionFactory::class => DI\object(r\Php\PhpMethodReflectionFactoryDI::class),

    r\FunctionReflectionFactory::class => DI\object(r\FunctionReflectionFactoryDI::class),

    r\PropertiesClassReflectionExtension::class => [
        DI\get(r\Php\PhpClassReflectionExtension::class),
        DI\get(r\Annotations\AnnotationsPropertiesClassReflectionExtension::class),
        DI\get(r\PhpDefect\PhpDefectClassReflectionExtension::class),
    ],

    r\MethodsClassReflectionExtension::class => [
        DI\get(r\Php\PhpClassReflectionExtension::class),
        DI\get(r\Annotations\AnnotationsMethodsClassReflectionExtension::class),
    ],

    PHPStan\Type\DynamicMethodReturnTypeExtension::class => [
    ],

    PHPStan\Type\DynamicStaticMethodReturnTypeExtension::class => [
    ],

    PHPStan\Rules\FunctionCallParametersCheck::class => $obj([
        'checkArgumentTypes' => 'checkFunctionArgumentTypes',
    ]),

    PHPStan\Type\FileTypeMapper::class => $obj([
        'enableUnionTypes',
    ]),

    PHPStan\Broker\Broker::class=> $obj([
        'propertiesClassReflectionExtensions' => DI\get(PropertiesClassReflectionExtension::class),
        'methodsClassReflectionExtensions' => DI\get(MethodsClassReflectionExtension::class),
        'dynamicMethodReturnTypeExtensions' => DI\get(DynamicMethodReturnTypeExtension::class),
        'dynamicStaticMethodReturnTypeExtensions' => DI\get(DynamicStaticMethodReturnTypeExtension::class),
    ]),

    Stash\Interfaces\DriverInterface::class => $obj([
        'options' => 'cacheOptions',
    ], Stash\Driver\FileSystem::class),

    Psr\Cache\CacheItemPoolInterface::class => DI\object(Stash\Pool::class),

    PHPStan\Parser\Parser::class => DI\object(PHPStan\Parser\DirectParser::class),

    PHPStan\Rules\Registry::class => DI\factory([PHPStan\Rules\RegistryFactory::class, 'create']),

    // rules cannot be autowiring
    PHPStan\Rules\Classes\AccessPropertiesRule::class => $obj([
        'checkThisOnly',
    ]),
    PHPStan\Rules\Methods\CallMethodsRule::class => $obj([
        'checkThisOnly',
    ])
];

return $parameters + $services;

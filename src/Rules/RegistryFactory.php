<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use Interop\Container\ContainerInterface;

class RegistryFactory
{
    const RULES = [
        // [level, rule class name]
        [0, Classes\AccessPropertiesRule::class],
        [0, Classes\AccessStaticPropertiesRule::class],
        [0, Classes\ClassConstantRule::class],
        [0, Classes\ExistingClassInInstanceOfRule::class],
        [0, Classes\ExistingClassesInPropertiesRule::class],
        [0, Classes\InstantiationRule::class],
        [0, Classes\RequireParentConstructCallRule::class],
        [0, Classes\UnusedConstructorParametersRule::class],
        [0, Exceptions\CatchedExceptionExistenceRule::class],
        [0, Functions\CallToFunctionParametersRule::class],
        [0, Functions\CallToNonExistentFunctionRule::class],
        [0, Functions\ExistingClassesInClosureTypehintsRule::class],
        [0, Functions\ExistingClassesInTypehintsRule::class],
        [0, Functions\PrintfParametersRule::class],
        [0, Functions\UnusedClosureUsesRule::class],
        [0, Methods\CallMethodsRule::class],
        [0, Methods\CallStaticMethodsRule::class],
        [0, Methods\ExistingClassesInTypehintsRule::class],
        [0, Variables\ThisVariableRule::class],
        [1, Variables\DefinedVariableRule::class],
        [1, Variables\DefinedVariableInAnonymousFunctionUseRule::class],
        [3, Arrays\AppendedArrayItemTypeRule::class],
        [3, Classes\DefaultValueTypesAssignedToPropertiesRule::class],
        [3, Classes\TypesAssignedToPropertiesRule::class],
        [3, Functions\ClosureReturnTypeRule::class],
        [3, Functions\ReturnTypeRule::class],
        [3, Methods\ReturnTypeRule::class],
        [3, Variables\VariableCloningRule::class],
        [4, Cast\UselessCastRule::class],
        [4, Comparison\StrictComparisonOfDifferentTypesRule::class],
    ];

    const RULE_TAG = 'phpstan.rules.rule';
    private static $selectedRules = [];

    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function create(): Registry
    {
        $services = [];
        foreach (self::$selectedRules as $rule) {
            if ($this->container->has($rule)) {
                $services[] = $this->container->get($rule);
            }
        }

        return new Registry($services);
    }

    public static function getRuleArgList(int $level)
    {
        $rules = [];
        $prefix = __NAMESPACE__.'\\';
        foreach (self::RULES as list($ruleLevel, $className)) {
            if ($ruleLevel <= $level) {
                $rule = str_replace($prefix, '', $className);
                $rules[] = "[level:$ruleLevel] ".$rule;
            }
        }

        return $rules;
    }

    public static function setRules(array $rules)
    {
        self::$selectedRules = [];
        foreach ($rules as $rule) {
            if ($rule[0] != "\\") {
                $rule = __NAMESPACE__."\\".$rule;
            }
            self::$selectedRules[] = $rule;
        }
    }
}

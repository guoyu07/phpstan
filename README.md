# PHPStan - PHP Static Analysis Tool

Why I want make my own fork? See the [story](why-fork.md).

[![Build Status](https://travis-ci.org/lvht/phpstan.svg?branch=master)](https://travis-ci.org/lvht/phpstan)
[![Latest Stable Version](https://poser.pugx.org/lvht/phpstan/v/stable)](https://packagist.org/packages/lvht/phpstan)
[![License](https://poser.pugx.org/lvht/phpstan/license)](https://packagist.org/packages/lvht/phpstan)
[![PHPStan](https://img.shields.io/badge/PHPStan-enabled-brightgreen.svg?style=flat)](https://github.com/lvht/phpstan)

PHPStan focuses on finding errors in your code without actually running it. It catches whole classes of bugs
even before you write tests for the code.

PHPStan moves PHP closer to compiled languages in the sense that the correctness of each line of the code
can be checked before you run the actual line.

**[Read more about PHPStan on Medium.com »](https://medium.com/@ondrejmirtes/phpstan-2939cd0ad0e3)**

## Is PHPStan helping you to avoid bugs in production?

It currently performs the following checks on your code:

* Existence of classes and interfaces in `instanceof`, `catch`, typehints, other language constructs and even annotations. PHP does not do this and just stays silent instead.
* Existence of variables while respecting scopes of branches and loops.
* Existence and visibility of called methods and functions.
* Existence and visibility of accessed properties and constants.
* Correct types assigned to properties.
* Correct number and types of parameters passed to constructors, methods and functions.
* Correct types returned from methods and functions.
* Correct number of parameters passed to `sprintf`/`printf` calls based on format strings.
* Useless casts like `(string) 'foo'`.
* Unused constructor parameters - they can either be deleted or the author forgot to
use them in the class code.
* That only objects are passed to the `clone` keyword.

## Extensibility

Unique feature of PHPStan is the ability to define and statically check "magic" behaviour of classes -
accessing properties that are not defined in the class but are created in `__get` and `__set`
and invoking methods using `__call`.

See [Class reflection extensions](#class-reflection-extensions) and [Dynamic return type extensions](#dynamic-return-type-extensions).

You can also install already created framework-specific extensions:

* [Doctrine](https://github.com/phpstan/phpstan-doctrine)
* [Nette Framework](https://github.com/phpstan/phpstan-nette)
* [Dibi - Database Abstraction Library](https://github.com/phpstan/phpstan-dibi)

Other framework-specific extension will be coming soon!

## Prerequisities

PHPStan requires PHP >= 7.0. You have to run it in environment with PHP 7.x but the actual code does not have to use
PHP 7.x features. (Code written for PHP 5.6 and earlier can run on 7.x mostly unmodified.)

PHPStan works best with modern object-oriented code. The more strongly-typed your code is, the more information
you give PHPStan to work with.

Properly annotated and typehinted code (class properties, function and method arguments, return types) helps
not only static analysis tools but also other people that work with the code to understand it.

## Installation

We recommend you download the [`phpstan.phar`](https://github.com/lvht/phpstan/releases/download/untagged-6de55632c924a49640fb/phpstan.phar).

If you want to build `phpstan.phar` manually, you need clone the project,
and then run `composer update --no-dev`, and then go to the parent dir and
run [`phar-composer`](https://github.com/clue/phar-composer).

To start performing analysis on your code, require PHPStan in [Composer](https://getcomposer.org/):

```
composer require --dev lvht/phpstan
```

Composer will install PHPStan's executable in its `bin-dir` which defaults to `vendor/bin`.

## First run

To let PHPStan analyse your codebase, you have use the `analyse` commmand and point it to the right directories.

So for example if you have your classes in directories `src` and `tests`, you can run PHPStan like this:

```
vendor/bin/phpstan analyse src tests
```

PHPStan will probably find some errors, but don't worry, your code might be just fine. Errors found
on the first run tend to be:

* Extra arguments passed to functions (e. g. function requires two arguments, the code passes three)
* Extra arguments passed to print/sprintf functions (e. g. format string contains one placeholder, the code passes two values to replace)
* Obvious errors in dead code
* Magic behaviour that needs to be defined. See [Extensibility](#extensibility).

After fixing the obvious mistakes in the code, look to the following section
for all the configuration options that will bring the number of reported errors to zero
making PHPStan suitable to run as part of your continous integration script.

## Rule levels

Rule levels. If you want to use PHPStan but your codebase isn't up to speed with strong typing
and PHPStan's strict checks, you can choose from currently 5 levels
(0 is the loosest and 4 is the strictest) by passing `--level` to `analyse` command. Default level is `0`.

This feature enables incremental adoption of PHPStan checks. You can start using PHPStan
with a lower rule level and increase it when you feel like it.

There's also **experimental** level 5 that currently enables:

* Union types (Foo|Bar will be a specified type with checks performed on it instead of mixed)
* Checking function and method argument types when calling them

### Autoloading

PHPStan uses Composer autoloader so the easiest way how to autoload classes
is through the `autoload`/`autoload-dev` sections in composer.json.

#### Autoloading for global installation

PHPStan supports global installation using [`composer global`](https://getcomposer.org/doc/03-cli.md#global).
In this case, it's not part of the project autoloader, but it supports autodiscovery of the Composer autoloader
from current working directory residing in `vendor/`:

```bash
cd /path/to/project
phpstan analyse src tests # looks for autoloader at /path/to/project/vendor/autoload.php
```

If you have your dependencies installed at a different path
or you're running PHPStan from a different directory,
you can specify the path to the autoloader with the `--autoload-file|-a` option:

```bash
phpstan analyse --autoload-file=/path/to/autoload.php --autoload-file=/path/to/another.php src tests
```

### Exclude files from analysis

If your codebase contains some files that are broken on purpose
(e. g. to test behaviour of your application on files with invalid PHP code),
you can specify the ignoring path with the `--ignore-path|-P` option:
String at each line is used as a pattern for the [`fnmatch`](https://secure.php.net/manual/en/function.fnmatch.php) function.

```bash
phpstan analyse --ignore-path='tests/*/data/*' src tests
```

### Universal object crates [TODO]

Classes without predefined structure are common in PHP applications.
They are used as universal holders of data - any property can be set and read on them. Notable examples
include `stdClass`, `SimpleXMLElement` (these are enabled by default), objects with results of database queries etc.
Use `universalObjectCratesClasses` array parameter to let PHPStan know which classes
with these characteristics are used in your codebase:

This need another option.

### Add non-obviously assigned variables to scope [TODO]

If you use some variables from a try block in your catch blocks, set `polluteCatchScopeWithTryAssignments` boolean parameter to `true`.

```php
try {
	$author = $this->getLoggedInUser();
	$post = $this->postRepository->getById($id);
} catch (PostNotFoundException $e) {
	// $author is probably defined here
	throw new ArticleByAuthorCannotBePublished($author);
}
```

If you are enumerating over all possible situations in if-elseif branches
and PHPStan complains about undefined variables after the conditions, you can either write
an else branch with throwing an exception:

```php
if (somethingIsTrue()) {
	$foo = true;
} elseif (orSomethingElseIsTrue()) {
	$foo = false;
} else {
	throw new ShouldNotHappenException();
}

doFoo($foo);
```

Or you can set `defineVariablesWithoutDefaultBranch` boolean parameter to `true` and leave the code like this:

```php
if (somethingIsTrue()) {
	$foo = true;
} elseif (orSomethingElseIsTrue()) {
	$foo = false;
}

doFoo($foo);
```

I recommend leaving `polluteScopeWithLoopInitialAssignments`, `polluteCatchScopeWithTryAssignments` and
`defineVariablesWithoutDefaultBranch` set to `false` because it leads to a clearer and more maintainable code.

### Custom early terminating method calls

Previous example showed that if a condition branches end with throwing an exception, that branch does not have
to define a variable used after the condition branches end.

But exceptions are not the only way how to terminate execution of a method early. Some specific method calls
can be perceived by project developers also as early terminating - like a `redirect()` that stops execution
by throwing an internal exception.

```php
if (somethingIsTrue()) {
	$foo = true;
} elseif (orSomethingElseIsTrue()) {
	$foo = false;
} else {
	$this->redirect('homepage');
}

doFoo($foo);
```

### Ignore error messages with regular expresions

If some issue in your code base is not easy to fix or just simply want to deal with it later,
you can specify the ignoring pattern with the `--ignore-error|-E` option:

```bash
phpstan analyse --ignore-error='Call to an undefined method [a-zA-Z0-9\\_]+::method\(\)' \
	--ignore-error='Call to an undefined method [a-zA-Z0-9\\_]+::expects\(\)' \
	--ignore-error='Access to an undefined property PHPUnit_Framework_MockObject_MockObject::\$[a-zA-Z0-9_]+' \
	--ignore-error='Call to an undefined method PHPUnit_Framework_MockObject_MockObject::[a-zA-Z0-9_]+\(\)' \
	src tests
```

### Custom rules

You can use the `--level` option to use a preset rules to check your source. However,
if you want use all the level n rules but SomeBuiltInRule, you can specify the
`--ignore-rule|-R` option:

```bash
phpstan analyse --level=5 --ignore-rule='Methods\ReturnTypeRule' src tests
```

**Please notice the leading slash, the builtin rule name should be relative name!**

Use `phpstan analyse --help` will get all bultin rule list.

PHPStan allows writing custom rules to check for specific situations in your own codebase. Your rule class
needs to implement the `PHPStan\Rules\Rule` interface and specify the rule with the `--rule|-r` option:

```bash
phpstan analyse --rule='\MyApp\PHPStan\Rules\DefaultValueTypesAssignedToPropertiesRule' src tests
```

**Please notice the leading slash, the custom rule name should be FQCN!**

For inspiration on how to implement a rule turn to [src/Rules](https://github.com/phpstan/phpstan/tree/master/src/Rules)
to see a lot of built-in rules.

## Class reflection extensions

Classes in PHP can expose "magical" properties and methods decided in run-time using
class methods like `__get`, `__set` and `__call`. Because PHPStan is all about static analysis
(testing code for errors without running it), it has to know about those properties and methods beforehand.

When PHPStan stumbles upon a property or a method that is unknown to built-in class reflection, it iterates
over all registered class reflection extensions until it finds one that defines the property or method.

### Properties class reflection extensions

This extension type must implement the following interface:

```php
namespace PHPStan\Reflection;

interface PropertiesClassReflectionExtension
{

	public function hasProperty(ClassReflection $classReflection, string $propertyName): bool;

	public function getProperty(ClassReflection $classReflection, string $propertyName): PropertyReflection;

}
```

Most likely you will also have to implement a new `PropertyReflection` class:

```php
namespace PHPStan\Reflection;

interface PropertyReflection
{

	public function getType(): Type;
	
	public function getDeclaringClass(): ClassReflection;

	public function isStatic(): bool;

	public function isPrivate(): bool;

	public function isPublic(): bool;

}
```

And you can specify the `--extension|-x` to use it:

```bash
phpstan analyse --level=5 --extension='\App\PHPStan\PropertiesFromAnnotationsClassReflectionExtension' src tests
```

### Methods class reflection extensions

This extension type must implement the following interface:

```php
namespace PHPStan\Reflection;

interface MethodsClassReflectionExtension
{

	public function hasMethod(ClassReflection $classReflection, string $methodName): bool;

	public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection;

}
```

Most likely you will also have to implement a new `MethodReflection` class:

```php
namespace PHPStan\Reflection;

interface MethodReflection
{

	public function getDeclaringClass(): ClassReflection;

	public function getPrototype(): self;

	public function isStatic(): bool;

	public function isPrivate(): bool;

	public function isPublic(): bool;
	
	public function getName(): string;

	/**
	 * @return \PHPStan\Reflection\ParameterReflection[]
	 */
	public function getParameters(): array;

	public function isVariadic(): bool;

	public function getReturnType(): Type;

}
```

And you can specify the `--extension|-x` to use it:

```bash
phpstan analyse --level=5 --extension='\App\PHPStan\EnumMethodsClassReflectionExtension' src tests
```

## Dynamic return type extensions

If the return type of a method is not always the same, but depends on an argument passed to the method,
you can specify the return type by writing and registering an extension.

Because you have to write the code with the type-resolving logic, it can be as complex as you want.

After writing the sample extension, the variable `$mergedArticle` will have the correct type:

```php
$mergedArticle = $this->entityManager->merge($article);
// $mergedArticle will have the same type as $article
```

This is the interface for dynamic return type extension:

```php
namespace PHPStan\Type;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;

interface DynamicMethodReturnTypeExtension
{

	public static function getClass(): string;

	public function isMethodSupported(MethodReflection $methodReflection): bool;

	public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type;

}
```

And this is how you'd write the extension that correctly resolves the EntityManager::merge() return type:

```php
public static function getClass(): string
{
	return \Doctrine\ORM\EntityManager::class;
}

public function isMethodSupported(MethodReflection $methodReflection): bool
{
	return $methodReflection->getName() === 'merge';
}

public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type
{
	if (count($methodCall->args) === 0) {
		return $methodReflection->getReturnType();
	}
	$arg = $methodCall->args[0]->value;
	$type = $scope->getType($arg);
	if ($type->getClass() !== null) {
		return $type;
	}

	return $methodReflection->getReturnType();
}
```

And finally, you can specify the `--extension|-x` to use it:

```bash
phpstan analyse --level=5 --extension='\App\PHPStan\EntityManagerDynamicReturnTypeExtension' src tests
```

There's also an analogous functionality for static methods using `DynamicStaticMethodReturnTypeExtension` interface
and `phpstan.broker.dynamicStaticMethodReturnTypeExtension` service tag.

## Known issues

* If `include` or `require` are used in the analysed code (instead of `include_once` or `require_once`),
PHPStan will throw `Cannot redeclare class` error. Use the `_once` variants to avoid this error.
* If PHPStan crashes without outputting any error, it's quite possible that it's
because of a low memory limit set on your system. **Run PHPStan again** to read a couple of hints
what you can do to prevent the crashes.
* If you install PHPStan globally on your system, you can experience errors resulting from
using different versions of dependencies than PHPStan uses. For example if PHPStan's
version of Symfony Console has a method with different arguments than your version
of Symfony Console and you use this method in the analysed code, PHPStan can mark that as error.
This will be solved in the future by prefixing the namespaces of PHPStan's dependencies.

## Code of Conduct

This project adheres to a [Contributor Code of Conduct](https://github.com/phpstan/phpstan/blob/master/CODE_OF_CONDUCT.md).
By participating in this project and its community, you are expected to uphold this code.

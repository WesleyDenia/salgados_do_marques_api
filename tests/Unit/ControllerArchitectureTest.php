<?php

namespace Tests\Unit;

use Illuminate\Foundation\Http\FormRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use ReflectionMethod;
use Tests\Support\ControllerArchitectureWhitelist;
use Tests\TestCase;

class ControllerArchitectureTest extends TestCase
{
    private const MUTATING_ACTIONS = [
        'store',
        'update',
        'reorder',
        'login',
        'register',
        'forgot',
        'verifyOtp',
        'reset',
        'redeem',
        'upload',
        'updateStatus',
        'activate',
    ];

    private const FORBIDDEN_PATTERNS = [
        'inline_request_validate' => '/\$request->validate\s*\(/',
        'inline_validator_make' => '/Validator::make\s*\(/',
        'db_transaction' => '/\bDB::transaction\s*\(/',
        'route_model_update' => '/(?<!\$this)\$[A-Za-z_][A-Za-z0-9_]*->update\s*\(/',
        'route_model_delete' => '/(?<!\$this)\$[A-Za-z_][A-Za-z0-9_]*->delete\s*\(/',
    ];

    #[DataProvider('controllerFileProvider')]
    public function test_non_whitelisted_controllers_do_not_use_forbidden_patterns(string $relativePath): void
    {
        $exceptions = ControllerArchitectureWhitelist::forbiddenPatternControllerExceptions();

        if (array_key_exists($relativePath, $exceptions)) {
            $this->markTestSkipped($exceptions[$relativePath]);
        }

        $source = $this->controllerSource($relativePath);

        foreach (self::FORBIDDEN_PATTERNS as $rule => $pattern) {
            $this->assertSame(
                0,
                preg_match($pattern, $source),
                sprintf('Forbidden controller pattern "%s" found in %s', $rule, $relativePath)
            );
        }

        foreach ($this->importedModelAliases($source) as $alias) {
            $pattern = sprintf('/\b%s::(?:query|create|update|delete)\s*\(/', preg_quote($alias, '/'));

            $this->assertSame(
                0,
                preg_match($pattern, $source),
                sprintf('Direct model access using "%s" found in %s', $alias, $relativePath)
            );
        }
    }

    #[DataProvider('controllerClassProvider')]
    public function test_mutating_actions_use_form_requests_outside_whitelist(string $className): void
    {
        $reflection = new ReflectionClass($className);
        $methodExceptions = ControllerArchitectureWhitelist::formRequestMethodExceptions()[$className] ?? [];
        $assertions = 0;

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->class !== $className) {
                continue;
            }

            if (!in_array($method->getName(), self::MUTATING_ACTIONS, true)) {
                continue;
            }

            if (array_key_exists($method->getName(), $methodExceptions)) {
                continue;
            }

            $hasFormRequest = false;

            foreach ($method->getParameters() as $parameter) {
                $type = $parameter->getType();

                if (!$type || $type->isBuiltin()) {
                    continue;
                }

                $parameterClass = $type->getName();

                if (is_a($parameterClass, FormRequest::class, true)) {
                    $hasFormRequest = true;
                    break;
                }
            }

            $this->assertTrue(
                $hasFormRequest,
                sprintf('Mutable action %s::%s must use a FormRequest.', $className, $method->getName())
            );
            $assertions++;
        }

        if ($assertions === 0) {
            $this->addToAssertionCount(1);
        }
    }

    #[DataProvider('controllerClassProvider')]
    public function test_non_whitelisted_controllers_do_not_inject_repositories_directly(string $className): void
    {
        $exceptions = ControllerArchitectureWhitelist::repositoryInjectionExceptions();

        if (array_key_exists($className, $exceptions)) {
            $this->markTestSkipped($exceptions[$className]);
        }

        $reflection = new ReflectionClass($className);
        $constructor = $reflection->getConstructor();

        if (!$constructor) {
            $this->addToAssertionCount(1);

            return;
        }

        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();

            if (!$type || $type->isBuiltin()) {
                continue;
            }

            $typeName = $type->getName();

            $this->assertFalse(
                str_starts_with($typeName, 'App\\Repositories\\'),
                sprintf('Controller %s injects repository %s directly.', $className, $typeName)
            );
        }
    }

    public static function controllerFileProvider(): array
    {
        $projectRoot = dirname(__DIR__, 2);
        $basePath = $projectRoot . '/app/Http/Controllers';
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($basePath));
        $files = [];

        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            if ($file->getBasename() === 'Controller.php') {
                continue;
            }

            $relative = str_replace($projectRoot . DIRECTORY_SEPARATOR, '', $file->getPathname());
            $files[$relative] = [$relative];
        }

        ksort($files);

        return $files;
    }

    public static function controllerClassProvider(): array
    {
        $classes = [];

        foreach (self::controllerFileProvider() as [$relativePath]) {
            $class = str_replace(
                ['/', '.php'],
                ['\\', ''],
                preg_replace('/^app\//', '', $relativePath) ?? $relativePath
            );

            $classes[$class] = [sprintf('App\\%s', $class)];
        }

        ksort($classes);

        return $classes;
    }

    private function controllerSource(string $relativePath): string
    {
        return (string) file_get_contents(base_path($relativePath));
    }

    /**
        * @return list<string>
        */
    private function importedModelAliases(string $source): array
    {
        preg_match_all('/^use\s+App\\\\Models\\\\([A-Za-z0-9_]+)\s*;/m', $source, $matches);

        return $matches[1] ?? [];
    }
}

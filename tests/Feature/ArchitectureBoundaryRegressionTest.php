<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Tests\TestCase;

class ArchitectureBoundaryRegressionTest extends TestCase
{
    #[DataProvider('delegatingControllerActionsProvider')]
    public function test_migrated_controller_actions_delegate_to_services(string $relativePath, string $method): void
    {
        $source = (string) file_get_contents(base_path($relativePath));
        $reflection = new ReflectionMethod($this->classNameFromPath($relativePath), $method);
        $startLine = $reflection->getStartLine();
        $endLine = $reflection->getEndLine();
        $body = implode("\n", array_slice(explode("\n", $source), $startLine - 1, $endLine - $startLine + 1));

        $this->assertMatchesRegularExpression(
            '/\$this->[A-Za-z_][A-Za-z0-9_]*->/',
            $body,
            sprintf('Expected %s::%s to delegate to an injected collaborator.', $relativePath, $method)
        );
    }

    public static function delegatingControllerActionsProvider(): array
    {
        return [
            'admin.product.store' => ['app/Http/Controllers/Admin/ProductController.php', 'store'],
            'admin.product.update' => ['app/Http/Controllers/Admin/ProductController.php', 'update'],
            'admin.content-home.store' => ['app/Http/Controllers/Admin/ContentHomeController.php', 'store'],
            'admin.content-home.update' => ['app/Http/Controllers/Admin/ContentHomeController.php', 'update'],
            'admin.setting.store' => ['app/Http/Controllers/Admin/SettingController.php', 'store'],
            'admin.setting.update' => ['app/Http/Controllers/Admin/SettingController.php', 'update'],
            'api.password-reset.forgot' => ['app/Http/Controllers/Api/V1/PasswordResetController.php', 'forgot'],
            'api.content-home.index' => ['app/Http/Controllers/Api/V1/ContentHomeController.php', 'index'],
        ];
    }

    private function classNameFromPath(string $relativePath): string
    {
        $trimmed = preg_replace('/^app\//', '', $relativePath) ?? $relativePath;

        return 'App\\' . str_replace(['/', '.php'], ['\\', ''], $trimmed);
    }
}

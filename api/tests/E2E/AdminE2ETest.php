<?php

declare(strict_types=1);

namespace App\Tests\E2E;

/**
 * @internal
 *
 * @coversNothing
 */
final class AdminE2ETest extends ApiTestCase
{
    public function testAdminDashboard(): void
    {
        // Arrange
        $admin = $this->createAdminUser();
        $this->loginAs($admin);

        // Act
        $this->makeJsonRequest('GET', '/api/admin');

        // Assert
        $this->assertApiResponseIsSuccessful();
        $this->assertJsonResponse();

        $data = $this->decodeJsonResponse();
        self::assertSame('Admin Dashboard', $data['title']);
        self::assertSame('Welcome to the administration panel', $data['message']);
        self::assertIsArray($data['user']);
        self::assertSame($admin->getEmail(), $data['user']['email']);
        self::assertIsArray($data['user']['roles']);
        self::assertContains('ROLE_ADMIN', $data['user']['roles']);
        self::assertArrayHasKey('modules', $data);
        self::assertArrayHasKey('stats', $data);
    }

    public function testAdminUsersEndpoint(): void
    {
        // Arrange
        $admin = $this->createAdminUser();
        $this->createUser('user1@test.com');
        $this->createUser('user2@test.com');
        $this->loginAs($admin);

        // Act
        $this->makeJsonRequest('GET', '/api/admin/users');

        // Assert
        $this->assertApiResponseIsSuccessful();
        $this->assertJsonResponse();

        $data = $this->decodeJsonResponse();
        self::assertSame('User Management', $data['title']);
        self::assertArrayHasKey('users', $data);
        self::assertIsArray($data['users']);
        self::assertGreaterThanOrEqual(3, \count($data['users'])); // admin + 2 users
    }

    public function testAdminContentEndpoint(): void
    {
        // Arrange
        $admin = $this->createAdminUser();
        $this->loginAs($admin);

        // Act
        $this->makeJsonRequest('GET', '/api/admin/content');

        // Assert
        $this->assertApiResponseIsSuccessful();
        $this->assertJsonResponse();

        $data = $this->decodeJsonResponse();
        self::assertSame('Content Management', $data['title']);
        self::assertArrayHasKey('content', $data);
    }

    public function testAdminSettingsEndpoint(): void
    {
        // Arrange
        $admin = $this->createAdminUser();
        $this->loginAs($admin);

        // Act
        $this->makeJsonRequest('GET', '/api/admin/settings');

        // Assert
        $this->assertApiResponseIsSuccessful();
        $this->assertJsonResponse();

        $data = $this->decodeJsonResponse();
        self::assertSame('System Settings', $data['title']);
        self::assertArrayHasKey('settings', $data);
    }

    public function testRegularUserCannotAccessAdminEndpoints(): void
    {
        // Arrange
        $user = $this->createUser('user@test.com', ['ROLE_USER']);
        $this->loginAs($user, 'password123');

        $adminEndpoints = [
            '/api/admin',
            '/api/admin/users',
            '/api/admin/content',
            '/api/admin/settings',
        ];

        foreach ($adminEndpoints as $endpoint) {
            // Act
            $this->makeJsonRequest('GET', $endpoint);

            // Assert
            $this->assertApiResponseStatusCodeSame(403);
        }
    }

    public function testUnauthenticatedUserCannotAccessAdminEndpoints(): void
    {
        $adminEndpoints = [
            '/api/admin',
            '/api/admin/users',
            '/api/admin/content',
            '/api/admin/settings',
        ];

        foreach ($adminEndpoints as $endpoint) {
            // Act
            $this->makeJsonRequest('GET', $endpoint);

            // Assert
            $this->assertApiResponseStatusCodeSame(302);
        }
    }

    public function testAdminDashboardShowsCorrectStatistics(): void
    {
        // Arrange
        $admin = $this->createAdminUser();
        $this->createUser('user1@test.com');
        $this->createUser('user2@test.com');
        $this->loginAs($admin);

        // Create some articles
        $this->makeJsonRequest('POST', '/api/articles', [
            'title' => 'Test Article 1',
            'content' => 'Content 1',
            'isPublished' => true,
        ]);
        $this->makeJsonRequest('POST', '/api/articles', [
            'title' => 'Test Article 2',
            'content' => 'Content 2',
            'isPublished' => false,
        ]);

        // Act
        $this->makeJsonRequest('GET', '/api/admin');

        // Assert
        $this->assertApiResponseIsSuccessful();
        $data = $this->decodeJsonResponse();

        self::assertArrayHasKey('stats', $data);
        self::assertIsArray($data['stats']);
        self::assertSame(3, $data['stats']['total_users']); // admin + 2 users
        self::assertArrayHasKey('last_login', $data['stats']);
    }

    public function testAdminModulesConfiguration(): void
    {
        // Arrange
        $admin = $this->createAdminUser();
        $this->loginAs($admin);

        // Act
        $this->makeJsonRequest('GET', '/api/admin');

        // Assert
        $this->assertApiResponseIsSuccessful();
        $data = $this->decodeJsonResponse();

        self::assertArrayHasKey('modules', $data);
        self::assertIsArray($data['modules']);

        $expectedModules = ['users', 'content', 'settings'];
        $actualModuleIds = \array_column($data['modules'], 'id');

        foreach ($expectedModules as $expectedModule) {
            self::assertContains($expectedModule, $actualModuleIds);
        }

        // Verify each module has required fields
        foreach ($data['modules'] as $module) {
            self::assertArrayHasKey('id', $module);
            self::assertArrayHasKey('title', $module);
            self::assertArrayHasKey('description', $module);
            self::assertArrayHasKey('icon', $module);
            self::assertArrayHasKey('url', $module);
        }
    }
}

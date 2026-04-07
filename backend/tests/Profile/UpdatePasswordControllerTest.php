<?php

namespace App\Tests\Profile;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UpdatePasswordControllerTest extends WebTestCase
{
    private string $currentPassword = 'TestPassword123!';

    public function testUpdatePasswordWithValidData(): void
    {
        $client = static::createClient();
        
        $container = self::getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $passwordHasher = $container->get('security.password_hasher');
        $jwtManager = $container->get('lexik_jwt_authentication.jwt_manager');

        // Create a test user
        $username = 'test_user_' . bin2hex(random_bytes(6));

        $user = new User();
        $user->setUsername($username);
        $user->setPassword($passwordHasher->hashPassword($user, $this->currentPassword));
        $user->setCreatedAt(new \DateTimeImmutable());
        $user->setUpdatedAt(new \DateTimeImmutable());

        $entityManager->persist($user);
        $entityManager->flush();
        $entityManager->clear();

        // Fetch user and create token
        $user = $entityManager->getRepository(User::class)->findOneByUsername($username);
        $userId = (string) $user->getId();
        $token = $jwtManager->create($user);

        $newPassword = 'NewPassword456@';

        $client->request(
            'PATCH',
            '/api/user/' . $userId . '/password',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            json_encode([
                'current_password' => $this->currentPassword,
                'new_password' => $newPassword,
            ])
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $data = json_decode($client->getResponse()->getContent() ?: '', true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('message', $data);
        $this->assertStringContainsString('successfully', $data['message']);
    }

    public function testUpdatePasswordWithoutToken(): void
    {
        $client = static::createClient();

        $newPassword = 'NewPassword456@';

        $client->request(
            'PATCH',
            '/api/user/00000000-0000-0000-0000-000000000000/password',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'current_password' => $this->currentPassword,
                'new_password' => $newPassword,
            ])
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testUpdatePasswordWithInvalidCurrentPassword(): void
    {
        $client = static::createClient();
        
        $container = self::getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $passwordHasher = $container->get('security.password_hasher');
        $jwtManager = $container->get('lexik_jwt_authentication.jwt_manager');

        // Create a test user
        $username = 'test_user_' . bin2hex(random_bytes(6));

        $user = new User();
        $user->setUsername($username);
        $user->setPassword($passwordHasher->hashPassword($user, $this->currentPassword));
        $user->setCreatedAt(new \DateTimeImmutable());
        $user->setUpdatedAt(new \DateTimeImmutable());

        $entityManager->persist($user);
        $entityManager->flush();
        $entityManager->clear();

        // Fetch user and create token
        $user = $entityManager->getRepository(User::class)->findOneByUsername($username);
        $userId = (string) $user->getId();
        $token = $jwtManager->create($user);

        $newPassword = 'NewPassword456@';

        $client->request(
            'PATCH',
            '/api/user/' . $userId . '/password',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            json_encode([
                'current_password' => 'WrongPassword123!',
                'new_password' => $newPassword,
            ])
        );

        $this->assertResponseStatusCodeSame(400);
        $data = json_decode($client->getResponse()->getContent() ?: '', true);
        $this->assertArrayHasKey('error', $data);
        $this->assertStringContainsString('Current password', $data['error']);
    }

    public function testUpdatePasswordWithTooShortPassword(): void
    {
        $client = static::createClient();
        
        $container = self::getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $passwordHasher = $container->get('security.password_hasher');
        $jwtManager = $container->get('lexik_jwt_authentication.jwt_manager');

        // Create a test user
        $username = 'test_user_' . bin2hex(random_bytes(6));

        $user = new User();
        $user->setUsername($username);
        $user->setPassword($passwordHasher->hashPassword($user, $this->currentPassword));
        $user->setCreatedAt(new \DateTimeImmutable());
        $user->setUpdatedAt(new \DateTimeImmutable());

        $entityManager->persist($user);
        $entityManager->flush();
        $entityManager->clear();

        // Fetch user and create token
        $user = $entityManager->getRepository(User::class)->findOneByUsername($username);
        $userId = (string) $user->getId();
        $token = $jwtManager->create($user);

        $newPassword = 'Short1@';

        $client->request(
            'PATCH',
            '/api/user/' . $userId . '/password',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            json_encode([
                'current_password' => $this->currentPassword,
                'new_password' => $newPassword,
            ])
        );

        $this->assertResponseStatusCodeSame(400);
        $data = json_decode($client->getResponse()->getContent() ?: '', true);
        $this->assertArrayHasKey('error', $data);
        $this->assertStringContainsString('at least 10 characters', $data['error']);
    }

    public function testUpdatePasswordWithoutNumbers(): void
    {
        $client = static::createClient();
        
        $container = self::getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $passwordHasher = $container->get('security.password_hasher');
        $jwtManager = $container->get('lexik_jwt_authentication.jwt_manager');

        // Create a test user
        $username = 'test_user_' . bin2hex(random_bytes(6));

        $user = new User();
        $user->setUsername($username);
        $user->setPassword($passwordHasher->hashPassword($user, $this->currentPassword));
        $user->setCreatedAt(new \DateTimeImmutable());
        $user->setUpdatedAt(new \DateTimeImmutable());

        $entityManager->persist($user);
        $entityManager->flush();
        $entityManager->clear();

        // Fetch user and create token
        $user = $entityManager->getRepository(User::class)->findOneByUsername($username);
        $userId = (string) $user->getId();
        $token = $jwtManager->create($user);

        $newPassword = 'NoNumbers@NoNum';

        $client->request(
            'PATCH',
            '/api/user/' . $userId . '/password',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            json_encode([
                'current_password' => $this->currentPassword,
                'new_password' => $newPassword,
            ])
        );

        $this->assertResponseStatusCodeSame(400);
        $data = json_decode($client->getResponse()->getContent() ?: '', true);
        $this->assertArrayHasKey('error', $data);
    }

    public function testUpdatePasswordWithoutSymbols(): void
    {
        $client = static::createClient();
        
        $container = self::getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $passwordHasher = $container->get('security.password_hasher');
        $jwtManager = $container->get('lexik_jwt_authentication.jwt_manager');

        // Create a test user
        $username = 'test_user_' . bin2hex(random_bytes(6));

        $user = new User();
        $user->setUsername($username);
        $user->setPassword($passwordHasher->hashPassword($user, $this->currentPassword));
        $user->setCreatedAt(new \DateTimeImmutable());
        $user->setUpdatedAt(new \DateTimeImmutable());

        $entityManager->persist($user);
        $entityManager->flush();
        $entityManager->clear();

        // Fetch user and create token
        $user = $entityManager->getRepository(User::class)->findOneByUsername($username);
        $userId = (string) $user->getId();
        $token = $jwtManager->create($user);

        $newPassword = 'NoSymbols1234';

        $client->request(
            'PATCH',
            '/api/user/' . $userId . '/password',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            json_encode([
                'current_password' => $this->currentPassword,
                'new_password' => $newPassword,
            ])
        );

        $this->assertResponseStatusCodeSame(400);
        $data = json_decode($client->getResponse()->getContent() ?: '', true);
        $this->assertArrayHasKey('error', $data);
    }

    public function testUserCanOnlyChangeTheirOwnPassword(): void
    {
        $client = static::createClient();
        
        $container = self::getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $passwordHasher = $container->get('security.password_hasher');
        $jwtManager = $container->get('lexik_jwt_authentication.jwt_manager');

        // Create two users
        $username1 = 'user1_' . bin2hex(random_bytes(6));
        $username2 = 'user2_' . bin2hex(random_bytes(6));
        $originalPassword = 'Original@Pass123';
        $newPassword = 'NewPassword456@';

        $user1 = new User();
        $user1->setUsername($username1);
        $user1->setPassword($passwordHasher->hashPassword($user1, $originalPassword));
        $user1->setCreatedAt(new \DateTimeImmutable());
        $user1->setUpdatedAt(new \DateTimeImmutable());

        $user2 = new User();
        $user2->setUsername($username2);
        $user2->setPassword($passwordHasher->hashPassword($user2, $originalPassword));
        $user2->setCreatedAt(new \DateTimeImmutable());
        $user2->setUpdatedAt(new \DateTimeImmutable());

        $entityManager->persist($user1);
        $entityManager->persist($user2);
        $entityManager->flush();
        $entityManager->clear();

        // Get token for user1
        $user1 = $entityManager->getRepository(User::class)->findOneByUsername($username1);
        $user1Id = (string) $user1->getId();
        $token1 = $jwtManager->create($user1);

        // User1 changes their password
        $client->request(
            'PATCH',
            '/api/user/' . $user1Id . '/password',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token1,
            ],
            json_encode([
                'current_password' => $originalPassword,
                'new_password' => $newPassword,
            ])
        );

        $this->assertResponseIsSuccessful();
        $entityManager->clear();

        // Verify user1's password was changed and user2's was not
        $user1Updated = $entityManager->getRepository(User::class)->findOneByUsername($username1);
        $user2Updated = $entityManager->getRepository(User::class)->findOneByUsername($username2);

        $this->assertTrue($passwordHasher->isPasswordValid($user1Updated, $newPassword), 'User1 password should be changed');
        $this->assertTrue($passwordHasher->isPasswordValid($user2Updated, $originalPassword), 'User2 password should remain unchanged');
    }
}

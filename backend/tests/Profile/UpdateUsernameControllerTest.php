<?php

namespace App\Tests\Profile;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UpdateUsernameControllerTest extends WebTestCase
{
    public function testUpdateUsernameWithValidData(): void
    {
        $client = static::createClient();
        
        $container = self::getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $passwordHasher = $container->get('security.password_hasher');
        $jwtManager = $container->get('lexik_jwt_authentication.jwt_manager');

        // Create a test user
        $username = 'test_user_' . bin2hex(random_bytes(6));
        $password = 'TestPassword123!';

        $user = new User();
        $user->setUsername($username);
        $user->setPassword($passwordHasher->hashPassword($user, $password));
        $user->setCreatedAt(new \DateTimeImmutable());
        $user->setUpdatedAt(new \DateTimeImmutable());

        $entityManager->persist($user);
        $entityManager->flush();
        $entityManager->clear();

        // Fetch user and create token
        $user = $entityManager->getRepository(User::class)->findOneByUsername($username);
        $userId = (string) $user->getId();
        $token = $jwtManager->create($user);

        $newUsername = 'new_username_' . bin2hex(random_bytes(6));

        $client->request(
            'PATCH',
            '/api/user/' . $userId . '/username',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            json_encode([
                'username' => $newUsername,
            ])
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $data = json_decode($client->getResponse()->getContent() ?: '', true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('user', $data);
        $this->assertSame($newUsername, $data['user']['username']);
    }

    public function testUpdateUsernameWithoutToken(): void
    {
        $client = static::createClient();

        $newUsername = 'new_username_' . bin2hex(random_bytes(6));

        $client->request(
            'PATCH',
            '/api/user/00000000-0000-0000-0000-000000000000/username',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'username' => $newUsername,
            ])
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testUpdateUsernameWithEmptyUsername(): void
    {
        $client = static::createClient();
        
        $container = self::getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $passwordHasher = $container->get('security.password_hasher');
        $jwtManager = $container->get('lexik_jwt_authentication.jwt_manager');

        // Create a test user
        $username = 'test_user_' . bin2hex(random_bytes(6));
        $password = 'TestPassword123!';

        $user = new User();
        $user->setUsername($username);
        $user->setPassword($passwordHasher->hashPassword($user, $password));
        $user->setCreatedAt(new \DateTimeImmutable());
        $user->setUpdatedAt(new \DateTimeImmutable());

        $entityManager->persist($user);
        $entityManager->flush();
        $entityManager->clear();

        // Fetch user and create token
        $user = $entityManager->getRepository(User::class)->findOneByUsername($username);
        $userId = (string) $user->getId();
        $token = $jwtManager->create($user);

        $client->request(
            'PATCH',
            '/api/user/' . $userId . '/username',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            json_encode([
                'username' => '',
            ])
        );

        $this->assertResponseStatusCodeSame(400);
        $data = json_decode($client->getResponse()->getContent() ?: '', true);
        $this->assertArrayHasKey('error', $data);
    }

    public function testUpdateUsernameWithExistingUsername(): void
    {
        $client = static::createClient();
        
        $container = self::getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $passwordHasher = $container->get('security.password_hasher');
        $jwtManager = $container->get('lexik_jwt_authentication.jwt_manager');

        // Create first user
        $username = 'test_user_' . bin2hex(random_bytes(6));
        $password = 'TestPassword123!';

        $user = new User();
        $user->setUsername($username);
        $user->setPassword($passwordHasher->hashPassword($user, $password));
        $user->setCreatedAt(new \DateTimeImmutable());
        $user->setUpdatedAt(new \DateTimeImmutable());

        // Create another user with a specific username
        $existingUsername = 'existing_user_' . bin2hex(random_bytes(6));
        $existingUser = new User();
        $existingUser->setUsername($existingUsername);
        $existingUser->setPassword($passwordHasher->hashPassword($existingUser, 'ExistPass123!'));
        $existingUser->setCreatedAt(new \DateTimeImmutable());
        $existingUser->setUpdatedAt(new \DateTimeImmutable());

        $entityManager->persist($user);
        $entityManager->persist($existingUser);
        $entityManager->flush();
        $entityManager->clear();

        // Fetch first user and create token
        $user = $entityManager->getRepository(User::class)->findOneByUsername($username);
        $userId = (string) $user->getId();
        $token = $jwtManager->create($user);

        $client->request(
            'PATCH',
            '/api/user/' . $userId . '/username',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            json_encode([
                'username' => $existingUsername,
            ])
        );

        $this->assertResponseStatusCodeSame(409);
        $data = json_decode($client->getResponse()->getContent() ?: '', true);
        $this->assertArrayHasKey('error', $data);
    }
}

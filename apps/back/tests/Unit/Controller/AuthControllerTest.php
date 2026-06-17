<?php

namespace App\Tests\Unit\Controller;

use App\Controller\AuthController;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Inscription : point d'entrée sécurité. On vérifie les garde-fous de
 * validation et que le mot de passe est bien hashé (jamais stocké en clair).
 */
final class AuthControllerTest extends TestCase
{
    private function makeController(
        ?UserRepository $userRepository = null,
        ?EntityManagerInterface $entityManager = null,
        ?UserPasswordHasherInterface $passwordHasher = null,
    ): AuthController {
        return new AuthController(
            $entityManager ?? $this->createStub(EntityManagerInterface::class),
            $userRepository ?? $this->createStub(UserRepository::class),
            $passwordHasher ?? $this->createStub(UserPasswordHasherInterface::class),
            $this->createStub(Security::class),
        );
    }

    private function request(array $body): Request
    {
        return Request::create('/api/auth/register', 'POST', [], [], [], [], json_encode($body));
    }

    public function testRejectsInvalidEmail(): void
    {
        $controller = $this->makeController();

        $response = $controller->register($this->request([
            'email' => 'pas-un-email',
            'password' => 'motdepasse123',
        ]));

        self::assertSame(422, $response->getStatusCode());
    }

    public function testRejectsTooShortPassword(): void
    {
        $controller = $this->makeController();

        $response = $controller->register($this->request([
            'email' => 'rider@aurora.test',
            'password' => 'court',
        ]));

        self::assertSame(422, $response->getStatusCode());
    }

    public function testRejectsDuplicateEmail(): void
    {
        $userRepository = $this->createStub(UserRepository::class);
        $userRepository->method('findOneBy')->willReturn(new User());

        $controller = $this->makeController($userRepository);

        $response = $controller->register($this->request([
            'email' => 'rider@aurora.test',
            'password' => 'motdepasse123',
        ]));

        self::assertSame(409, $response->getStatusCode());
    }

    public function testCreatesUserWithHashedPassword(): void
    {
        $userRepository = $this->createStub(UserRepository::class);
        $userRepository->method('findOneBy')->willReturn(null);

        $passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $passwordHasher->expects(self::once())
            ->method('hashPassword')
            ->willReturn('hashed-secret');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist')
            ->with(self::callback(static fn (User $u): bool => 'hashed-secret' === $u->getPassword()));
        $entityManager->expects(self::once())->method('flush');

        $controller = $this->makeController($userRepository, $entityManager, $passwordHasher);

        $response = $controller->register($this->request([
            'email' => 'rider@aurora.test',
            'password' => 'motdepasse123',
            'name' => 'Aurora',
        ]));

        self::assertSame(201, $response->getStatusCode());
    }
}

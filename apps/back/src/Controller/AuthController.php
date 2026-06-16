<?php

namespace App\Controller;

use App\Entity\Enum\RewardsLevel;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\SecurityBundle\Security;

class AuthController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private Security $security,
    ) {
    }

    #[Route('/api/auth/register', name: 'api_auth_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true) ?? [];

        $email = trim((string) ($payload['email'] ?? ''));
        $password = (string) ($payload['password'] ?? '');
        $name = trim((string) ($payload['name'] ?? ''));
        $city = trim((string) ($payload['city'] ?? ''));

        if ('' === $email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(['error' => 'Adresse email invalide.'], 422);
        }

        if (strlen($password) < 8) {
            return new JsonResponse(['error' => 'Le mot de passe doit contenir au moins 8 caractères.'], 422);
        }

        if ($this->userRepository->findOneBy(['email' => $email])) {
            return new JsonResponse(['error' => 'Un compte existe déjà avec cet email.'], 409);
        }

        $user = new User();
        $user->setEmail($email);
        $user->setName('' !== $name ? $name : $email);
        $user->setCity($city);
        $user->setRewardsLevel(RewardsLevel::Explorer);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return new JsonResponse([
            'id' => (string) $user->getId(),
            'email' => $user->getEmail(),
            'name' => $user->getName(),
        ], 201);
    }

    #[Route('/api/auth/login', name: 'api_auth_login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        // Cette route existe uniquement pour être enregistrée dans le routeur :
        // l'authentification réelle est interceptée plus tôt par le firewall
        // `json_login` (config/packages/security.yaml). Ce code n'est exécuté
        // que si l'authentification a échoué de manière inattendue.
        return new JsonResponse(['error' => 'Identifiants invalides.'], 401);
    }

    #[Route('/api/auth/me', name: 'api_auth_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        /** @var User $user */
        $user = $this->security->getUser();

        return new JsonResponse([
            'id' => (string) $user->getId(),
            'email' => $user->getEmail(),
            'name' => $user->getName(),
            'city' => $user->getCity(),
            'rewards_level' => $user->getRewardsLevel()->value,
            'total_points' => $user->getTotalPoints(),
        ]);
    }
}

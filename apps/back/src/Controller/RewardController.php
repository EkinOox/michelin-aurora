<?php

namespace App\Controller;

use App\Entity\Enum\RewardsLevel;
use App\Entity\RewardCode;
use App\Entity\User;
use App\Repository\RewardCatalogItemRepository;
use App\Repository\RewardCodeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class RewardController
{
    private const RANKS = [
        ['key' => RewardsLevel::Explorer, 'min' => 0],
        ['key' => RewardsLevel::Rider, 'min' => 1000],
        ['key' => RewardsLevel::Performer, 'min' => 2500],
        ['key' => RewardsLevel::EliteCyclist, 'min' => 4000],
        ['key' => RewardsLevel::MichelinAmbassador, 'min' => 8000],
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
        private RewardCatalogItemRepository $rewardCatalogItemRepository,
        private RewardCodeRepository $rewardCodeRepository,
    ) {
    }

    #[Route('/api/rewards', name: 'api_rewards_show', methods: ['GET'])]
    public function show(): JsonResponse
    {
        /** @var User $user */
        $user = $this->security->getUser();

        $points = $user->getTotalPoints();
        $nextAt = null;
        foreach (self::RANKS as $rank) {
            if ($rank['min'] > $points) {
                $nextAt = $rank['min'];
                break;
            }
        }

        $catalog = array_map(static fn ($item) => [
            'id' => (string) $item->getId(),
            'cost' => $item->getCost(),
            'title' => $item->getTitle(),
            'sub' => $item->getSub(),
            'icon' => $item->getIcon(),
        ], $this->rewardCatalogItemRepository->findAll());

        $codes = array_map(static fn ($code) => [
            'id' => (string) $code->getId(),
            'code' => $code->getCode(),
            'discount_eur' => $code->getDiscountEur(),
            'used' => $code->isUsed(),
            'generated_at' => $code->getGeneratedAt()->format(\DateTimeInterface::ATOM),
        ], $this->rewardCodeRepository->findBy(['user' => $user]));

        return new JsonResponse([
            'points' => $points,
            'rank' => $user->getRewardsLevel()->value,
            'next_at' => $nextAt,
            'catalog' => $catalog,
            'codes' => $codes,
        ]);
    }

    #[Route('/api/rewards/redeem/{id}', name: 'api_rewards_redeem', methods: ['POST'])]
    public function redeem(string $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->security->getUser();

        $item = $this->rewardCatalogItemRepository->find($id);

        if (!$item) {
            return new JsonResponse(['error' => 'Unknown reward'], 404);
        }

        if ($user->getTotalPoints() < $item->getCost()) {
            return new JsonResponse(['error' => 'Not enough points'], 422);
        }

        $user->setTotalPoints($user->getTotalPoints() - $item->getCost());

        $code = new RewardCode();
        $code->setUser($user);
        $code->setCode(strtoupper(substr(bin2hex(random_bytes(6)), 0, 10)));
        $code->setDiscountEur('0.00');

        $this->entityManager->persist($code);
        $this->entityManager->flush();

        return new JsonResponse([
            'status' => 'redeemed',
            'code' => $code->getCode(),
            'remaining_points' => $user->getTotalPoints(),
        ]);
    }
}

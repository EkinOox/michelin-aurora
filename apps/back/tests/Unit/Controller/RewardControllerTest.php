<?php

namespace App\Tests\Unit\Controller;

use App\Controller\RewardController;
use App\Entity\RewardCatalogItem;
use App\Entity\RewardCode;
use App\Entity\User;
use App\Repository\RewardCatalogItemRepository;
use App\Repository\RewardCodeRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Échange de points contre une récompense : touche au « solde » de
 * l'utilisateur. On vérifie qu'on ne peut pas dépenser plus que son solde
 * et que la déduction est correcte (pas de débit injustifié).
 */
final class RewardControllerTest extends TestCase
{
    private function makeController(
        Security $security,
        ?RewardCatalogItemRepository $catalog = null,
        ?EntityManagerInterface $entityManager = null,
    ): RewardController {
        return new RewardController(
            $entityManager ?? $this->createStub(EntityManagerInterface::class),
            $security,
            $catalog ?? $this->createStub(RewardCatalogItemRepository::class),
            $this->createStub(RewardCodeRepository::class),
        );
    }

    private function securityFor(User $user): Security
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn($user);

        return $security;
    }

    public function testReturns404WhenRewardUnknown(): void
    {
        $catalog = $this->createStub(RewardCatalogItemRepository::class);
        $catalog->method('find')->willReturn(null);

        $controller = $this->makeController($this->securityFor(new User()), $catalog);

        $response = $controller->redeem('unknown-id');

        self::assertSame(404, $response->getStatusCode());
    }

    public function testReturns422WhenNotEnoughPoints(): void
    {
        $user = new User();
        $user->setTotalPoints(100);

        $item = (new RewardCatalogItem())->setCost(500);
        $catalog = $this->createStub(RewardCatalogItemRepository::class);
        $catalog->method('find')->willReturn($item);

        $controller = $this->makeController($this->securityFor($user), $catalog);

        $response = $controller->redeem('reward-id');

        self::assertSame(422, $response->getStatusCode());
        self::assertSame(100, $user->getTotalPoints(), 'Le solde ne doit pas être débité en cas de refus.');
    }

    public function testDeductsPointsAndPersistsCode(): void
    {
        $user = new User();
        $user->setTotalPoints(1000);

        $item = (new RewardCatalogItem())->setCost(500);
        $catalog = $this->createStub(RewardCatalogItemRepository::class);
        $catalog->method('find')->willReturn($item);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist')
            ->with(self::isInstanceOf(RewardCode::class));
        $entityManager->expects(self::once())->method('flush');

        $controller = $this->makeController($this->securityFor($user), $catalog, $entityManager);

        $response = $controller->redeem('reward-id');

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(500, $user->getTotalPoints());

        $payload = json_decode((string) $response->getContent(), true);
        self::assertSame('redeemed', $payload['status']);
        self::assertSame(500, $payload['remaining_points']);
        self::assertNotEmpty($payload['code']);
    }
}

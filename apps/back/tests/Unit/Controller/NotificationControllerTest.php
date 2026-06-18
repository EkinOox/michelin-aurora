<?php

namespace App\Tests\Unit\Controller;

use App\Controller\NotificationController;
use App\Entity\Notification;
use App\Entity\User;
use App\Repository\FriendshipRepository;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Uid\Uuid;

/**
 * Lecture / suppression d'une notification : barrière d'autorisation (IDOR).
 * On ne doit jamais pouvoir toucher la notification d'un autre utilisateur,
 * même en connaissant son id.
 */
final class NotificationControllerTest extends TestCase
{
    /** Assigne un id (genere normalement par Doctrine) via reflexion. */
    private function withId(object $entity, Uuid $id): object
    {
        $prop = new \ReflectionProperty($entity, 'id');
        $prop->setValue($entity, $id);

        return $entity;
    }

    private function userWithId(Uuid $id): User
    {
        return $this->withId(new User(), $id);
    }

    private function makeController(
        User $me,
        ?NotificationRepository $notifications = null,
        ?EntityManagerInterface $entityManager = null,
    ): NotificationController {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn($me);

        return new NotificationController(
            $notifications ?? $this->createStub(NotificationRepository::class),
            $this->createStub(FriendshipRepository::class),
            $entityManager ?? $this->createStub(EntityManagerInterface::class),
            $security,
        );
    }

    public function testReadReturns404WhenNotificationBelongsToAnotherUser(): void
    {
        $me = $this->userWithId(Uuid::v4());
        $other = $this->userWithId(Uuid::v4());

        $notif = $this->withId((new Notification())->setUser($other), Uuid::v4());
        $repository = $this->createStub(NotificationRepository::class);
        $repository->method('find')->willReturn($notif);

        $response = $this->makeController($me, $repository)->read($notif->getId()->toString());

        self::assertSame(404, $response->getStatusCode());
    }

    public function testReadMarksAsReadAndFlushesForOwner(): void
    {
        $me = $this->userWithId(Uuid::v4());

        $notif = $this->withId((new Notification())->setUser($me), Uuid::v4());
        $repository = $this->createStub(NotificationRepository::class);
        $repository->method('find')->willReturn($notif);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('flush');

        $response = $this->makeController($me, $repository, $entityManager)->read($notif->getId()->toString());

        self::assertSame(200, $response->getStatusCode());
        self::assertTrue($notif->isRead());
    }

    public function testDeleteReturns404WhenNotificationBelongsToAnotherUser(): void
    {
        $me = $this->userWithId(Uuid::v4());
        $other = $this->userWithId(Uuid::v4());

        $notif = $this->withId((new Notification())->setUser($other), Uuid::v4());
        $repository = $this->createStub(NotificationRepository::class);
        $repository->method('find')->willReturn($notif);

        // Le flush ne doit jamais survenir pour une notif qui n'appartient pas a `me`.
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('remove');
        $entityManager->expects(self::never())->method('flush');

        $response = $this->makeController($me, $repository, $entityManager)->delete($notif->getId()->toString());

        self::assertSame(404, $response->getStatusCode());
    }
}

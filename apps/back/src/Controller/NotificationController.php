<?php

namespace App\Controller;

use App\Repository\FriendshipRepository;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class NotificationController
{
    private const AVATAR_COLORS = [
        '#27509B', '#84BD00', '#FCE500', '#D62828',
        '#582C83', '#F77F00', '#2EC4B6', '#E71D36',
    ];

    public function __construct(
        private NotificationRepository $notificationRepository,
        private FriendshipRepository $friendshipRepository,
        private EntityManagerInterface $entityManager,
        private Security $security,
    ) {
    }

    #[Route('/api/notifications', name: 'api_notifications_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        /** @var \App\Entity\User $me */
        $me = $this->security->getUser();

        $result = [];

        // Friend requests where I'm the recipient
        foreach ($this->friendshipRepository->findAllForUser($me) as $f) {
            if ($f->getStatus() !== 'pending') {
                continue;
            }
            if ($f->getUserTo()->getId()->toString() !== $me->getId()->toString()) {
                continue;
            }

            $from = $f->getUserFrom();
            $uid  = $from->getId()->toString();

            $result[] = [
                'id'            => 'friendship-' . $f->getId()->toString(),
                'type'          => 'friend_request',
                'title'         => 'Nouvelle demande d\'ami·e',
                'body'          => $from->getName() . ' veut rejoindre ton cercle de cyclistes',
                'read'          => false,
                'created_at'    => $f->getCreatedAt()->format(\DateTimeInterface::ATOM),
                'from'          => [
                    'id'        => $uid,
                    'name'      => $from->getName(),
                    'color_hex' => self::AVATAR_COLORS[crc32($uid) % count(self::AVATAR_COLORS)],
                ],
                'friendship_id' => $f->getId()->toString(),
            ];
        }

        // System / tire notifications
        foreach ($this->notificationRepository->findForUser($me) as $n) {
            $result[] = [
                'id'         => $n->getId()->toString(),
                'type'       => $n->getType(),
                'title'      => $n->getTitle(),
                'body'       => $n->getBody(),
                'read'       => $n->isRead(),
                'created_at' => $n->getCreatedAt()->format(\DateTimeInterface::ATOM),
                'data'       => $n->getData(),
            ];
        }

        usort($result, static fn ($a, $b) => strcmp($b['created_at'], $a['created_at']));

        return new JsonResponse($result);
    }

    #[Route('/api/notifications/count', name: 'api_notifications_count', methods: ['GET'])]
    public function count(): JsonResponse
    {
        /** @var \App\Entity\User $me */
        $me = $this->security->getUser();

        // Unread system notifications
        $system = $this->notificationRepository->countUnreadForUser($me);

        // Pending friend requests where I'm the recipient
        $friendReqs = 0;
        foreach ($this->friendshipRepository->findAllForUser($me) as $f) {
            if ($f->getStatus() === 'pending' && $f->getUserTo()->getId()->toString() === $me->getId()->toString()) {
                $friendReqs++;
            }
        }

        return new JsonResponse(['unread' => $system + $friendReqs]);
    }

    #[Route('/api/notifications/read-all', name: 'api_notifications_read_all', methods: ['POST'])]
    public function readAll(): JsonResponse
    {
        /** @var \App\Entity\User $me */
        $me = $this->security->getUser();

        foreach ($this->notificationRepository->findForUser($me) as $n) {
            $n->setRead(true);
        }
        $this->entityManager->flush();

        return new JsonResponse(['ok' => true]);
    }

    #[Route('/api/notifications/{id}/read', name: 'api_notification_read', methods: ['POST'])]
    public function read(string $id): JsonResponse
    {
        /** @var \App\Entity\User $me */
        $me = $this->security->getUser();

        $n = $this->notificationRepository->find($id);
        if (!$n || $n->getUser()->getId()->toString() !== $me->getId()->toString()) {
            return new JsonResponse(['error' => 'Not found'], 404);
        }

        $n->setRead(true);
        $this->entityManager->flush();

        return new JsonResponse(['ok' => true]);
    }

    #[Route('/api/notifications/{id}', name: 'api_notification_delete', methods: ['DELETE'])]
    public function delete(string $id): JsonResponse
    {
        /** @var \App\Entity\User $me */
        $me = $this->security->getUser();

        // Friendship-type virtual notifications have id like "friendship-<uuid>"
        if (str_starts_with($id, 'friendship-')) {
            $fid = substr($id, strlen('friendship-'));
            $f   = $this->friendshipRepository->find($fid);
            if (!$f || $f->getUserTo()->getId()->toString() !== $me->getId()->toString()) {
                return new JsonResponse(['error' => 'Not found'], 404);
            }
            $this->entityManager->remove($f);
            $this->entityManager->flush();

            return new JsonResponse(['ok' => true]);
        }

        $n = $this->notificationRepository->find($id);
        if (!$n || $n->getUser()->getId()->toString() !== $me->getId()->toString()) {
            return new JsonResponse(['error' => 'Not found'], 404);
        }

        $this->entityManager->remove($n);
        $this->entityManager->flush();

        return new JsonResponse(['ok' => true]);
    }
}

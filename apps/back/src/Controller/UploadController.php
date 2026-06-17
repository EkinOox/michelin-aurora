<?php

namespace App\Controller;

use App\Repository\CyclistProfileRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

class UploadController
{
    private const ALLOWED_MIME = ['image/jpeg', 'image/png', 'image/webp', 'image/heic'];
    private const MAX_SIZE = 8 * 1024 * 1024; // 8 MB

    public function __construct(
        private Security $security,
        private CyclistProfileRepository $cyclistProfileRepository,
        private EntityManagerInterface $entityManager,
        private string $projectDir,
    ) {
    }

    #[Route('/api/profile/bike-photo', name: 'api_profile_bike_photo_upload', methods: ['POST'])]
    public function bikePhoto(Request $request): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->security->getUser();

        $file = $request->files->get('photo');
        if (!$file) {
            return new JsonResponse(['error' => 'No file provided'], 400);
        }

        // Check for PHP-level upload errors (size exceeded, partial, etc.)
        if ($file->getError() !== \UPLOAD_ERR_OK) {
            $phpErrors = [
                \UPLOAD_ERR_INI_SIZE   => 'Fichier trop grand (limite serveur)',
                \UPLOAD_ERR_FORM_SIZE  => 'Fichier trop grand (limite formulaire)',
                \UPLOAD_ERR_PARTIAL    => 'Envoi interrompu',
                \UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant',
                \UPLOAD_ERR_CANT_WRITE => 'Impossible d\'écrire sur le disque',
            ];
            return new JsonResponse(
                ['error' => $phpErrors[$file->getError()] ?? 'Erreur upload PHP #' . $file->getError()],
                400
            );
        }

        if ($file->getSize() > self::MAX_SIZE) {
            return new JsonResponse(['error' => 'Fichier trop grand (max 8 Mo)'], 400);
        }

        $mime = $file->getMimeType() ?? '';
        if (!in_array($mime, self::ALLOWED_MIME, true)) {
            return new JsonResponse(['error' => 'Type de fichier non autorisé (' . $mime . ')'], 400);
        }

        $ext = match ($mime) {
            'image/png'  => 'png',
            'image/webp' => 'webp',
            default      => 'jpg',
        };

        $filename = Uuid::v4()->toRfc4122() . '.' . $ext;
        $uploadDir = $this->projectDir . '/public/uploads/bikes';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        try {
            $file->move($uploadDir, $filename);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Impossible de sauvegarder le fichier : ' . $e->getMessage()], 500);
        }

        $url = '/uploads/bikes/' . $filename;

        $profile = $this->cyclistProfileRepository->findOneBy(['user' => $user]);
        if ($profile) {
            $profile->setBikePhotoUrl($url);
            $this->entityManager->flush();
        }

        return new JsonResponse(['url' => $url]);
    }
}

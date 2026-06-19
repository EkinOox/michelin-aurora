<?php

namespace App\Tests\Unit\Controller;

use App\Controller\UploadController;
use App\Entity\User;
use App\Repository\CyclistProfileRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

/**
 * Upload de photo de vélo : point d'entrée qui accepte un fichier client.
 * Les garde-fous (présence, taille, type MIME) sont la barrière de sécurité ;
 * on vérifie qu'un fichier invalide est rejeté AVANT toute écriture disque.
 */
final class UploadControllerTest extends TestCase
{
    private function makeController(): UploadController
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(new User());

        return new UploadController(
            $security,
            $this->createStub(CyclistProfileRepository::class),
            $this->createStub(EntityManagerInterface::class),
            sys_get_temp_dir(),
        );
    }

    /** Request POST avec (ou sans) fichier `photo`. */
    private function request(?UploadedFile $file): Request
    {
        $request = Request::create('/api/profile/bike-photo', 'POST');
        if ($file) {
            $request->files->set('photo', $file);
        }

        return $request;
    }

    public function testReturns400WhenNoFile(): void
    {
        $response = $this->makeController()->bikePhoto($this->request(null));

        self::assertSame(400, $response->getStatusCode());
    }

    public function testReturns400WhenFileTooLarge(): void
    {
        $file = $this->createStub(UploadedFile::class);
        $file->method('getError')->willReturn(\UPLOAD_ERR_OK);
        $file->method('getSize')->willReturn(9 * 1024 * 1024); // 9 Mo > limite 8 Mo
        $file->method('getMimeType')->willReturn('image/jpeg');

        $response = $this->makeController()->bikePhoto($this->request($file));

        self::assertSame(400, $response->getStatusCode());
    }

    public function testReturns400WhenMimeNotAllowed(): void
    {
        $file = $this->createStub(UploadedFile::class);
        $file->method('getError')->willReturn(\UPLOAD_ERR_OK);
        $file->method('getSize')->willReturn(1024);
        $file->method('getMimeType')->willReturn('application/pdf'); // type non autorisé

        $response = $this->makeController()->bikePhoto($this->request($file));

        self::assertSame(400, $response->getStatusCode());
    }
}

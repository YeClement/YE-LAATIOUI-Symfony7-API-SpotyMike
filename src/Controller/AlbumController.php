<?php

namespace App\Controller;

use App\Entity\Album;
use App\Repository\AlbumRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AlbumController extends AbstractController
{
    private $entityManager;
    private $repository;

    public function __construct(EntityManagerInterface $entityManager, AlbumRepository $repository)
    {
        $this->entityManager = $entityManager;
        $this->repository = $repository;
    }

    #[Route('/albums', name: 'album_list', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $albums = $this->repository->findAll();

        $albumsArray = [];
        foreach ($albums as $album) {
            $albumsArray[] = [
                'id' => $album->getId(),
                'idAlbum' => $album->getIdAlbum(),
                'nom' => $album->getNom(),
                'categ' => $album->getCateg(),
                'cover' => $album->getCover(),
                'year' => $album->getYear(),
                'artistUserId' => $album->getArtistUserIdUser() ? $album->getArtistUserIdUser()->getId() : null,
            ];
        }

        return $this->json($albumsArray);
    }

    #[Route('/album', name: 'album_create', methods: ['POST', 'PUT'])]
    public function createOrUpdate(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $album = new Album();
        $album->setIdAlbum($data['idAlbum'] ?? null);
        $album->setNom($data['nom'] ?? null);
        $album->setCateg($data['categ'] ?? null);
        $album->setCover($data['cover'] ?? null);
        $album->setYear($data['year'] ?? null);
        
        if (isset($data['artistUserId'])) {
            $artist = $this->entityManager->getReference('App\Entity\Artist', $data['artistUserId']);
            $album->setArtistUserIdUser($artist);
        }

        $this->entityManager->persist($album);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Album created or updated successfully'], JsonResponse::HTTP_CREATED);
    }
}

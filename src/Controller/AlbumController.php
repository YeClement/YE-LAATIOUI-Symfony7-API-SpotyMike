<?php

namespace App\Controller;

use App\Entity\Album;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Doctrine\ORM\EntityManagerInterface;

class AlbumController extends AbstractController
{
    private $entityManager;
    private $tokenStorage;

    public function __construct(EntityManagerInterface $entityManager, TokenStorageInterface $tokenStorage)
    {
        $this->entityManager = $entityManager;
        $this->tokenStorage = $tokenStorage;
    }

    #[Route('/album', name: 'album_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $token = $this->tokenStorage->getToken();
        $user = $token ? $token->getUser() : null;

        if (!$user || !$user->getArtist()) {
            return $this->json([
                'message' => 'Authentication requise. Vous devez être connecté et être un artiste pour effectuer cette action'
            ], JsonResponse::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        if (!isset($data['nom'], $data['categ'], $data['label'], $data['cover'], $data['year']) || !is_array($data)) {
            return $this->json([
                'message' => 'Missing required album fields'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            $album = new Album();
            $album->setNom($data['nom']);
            $album->setCateg($data['categ']);
            $album->setLabel($data['label']);
            $album->setCover($data['cover']);
            $album->setYear($data['year']);
            $album->setArtist($user->getArtist());

            $this->entityManager->persist($album);
            $this->entityManager->flush();

            return $this->json([
                'message' => 'Album created successfully',
               
            ], JsonResponse::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'An error occurred while creating the album.',
                'error' => $e->getMessage(),
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

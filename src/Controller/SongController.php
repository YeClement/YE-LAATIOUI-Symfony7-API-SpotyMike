<?php

namespace App\Controller;

use App\Entity\Song;
use App\Entity\Album;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class SongController extends AbstractController
{
    private $entityManager;
    private $tokenStorage;

    public function __construct(EntityManagerInterface $entityManager, TokenStorageInterface $tokenStorage)
    {
        $this->entityManager = $entityManager;
        $this->tokenStorage = $tokenStorage;
    }

    #[Route('/song', name: 'song_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $token = $this->tokenStorage->getToken();
        $user = $token ? $token->getUser() : null;
    
        if (!$user || !$user->getArtist()) {
            return $this->json([
                'message' => 'Authentication required. You must be logged in and be an artist to perform this action.'
            ], JsonResponse::HTTP_FORBIDDEN);
        }
    
        $data = json_decode($request->getContent(), true);
        if (!isset($data['title'], $data['cover']) || !is_array($data)) {
            return $this->json([
                'message' => 'Missing required song fields: title, cover.'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
    
        $album = null; 
        if (isset($data['albumId'])) {
            $album = $this->entityManager->getRepository(Album::class)->find($data['albumId']);
            if (!$album) {
                return $this->json([
                    'message' => 'Album not found.'
                ], JsonResponse::HTTP_NOT_FOUND);
            }
        }
    
        try {
            $song = new Song();
            $song->setTitle($data['title']);
            $song->setCover($data['cover']);
            $song->setCreatedAt(new \DateTimeImmutable());
            if ($album) { 
                $song->setAlbum($album);
            }
    
            $this->entityManager->persist($song);
            $this->entityManager->flush();
    
            return $this->json([
                'message' => 'Song created successfully.',
                'songId' => $song->getId(),
                'albumId' => $album ? $album->getId() : null 
            ], JsonResponse::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'An error occurred while creating the song.',
                'error' => $e->getMessage(),
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}    
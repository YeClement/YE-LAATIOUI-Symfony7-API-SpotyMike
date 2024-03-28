<?php

namespace App\Controller;

use App\Entity\Artist;
use App\Repository\ArtistRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ArtistController extends AbstractController
{
    private $entityManager;
    private $repository;

    public function __construct(EntityManagerInterface $entityManager, ArtistRepository $repository)
    {
        $this->entityManager = $entityManager;
        $this->repository = $repository;
    }

    #[Route('/artists', name: 'artist_list', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $artists = $this->repository->findAll();

        $artistsArray = [];
        foreach ($artists as $artist) {
            $artistsArray[] = [
                'id' => $artist->getId(),
                'userId' => $artist->getUserIdUser() ? $artist->getUserIdUser()->getId() : null,
                'fullname' => $artist->getFullname(),
                'label' => $artist->getLabel(),
                'description' => $artist->getDescription(),
            ];
        }

        return $this->json($artistsArray);
    }

    #[Route('/artist', name: 'artist_create', methods: ['POST', 'PUT'])]
    public function createOrUpdate(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $artist = new Artist();

        if (isset($data['userId'])) {
            $user = $this->entityManager->getReference('App\Entity\User', $data['userId']);
            $artist->setUserIdUser($user);
        }
        $artist->setFullname($data['fullname'] ?? null);
        $artist->setLabel($data['label'] ?? null);
        $artist->setDescription($data['description'] ?? null);

        $this->entityManager->persist($artist);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Artist created or updated successfully'], JsonResponse::HTTP_CREATED);
    }

    #[Route('/artist/{id}', name: 'artist_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $artist = $this->repository->find($id);

        if (!$artist) {
            return new JsonResponse(['error' => 'Artist not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($artist);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Artist deleted successfully'], JsonResponse::HTTP_OK);
    }
}
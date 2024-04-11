<?php

namespace App\Controller;
use App\Entity\User;
use App\Entity\Artist;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ArtistController extends AbstractController
{
    private $entityManager;
    private $artistRepository;
    private $security;

    public function __construct(EntityManagerInterface $entityManager,TokenStorageInterface $tokenStorage)
    {
        $this->entityManager = $entityManager;
        $this->artistRepository = $entityManager->getRepository(Artist::class);
        $this->tokenStorage = $tokenStorage;
    }

    #[Route('/artist', name: 'artist_create', methods: 'POST')]
    public function create(Request $request): JsonResponse
    {
        $token = $this->tokenStorage->getToken();
        $user = $token ? $token->getUser() : null;

        if (!$user instanceof UserInterface) {
            return $this->json(['message' => 'Authentication requise . Vous devez etre connecté pour effectuer cette action'], JsonResponse::HTTP_FORBIDDEN);
        }
        

        $data = json_decode($request->getContent(), true);

        if (!isset($data['fullname'], $data['label']) || !is_array($data)) {
            return $this->json([
                'message' => 'L id du label et le fullname sont obligatoires.'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            $artist = new Artist();
            $artist->setFullname($data['fullname']);
            $artist->setLabel($data['label']);
            $artist->setDescription($data['description'] ?? '');

            $this->entityManager->persist($artist);
            $this->entityManager->flush();

            return $this->json([
                'message' => "Votre compte d'artiste a été créé avec succès. Bienvenue dans notre communauté d'artistes !",
                'artist_id' => $artist->getId(),
            ], JsonResponse::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'An error occurred while creating the artist account.',
                'error' => $e->getMessage(), 
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }



    #[Route('/artist/{id}', name: 'artist_delete', methods: 'DELETE')]
    public function delete(int $id): JsonResponse
    {
        $artist = $this->artistRepository->find($id);

        if (!$artist) {
            return new JsonResponse(['error' => 'Artist not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($artist);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Artist deleted successfully'], JsonResponse::HTTP_OK);
    }

    #[Route('/artist', name: 'artist_get_all', methods: 'GET')]
    public function readAll(): JsonResponse
    {
        $result = [];

        try {
            $artists = $this->artistRepository->findAll();
            if (count($artists) > 0) {
                foreach ($artists as $artist) {
                    array_push($result, $artist->serializer());
                }
                return new JsonResponse([
                    'data' => $result,
                    'message' => 'Successful'
                ], JsonResponse::HTTP_OK);
            }
            return new JsonResponse([
                'message' => 'No artists found'
            ], JsonResponse::HTTP_NOT_FOUND);
        } catch (\Exception $exception) {
            return new JsonResponse([
                'message' => $exception->getMessage()
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/artist/{id}', name: 'artist_update', methods: ['PUT'])]
public function update(Request $request, int $id): JsonResponse
{
    $artist = $this->artistRepository->find($id);

    if (!$artist) {
        return $this->json(['message' => 'Artist not found'], JsonResponse::HTTP_NOT_FOUND);
    }

    $data = json_decode($request->getContent(), true);

    if (isset($data['fullname'])) {
        $artist->setFullname($data['fullname']);
    }
    if (isset($data['label'])) {
        $artist->setLabel($data['label']);
    }
    if (isset($data['description'])) {
        $artist->setDescription($data['description']);
    }

    $this->entityManager->flush();

    return $this->json([
        'message' => 'Artist updated successfully',
        'artist' => $artist->serializer()
    ], JsonResponse::HTTP_OK);
}

}

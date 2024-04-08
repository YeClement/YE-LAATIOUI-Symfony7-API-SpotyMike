<?php

namespace App\Controller;
use App\Entity\User;
use App\Entity\Artist;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ArtistController extends AbstractController
{
    private $entityManager;
    private $artistRepository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->artistRepository = $entityManager->getRepository(Artist::class);
    }

    #[Route('/artist', name: 'artist_create', methods: 'POST')]
public function create(Request $request): JsonResponse
{
    $data = json_decode($request->getContent(), true);

    if (!is_array($data)) {
        return $this->json(['message' => 'Invalid request data.'], JsonResponse::HTTP_BAD_REQUEST);
    }

    $userId = $data['user_id_user_id'] ?? null;

    if (!$userId) {
        return $this->json(['message' => 'User ID is required for creating an artist.'], JsonResponse::HTTP_BAD_REQUEST);
    }

    $user = $this->entityManager->getRepository(User::class)->find($userId);

    if (!$user) {
        return $this->json(['message' => 'User not found.'], JsonResponse::HTTP_NOT_FOUND);
    }

    $artist = new Artist();
    $artist->setUserIdUser($user); // Corrected method name
    $artist->setFullname($data['fullname'] ?? '');
    $artist->setLabel($data['label'] ?? '');
    $artist->setDescription($data['description'] ?? '');

    $this->entityManager->persist($artist);
    $this->entityManager->flush();

    return $this->json([
        'message' => 'Artist created successfully',
        'artist' => $artist->serializer()
    ], JsonResponse::HTTP_CREATED);
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

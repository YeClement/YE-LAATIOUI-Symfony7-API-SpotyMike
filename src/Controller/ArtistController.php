<?php

namespace App\Controller;
use App\Entity\User;
use App\Entity\Artist;
use App\Repository\ArtistRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
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

    
    $dateOfBirth = $user->getDateBirth();
    $today = new \DateTimeImmutable();
    $age = $today->diff($dateOfBirth)->y;

    if ($age < 16) {
        return $this->json(['message' => 'Vous devez avoir au moins 16 ans pour être artiste'], JsonResponse::HTTP_BAD_REQUEST);
    }

    
    if ($user->getArtist()) {
        return $this->json(['message' => 'Un utilisateur ne peut gérer qu’un seul compte existant pour créer un nouveau'], JsonResponse::HTTP_BAD_REQUEST);
    }

    $data = json_decode($request->getContent(), true);

    if (!isset($data['fullname'], $data['label']) || !is_array($data)) {
        return $this->json(['message' => 'L\'id du label et le fullname sont obligatoires'], JsonResponse::HTTP_BAD_REQUEST);
    }

    if ($this->entityManager->getRepository(Artist::class)->findOneBy(['fullname' => $data['fullname']])) {
        return $this->json(['message' => 'Ce nom d\'artiste est déjà pris, veuillez choisir un autre'], JsonResponse::HTTP_BAD_REQUEST);
    }

    //TO CHANGE AFTER
    if (strlen($data['label']) > 3) {
        return $this->json(['message' => 'Le label ne respecte pas le format attendu'], JsonResponse::HTTP_BAD_REQUEST);
    }


        try {
            $artist = new Artist();
            $artist->setUser($user);
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

    #[Route('/artist/{id}', name: 'artist_update', methods: ['POST'])]
public function updateArtist(int $id, Request $request, EntityManagerInterface $entityManager): JsonResponse
{
    $artist = $entityManager->getRepository(Artist::class)->find($id);
    if (!$artist) {
        return $this->json(['message' => 'Artist not found'], JsonResponse::HTTP_NOT_FOUND);
    }

    $data = json_decode($request->getContent(), true);
    if (!isset($data['fullname'], $data['label'], $data['description'])) {
        return $this->json(['message' => 'Missing required fields'], JsonResponse::HTTP_BAD_REQUEST);
    }

    $artist->setFullname($data['fullname']);
    $artist->setLabel($data['label']);
    $artist->setDescription($data['description']);

    $entityManager->persist($artist);
    $entityManager->flush();

    return $this->json([
        'message' => 'Artist updated successfully',
        'artist' => $artist->serializer()  // Assuming serializer() method exists to format the response
    ], JsonResponse::HTTP_OK);
}

#[Route('/artist/{id}', name: 'artist_get_by_id', methods: ['GET'])]
public function getArtistById(int $id, Request $request, ArtistRepository $artistRepository): JsonResponse
{
    
    $token = $this->tokenStorage->getToken();
    $user = $token ? $token->getUser() : null;

    
    if (!$user || !$user->getArtist()) {
        return $this->json([
            'message' => 'Authentication required. You must be logged in and be an artist to perform this action.'
        ], JsonResponse::HTTP_FORBIDDEN);
    }

   
    $fullname = $request->query->get('fullname');
    if (empty($fullname)) {
        return $this->json([
            'message' => 'Le nom d\'artiste est obligatoire pour cette requête.'
        ], JsonResponse::HTTP_BAD_REQUEST);
    }

    if (!preg_match('/^[a-zA-ZÀ-ÿ \'-]+$/u', $fullname)) {
        return $this->json([
            'message' => 'Le format du nom d\'artiste fourni est invalide.'
        ], JsonResponse::HTTP_BAD_REQUEST);
    }

    $artist = $artistRepository->findOneBy(['id' => $id, 'fullname' => $fullname]);
    if (!$artist) {
        return $this->json([
            'message' => 'Aucun artiste trouvé correspondant au nom fourni.'
        ], JsonResponse::HTTP_NOT_FOUND);
    }

    return $this->json([
        'data' => $artist->serializer(),
        'message' => 'Artist retrieved successfully'
    ], JsonResponse::HTTP_OK);
}
}
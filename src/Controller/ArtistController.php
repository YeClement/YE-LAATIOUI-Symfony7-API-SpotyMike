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

   



    #[Route('/artist/{id}', name: 'artist_delete', methods: 'DELETE')]
    public function delete(int $id): JsonResponse
    {
        $artist = $this->artistRepository->find($id);

        if (!$artist) {
            return new JsonResponse(['error' => 'Artiste'], JsonResponse::HTTP_NOT_FOUND);
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
                    'message' => 'Succes'
                ], JsonResponse::HTTP_OK);
            }
            return new JsonResponse([
                'message' => 'Artiste non trouve'
            ], JsonResponse::HTTP_NOT_FOUND);
        } catch (\Exception $exception) {
            return new JsonResponse([
                'message' => $exception->getMessage()
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/artist', name: 'artist_handle', methods: ['POST'])]
    public function handleArtist(Request $request, EntityManagerInterface $entityManager, TokenStorageInterface $tokenStorage): JsonResponse
    {
        $token = $tokenStorage->getToken();
        $user = $token ? $token->getUser() : null;
    
        if (!$user instanceof UserInterface) {
            return $this->json(['message' => 'Authentication requise. Vous devez être connecté pour effectuer cette action'], JsonResponse::HTTP_FORBIDDEN);
        }
    
    
        $fullname = $request->request->get('fullname');
        $label = $request->request->get('label');
        $description = $request->request->get('description');
    
        if (empty($fullname) || empty($label)) {
            return $this->json([
                'message' => 'Missing required fields.'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
    
       
        $artist = $user->getArtist();
    
        if ($artist) {
            $data = [
                'fullname' => $fullname,
                'label' => $label,
                'description' => $description
            ];
            return $this->updateArtist($data, $artist, $entityManager);
        } else {
            $data = [
                'fullname' => $fullname,
                'label' => $label,
                'description' => $description
            ];
            return $this->createArtist($data, $user, $entityManager);
        }
    }
    
    private function createArtist(array $data, UserInterface $user, EntityManagerInterface $entityManager): JsonResponse
    {
        $dateOfBirth = $user->getDateBirth();
        $today = new \DateTimeImmutable();
        $age = $today->diff($dateOfBirth)->y;
    
        if ($age < 16) {
            return $this->json(['message' => 'Vous devez avoir au moins 16 ans pour être artiste'], JsonResponse::HTTP_BAD_REQUEST);
        }
    
        if (empty($data['fullname']) || empty($data['label'])) {
            return $this->json(['message' => 'Les champs fullname et label sont obligatoires'], JsonResponse::HTTP_BAD_REQUEST);
        }
    
        if (strlen($data['label']) > 33) {
            return $this->json(['message' => 'Le label dépasse la longueur autorisée de 33 caractères'], JsonResponse::HTTP_BAD_REQUEST);
        }
    
        if (!preg_match('/^[a-zA-ZÀ-ÿ \'-]+$/u', $data['fullname'])) {
            return $this->json(['message' => 'Le format du nom d\'artiste fourni est invalide.'], JsonResponse::HTTP_BAD_REQUEST);
        }
    
        $existingArtist = $entityManager->getRepository(Artist::class)->findOneBy(['fullname' => $data['fullname']]);
        if ($existingArtist) {
            return $this->json(['message' => 'Ce nom d\'artiste est déjà utilisé, veuillez choisir un autre'], JsonResponse::HTTP_BAD_REQUEST);
        }
    
        $artist = new Artist();
        $artist->setUser($user);
        $artist->setFullname($data['fullname']);
        $artist->setLabel($data['label']);
        $artist->setDescription($data['description'] ?? '');
    
        $entityManager->persist($artist);
        $entityManager->flush();
    
        return $this->json([
            'message' => "Votre compte d'artiste a été créé avec succès.",
            'artist_id' => $artist->getId(),
        ], JsonResponse::HTTP_CREATED);
    }
    
    private function updateArtist(array $data, Artist $artist, EntityManagerInterface $entityManager): JsonResponse
    {
        if (empty($data['fullname']) || empty($data['label']) || empty($data['description'])) {
            return $this->json(['message' => 'Les données nécessaires à la mise à jour sont manquantes'], JsonResponse::HTTP_BAD_REQUEST);
        }
    
        // Validate the format of the fullname
        if (!preg_match('/^[a-zA-ZÀ-ÿ \'-]+$/u', $data['fullname'])) {
            return $this->json(['message' => 'Le format du nom d\'artiste fourni est invalide.'], JsonResponse::HTTP_BAD_REQUEST);
        }
    
        // Check if the fullname is already used by another artist (excluding the current artist)
        $existingArtist = $entityManager->getRepository(Artist::class)->findOneBy(['fullname' => $data['fullname']]);
        if ($existingArtist && $existingArtist->getId() !== $artist->getId()) {
            return $this->json(['message' => 'Le nom d\'artiste est déjà utilisé'], JsonResponse::HTTP_BAD_REQUEST);
        }
    
        // Update the artist details
        $artist->setFullname($data['fullname']);
        $artist->setLabel($data['label']);
        $artist->setDescription($data['description']);
    
        // Persist the changes to the database
        $entityManager->persist($artist);
        $entityManager->flush();
    
        // Return a successful response with the artist data
        return $this->json([
            'message' => 'Artist updated successfully',
            'artist' => $artist->serializer()
        ], JsonResponse::HTTP_OK);
    }
    

    #[Route('/artist/{fullname}', name: 'artist_get_by_id', methods: ['GET'])]
public function getArtistById( string $fullname, Request $request, ArtistRepository $artistRepository): JsonResponse
{
    
    $token = $this->tokenStorage->getToken();
    $user = $token ? $token->getUser() : null;

    
    if (!$user || !$user->getArtist()) {
        return $this->json([
            'message' => 'Authentication required. You must be logged in and be an artist to perform this action.'
        ], JsonResponse::HTTP_FORBIDDEN);
    }

    if (!preg_match('/^[a-zA-Z0-9]+$/', $fullname)) {
        return $this->json([
            'message' => 'Le format du nom d\'artiste fourni est invalide.'
        ], JsonResponse::HTTP_BAD_REQUEST);
    }

    $artist = $artistRepository->findOneBy(['fullname' => $fullname]);
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
<?php

namespace App\Controller;

use App\Entity\Album;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

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
                'error' => true,
                'message' => 'Authentification requise. Vous devez être connecté et être un artiste pour effectuer cette action.'
            ], JsonResponse::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        if (!isset($data['nom'], $data['categ'], $data['label'], $data['cover'], $data['year']) || !is_array($data)) {
            return $this->json([
                'error' => true,
                'message' => 'Champs d\'album requis manquants'
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
                'message' => 'Album créé avec succès',
            ], JsonResponse::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json([
                'error' => true,
                'message' => 'Une erreur est survenue lors de la création de l\'album.',
                'error' => $e->getMessage(),
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/albums', name: 'albums_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $token = $this->tokenStorage->getToken();
        if (!$token) {
            return $this->json([
                'error' => true,
                'message' => 'Authentification requise. Vous devez être connecté pour effectuer cette action.'
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $user = $token->getUser();

        $currentPage = $request->query->getInt('currentPage', 1);
        $limit = $request->query->getInt('limit', 5);

        if ($currentPage < 1) {
            return $this->json([
                'error' => true,
                'message' => 'Le paramètre de pagination est invalide. Veuillez fournir un numéro de page valide.'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $albums = $this->getDoctrine()->getRepository(Album::class)->findByArtist($user->getArtist(), $currentPage, $limit);

        if (empty($albums)) {
            return $this->json([
                'error' => true,
                'message' => 'Aucun album trouvé pour la page demandée.'
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        $response = [
            'error' => false,
            'albums' => $albums,
            'pagination' => [
                'currentPage' => $currentPage,
                'totalPages' => ceil(count($albums) / $limit),
                'totalAlbums' => count($albums)
            ]
        ];

        return $this->json($response, JsonResponse::HTTP_OK);
    }
    
    #[Route('/album/{id}', name: 'album_show', methods: ['GET'])]
    public function show(Request $request, $id): JsonResponse
    {
        // Vérifiez l'authentification de l'utilisateur
        if (!$this->getToken()) {
            return $this->json([
                'error' => true,
                'message' => 'Authentification requise. Vous devez être connecté pour effectuer cette action.'
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        // Récupérez l'utilisateur connecté
        $user = $this->getToken()->getUser();

        // Vérifiez si l'ID de l'album est fourni
        if (!$id) {
            return $this->json([
                'error' => true,
                'message' => "L'id de l'album est obligatoire pour cette requête."
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Récupérez l'album en fonction de l'ID
        $album = $this->getDoctrine()->getRepository(Album::class)->find($id);

        // Vérifiez si l'album existe
        if (!$album) {
            return $this->json([
                'error' => true,
                'message' => "L'album non trouvé. Vérifiez les informations fournies et réessayez."
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        // Vérifiez si l'utilisateur est autorisé à voir cet album
        if (!$album->isVisible() && $album->getArtist() !== $user->getArtist()) {
            return $this->json([
                'error' => true,
                'message' => "L'album non trouvé. Vérifiez les informations fournies et réessayez."
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        $response = [
            'error' => false,
            'album' => $album
        ];

        return $this->json($response, JsonResponse::HTTP_OK);
    }
}

<?php

namespace App\Controller;
use App\Entity\User;
use App\Entity\Artist;
use DateTimeImmutable;
use App\Repository\ArtistRepository;
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

    #[Route('/artist', name: 'artist_get_all', methods: ['GET'])]
    public function getAllArtists(Request $request , TokenStorageInterface $tokenStorage): JsonResponse
    {
        $token = $tokenStorage->getToken();
        $user = $token ? $token->getUser() : null;
    
        
        if (!$user) {
            return $this->json([
                'error' => true,
                'message' => 'Authentification requise. Vous devez être connecté pour effectuer cette action.'
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $currentPage = $request->query->get('currentPage');
        $limit = (int) $request->query->get('limit', 5);

        if (!filter_var($currentPage, FILTER_VALIDATE_INT) || null === $currentPage ||$currentPage < 1 || $limit < 1) {
            return new JsonResponse(['error' => true, 'message' => 'Le paramètre de pagination est invalide. Veuillez fournir un numéro de page valide.'], JsonResponse::HTTP_BAD_REQUEST);
        }
        $currentPage = (int) $currentPage;
        $artists = $this->artistRepository->findAllWithPagination($currentPage, $limit);
        if (!$artists) {
            return new JsonResponse(['error' => true, 'message' => 'Aucun artiste trouvé pour la page demandée.'], JsonResponse::HTTP_NOT_FOUND);
        }

        $totalArtists = $this->artistRepository->count([]);
        $totalPages = ceil($totalArtists / $limit);
        $artistsData = $this->serializerArtists($artists);

        return new JsonResponse([
            'error' => false,
            'artists' => $artistsData,
            'message' => 'Informations des artistes récupérées avec succès.',
            'pagination' => [
                'currentPage' => $currentPage,
                'totalPages' => $totalPages,
                'totalArtists' => $totalArtists
            ]
        ], JsonResponse::HTTP_OK);
    }

    private function serializerArtists(array $artists): array
    {
        $result = [];
        foreach ($artists as $artist) {
            $user = $artist->getUser();
            $albumsSerialized = []; // Array to hold serialized albums
            foreach ($artist->getAlbums() as $album) {
                $albumsSerialized[] = $album->serializer(); // Serialize each album
            }
            
            $result[] = [
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
                'fullname' => $artist->getFullname(),
                'avatar' => $artist->getAvatar() ?? '', 
                'sexe' => $user->getSexe() ? 'homme' : 'femme', // Assuming this data is in the User entity
                'dateBirth' => $user->getDateBirth()->format('d-m-Y'),
                'Artist.createdAt' => $artist->getCreatedAt()->format('Y-m-d'),
                'albums' => $albumsSerialized // Assuming you have a method to serialize albums
            ];
        }
        return $result;
    }


    #[Route('/artist/{fullname}', name: 'get_artist_by_fullname', methods: ['GET'])]
public function getArtistByFullname(string $fullname, Request $request, TokenStorageInterface $tokenStorage): JsonResponse
{
    $token = $tokenStorage->getToken();
    $user = $token ? $token->getUser() : null;

    if (!$user) {
        return $this->json([
            'error' => true,
            'message' => 'Authentification requise. Vous devez être connecté pour effectuer cette action.'
        ], JsonResponse::HTTP_UNAUTHORIZED);
    }

    if (empty($fullname)) {
        return new JsonResponse(['error' => true, 'message' => 'Le nom d\'artiste est obligatoire pour cette requête.'], JsonResponse::HTTP_BAD_REQUEST);
    }

    $artist = $this->artistRepository->findOneBy(['fullname' => $fullname]);
    if (!$artist) {
        return new JsonResponse(['error' => true, 'message' => 'Aucun artiste trouvé correspondant au nom fourni.'], JsonResponse::HTTP_NOT_FOUND);
    }

    return new JsonResponse(['error' => false, 'artist' => $this->serializeArtist($artist)], JsonResponse::HTTP_OK);
}

   

public function serializeArtist(Artist $artist): array
{
    $user = $artist->getUser();  // This is the artist's user information

    $featuringSongs = [];
    foreach ($artist->getAlbums() as $album) {
        foreach ($album->getSongs() as $song) {
            if ($song->isFeaturing()) {
                $featuringSongs[] = [
                    'id' => $song->getId(),
                    'title' => $song->getTitle(),
                    'cover' => $song->getCover(),
                    'createdAt' => $song->getCreatedAt()->format('c'),
                    'artist' => $song->getFeaturedArtist() ? $song->getFeaturedArtist()->getFullname() : null
                ];
            }
        }
    }

    $Follower = $artist->getFollower() ?? '';
   

    return [
        'firstname' => $user->getFirstname(),
        'lastname' => $user->getLastname(),
        'fullname' => $artist->getFullname(),
        'avatar' => $artist->getAvatar() ?? '',
        'follower' => $Follower->getFirstname() ?? '',  // Now returns featured follower details
        'sexe' => $user->getSexe() ? 'homme' : 'femme',
        'dateBirth' => $user->getDateBirth()->format('d-m-Y'),
        'Artist.createdAt' => $artist->getCreatedAt()->format('Y-m-d'),
        'featuring' => $featuringSongs,
        'albums' => array_map(function ($album) {
            return [
                'id' => $album->getId(),
                'nom' => $album->getNom(),
                'categ' => $album->getCateg(),
                'label' => $album->getLabel(),
                'cover' => $album->getCover(),
                'year' => $album->getYear(),
                'createdAt' => $album->getCreatedAt()->format('Y-m-d H:i:s'),
                'songs' => array_map(function ($song) {
                    return [
                        'id' => $song->getId(),
                        'title' => $song->getTitle(),
                        'cover' => $song->getCover(),
                        'createdAt' => $song->getCreatedAt()->format('c')
                    ];
                }, $album->getSongs()->toArray())
            ];
        }, $artist->getAlbums()->toArray())
    ];
}

   /* private function getFeaturingSongs(Song $song): array
    {
        
        'albums' => $artist->getAlbums()->map(function ($album) {
            return $album->serializer();
        })->toArray(),
        return $song->getSong()->map(function ($song) {
            return [
                'id' => $song->getId(),
                'title' => $song->getTitle(),
                'cover' => $song->getCover(),
                'artist' => $song->getArtist()->getFullname(),
                'createdAt' => $song->getCreatedAt()->format('Y-m-d'),
            ];
        })->toArray();
    }*/

    private function getAlbumsData(Artist $artist): array
    {
        return $artist->getAlbums()->map(function ($album) {
            return [
                'id' => $album->getId(),
                'nom' => $album->getNom(),
                'categ' => $album->getCateg(),
                'label' => $album->getLabel(),
                'cover' => $album->getCover(),
                'year' => $album->getYear(),
                'createdAt' => $album->getCreatedAt()->format('Y-m-d'),
                'songs' => $album->getSong()->map(function ($song) {
                    return [
                        'id' => $song->getId(),
                        'title' => $song->getTitle(),
                        'cover' => $song->getCover(),
                        'createdAt' => $song->getCreatedAt()->format('Y-m-d'),
                    ];
                })->toArray()
            ];
        })->toArray();
    }

    
    #[Route('/artist', name: 'artist_handle', methods: ['POST'])]
    public function handleArtist(Request $request, EntityManagerInterface $entityManager, TokenStorageInterface $tokenStorage): JsonResponse
    {
        $token = $tokenStorage->getToken();
        $user = $token ? $token->getUser() : null;
    
        
        if (!$user) {
            return $this->json([
                'error' => true,
                'message' => 'Authentification requise. Vous devez être connecté pour effectuer cette action.'
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }
    
        $artist = $user->getArtist();
        
        if ($artist) {
            return $this->updateArtist($request, $artist, $entityManager);
        } else {
            return $this->createArtist($request, $user, $entityManager);
        }
    }
    

    
    private function createArtist(Request $request, UserInterface $user, EntityManagerInterface $entityManager): JsonResponse
    {
        $dateOfBirth = $user->getDateBirth();
        $today = new \DateTimeImmutable();
        $age = $today->diff($dateOfBirth)->y;
        $requestData = $request->request->all();
    
        if ($age < 16) {
            return $this->json([
                'error' => true,
                'message' => 'Vous devez avoir au moins 16 ans pour être artiste.'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
    
        if (empty($requestData['fullname']) || empty($requestData['label'])) {
            return $this->json([
                'error' => true,
                'message' => 'L\'id du label et le fullname sont obligatoires.'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
    
        if (strlen($requestData['label']) > 50) {
            return $this->json([
                'error' => true,
                'message' => 'Le label dépasse la longueur autorisée de 50 caractères.'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
    
        $existingArtist = $entityManager->getRepository(Artist::class)->findOneBy(['fullname' => $requestData['fullname']]);
        if ($existingArtist) {
            return $this->json([
                'error' => true,
                'message' => 'Ce nom d\'artiste est déjà pris. Veuillez en choisir un autre.'
            ], JsonResponse::HTTP_CONFLICT);
        }
    
        $avatarData = $request->request->get('avatar');
        $imageName = ''; 
    
        if (!empty($avatarData)) {
            $avatarBinary = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $avatarData));
    
            if ($avatarBinary === false) {
                return $this->json([
                    'error' => true,
                    'message' => 'Le serveur ne peut pas décoder le contenu base64 en fichier binaire.'
                ], JsonResponse::HTTP_BAD_REQUEST);
            }
    
            $f = finfo_open();
            $mimeType = finfo_buffer($f, $avatarBinary, FILEINFO_MIME_TYPE);
            finfo_close($f);
            if (!in_array($mimeType, ['image/jpeg', 'image/png'])) {
                return $this->json([
                    'error' => true,
                    'message' => 'Erreur sur le format de fichier, qui n\'est pas pris en compte. Uniquement JPEG et PNG sont acceptés.'
                ], JsonResponse::HTTP_BAD_REQUEST);
            }
    // TO CHANGE THE SIZE AFTER
            $fileSize = strlen($avatarBinary);
            if ($fileSize < 104 || $fileSize > 7340032) { 
                return $this->json([
                    'error' => true,
                    'message' => 'Le fichier envoyé est trop ou pas assez volumineux. Vous devez respecter la taille entre 1MB et 7MB.'
                ], JsonResponse::HTTP_BAD_REQUEST);
            }
    
            $imageName = uniqid('avatar_') . '.jpg';
            $imagePath = $this->getParameter('kernel.project_dir') . '/public/images/' . $imageName;
            if (!file_put_contents($imagePath, $avatarBinary)) {
                return $this->json([
                    'error' => true,
                    'message' => 'Erreur lors de l\'enregistrement du fichier.'
                ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
    
        $artist = new Artist();
        $artist->setUser($user);
        $artist->setFullname($requestData['fullname']);
        $artist->setLabel($requestData['label']);
        $artist->setDescription($requestData['description'] ?? '');
        $artist->setAvatar($imageName); 
    
        $entityManager->persist($artist);
        $entityManager->flush();
    
        return $this->json([
            'success' => true,
            'message' => "Votre compte d'artiste a été créé avec succès. Bienvenue dans notre communauté d'artistes!",
            'artist_id' => $artist->getId(),
        ], JsonResponse::HTTP_CREATED);
    }
    
    
private function updateArtist(Request $request, Artist $artist, EntityManagerInterface $entityManager): JsonResponse
{
    $requestData = $request->request->all();

    $validKeys = ['fullname', 'label', 'description', 'avatar'];
    foreach ($requestData as $key => $value) {
        if (!in_array($key, $validKeys)) {
            return $this->json([
                'error' => true,
                'message' => 'Les patamètres fournies sont invalides. Veuillez vérifier les données soumises.'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
    }
   
    
    if (isset($requestData['fullname'])) {
        $existingArtist = $entityManager->getRepository(Artist::class)->findOneBy(['fullname' => $requestData['fullname']]);
        if ($existingArtist && $existingArtist->getId() !== $artist->getId()) {
            return $this->json(['message' => 'Le nom d\'artiste est déjà utilisé'], JsonResponse::HTTP_BAD_REQUEST);
        }
        $artist->setFullname($requestData['fullname']);
    }

    if (isset($requestData['label'])) {
        $artist->setLabel($requestData['label']);
    }
    if (isset($requestData['description'])) {
        $artist->setDescription($requestData['description']);
    }
    

    $entityManager->persist($artist);
    $entityManager->flush();

    
    return $this->json([
        'succes' => true,
        'message' => 'Les informations de l\'artiste ont été mises àa jour avec succès.'
    ], JsonResponse::HTTP_OK);
}

    

  




}    
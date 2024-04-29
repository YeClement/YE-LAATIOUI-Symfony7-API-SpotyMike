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
                    'error' => false,
                    'data' => $result,
                    'message' => 'Informations des artistes récupérées avec succès.'
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
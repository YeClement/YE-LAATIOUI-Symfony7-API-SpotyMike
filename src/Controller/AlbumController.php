<?php
 
namespace App\Controller;
use App\Entity\Album ;
use App\Repository\AlbumRepository;
use App\Service\TokenVerifierService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
 
class AlbumController extends AbstractController
{
    private AlbumRepository $albumRepository;
    private TokenVerifierService $tokenVerifierService;
    private EntityManagerInterface $entityManager;
 
    public function __construct(AlbumRepository $albumRepository,TokenVerifierService $tokenVerifierService,EntityManagerInterface $entityManager)
    {
        $this->albumRepository = $albumRepository;
        $this->tokenVerifierService = $tokenVerifierService;
        $this->entityManager = $entityManager;
    }
 
    #[Route('/albums', name: 'get_albums', methods: ['GET'])]
    public function getAlbums(Request $request): JsonResponse
    {
        // Vérification de l'authentification de l'utilisateur
        $user = $this->getUser();
        if (!$user) {
            return $this->json([
                'error' => true,
                'message' => 'Authentification requise. Vous devez être connecté pour effectuer cette action.'
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }
 
        // Récupération des paramètres de la requête
        $currentPage = (int)$request->query->get('currentPage', 1);
        $limit = (int)$request->query->get('limit', 5);
 
        // Validation des paramètres de pagination
        if (!filter_var($currentPage, FILTER_VALIDATE_INT) || $currentPage < 1 || $limit < 1) {
            return $this->json([
                'error' => true,
                'message' => 'Le paramètre de pagination est invalide. Veuillez fournir un numéro de page valide.'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
 
        // Récupération des albums avec pagination
        $albums = $this->albumRepository->findAllWithPagination($currentPage, $limit);
 
        // Vérification s'il y a des albums trouvés
        if (empty($albums)) {
            return $this->json([
                'error' => true,
                'message' => 'Aucun album trouvé pour la page demandée.'
            ], JsonResponse::HTTP_NOT_FOUND);
        }
 
        // Récupération du nombre total d'albums
        $totalAlbums = $this->albumRepository->count([]);
 
        // Calcul du nombre total de pages
        $totalPages = ceil($totalAlbums / $limit);
 
        // Sérialisation des albums
        $albumsData = $this->serializeAlbums($albums);
 
        // Retour de la réponse JSON
        return $this->json([
            'error' => false,
            'albums' => $albumsData,
            'pagination' => [
                'currentPage' => $currentPage,
                'totalPages' => $totalPages,
                'totalAlbums' => $totalAlbums
            ]
        ], JsonResponse::HTTP_OK);
    }
 
   #[Route('/album/{id}', name: 'get_album', methods: ['GET'])]
    public function getAlbum(Request $request, string $id): JsonResponse
    {
        // Vérification de l'authentification de l'utilisateur
        $user = $this->getUser();
        if (!$user) {
            return $this->json([
                'error' => true,
                'message' => 'Authentification requise. Vous devez être connecté pour effectuer cette action.'
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }
 
        // Vérification si l'ID de l'album est fourni
        if (empty($id)) {
            return $this->json([
                'error' => true,
                'message' => "L'id de l'album est obligatoire pour cette requête."
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
 
        // Récupération de l'album avec l'ID spécifié
        $album = $this->albumRepository->find($id);
 
        // Vérification si l'album est trouvé
        if (!$album) {
            return $this->json([
                'error' => true,
                'message' => "L'album non trouvé. Vérifiez les informations fournies et réessayez."
            ], JsonResponse::HTTP_NOT_FOUND);
        }
 
        // Sérialisation des données de l'album
        $albumData = $this->serializeAlbum($album);
 
        // Retour de la réponse JSON
        return $this->json([
            'error' => false,
            'album' => $albumData
        ], JsonResponse::HTTP_OK);
    }
 
    #[Route('/album/search', name: 'album_search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
       
    $user = $this->getUser();
    if (!$user) {
        return $this->json([
            'error' => true,
            'message' => 'Authentification requise. Vous devez être connecté pour effectuer cette action.'
        ], JsonResponse::HTTP_UNAUTHORIZED);
    }
        $requestData = $request->query->all();
        if(count($requestData) <= 0){
            return $this->json([
                'error'=>true,
                'message'=> "Les paramètres fournis sont invalides. Veuillez vérifier les données soumises."
            ],400);
        }
 
        $validKeys = array("currentPage","nom", "fullname", "labe", "year", "featuring", "category", "limit");
        foreach ($requestData as $key => $value){
            if (!in_array($key, $validKeys)){
                return $this->json([
                    'error'=>true,
                    'message'=> "Les paramètres fournis sont invalides. Veuillez vérifier les données soumises."
                ],400);
            }
        };    
 
 
 
        $featuringList = null;
        if(isset($requestData['featuring'])){
            $featuringList = json_decode($requestData['featuring']);
            if (!$featuringList || !is_array($featuringList)){
                return $this->json([
                    'error'=>true,
                    'message'=> "Les featuring ciblée sont invalide."
                ],400);
            }
        }
       
 
        $currentPage = (int) $request->query->get('currentPage', 1);
        $limit = (int) $request->query->get('limit', 5);
       
 
        if (!filter_var($currentPage, FILTER_VALIDATE_INT) || $currentPage < 1 || $limit < 1) {
            return $this->json([
                'error' => true,
                'message' => 'Invalid pagination parameter. Please provide a valid page number.'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
 
       
       
        $totalAlbums = $this->albumRepository->count([]);
        $totalPages = ceil($totalAlbums / $limit);
        if ($currentPage > $totalPages) {
            return $this->json([
                'error' => true,
                'message' => 'Aucun album trouvé pour la page demandée.'
            ], JsonResponse::HTTP_NOT_FOUND);
        }
 
       
        $validCategories = ["rap", "r'n'b", "gospel", "soul", "country", "hip hop", "jazz", "le Mike"];
        $categoryString = $request->query->get('category', '');

        
        if (!empty($categoryString)) {
            $categories = explode(',', $categoryString);
            foreach ($categories as $category) {
                $category = trim(strtolower($category));
                if (!in_array($category, array_map('strtolower', $validCategories))) {
                    return $this->json([
                        'error' => true,
                        'message' => 'Les catégorie ciblée sont invalides.'
                    ], JsonResponse::HTTP_BAD_REQUEST);
                }
            }
        }  
   
 
    $albums = $this->albumRepository->findAllWithPagination($currentPage, $limit);
 
        $totalAlbums = $this->albumRepository->count([]);
        $totalPages = ceil($totalAlbums / $limit);
        $albumsData = $this->serializeAlbums($albums);
 
        return $this->json([
            'error' => false,
            'albums' => $albumsData,
            'pagination' => [
                'currentPage' => $currentPage,
                'totalPages' => $totalPages,
                'totalAlbums' => $totalAlbums
            ]
        ], JsonResponse::HTTP_OK);
    }
 
    private function serializeAlbums(array $albums): array
    {
        $result = [];
        foreach ($albums as $album) {
            $artist = $album->getArtist();
            $user = $artist->getUser();
 
   
            // Serialize artist data
            $artistData = [
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
                'fullname' => $artist->getFullname(),
                'avatar' => $artist->getAvatar() ?? '',
                'follower' => $artist->getFollower() ?? '',
                'cover' => $album->getCover(),
                'sexe' => $user->getSexe() ? 'homme' : 'femme',
                'dateBirth' => $user->getDateBirth()->format('d-m-Y'),
                'createdAt' => $artist->getCreatedAt()->format('Y-m-d'),
            ];
   
            // Serialize songs data
            $songsData = [];
            foreach ($album->getSongs() as $song) {
                $songData = [
                    'id' => $song->getId(),
                    'title' => $song->getTitle(),
                    'cover' => $song->getCover(),
                    'createdAt' => $song->getCreatedAt()->format('Y-m-d'),
                ];
   
                // featuring
                if ($song->isFeaturing() && $song->getFeaturedArtist()) {
                    $featuredArtist = $song->getFeaturedArtist();
                    $songData['featuring'] = [
                        'firstname' => $featuredArtist->getUser()->getFirstname(),
                        'lastname' => $featuredArtist->getUser()->getLastname(),
                        'fullname' => $featuredArtist->getFullname(),
                        'avatar' => $featuredArtist->getAvatar() ?? '',
                        'follower' => $featuredArtist->getFollower() ?? '',
                        'cover' => $album->getCover(),
                        'sexe' => $featuredArtist->getUser()->getSexe() ? 'homme' : 'femme',
                        'dateBirth' => $featuredArtist->getUser()->getDateBirth()->format('d-m-Y'),
                        'Artist.createdAt' => $featuredArtist->getCreatedAt()->format('Y-m-d'),
                    ];
                }
   
                $songsData[] = $songData;
            }
   
            // all album data
            $result[] = [
                'id' => $album->getId(),
                'nom' => $album->getNom(),
                'categ' => $album->getCateg(),
                'label' => $album->getLabel(),
                'cover' => $album->getCover(),
                'year' => $album->getYear(),
                'createdAt' => $album->getCreatedAt()->format('Y-m-d'),
                'artist' => $artistData,
                'songs' => $songsData
            ];
        }
        return $result;
    }
 
    #[Route('/album', name: 'create_album', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json([
                'error' => true,
                'message' => 'Authentification requise. Vous devez être connecté pour effectuer cette action.'
            ], 401);
        }

        $data = $request->request->all();

        if (count($data) != 4 || !isset($data['visibility']) || !isset($data['cover']) || !isset($data['title']) || !isset($data['categorie'])) {
            return $this->json([
                'error' => true,
                'message' => 'Les paramètres fournis sont invalides. Veuillez vérifier les données soumises.'
            ], 400);
        }

        $validCategories = ["rap", "r'n'b", "gospel", "soul", "country", "hip hop", "jazz", "le Mike"];
        $categoryString = $request->query->get('category', '');

        
        if (!empty($categoryString)) {
            $categories = explode(',', $categoryString);
            foreach ($categories as $category) {
                $category = trim(strtolower($category));
                if (!in_array($category, array_map('strtolower', $validCategories))) {
                    return $this->json([
                        'error' => true,
                        'message' => 'Les catégorie ciblée sont invalides.'
                    ], JsonResponse::HTTP_BAD_REQUEST);
                }
            }
        }  

        if (!in_array($data['visibility'], [0, 1])) {
            return $this->json([
                'error' => true,
                'message' => 'La valeur du champ visibility est invalide. Les valeurs autorisées sont 0 pour invisible, 1 pour visible.'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
        

        $existingAlbum = $this->albumRepository->findOneBy(['nom' => $data['title']]);
        if ($existingAlbum) {
            return $this->json([
                'error' => true,
                'message' => 'Ce titre est déjà pris. Veuillez en choisir un autre.'
            ], JsonResponse::HTTP_CONFLICT);
        }
        $data = $request->request->all();
        $coverData = $data['cover'] ?? null;
        $imageName = '';

        if ($coverData) {
            $coverBinary = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $coverData));

            if ($coverBinary === false) {
                return $this->json([
                    'error' => true,
                    'message' => 'Le serveur ne peut pas décoder le contenu base64 en fichier binaire.'
                ], 422);
            }

            $f = finfo_open();
            $mimeType = finfo_buffer($f, $coverBinary, FILEINFO_MIME_TYPE);
            finfo_close($f);
            if (!in_array($mimeType, ['image/jpeg', 'image/png'])) {
                return $this->json([
                    'error' => true,
                    'message' => 'Erreur sur le format de fichier, qui n\'est pas pris en compte. Uniquement JPEG et PNG sont acceptés.'
                ], 422);
            }
// 1048576
            $fileSize = strlen($coverBinary);
            if ($fileSize < 1048576 || $fileSize > 7340032) { 
                return $this->json([
                    'error' => true,
                    'message' => 'Le fichier envoyé est trop ou pas assez volumineux. Vous devez respecter la taille entre 1MB et 7MB.'
                ], 422);
            }

            $imageName = uniqid('album_cover_') . '.' . ($mimeType === 'image/jpeg' ? 'jpg' : 'png');
            $imagePath = $this->getParameter('kernel.project_dir') . '/public/images/' . $imageName;
            if (!file_put_contents($imagePath, $coverBinary)) {
                return $this->json([
                    'error' => true,
                    'message' => 'Erreur lors de l\'enregistrement du fichier.'
                ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        $requestData = $request->request->all();
        if (strlen($requestData['title']) < 1 || strlen($requestData['title']) > 40) {
            return $this->json([
                'error' => true,
                'message' => 'Erreur de validation des données.'
            ], 422);
        }

        $album = new Album();
        $album->setNom($data['title']);
        $album->setCateg($data['categorie']);
        $album->setVisibility($data['visibility']);
        $album->setCover($data['cover']);
        //$album->setUser($user);

        $this->entityManager->persist($album);
$this->entityManager->flush();
      

        return $this->json([
            'error' => false,
            'message' => 'Album créé avec succès.',
            'id' => $album->getId()
            
        ], JsonResponse::HTTP_CREATED);
    }
 
}
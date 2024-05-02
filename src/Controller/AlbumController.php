<?php

namespace App\Controller;

use App\Repository\AlbumRepository;
use App\Service\TokenVerifierService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class AlbumController extends AbstractController
{
    private AlbumRepository $albumRepository;
    private $entityManager;
    

    public function __construct(AlbumRepository $albumRepository, TokenVerifierService $tokenVerifierService, EntityManagerInterface $entityManager)
    {
    
        $this->tokenVerifierService = $tokenVerifierService;
        $this->albumRepository = $albumRepository;
     
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
        if (empty($categoryString)) {
            return $this->json([
                'error' => true,
                'message' => 'Les catégorie ciblée sont invalides.'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
    
        // Split the string by commas to get an array of categories
        $categories = explode(',', $categoryString);
        $categ = "";
        foreach ($categories as $key => $category) {
            $category = trim(strtolower($category)); 
            if (!in_array($category, array_map('strtolower', $validCategories))) {
                return $this->json([
                    'error' => true,
                    'message' => 'Les catégorie ciblée sont invalides.'
                ], JsonResponse::HTTP_BAD_REQUEST);
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
    
                // Check if there is a featuring artist and the song is marked as featuring
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
    
            // Combine all album data
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


}
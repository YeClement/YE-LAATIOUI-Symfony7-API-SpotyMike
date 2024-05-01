<?php

namespace App\Controller;

use App\Entity\User;
use DateTimeImmutable;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\CacheInterface;

class LoginController extends AbstractController
{
    private $entityManager;
    private $repository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(User::class);
    }

    #[Route('/', name: 'app_index', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        // Renvoie le contenu du fichier index.php comme réponse
        $content = file_get_contents(__DIR__ . '/public/index.php');

        return new JsonResponse($content);
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(Request $request, UserPasswordHasherInterface $passwordHasher, JWTTokenManagerInterface $JWTManager, UserRepository $userRepository, FilesystemAdapter $cache): JsonResponse
    {
        // Récupération des données de la requête
        $email = $request->request->get('email');
        $password = $request->request->get('password');
        $data = $request->request->all();

        // Vérification si les données nécessaires sont fournies
        if (!isset($data['email']) || !isset($data['password'])) {
            return new JsonResponse(['error' => true, 'message' => 'Email/password manquants.'], JsonResponse::HTTP_BAD_REQUEST);
        }


        
        $regex = '/^(?:(?:[a-zA-Z0-9!#$%&\'*+\/=?^_`{|}~.-]+)|(?:\"(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21\x23-\x5b\x5d-\x7f]|\\\\[\x01-\x09\x0b\x0c\x0e-\x7f])*\"))@(?:(?:[a-zA-Z0-9](?:[a-zA-Z0-9-\x{2014}]*[a-zA-Z0-9])?\.)*(?:[a-zA-Z\x{2014}]{2,}|(?:\[(?:(?:(?:25[0-5]|2[0-4][0-9]|1[0-9]{2}|[1-9]?[0-9])\.){3}(?:25[0-5]|2[0-4][0-9]|1[0-9]{2}|[1-9]?[0-9])|(?:IPv6:[a-fA-F0-9:]+))\])))$/iu';
        if (!preg_match($regex, $email)) {

            return new JsonResponse(['error' => true, 'message' => 'Le format de l\'email est invalide.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Vérification des exigences du mot de passe
        $passwordRequirements = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z\d]).{8,}$/';
        if (!preg_match($passwordRequirements, $data['password'])) {
            return new JsonResponse(['error' => true, 'message' => 'Le mot de passe doit contenir au moins une majuscule, une minuscule, un chiffre, un caractère spécial et avoir 8 caractères minimum.'], JsonResponse::HTTP_FORBIDDEN);
        }

        // Recherche de l'utilisateur par email
        $user = $userRepository->findOneBy(["email" => $email]);

        // Vérification si l'utilisateur existe et si le mot de passe est correct
if (!$user || !$passwordHasher->isPasswordValid($user, $password)) {
    // Gérer le compteur de tentatives de connexion échouées
    $email = $request->request->get('email');
    $cacheKey = 'login_attempts_' . str_replace(['@', '.'], ['_', '_'], $email);
    $loginAttempts = $cache->getItem($cacheKey)->get() ?? 0;
    $loginAttempts++;
    $cache->getItem($cacheKey)->set($loginAttempts);
    $cache->getItem($cacheKey)->expiresAfter(120); // Bloquer pour 2 minutes après un certain nombre de tentatives

    // Vérifier si le nombre maximal de tentatives est dépassé
    if ($loginAttempts <= 5) {
        return new JsonResponse(['error' => true, 'message' => 'Trop de tentatives de connexion (5 max). Veuillez réessayer ultérieurement - 2 min d\'attente.'], JsonResponse::HTTP_TOO_MANY_REQUESTS);
    } else {
        return new JsonResponse(['error' => true, 'message' => 'Email/mot de passe incorrect(s).'], JsonResponse::HTTP_UNAUTHORIZED);
    }
}    


        // Vérification si le compte de l'utilisateur est actif
        if (!$user->getActive()) {
            return new JsonResponse(['error' => true, 'message' => 'Le compte n\'est plus actif ou est suspendu.'], JsonResponse::HTTP_FORBIDDEN);
        }

        // Génération du token JWT pour l'utilisateur authentifié
        $token = $JWTManager->create($user);

        return new JsonResponse([
            'error' => false,
            'message' => 'L\'utilisateur a été authentifié avec succès',
            'user' => $user->serializer(), 
            'token' => $token
        ]);
    }

    #[Route('/register', name: 'app_create_user', methods: ['POST'])]
    public function createUser(Request $request, UserPasswordHasherInterface $passwordHash): JsonResponse
    {
        $requestData = $request->request->all();
        $sexe = $request->request->get('sexe');
        $tel = $request->request->get('tel');
    
        $requiredFields = ['firstname', 'lastname', 'email', 'password', 'dateBirth'];
    
        foreach ($requiredFields as $field) {
            if (!isset($requestData[$field])) {
                return $this->json([
                    'error' => true,
                    'message' => 'Des champs obligatoires sont manquants.',
                ], JsonResponse::HTTP_BAD_REQUEST);
            }
        }
    
        $email = $requestData['email'];
        $password = $requestData['password'];
        $dateBirth = $requestData['dateBirth'];

        // Validation du format de l'email
        $regex = '/^(?:(?:[a-zA-Z0-9!#$%&\'*+\/=?^_`{|}~.-]+)|(?:\"(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21\x23-\x5b\x5d-\x7f]|\\\\[\x01-\x09\x0b\x0c\x0e-\x7f])*\"))@(?:(?:[a-zA-Z0-9](?:[a-zA-Z0-9-\x{2014}]*[a-zA-Z0-9])?\.)*(?:[a-zA-Z\x{2014}]{2,}|(?:\[(?:(?:(?:25[0-5]|2[0-4][0-9]|1[0-9]{2}|[1-9]?[0-9])\.){3}(?:25[0-5]|2[0-4][0-9]|1[0-9]{2}|[1-9]?[0-9])|(?:IPv6:[a-fA-F0-9:]+))\])))$/iu';
        if (!preg_match($regex, $email)) {
            return $this->json([
                'error' => true,
                'message' => 'Le format de l\'email est invalide.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
    
        // Validation des exigences du mot de passe
        $passwordRequirements = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/';
        if (!preg_match($passwordRequirements, $password)) {
            return $this->json([
                'error' => true,
                'message' => 'Le mot de passe doit contenir au moins une majuscule, une minuscule, un chiffre, un caractère spécial et avoir 8 caractères minimum',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
    
        // Validation du format de la date de naissance
        $dateBirthFormat = DateTimeImmutable::createFromFormat('d/m/Y', $dateBirth);
        if (!$dateBirthFormat) {
            return $this->json([
                'error' => true,
                'message' => 'Le format de la date de naissance est invalide. Le format attendu est JJ/MM/AAAA.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
    
        // Vérification de l'âge minimum
        $age = $dateBirthFormat->diff(new DateTimeImmutable())->y;
        if ($age < 12) {
            return $this->json([
                'error' => true,
                'message' => 'L\'utilisateur doit avoir au moins 12 ans.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Vérification si l'email est déjà utilisé
        $existingUser = $this->repository->findOneBy(['email' => $email]);
        if ($existingUser) {
            return $this->json([
                'error' => true,
                'message' => 'Cet email est déjà utilisé par un autre compte.',
            ], JsonResponse::HTTP_CONFLICT);
        }

        
    // Vérifier si la valeur de sexe est une chaîne de caractères représentant un entier
    /*
    if (!ctype_digit($sexe)) {
        return $this->json([
            'error' => true,
            'message' => 'La valeur du champ sexe est invalide. Les valeurs autorisées sont 0 pour Femme, 1 pour Homme.',
        ], JsonResponse::HTTP_BAD_REQUEST);
    }*/


    // Convertir la valeur de sexe en entier
    $sexe = (int) $sexe;

    // Vérifier si la valeur du champ sexe est un entier valide (0 ou 1)
    if (!in_array($sexe, [0, 1], true)) {
        return $this->json([
            'error' => true,
            'message' => 'La valeur du champ sexe est invalide. Les valeurs autorisées sont 0 pour Femme, 1 pour Homme.',
        ], JsonResponse::HTTP_BAD_REQUEST);
    }


        // Validation du format du numéro de téléphone
        $tel = $requestData['tel'] ?? '';
        if ($tel && !preg_match('/^0[1-9][0-9]{8}$/', $requestData['tel'])) {
            return new JsonResponse(['error' => true, 'message' => 'Le format du numéro de téléphone est invalide.'], JsonResponse::HTTP_BAD_REQUEST);
        }
    
        // Création de l'utilisateur
        $user = new User();
        $hash = $passwordHash->hashPassword($user, $password);
        $user->setFirstname($requestData['firstname'])
            ->setLastname($requestData['lastname'])
            ->setEmail($email)
            ->setPassword($hash)
            ->setDateBirth($dateBirthFormat)
            ->setSexe($sexe)
            ->setTel($tel);

        // Sauvegarde de l'utilisateur en base de données
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    
        return $this->json([
            'error' => false,
            'message' => 'L\'utilisateur a bien été créé avec succès.',
            'user' => $user->serializer(),
        ], JsonResponse::HTTP_CREATED);
    }    
}

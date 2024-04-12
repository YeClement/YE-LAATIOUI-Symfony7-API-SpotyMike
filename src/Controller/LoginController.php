<?php

namespace App\Controller;

use App\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class LoginController extends AbstractController
{
    private $entityManager;
    private $repository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager =  $entityManager;
        $this->repository = $entityManager->getRepository(User::class);
    }

    #[Route('/', name: 'app_index', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $content = file_get_contents(__DIR__ . 'public\index.php');

        return new JsonResponse($content);
    }

    #[Route('/login', name: 'app_login_post', methods: ['POST'])]
    public function login(Request $request, JWTTokenManagerInterface $JWTManager, UserPasswordHasherInterface $passwordHash): JsonResponse
    {
        $email = $request->request->get('email');
        $password = $request->request->get('password');
    
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json([
                'error' => true,
                'message' => 'Le format de l\'email est invalide'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
    
        $passwordRequirements = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/';
        if (!preg_match($passwordRequirements, $password)) {
            return $this->json([
                'error' => true,
                'message' => 'Le mot de passe doit contenir au moins une majuscule, une minuscule, un chiffre, un caractère spécial et avoir 8 caractères minimum'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
    
        $user = $this->repository->findOneBy(["email" => $email]);
    
        if (!$user || !$passwordHash->isPasswordValid($user, $password)) {
            return $this->json([
                'error' => true,
                'message' => 'Email/password incorrect'
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }
    
        if (!$user->isActive() || $user->isSuspended()) {
            return $this->json([
                'error' => true,
                'message' => 'Le compte n\'est plus actif ou est suspendu'
            ], JsonResponse::HTTP_FORBIDDEN);
        }
    
            //rate limiting à faire
    
        $token = $JWTManager->create($user);

        return $this->json([
            'error' => true,
            'message' => 'L\'utilisateur a été authentifié avec succès',
            'user' => $user->serializer(),
            'token' => $token
        ]);
    }
    
    #[Route('/register', name: 'app_create_user', methods: ['POST'])]
    public function createUser(Request $request, UserPasswordHasherInterface $passwordHash): JsonResponse
    {
        $requestData = $request->request->all();
    
        $requiredFields = ['firstname', 'lastname', 'email', 'password', 'dateBirth'];
    
        foreach ($requiredFields as $field) {
            if (!isset($requestData[$field])) {
                return $this->json([
                    'error' => true,
                    'message' => 'Des champs obligatoires sont manquants',
                ], JsonResponse::HTTP_BAD_REQUEST);
            }
        }
    
        $email = $requestData['email'];
        $password = $requestData['password'];
        $dateBirth = $requestData['dateBirth'];
    
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json([
                'error' => true,
                'message' => 'Le format de l\'email est invalide',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
    
        $passwordRequirements = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/';
        if (!preg_match($passwordRequirements, $password)) {
            return $this->json([
                'error' => true,
                'message' => 'Le mot de passe doit contenir au moins une majuscule, une minuscule, un chiffre, un caractère spécial et avoir 8 caractères minimum',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
    
        $dateBirthFormat = DateTimeImmutable::createFromFormat('d/m/Y', $dateBirth);
        if (!$dateBirthFormat) {
            return $this->json([
                'error' => true,
                'message' => 'Le format de la date de naissance est invalide. Le format attendu est JJ/MM/AAAA',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
    
        $age = $dateBirthFormat->diff(new DateTimeImmutable())->y;
        if ($age < 12) {
            return $this->json([
                'error' => true,
                'message' => 'L\'utilisateur doit avoir au moins 12 ans.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $existingUser = $this->repository->findOneBy(['email' => $email]);
        if ($existingUser) {
            return $this->json([
                'error' => true,
                'message' => 'Cet email est déjà utilisé par un autre compte',
            ], JsonResponse::HTTP_CONFLICT);
        }
    
        $user = new User();
        $hash = $passwordHash->hashPassword($user, $password);
        $user->setFirstname($requestData['firstname'])
            ->setLastname($requestData['lastname'])
            ->setEmail($email)
            ->setPassword($hash)
            ->setDateBirth($dateBirthFormat);
    
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    
        return $this->json([
            'error' => false,
            'message' => 'L\'utilisateur a bien été créé avec succès.',
            'user' => $user->serializer(),
        ], JsonResponse::HTTP_CREATED);
    }    
}

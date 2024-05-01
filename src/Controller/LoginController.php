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

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(Request $request, UserPasswordHasherInterface $passwordHasher, JWTTokenManagerInterface $JWTManager, UserRepository $userRepository): JsonResponse
    {
        $email = $request->request->get('email');
        $password = $request->request->get('password');
        $data =$request->request->all();

        $user = $this->repository->findOneBy(["email" => $email]);
       

        // donnée manquante
        if (!isset($data['email']) || !isset($data['password'])) {
            return new JsonResponse(['error' => true, 'message' => 'Email/password manquants.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        
        $regex = '/^(?:(?:[a-zA-Z0-9!#$%&\'*+\/=?^_`{|}~.-]+)|(?:\"(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21\x23-\x5b\x5d-\x7f]|\\\\[\x01-\x09\x0b\x0c\x0e-\x7f])*\"))@(?:(?:[a-zA-Z0-9](?:[a-zA-Z0-9-\x{2014}]*[a-zA-Z0-9])?\.)*(?:[a-zA-Z\x{2014}]{2,}|(?:\[(?:(?:(?:25[0-5]|2[0-4][0-9]|1[0-9]{2}|[1-9]?[0-9])\.){3}(?:25[0-5]|2[0-4][0-9]|1[0-9]{2}|[1-9]?[0-9])|(?:IPv6:[a-fA-F0-9:]+))\])))$/iu';
        if (!preg_match($regex, $email)) {
            return new JsonResponse(['error' => true, 'message' => 'Le format de l\'email est invalide.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $passwordRequirements = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z\d]).{8,}$/';
        if (!preg_match($passwordRequirements, $data['password'])) {
            return new JsonResponse(['error' => true, 'message' => 'Le mot de passe doit contenir au moins une majuscule, une minuscule, un chiffre, un caractère spécial et avoir 8 caractères minimum.'], JsonResponse::HTTP_FORBIDDEN);
        }
       
       
        if (!$passwordHasher->isPasswordValid($user, $data['password'])) {
            return new JsonResponse(['error' =>true, 'message' => 'Le mot de passe incorrect.'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        if (!$user->getActive()) {
            return new JsonResponse(['error' => true, 'message' => 'Le compte n\'est plus actif ou est suspendu.'], JsonResponse::HTTP_FORBIDDEN);
        }
        $token = $JWTManager->create($user);
        return $this->json([
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
        //$sexe = $requestData['sexe'];
        //$tel = $requestData['tel'];
        
        $regex = '/^(?:(?:[a-zA-Z0-9!#$%&\'*+\/=?^_`{|}~.-]+)|(?:\"(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21\x23-\x5b\x5d-\x7f]|\\\\[\x01-\x09\x0b\x0c\x0e-\x7f])*\"))@(?:(?:[a-zA-Z0-9](?:[a-zA-Z0-9-\x{2014}]*[a-zA-Z0-9])?\.)*(?:[a-zA-Z\x{2014}]{2,}|(?:\[(?:(?:(?:25[0-5]|2[0-4][0-9]|1[0-9]{2}|[1-9]?[0-9])\.){3}(?:25[0-5]|2[0-4][0-9]|1[0-9]{2}|[1-9]?[0-9])|(?:IPv6:[a-fA-F0-9:]+))\])))$/iu';
        if (!preg_match($regex, $email)) {
            return $this->json([
                'error' => true,
                'message' => 'Le format de l\'email est invalide.',
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
                'message' => 'Le format de la date de naissance est invalide. Le format attendu est JJ/MM/AAAA.',
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
                'message' => 'Cet email est déjà utilisé par un autre compte.',
            ], JsonResponse::HTTP_CONFLICT);
        }
        //Convertir le type en int pour permettre des comparaisons avec d'autres valeurs int
        $sexe = $data['sexe'] ?? '';
        if ($sexe !== null && $sexe !== '0' && $sexe !== '1')  {
            return $this->json([
                'error' => true,
                'message' => 'La valeur du champ sexe est invalide. Les valeurs autorisées sont 0 pour Femme, 1 pour Homme.'
            ], JsonResponse::HTTP_BAD_REQUEST);
     
        }

        $tel = $requestData['tel'] ?? '';
        if ($tel && !preg_match('/^0[1-9][0-9]{8}$/', $requestData['tel'])) {
            return new JsonResponse(['error' => true, 'message' => 'Le format du numéro de téléphone est invalide.'], JsonResponse::HTTP_BAD_REQUEST);
        }
    
        $user = new User();
        $hash = $passwordHash->hashPassword($user, $password);
        $user->setFirstname($requestData['firstname'])
            ->setLastname($requestData['lastname'])
            ->setEmail($email)
            ->setPassword($hash)
            ->setDateBirth($dateBirthFormat)
            ->setSexe($sexe)
            ->setTel($tel);


    
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    
        return $this->json([
            'error' => false,
            'message' => 'L\'utilisateur a bien été créé avec succès.',
            'user' => $user->serializer(),
        ], JsonResponse::HTTP_CREATED);
    }    
}

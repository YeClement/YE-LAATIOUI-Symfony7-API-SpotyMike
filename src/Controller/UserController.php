<?php

namespace App\Controller;

use App\Entity\User;
use DateTimeImmutable;
use App\Service\TokenVerifierService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class UserController extends AbstractController
{
    private $entityManager;
    private $tokenVerifierService;

    public function __construct(EntityManagerInterface $entityManager, TokenVerifierService $tokenVerifierService)
    {
        $this->entityManager = $entityManager;
        $this->tokenVerifierService = $tokenVerifierService;
    }

    private function validateSexe(?string $sexe): bool
    {
        // Allow the sexe field to be an empty string
        if ($sexe === '') {
            return true;
        }

        // Check if the non-empty sexe is strictly '0' or '1'
        return in_array($sexe, ['0', '1'], true);
    }

    #[Route('/user', name: 'user_post', methods: ['POST'])]
    public function create(Request $request, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        // Vérification de l'authentification
        if (!$this->getUser()) {
            return $this->json([
                'error' => true,
                'message' => 'Authentification requise. Vous devez être connecté pour effectuer cette action.'
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

       

 
        $data = $request->request->all();

        // Vérification si aucun champ n'est fourni
        if (empty($data)) {
            return $this->json([
                'error' => true,
                'message' => 'Les données fournies sont invalides ou incomplètes.'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Vérification si au moins un champ obligatoire est fourni
        $mandatoryFields = ['firstname', 'lastname', 'tel', 'sexe'];
        $providedMandatoryField = false;
        foreach ($mandatoryFields as $field) {
            if (isset($data[$field])) {
                $providedMandatoryField = true;
                break;
            }
        }

 
        if (!$providedMandatoryField) {
            return $this->json([
                'error' => true,
                'message' => 'Au moins l\'un des champs "firstname", "lastname", "tel" ou "sexe" est obligatoire.'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
 
        // Vérification des champs valides
        $validKeys = ['firstname', 'lastname', 'tel', 'sexe'];
        foreach ($data as $key => $value) {
            if (!in_array($key, $validKeys)) {
                return $this->json([
                    'error' => true,
                    'message' => 'Les données fournies sont invalides ou incomplètes.'
                ], JsonResponse::HTTP_BAD_REQUEST);
            }
        }
 
        // Vérification du format du numéro de téléphone
        if (isset($data['tel']) && !preg_match("/^[0-9]{10}$/", $data['tel'])) {
            return $this->json([
                'error' => true,
                'message' => 'Le format du numéro de téléphone est invalide.'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
 
        // Vérification de la valeur du champ sexe
       /* $sexe = $request->request->get('sexe');
        $sexe = (int) $sexe;
    /*if (!in_array($sexe, [0, 1], true)) {
        return $this->json([
            'error' => true,
            'message' => 'La valeur du champ sexe est invalide. Les valeurs autorisées sont 0 pour Femme, 1 pour Homme.',
        ], JsonResponse::HTTP_BAD_REQUEST);
    } if (!ctype_digit($sexe) ) {
        return $this->json([
            'error' => true,
            'message' => 'La valeur du champ sexe est invalide. Les valeurs autorisées sont 0 pour Femme, 1 pour Homme.',
        ], JsonResponse::HTTP_BAD_REQUEST);
    }*/

    // Retrieve sexe and perform validation
    $sexe = $request->request->get('sexe', '');
    if (!$this->validateSexe($sexe)) {
        return $this->json([
            'error' => true,
            'message' => 'La valeur du champ sexe est invalide. Les valeurs autorisées sont 0 pour Femme, 1 pour Homme.'
        ], JsonResponse::HTTP_BAD_REQUEST);
    }
 
        // Vérification de l'existence d'un utilisateur avec le même numéro de téléphone
        if (isset($data['tel'])) {
            $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['tel' => $data['tel']]);
            if ($existingUser) {
                return $this->json([
                    'error' => true,
                    'message' => 'Conflit de données. Le numéro de téléphone est déjà utilisé par un autre utilisateur.'
                ], JsonResponse::HTTP_CONFLICT);
        }
    }
 
        // Vérification de la longueur du prénom
        if (isset($data['firstname']) && (strlen($data['firstname']) < 2 || strlen($data['firstname']) > 50)) {
            return $this->json([
                'error' => true,
                'message' => 'Erreur de validation des données.'
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }
 
        // Vérification de la longueur du nom de famille
        if (isset($data['lastname']) && (strlen($data['lastname']) < 2 || strlen($data['lastname']) > 50)) {
            return $this->json([
                'error' => true,
                'message' => 'Erreur de validation des données. Le nom de famille est trop court ou trop long.'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
 
        $user = new User();
 
        // Définition des champs optionnels et fournis
        $user->setFirstname($data['firstname'] ?? '');
        $user->setLastname($data['lastname'] ?? '');
        $user->setTel($data['tel'] ?? '');
        $user->setSexe((int)$sexe);
 
        // Persistance et enregistrement des données utilisateur
        $this->entityManager->persist($user);
        $this->entityManager->flush();
 
        return $this->json([
            'error' => false,
            'message' => 'Votre inscription a bien été prise en compte'
        ], JsonResponse::HTTP_OK);
    }
 



    #[Route('/user/{id}', name: 'user_update', methods: ['PUT'])]
    public function update(Request $request, UserPasswordHasherInterface $passwordHasher, int $id): JsonResponse
    {
        $user = $this->entityManager->getRepository(User::class)->find($id);

        if (!$user) {
            return $this->json(['message' => 'User not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['firstname'])) {
            $user->setFirstname($data['firstname']);
        }
        if (isset($data['lastname'])) {
            $user->setLastname($data['lastname']);
        }
        if (isset($data['email'])) {
            $user->setEmail($data['email']);
        }
        if (isset($data['tel'])) {
            $user->setTel($data['tel']);
        }
        if (isset($data['sexe'])) {
            $user->setSexe((bool) $data['sexe']);
        }
        if (isset($data['dateBirth'])) {
            $user->setDateBirth(new DateTimeImmutable($data['dateBirth']));
        }
        if (isset($data['password'])) {
            $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);
        }

        $this->entityManager->flush();

        return $this->json(['message' => 'User updated successfully'], JsonResponse::HTTP_OK);
    }

    #[Route('/user/{id}', name: 'user_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $user = $this->entityManager->getRepository(User::class)->find($id);

        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'User deleted successfully'], JsonResponse::HTTP_OK);
    }

    #[Route('/user', name: 'user_get_all', methods: ['GET'])]
    public function readAll(): JsonResponse
    {
        $users = $this->entityManager->getRepository(User::class)->findAll();
        $data = array_map(function ($user) {
            return $user->serializer();
        }, $users);

        return new JsonResponse(['data' => $data, 'message' => 'Successful'], JsonResponse::HTTP_OK);
    }

    #[Route('/user-info', name: 'get_user_info_from_token', methods: ['GET'])]
    public function getUserInfoFromToken(Request $request): JsonResponse
    {
        $user = $this->tokenVerifierService->getUserFromRequest($request);

        if (!$user) {
            return $this->json(['message' => 'User not found or token invalid'], 404);
        }

        return $this->json($user->serializer());
    }

    #[Route('/password-lost', name: 'password_lost', methods: ['POST'])]
    public function passwordLost(Request $request , JWTTokenManagerInterface $JWTManager, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $requestData = $request->request->all();
        $password = $request->request->get('password');

        if (!isset($requestData['email'])) {
            return $this->json([
                'error' => true,
                'message' => 'Email manquant. Veuillez fournir votre email pour la récupération du mot de passe.'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $email = $requestData['email'];
        $regex = '/^(?:(?:[a-zA-Z0-9!#$%&\'*+\/=?^_`{|}~.-]+)|(?:\"(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21\x23-\x5b\x5d-\x7f]|\\\\[\x01-\x09\x0b\x0c\x0e-\x7f])*\"))@(?:(?:[a-zA-Z0-9](?:[a-zA-Z0-9-\x{2014}]*[a-zA-Z0-9])?\.)*(?:[a-zA-Z\x{2014}]{2,}|(?:\[(?:(?:(?:25[0-5]|2[0-4][0-9]|1[0-9]{2}|[1-9]?[0-9])\.){3}(?:25[0-5]|2[0-4][0-9]|1[0-9]{2}|[1-9]?[0-9])|(?:IPv6:[a-fA-F0-9:]+))\])))$/iu';
        if (!preg_match($regex, $email)) {
            return $this->json([
                'error' => true,
                'message' => 'Le format de l\'email est invalide. Veuillez entrer un email valide.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user) {
            return $this->json([
                'error' => true,
                'message' => 'Aucun compte n\'est associé à cet email. Veuillez vérifier et réessayer.'
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        // Rate limite à faire
   
        $token = $JWTManager->create($user);
        return $this->json([
            'success' => true,
            'token' => $token,
            'message' => 'Un email de réinitialisation de mot de passe a été envoyé à votre adresse email. Veuillez suivre les instructions contenues dans l\'email pour réinitialiser votre mot de passe.'
            
        ], JsonResponse::HTTP_OK);
    }


    #[Route('/reset-password/{token}', name: 'reset_password', methods: ['POST', 'GET'])]

    public function resetPassword(Request $request, UserPasswordHasherInterface $passwordHasher, string $token): JsonResponse
    {
        $requestData = $request->request->all();

        if (!$token) {
            return $this->json([
                'error' => true,
                'message' => 'Token de réinitialisation manquant ou invalide. Veuillez utiliser le lien fourni dans l\'email de réinitialisation de mot de passe.'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        if (!isset($requestData['password'])) {
            return $this->json([
                'error' => true,
                'message' => 'Veuillez fournir un nouveau mot de passe.'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $newPassword = $requestData['password'];

        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z\d]).{8,}$/', $newPassword)) {
            return $this->json([
                'error' => true,
                'message' => 'Le nouveau mot de passe ne respecte pas les critères requis. Il doit contenir au moins une majuscule, une minuscule, un chiffre, un caractère spécial et être composé d\'au moins 8 caractères.'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter avec votre nouveau mot de passe.'
        ], JsonResponse::HTTP_OK);
    }
 

#[Route('/account-deactivation', name: 'account_deactivation', methods: ['DELETE'])]
public function deactivateAccount(Request $request , EntityManagerInterface $entityManager): JsonResponse
{
    $user = $this->getUser();
    if (!$user) {
        return $this->json([
            'error' => true,
            'message' => 'Authentification requise. Vous devez être connecté pour effectuer cette action.'
        ], JsonResponse::HTTP_UNAUTHORIZED);
    }

    if (!$user->getActive()) {
        return $this->json([
            'error' => true,
            'message' => 'Le compte est déjà désactivé.'
        ], JsonResponse::HTTP_CONFLICT);
    }

    $user->setActive(false);
    $entityManager->persist($user);
    $entityManager->flush();

    return $this->json([
        'success' => true,
        'message' => 'Votre compte a été désactivé avec succès. Nous sommes désolés de vous voir partir.'
    ], JsonResponse::HTTP_OK);
}
}
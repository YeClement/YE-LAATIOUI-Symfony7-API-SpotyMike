<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use App\Repository\UserRepository;


class LoginController extends AbstractController
{
    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(Request $request, UserPasswordHasherInterface $passwordHasher, JWTTokenManagerInterface $JWTManager, UserRepository $userRepository): JsonResponse
    {
        // Decode the JSON from the request
        $data = json_decode($request->getContent(), true);

        // Validate required fields
        if (!isset($data['email'])) {
            return new JsonResponse(['error' => 'Email and password fields are required'], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Attempt to find the user by email
        $user = $userRepository->findOneBy(['email' => $data['email']]);

        // User not found
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        /* Password validation
        if (!$passwordHasher->isPasswordValid($user, $data['password'])) {
            return new JsonResponse(['error' => 'Invalid credentials'], JsonResponse::HTTP_UNAUTHORIZED);
        }*/

        // Generate and return the JWT
        $token = $JWTManager->create($user);
        return new JsonResponse(['token' => $token]);
    }
}

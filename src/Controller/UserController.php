<?php

namespace App\Controller;

use App\Entity\User;
use DateTimeImmutable;
use App\Service\TokenVerifierService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserController extends AbstractController
{
    private $entityManager;
    private $tokenVerifierService;

    public function __construct(EntityManagerInterface $entityManager, TokenVerifierService $tokenVerifierService)
    {
        $this->entityManager = $entityManager;
        $this->tokenVerifierService = $tokenVerifierService;
    }

    #[Route('/user', name: 'user_post', methods: ['POST'])]
    public function create(Request $request, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['firstname'], $data['lastname'], $data['email'], $data['tel'], $data['sexe'], $data['dateBirth'])) {
            return $this->json(['message' => 'Missing required fields.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $user = new User();
        $user->setFirstname($data['firstname']);
        $user->setLastname($data['lastname']);
        $user->setEmail($data['email']);
        $user->setTel($data['tel']);
        $user->setDateBirth(new DateTimeImmutable($data['dateBirth']));
        $user->setCreatedAt(new DateTimeImmutable());
        $user->setUpdatedAt(new DateTimeImmutable());

        if (!empty($data['password'])) {
            $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);
        } 

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->json(['message' => 'User created successfully'], JsonResponse::HTTP_CREATED);
    }

    #[Route('/user/{id}', name: 'user_update', methods: ['PUT'])]
    public function update(Request $request, UserPasswordHasherInterface $passwordHasher, int $id): JsonResponse
    {
        $user = $this->entityManager->getRepository(User::class)->find($id);

        if (!$user) {
            return $this->json(['message' => 'User not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        // Update fields as necessary
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
}

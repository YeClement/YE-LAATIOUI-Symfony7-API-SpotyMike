<?php

namespace App\Controller;

use App\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserController extends AbstractController
{
    private $repository;
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(User::class);
    }

    #[Route('/user', name: 'user_post', methods: 'POST')]
    public function create(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['message' => 'Invalid request data.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $user = new User();
        $user->setName($data['name'] ?? '');
        $user->setEmail($data['email'] ?? '');
        $user->setIdUser($data['idUser'] ?? '');
        if (isset($data['tel']) && preg_match("/^[0-9]{10}$/", $data['tel'])) {
            $user->setTel($data['tel']);
        } else if (isset($data['tel'])) {
            return $this->json(['message' => 'Invalid telephone number .'], JsonResponse::HTTP_BAD_REQUEST);
        }
        $createdAt = isset($data['createAt']['date']) ? new DateTimeImmutable($data['createAt']['date']) : new DateTimeImmutable();
        $user->setCreateAt($createdAt);
        $user->setUpdateAt($createdAt);

        if (!empty($data['password'])) {
            $hash = $passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hash);
        } else {
            $user->setPassword($passwordHasher->hashPassword($user, 'defaultPassword'));
        }

        $entityManager->persist($user);
        $entityManager->flush();

        return $this->json([
            'message' => 'User created successfully',
            
        ], JsonResponse::HTTP_CREATED);
    }

    #[Route('/user/{id}', name: 'user_update', methods: ['PUT'])]
    public function update(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager, int $id): JsonResponse
    {
        $user = $this->repository->find($id);

        if (!$user) {
            return $this->json(['message' => 'User not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) {
            $user->setName($data['name']);
        }
        if (isset($data['email'])) {
            $user->setEmail($data['email']);
        }
        if (isset($data['tel']) && preg_match("/^[0-9]{10}$/", $data['tel'])) {
            $user->setTel($data['tel']);
        } else if (isset($data['tel'])) {
            return $this->json(['message' => 'Invalid telephone number .'], JsonResponse::HTTP_BAD_REQUEST);
        
        }
        if (isset($data['password'])) {
            $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);
        }

        $user->setUpdateAt(new DateTimeImmutable());
        $entityManager->flush();

        return $this->json([
            'message' => 'User updated successfully',
        ], JsonResponse::HTTP_OK);
    }

    #[Route('/user/{id}', name: 'user_delete', methods: 'DELETE')]
    public function delete(int $id): JsonResponse
    {
        $user = $this->repository->find($id);

        if (!$user) {
            return new JsonResponse(['error' => 'user not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'user deleted successfully'], JsonResponse::HTTP_OK);
    }

    #[Route('/user', name: 'user_get_all', methods: 'GET')]
    public function readAll(): JsonResponse
    {
        $result = [];

            $users = $this->repository->findAll();
            if (count($users) > 0) {
                foreach ($users as $user) {
                    array_push($result, $user->serializer());
                }
                return new JsonResponse(['data' => $result,'message' => 'Successful'], JsonResponse::HTTP_OK);
            }
            return new JsonResponse([ 'message' => 'No users found' ], JsonResponse::HTTP_NOT_FOUND);
        
    }
}

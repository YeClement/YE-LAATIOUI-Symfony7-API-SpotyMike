<?php

namespace App\Controller;
use App\Entity\User;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class UserController extends AbstractController
  
{
    private $entityManager;
    private $repository ; 
    

    public function __construct(EntityManagerInterface $entityManager)
    {
     $this->entityManager =  $entityManager ;
     $this->repository =  $entityManager->getRepository(User::class) ;
    }


    #[Route('/user', name: 'app_user', methods: ['GET'])]
    public function index(): JsonResponse
    {
        
        $users = $this->repository->findAll();


        $usersArray = [];
        foreach ($users as $user) {
            $usersArray[] = [
                'id' => $user->getId(),
                'idUser' => $user->getIdUser(),
                'name' => $user->getName(),
                'email' => $user->getEmail(),
                'encrypte' => $user->getEncrypte(),
                'tel' => $user->getTel(),
                'createAt' => $user->getCreateAt() ? $user->getCreateAt()->format('Y-m-d H:i:s') : null,
              'updateAt' => $user->getUpdateAt() ? $user->getUpdateAt()->format('Y-m-d H:i:s') : null,
                
            ];
        }
        return $this->json($usersArray);
    }

    #[Route('/user', name: 'create_user', methods: ['POST'])]
    public function createUser(Request $request, EntityManagerInterface $entityManager): JsonResponse
{
    
    $data = json_decode($request->getContent(), true);

    $existingEmail = $this->repository->findOneBy(['email'=> $data['email']]);
    if ($existingEmail !== null)
    return new JsonResponse([ 'Email has already been used']);
     
    $user = new User();

   
    if (isset($data['idUser'])) {
        $user->setIdUser($data['idUser']);
    }
    if (isset($data['name'])) {
        $user->setName($data['name']);
    }

    if (isset($data['email'])) {
        $user->setEmail($data['email']);
    }
    if (isset($data['encrypte'])) {
        $user->setEncrypte($data['encrypte']);
    }
    
    if (isset($data['tel'])) {
        $user->setTel($data['tel']);
    }

    if (isset($data['createAt'])) {
        $user->setCreateAt(new \DateTimeImmutable($data['createAt']));
    }

    if (isset($data['updateAt'])) {
        $user->setUpdateAt(new \DateTimeImmutable($data['updateAt']));
    }
    
    $entityManager->persist($user);
    $entityManager->flush();

     
    return new JsonResponse([ 'User created successfully']);

    
}
#[Route('/user/{id}', name: 'update_user', methods: ['PUT'])]
public function updateUser(Request $request, EntityManagerInterface $entityManager, int $id): JsonResponse
{
    $data = json_decode($request->getContent(), true);

    
    $userRepository = $entityManager->getRepository(User::class);
    $user = $userRepository->find($id);

    
    if (!$user) {
        return new JsonResponse([ 'User not found']);
    }

    
    if (isset($data['idUser'])) {
        $user->setIdUser($data['idUser']);
    }
    if (isset($data['name'])) {
        $user->setName($data['name']);
    }
    if (isset($data['email'])) {
        $user->setEmail($data['email']);
    }
    if (isset($data['tel'])) {
        $user->setTel($data['tel']);
    }
    if (isset($data['createAt'])) {
        $user->setCreateAt(new \DateTimeImmutable($data['createAt']));
    }
    if (isset($data['updateAt'])) {
        $user->setUpdateAt(new \DateTimeImmutable($data['updateAt']));
    }

    
    $entityManager->flush();

    
    return new JsonResponse(['User updated successfully']);
}



#[Route('/user/{id}', name: 'delete_user', methods: ['DELETE'])]
public function deleteUser(EntityManagerInterface $entityManager, int $id): JsonResponse
{
   
    $userRepository = $entityManager->getRepository(User::class);
    $user = $userRepository->find($id);

   
    if (!$user) {
        return new JsonResponse([ 'User not found']);
    }

    $entityManager->remove($user);
    $entityManager->flush();

    return new JsonResponse([ 'User deleted successfully']);
}

}

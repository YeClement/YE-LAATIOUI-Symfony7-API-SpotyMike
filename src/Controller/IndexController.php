<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class IndexController extends AbstractController
{
    #[Route('/index', name: 'app_index' , methods :['GET'])]
    public function index(): JsonResponse
    {
        $indexExsits = true;

        if($indexExsits){

            return $this->json(['message'=>'welcome to the homepage'],JsonResponse ::HTTP_OK);
        
        }
        else {
            return $this->json(['error'=>'page not found'],JsonResponse ::HTTP_NOT_FOUND);

        }


       
    }
}

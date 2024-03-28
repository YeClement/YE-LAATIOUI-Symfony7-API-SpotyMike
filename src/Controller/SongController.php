<?php

namespace App\Controller;

use App\Entity\Song;
use App\Repository\SongRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SongController extends AbstractController
{
    private $entityManager;
    private $songRepository;

    public function __construct(EntityManagerInterface $entityManager, SongRepository $songRepository)
    {
        $this->entityManager = $entityManager;
        $this->songRepository = $songRepository;
    }

    #[Route('/songs', name: 'list_songs', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $songs = $this->songRepository->findAll();

        $songsArray = [];
        foreach ($songs as $song) {
            $songsArray[] = [
                'id' => $song->getId(),
                'idSong' => $song->getIdSong(),
                'title' => $song->getTitle(),
                'url' => $song->getUrl(),
                'cover' => $song->getCover(),
                'visibility' => $song->isVisibility(),
                'createAt' => $song->getCreateAt() ? $song->getCreateAt()->format('Y-m-d H:i:s') : null,
              
            ];
        }

        return $this->json($songsArray);
    }

    #[Route('/songs', name: 'create_song', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $existingSong = $this->songRepository->findOneBy(['idSong' => $data['idSong']]);
        if ($existingSong !== null) {
            return new JsonResponse(['idSong has already been used']);
        }

        $song = new Song();
        $song->setIdSong($data['idSong']);
        $song->setTitle($data['title']);
        $song->setUrl($data['url']);
        $song->setCover($data['cover']);
        $song->setVisibility($data['visibility']);
        $song->setCreateAt(new \DateTimeImmutable($data['createAt']));

        $this->entityManager->persist($song);
        $this->entityManager->flush();

        return $this->json(['Song created successfully']);
    }

    #[Route('/songs/{id}', name: 'update_song', methods: ['PUT'])]
    public function update(Request $request, int $id): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $song = $this->songRepository->find($id);

        if (!$song) {
            return $this->json([ 'Song not found']);
        }

        $song->setTitle($data['title']);
        $song->setUrl($data['url']);
        $song->setCover($data['cover']);
        $song->setVisibility($data['visibility']);
       

        $this->entityManager->flush();

        return $this->json([ 'Song updated successfully']);
    }

    #[Route('/songs/{id}', name: 'delete_song', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $song = $this->songRepository->find($id);

        if (!$song) {
            return $this->json([ 'Song not found']);
        }

        $this->entityManager->remove($song);
        $this->entityManager->flush();

        return $this->json([ 'Song deleted successfully']);
    }
}

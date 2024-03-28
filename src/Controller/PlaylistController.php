<?php

namespace App\Controller;

use App\Entity\Playlist;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class PlaylistController extends AbstractController
{
    private $entityManager;
    private $repository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(Playlist::class);
    }

    #[Route('/playlist', name: 'app_playlist_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $playlists = $this->repository->findAll();

        $playlistsArray = [];
        foreach ($playlists as $playlist) {
            $playlistsArray[] = [
                'id' => $playlist->getId(),
                'idPlaylist' => $playlist->getIdPlaylist(),
                'title' => $playlist->getTitle(),
                'public' => $playlist->isPublic(),
                'createAt' => $playlist->getCreateAt() ? $playlist->getCreateAt()->format('Y-m-d H:i:s') : null,
                'updateAt' => $playlist->getUpdateAt() ? $playlist->getUpdateAt()->format('Y-m-d H:i:s') : null,
            ];
        }

        return $this->json($playlistsArray);
    }

    #[Route('/playlist', name: 'app_playlist_create', methods: ['POST'])]
    public function createPlaylist(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $playlist = new Playlist();

        if (isset($data['idPlaylist'])) {
            $playlist->setIdPlaylist($data['idPlaylist']);
        }
        if (isset($data['title'])) {
            $playlist->setTitle($data['title']);
        }
        if (isset($data['public'])) {
            $playlist->setPublic($data['public']);
        }
        if (isset($data['createAt'])) {
            $playlist->setCreateAt(new \DateTimeImmutable($data['createAt']));
        }
        if (isset($data['updateAt'])) {
            $playlist->setUpdateAt(new \DateTimeImmutable($data['updateAt']));
        }

        $this->entityManager->persist($playlist);
        $this->entityManager->flush();

        return new JsonResponse(['Playlist created successfully']);
    }

    #[Route('/playlist/{id}', name: 'app_playlist_update', methods: ['PUT'])]
    public function updatePlaylist(Request $request, int $id): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $playlist = $this->repository->find($id);

        if (!$playlist) {
            return new JsonResponse(['Playlist not found']);
        }

        if (isset($data['idPlaylist'])) {
            $playlist->setIdPlaylist($data['idPlaylist']);
        }
        if (isset($data['title'])) {
            $playlist->setTitle($data['title']);
        }
        if (isset($data['public'])) {
            $playlist->setPublic($data['public']);
        }
        if (isset($data['createAt'])) {
            $playlist->setCreateAt(new \DateTimeImmutable($data['createAt']));
        }
        if (isset($data['updateAt'])) {
            $playlist->setUpdateAt(new \DateTimeImmutable($data['updateAt']));
        }

        $this->entityManager->flush();

        return new JsonResponse(['Playlist updated successfully']);
    }

    #[Route('/playlist/{id}', name: 'app_playlist_delete', methods: ['DELETE'])]
    public function deletePlaylist(int $id): JsonResponse
    {
        $playlist = $this->repository->find($id);

        if (!$playlist) {
            return new JsonResponse([ 'Playlist not found']);
        }

        $this->entityManager->remove($playlist);
        $this->entityManager->flush();

        return new JsonResponse(['Playlist deleted successfully']);
    }
}

<?php

namespace App\Controller;

use App\Entity\Message;
use App\Entity\Room;
use App\Repository\RoomRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class ChatController extends AbstractController
{
    private const SESSION_USERNAME_KEY = 'chat_username';

    public function __construct(
        private HubInterface $hub,
    ) {
    }

    #[Route('/', name: 'app_home')]
    public function home(SessionInterface $session): Response
    {
        // Redirect to login if not authenticated, otherwise to rooms list
        if (!$session->get(self::SESSION_USERNAME_KEY)) {
            return $this->redirectToRoute('app_login');
        }

        return $this->redirectToRoute('app_rooms');
    }

    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(Request $request, SessionInterface $session): Response
    {
        if ($session->get(self::SESSION_USERNAME_KEY)) {
            return $this->redirectToRoute('app_rooms');
        }

        if ($request->isMethod('POST')) {
            $username = trim($request->request->get('username', ''));

            if (strlen($username) < 2 || strlen($username) > 50) {
                $this->addFlash('error', 'Le pseudo doit contenir entre 2 et 50 caractères.');
                return $this->render('chat/login.html.twig');
            }

            $session->set(self::SESSION_USERNAME_KEY, $username);

            return $this->redirectToRoute('app_rooms');
        }

        return $this->render('chat/login.html.twig');
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(SessionInterface $session): Response
    {
        $session->remove(self::SESSION_USERNAME_KEY);

        return $this->redirectToRoute('app_login');
    }

    #[Route('/rooms', name: 'app_rooms')]
    public function rooms(RoomRepository $roomRepository, SessionInterface $session): Response
    {
        $this->ensureAuthenticated($session);

        $rooms = $roomRepository->findAllOrdered();

        return $this->render('chat/rooms.html.twig', [
            'rooms' => $rooms,
            'username' => $session->get(self::SESSION_USERNAME_KEY),
        ]);
    }

    #[Route('/rooms/new', name: 'app_room_new', methods: ['GET', 'POST'])]
    public function newRoom(
        Request $request,
        SessionInterface $session,
        EntityManagerInterface $entityManager,
    ): Response {
        $this->ensureAuthenticated($session);

        if ($request->isMethod('POST')) {
            $roomName = trim($request->request->get('name', ''));

            if (strlen($roomName) < 2 || strlen($roomName) > 100) {
                $this->addFlash('error', 'Le nom de la salle doit contenir entre 2 et 100 caractères.');
                return $this->render('chat/new_room.html.twig');
            }

            $room = new Room();
            $room->setName($roomName);

            $entityManager->persist($room);
            $entityManager->flush();

            $this->addFlash('success', 'Salle créée avec succès !');

            return $this->redirectToRoute('app_room_show', ['slug' => $room->getSlug()]);
        }

        return $this->render('chat/new_room.html.twig');
    }

    #[Route('/room/{slug}', name: 'app_room_show', methods: ['GET'])]
    public function showRoom(
        string $slug,
        RoomRepository $roomRepository,
        SessionInterface $session,
    ): Response {
        $this->ensureAuthenticated($session);

        $room = $roomRepository->findOneBy(['slug' => $slug]);

        if (!$room) {
            throw $this->createNotFoundException('Salle non trouvée');
        }

        return $this->render('chat/room.html.twig', [
            'room' => $room,
            'messages' => $room->getMessages(),
            'username' => $session->get(self::SESSION_USERNAME_KEY),
            'mercure_topic' => '/room/' . $room->getSlug(),
        ]);
    }

    #[Route('/room/{slug}/message', name: 'app_room_message', methods: ['POST'])]
    public function postMessage(
        string $slug,
        Request $request,
        RoomRepository $roomRepository,
        SessionInterface $session,
        EntityManagerInterface $entityManager,
    ): Response {
        $this->ensureAuthenticated($session);

        $room = $roomRepository->findOneBy(['slug' => $slug]);

        if (!$room) {
            throw $this->createNotFoundException('Salle non trouvée');
        }

        $content = trim($request->request->get('content', ''));

        if (empty($content)) {
            return $this->redirectToRoute('app_room_show', ['slug' => $slug]);
        }

        $message = new Message();
        $message->setRoom($room);
        $message->setAuthorName($session->get(self::SESSION_USERNAME_KEY));
        $message->setContent($content);

        $entityManager->persist($message);
        $entityManager->flush();

        // Publish to Mercure for real-time updates
        $update = new Update(
            '/room/' . $room->getSlug(),
            $this->renderView('chat/message_stream.html.twig', [
                'message' => $message,
            ]),
            false, // public update - no JWT required
        );

        $this->hub->publish($update);

        // Redirect back to room (Turbo Drive will handle it smoothly)
        return $this->redirectToRoute('app_room_show', ['slug' => $slug]);
    }

    private function ensureAuthenticated(SessionInterface $session): void
    {
        if (!$session->get(self::SESSION_USERNAME_KEY)) {
            throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException('Vous devez être connecté.');
        }
    }
}

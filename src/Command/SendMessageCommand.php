<?php

namespace App\Command;

use App\Entity\Message;
use App\Entity\Room;
use App\Repository\RoomRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Twig\Environment;

#[AsCommand(
    name: 'app:send-message',
    description: 'Send a test message to a room to test Mercure dispatch',
)]
class SendMessageCommand extends Command
{
    public function __construct(
        private RoomRepository $roomRepository,
        private EntityManagerInterface $entityManager,
        private HubInterface $hub,
        private Environment $twig,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('room-slug', InputArgument::REQUIRED, 'The room slug')
            ->addArgument('content', InputArgument::REQUIRED, 'The message content')
            ->addArgument('author', InputArgument::OPTIONAL, 'The author name', 'ConsoleBot');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $roomSlug = $input->getArgument('room-slug');
        $content = $input->getArgument('content');
        $author = $input->getArgument('author');

        $room = $this->roomRepository->findOneBy(['slug' => $roomSlug]);

        if (!$room) {
            $io->error("Room with slug '$roomSlug' not found");
            return Command::FAILURE;
        }

        $io->info("Sending message to room: {$room->getName()} (slug: $roomSlug)");
        $io->info("Author: $author");
        $io->info("Content: $content");

        // Create and persist message
        $message = new Message();
        $message->setRoom($room);
        $message->setAuthorName($author);
        $message->setContent($content);

        $this->entityManager->persist($message);
        $this->entityManager->flush();

        $io->success("Message persisted to database with ID: {$message->getId()}");

        // Dispatch to Mercure
        $topic = '/room/' . $room->getSlug();
        $io->info("Dispatching to Mercure topic: $topic");

        try {
            $update = new Update(
                $topic,
                $this->twig->render('chat/message_stream.html.twig', [
                    'message' => $message,
                ]),
                false, // public update
            );

            $this->hub->publish($update);
            $io->success("Message dispatched to Mercure successfully!");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error("Failed to dispatch to Mercure: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

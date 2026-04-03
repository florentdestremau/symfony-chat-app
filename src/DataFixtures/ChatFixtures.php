<?php

namespace App\DataFixtures;

use App\Entity\Message;
use App\Entity\Room;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ChatFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Create rooms
        $generalRoom = new Room();
        $generalRoom->setName('Général');
        $manager->persist($generalRoom);

        $techRoom = new Room();
        $techRoom->setName('Tech & Dev');
        $manager->persist($techRoom);

        $randomRoom = new Room();
        $randomRoom->setName('Random');
        $manager->persist($randomRoom);

        $musicRoom = new Room();
        $musicRoom->setName('Musique');
        $manager->persist($musicRoom);

        // Create some messages in General room
        $message1 = new Message();
        $message1->setRoom($generalRoom);
        $message1->setAuthorName('Alice');
        $message1->setContent('Salut tout le monde ! Bienvenue sur le chat ! 👋');
        $message1->setCreatedAt(new \DateTimeImmutable('-2 hours'));
        $manager->persist($message1);

        $message2 = new Message();
        $message2->setRoom($generalRoom);
        $message2->setAuthorName('Bob');
        $message2->setContent('Hey Alice ! Content d\'être ici !');
        $message2->setCreatedAt(new \DateTimeImmutable('-1 hour 45 minutes'));
        $manager->persist($message2);

        $message3 = new Message();
        $message3->setRoom($generalRoom);
        $message3->setAuthorName('Charlie');
        $message3->setContent('Bonjour à tous ! Comment ça va aujourd\'hui ?');
        $message3->setCreatedAt(new \DateTimeImmutable('-30 minutes'));
        $manager->persist($message3);

        // Create some messages in Tech room
        $message4 = new Message();
        $message4->setRoom($techRoom);
        $message4->setAuthorName('DevMaster');
        $message4->setContent('Quelqu\'un a testé la nouvelle version de Symfony 8 ?');
        $message4->setCreatedAt(new \DateTimeImmutable('-3 hours'));
        $manager->persist($message4);

        $message5 = new Message();
        $message5->setRoom($techRoom);
        $message5->setAuthorName('PHP_Lover');
        $message5->setContent('Oui ! Les nouvelles fonctionnalités sont incroyables 🚀');
        $message5->setCreatedAt(new \DateTimeImmutable('-2 hours 30 minutes'));
        $manager->persist($message5);

        $message6 = new Message();
        $message6->setRoom($techRoom);
        $message6->setAuthorName('DevMaster');
        $message6->setContent('Mercure avec Turbo Streams fonctionne vraiment bien !');
        $message6->setCreatedAt(new \DateTimeImmutable('-1 hour'));
        $manager->persist($message6);

        // Create a message in Random room
        $message7 = new Message();
        $message7->setRoom($randomRoom);
        $message7->setAuthorName('FunnyGuy');
        $message7->setContent('Quelle est la différence entre un développeur et un informaticien ? 🤔');
        $message7->setCreatedAt(new \DateTimeImmutable('-5 hours'));
        $manager->persist($message7);

        $message8 = new Message();
        $message8->setRoom($randomRoom);
        $message8->setAuthorName('JokeMaster');
        $message8->setContent('Je donne ma langue au chat ! Dites-nous ! 😄');
        $message8->setCreatedAt(new \DateTimeImmutable('-4 hours 45 minutes'));
        $manager->persist($message8);

        // Create a message in Music room
        $message9 = new Message();
        $message9->setRoom($musicRoom);
        $message9->setAuthorName('MusicFan');
        $message9->setContent('Vous écoutez quoi en ce moment ?');
        $message9->setCreatedAt(new \DateTimeImmutable('-6 hours'));
        $manager->persist($message9);

        $manager->flush();
    }
}

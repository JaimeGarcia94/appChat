<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Mercure\PublisherInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Conversation;
use App\Repository\MessageRepository;
use App\Entity\Message;
use App\Entity\User;

#[Route('/messages', name: 'messages.')]
class MessageController extends AbstractController
{
    const ATTRIBUTES_TO_SERIALIZE = ['id', 'content', 'createdAt', 'mine']; 
    private $em;

    public function __construct(EntityManagerInterface $em, PublisherInterface $publisher)
    {
        $this->em = $em;
        $this->publisher = $publisher;
    }

    #[Route('/{id}', name: 'getMessages', methods: ['GET'])]
    public function index(Request $request, Conversation $conversation): Response
    {

        $this->denyAccessUnlessGranted('view', $conversation);

        $messages = $this->em->getRepository(Message::class)->findMessageByConversationId(
            $conversation->getId()
        );

        array_map(function($message){
            $message->setMine(
                $message->getUser()->getId() === $this->getUser()->getId() ? true : false
            );
        }, $messages);

        return $this->json($messages, Response::HTTP_OK, [], [
            'attributes' => self::ATTRIBUTES_TO_SERIALIZE
        ]);
    }

    #[Route('/{id}', name: 'newMessage', methods: ['POST'])]
    public function newMessage(Request $request, Conversation $conversation, SerializerInterface $serializer): Response
    {
        $user = $this->getUser();
        // $user = $this->em->getRepository(User::class)->findOneBy(['id' => 7]);

        $recipient = $this->em->getRepository(Participant::class)->findParticipantByConversationIdAndUserId(
            $conversation->getId(),
            $user->getId()
        );
        
        $content = $request->get('content', null);

        $message = new Message();
        $message->setContent($content);
        $message->setUser($user);

        $conversation->addMessage($message);
        $conversation->setLastMessage($message);

        $this->em->getConnection()->beginTransaction();

        try{
            $this->em->persist($message);
            $this->em->persist($conversation);

            $this->em->flush();
            $this->em->commit();

        } catch (\Exception $e) {
            $this->em->rollback();
            throw $e;
        }
        $message->setMine(false);
        $messageSerialized = $serializer->serialize($message, 'json', [
            'attributes' => ['id', 'content', 'createdAt', 'mine', 'conversation' => ['id']]
        ]);

        $update = new Update(
            [
                sprintf("/%s", $conversation->getId()),
                sprintf("/%s", $recipient->getUser()->getUsername()),
            ],
            $messageSerialized,
            [
                [sprintf("/%s", $recipient->getUser()->getUsername())]
            ]
        );

        $this->publisher->__invoke($update);

        $message->setMine(true);
        
        return $this->json($message, Response::HTTP_CREATED, [], [
            'attributes' => self::ATTRIBUTES_TO_SERIALIZE
        ]);
    
    }
}

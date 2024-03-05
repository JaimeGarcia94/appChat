<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use App\Entity\Conversation;
use App\Entity\Participant;
use DateTime;
use Symfony\Component\WebLink\Link;

#[Route('/conversations', name: 'conversations.')]
class ConversationController extends AbstractController
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    #[Route('/', name: 'newConversations', methods: ['POST'])]
    public function index(Request $request): Response
    {
        $otherUser = $request->get('otherUser', 0);
        $otherUser = $this->em->getRepository(User::class)->find($otherUser);

        if(is_null($otherUser)){
            throw new \Exception("El usuario no se ha encontrado");
        }

        if($otherUser->getId() === $this->getUser()->getId()) {
            throw new \Exception("No puedes crear una conversación contigo mismo");
        }

        $conversation = $this->em->getRepository(Conversation::class)->findConversationByParticipants(
            $this->getUser()->getId(),
            $otherUser->getId()
        );

        if(count($conversation)) {
            throw new \Exception("La conversación ya existe");
        }

        $conversation = new Conversation();
        $date = new DateTime();

        $participant = new Participant();
        $participant->setUser($this->getUser());
        $participant->setMessagesReadAt($date);
        $participant->setConversationId($conversation);

        $otherParticipant = new Participant();
        $otherParticipant->setUser($otherUser);
        $otherParticipant->setMessagesReadAt($date);
        $otherParticipant->setConversationId($conversation);

        $this->em->getConnection()->beginTransaction();

        try{
            $this->em->persist($conversation);
            $this->em->persist($participant);
            $this->em->persist($otherParticipant);

            $this->em->flush();
            $this->em->commit();

        } catch (\Exception $e) {
            $this->em->rollback();
            throw $e;
        }
        
        return $this->json([
            "id" => $conversation->getId()
        ], Response::HTTP_CREATED, [], []);
    }

    #[Route('/', name: 'getConversations', methods: ['GET'])]
    public function getConversation(Request $request): Response
    {
        $userId = $this->getUser()->getId();
        $conversations = $this->em->getRepository(Conversation::class)->findConversationByUser($userId);

        $hubUrl = $this->getParameter('mercure.default_hub');

        $this->addLink($request, new Link('mercure', $hubUrl));

        return $this->json($conversations);
    }

}

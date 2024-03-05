<?php

namespace App\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use App\Entity\Conversation;
use App\Repository\ConversationRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;

class ConversationVoter extends Voter
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;        
    }

    const VIEW = 'view';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute == self::VIEW && $subject instanceof Conversation;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $this->em->getRepository(User::class)->find($token->getUser());

        $result = $this->em->getRepository(Conversation::class)->checkIfUserIsParticipant(
            $subject->getId(),
            $user->getId()
        );

        if(is_null($result)){
            return false;
        }

        return true;
    }

}

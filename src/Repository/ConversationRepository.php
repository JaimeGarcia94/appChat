<?php

namespace App\Repository;

use App\Entity\Conversation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr;

/**
 * @extends ServiceEntityRepository<Conversation>
 *
 * @method Conversation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Conversation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Conversation[]    findAll()
 * @method Conversation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ConversationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conversation::class);
    }

    public function findConversationByParticipants($myId, $otherUserId)
    {
        return $this->createQueryBuilder('c')
            ->select('COUNT(p.conversation)')
            ->innerJoin('c.participants', 'p')
            ->where('p.user IN (:me, :otherUser)')
            ->groupBy('p.conversation')
            ->having('COUNT(p.conversation) = :totalParticipants')
            ->setParameters(['me' => $myId, 'otherUser' => $otherUserId, 'totalParticipants' => 2])
            ->getQuery()
            ->getResult();
    }

    public function findConversationByUser($userId)
    {
        return $this->createQueryBuilder('c')
            ->select('otherUser.username', 'c.id as conversationId', 'lm.content', 'lm.created_at')
            ->innerJoin('c.participants', 'p', Expr\Join::WITH, 'p.user = :userId')
            ->innerJoin('c.participants', 'me', Expr\Join::WITH, 'me.user = :userId')
            ->leftJoin('c.lastMessage', 'lm')
            ->innerJoin('me.user', 'meUser')
            ->innerJoin('p.user', 'otherUser')
            ->where('meUser.id = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('lm.created_at', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function checkIfUserIsParticipant($conversationId, $userId)
    {
        return $this->createQueryBuilder('c')
        ->innerJoin('c.participants', 'p')
        ->where('c.id = :conversationId')
        ->andWhere('p.user = :userId')
        ->setParameter('conversationId', $conversationId)
        ->setParameter('userId', $userId)
        ->getQuery()
        ->getOneOrNullResult();
    }

//    /**
//     * @return Conversation[] Returns an array of Conversation objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Conversation
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}

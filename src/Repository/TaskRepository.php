<?php

namespace App\Repository;

use App\Entity\Task;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    public function findTasksByUser(User $user): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.assignedTo = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }
}

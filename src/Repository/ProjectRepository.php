<?php

namespace App\Repository;

use App\Entity\Project;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    public function findProjectsByUser(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.projectMembers', 'pm')
            ->where('pm.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    public function findManagedProjects(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.createdBy = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    public function findMemberProjects(User $user): array
    {
        return $this->findProjectsByUser($user);
    }
}

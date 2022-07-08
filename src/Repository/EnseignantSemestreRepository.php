<?php

namespace App\Repository;

use App\Entity\EnseignantSemestre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method EnseignantSemestre|null find($id, $lockMode = null, $lockVersion = null)
 * @method EnseignantSemestre|null findOneBy(array $criteria, array $orderBy = null)
 * @method EnseignantSemestre[]    findAll()
 * @method EnseignantSemestre[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EnseignantSemestreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EnseignantSemestre::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(EnseignantSemestre $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(EnseignantSemestre $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    // /**
    //  * @return EnseignantSemestre[] Returns an array of EnseignantSemestre objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('e.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    
    public function getEnseignantSemestresByClosedTime($user)
    {

        return $this->createQueryBuilder('e')
            ->Where('e.user = :user')
            ->andWhere("e.closeDate > :date")
            ->setParameter('user', $user)
            ->setParameter('date', new \DateTime("now"))
            ->getQuery()
            ->getResult()
        ;
    }
    public function findByUserAndSemestre($user, $semestre)
    {
        return $this->createQueryBuilder('e')
            ->Where('e.user = :user')
            ->andWhere("e.closeDate > :date")
            ->andWhere("e.semestre = :semestre")
            ->setParameter('user', $user)
            ->setParameter('date', new \DateTime("now"))
            ->setParameter('semestre',$semestre)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    
}

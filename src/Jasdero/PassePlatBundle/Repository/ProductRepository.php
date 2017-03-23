<?php


namespace Jasdero\PassePlatBundle\Repository;


use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr;

class ProductRepository extends EntityRepository
{
    public function findOrderByCatalog($id)
    {
        $dq = $this->createQueryBuilder('p');
        $dq ->select('orders.id')
            ->join('p.orders', 'orders')
            ->join('p.catalog', 'c')
            ->where('c.id = :id')
            ->setParameter('id', $id)
            ->groupBy('orders.id');

        return $dq->getQuery()->getResult();
    }

    public function countProducts()
    {
        $qb = $this->createQueryBuilder('p');
        $qb->select('count(p.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }

}
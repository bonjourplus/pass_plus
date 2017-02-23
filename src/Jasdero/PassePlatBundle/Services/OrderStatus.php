<?php

namespace Jasdero\PassePlatBundle\Services;

use Jasdero\PassePlatBundle\Entity\Orders;
use Doctrine\ORM\EntityManager;

class OrderStatus
{


    public function __construct(EntityManager $em)
    {
        $this->em = $em;

    }
    //function to determine status of order according to products statuses: needs refactoring

    public function orderStatusAction(Orders $order)
    {

        //getting all products in the orders
        $products = $this->em->getRepository('JasderoPassePlatBundle:Product')->findBy(['orders'=>$order->getId()]);
        $weights = [];

        //getting max weight
        foreach ($products as $product) {
            $weights[]=$product->getState()->getWeight();
        }
        $maxWeight = max($weights);

        //Finding correspnding status
        $status = $this->em->getRepository('JasderoPassePlatBundle:State')->findOneBy(['weight'=>$maxWeight]);

        //Setting order status
        $order->setState($status);
        $this->em->persist($order);
        $this->em->flush();
    }
}
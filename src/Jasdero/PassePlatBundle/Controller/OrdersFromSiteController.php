<?php

namespace Jasdero\PassePlatBundle\Controller;

use Jasdero\PassePlatBundle\Form\OrdersType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class OrdersFromSiteController extends Controller
{
    /**
     * Creates a new order entity from the site
     *
     * @Route("/admin/order/new", name="order_site_new")
     * @Method({"GET", "POST"})
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction(Request $request)
    {
        $form = $this->createForm(OrdersType::class);
        $form->handleRequest($request);
        $products = [];
        if ($form->isSubmitted() && $form->isValid()) {
            //retrieving user
            $user = $form->get('user')->getData();
            //retrieving catalogs
            $catalogs = $form->get('catalogs')->getData();
            foreach ($catalogs as $catalog) {
                $products[] = $catalog->getId();
            }
            //creating order and recovering its id
            $orderId = $this->forward('JasderoPassePlatBundle:Orders:new', array(
                'user' => $user,
                'products' => $products
            ))->getContent();

            //retrieving order and creating file on Drive
            $em = $this->getDoctrine()->getManager();
            $order = $em->getRepository('JasderoPassePlatBundle:Orders')->findOneBy(['id'=>$orderId]);
            $status = $order->getState()->getName();
            $this->get('jasdero_passe_plat.drive_folder_as_status')->driveFolder($status, $orderId);

            //displaying the new order
            return $this->redirectToRoute('orders_show', array('id' => $orderId));
        }

        return $this->render('@JasderoPassePlat/orders/new.html.twig', array(
            'form' => $form->createView(),
        ));
    }
}

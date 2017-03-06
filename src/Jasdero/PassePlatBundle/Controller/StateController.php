<?php

namespace Jasdero\PassePlatBundle\Controller;

use Jasdero\PassePlatBundle\Entity\State;
use Jasdero\PassePlatBundle\Entity\Product;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * State controller.
 *
 * @Route("admin/state")
 */
class StateController extends Controller
{


    //token management
    protected function csrfProtect()
    {
        // store token into session
        $session = $this->get('session');          // expired token treatment
        $expire = $session->get('bjp_token_expire');
        if (!empty($expire) && ($expire < time())) {
            $session->remove('bjp_token');
            $session->remove('bjp_token_expire');
        }          // token generation
        if (empty($session->get('bjp_token'))) {
            $session->set('bjp_token', bin2hex(random_bytes(10)));
            $session->set('bjp_token_expire', time() + 3600);
        }
        return $session->get('bjp_token');
    }

    //override render method to send token
    public function render($view, array $parameters = array(), Response $response = null)
    {
        // generate token
        $parameters['bjptoken'] = $this->csrfProtect();
        // call to parent render()
        return parent::render($view, $parameters, $response);
    }

    //also sending token to compare when doing Ajax
    /**
     * Lists all state entities.
     *
     * @Route("/", name="state_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $states = $em->getRepository('JasderoPassePlatBundle:State')->findBy([], ['weight' => 'DESC']);
        $products = $em->getRepository('JasderoPassePlatBundle:Product')->findAll();
        $orders = $em->getRepository('JasderoPassePlatBundle:Orders')->findAll();
        $totalOrders = count($orders);
        $totalProducts = count($products);

        return $this->render('state/index.html.twig', array(
            'states' => $states,
            'products' => $products,
            'orders' => $orders,
            'totalOrders' => $totalOrders,
            'totalProducts' => $totalProducts,
            'token' => $this->get('session')->get('bjp_token'),
        ));
    }

    /**
     * Creates a new state entity.
     *
     * @Route("/new", name="state_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $state = new State();
        $form = $this->createForm('Jasdero\PassePlatBundle\Form\StateType', $state);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($state);
            $em->flush($state);

            return $this->redirectToRoute('state_show', array('id' => $state->getId()));
        }

        return $this->render('state/new.html.twig', array(
            'state' => $state,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a state entity.
     *
     * @Route("/{id}", name="state_show")
     * @Method("GET")
     */
    public function showAction(State $state)
    {
        $deleteForm = $this->createDeleteForm($state);

        return $this->render('state/show.html.twig', array(
            'state' => $state,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing state entity.
     *
     * @Route("/{id}/edit", name="state_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, State $state)
    {
        $deleteForm = $this->createDeleteForm($state);
        $editForm = $this->createForm('Jasdero\PassePlatBundle\Form\StateType', $state);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('state_show', array('id' => $state->getId()));
        }

        return $this->render('state/edit.html.twig', array(
            'state' => $state,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a state entity.
     *
     * @Route("/{id}", name="state_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, State $state)
    {
        $form = $this->createDeleteForm($state);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($state);
            $em->flush();
        }

        return $this->redirectToRoute('state_index');
    }

    //method to change statuses weights from Ajax call
    /**
     *
     * @Route("/dynamicChange", name="weight_change")
     * @Method({"POST"})
     */

    public function weightChange(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $responseText = '';

        //checking Ajax
        if (!$request->isXmlHttpRequest()) {
            $responseText = 'ERROR';
        }

        //checking bjp-token
        if ($request->request->get('token') !== $request->getSession()->get('bjp_token')) {
            $responseText = 'ERROR';
        }

        //retrieving new table order
        $newOrder = $request->request->get('request');

        //checking array type
        if (!is_array($newOrder)) {
            $responseText = 'ERROR';
        }

        //checking array values
        foreach ($newOrder as $value) {
            if (preg_match('#\D#', $value)) {
                $responseText = 'ERROR';
            }
        }

        //redirect to home if something wrong
        if ($responseText == 'ERROR') {
            return $this->redirectToRoute('home');
        } else {
            //doing the changes if everything is OK

            //cleaning array
            array_shift($newOrder);

            //setting statuses weights
            foreach ($newOrder as $key => $stateId) {
                $state = $em->getRepository('JasderoPassePlatBundle:State')->findOneBy(['id' => $stateId]);
                $state->setWeight(1000 - ($key * 100));
                $em->persist($state);
                $em->flush();
            }

            //updating orders statuses
            $orders = $em->getRepository('JasderoPassePlatBundle:Orders')->findAll();
            foreach ($orders as $order) {
                $this->get('orderstatus')->orderStatusAction($order);
            }
            return new Response();
        }
    }

    /**
     * Creates a form to delete a state entity.
     *
     * @param State $state The state entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(State $state)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('state_delete', array('id' => $state->getId())))
            ->setMethod('DELETE')
            ->getForm();
    }
}

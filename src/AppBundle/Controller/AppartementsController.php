<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Appartements;
use AppBundle\Entity\Tokens;
use AppBundle\Controller\AppartementsController;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
//use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * Controller for managing apprtments 
 *
 * @author Andrew
 */
class AppartementsController extends Controller

{
    /**
     * Action for rendering the apartments list.
     * 
     * @Route("/appartments")
     */
    public function allAction()
    {
        //$a = \PDO::getAvailableDrivers();
        //file_put_contents('log', var_export($a, TRUE), FILE_APPEND);
        
        return $this->render('appartments/appartments.html.twig');
    }

    /**
     * Read all appartments.
     * 
     * @Route("/appartments/read_all")
     */
    public function readAllAction()
    {
        $entityManager = $this->getDoctrine()->getManager();
        $appartments = $entityManager->getRepository(Appartements::class)->findAll();

        $records = array();
        foreach ($appartments as $a)
        {
            $records[] = array(
                'id' => $a->getId(),
                'address' => $a->getAddress(),
                'price' => $a->getPrice(),
                'area' => $a->getArea(),
                'rooms' => $a->getRooms(),
                'link' => 'appartments/view?id=' . $a->getId()
            );
        }
        $response['records'] = $records;

        $res = new Response(json_encode($response));
        $res->headers->set('Content-Type', 'application/json');

        return $res;
    }

    /**
     * Read one apartment by ID set per POST.
     * 
     * @Route("/appartments/read")
     */
    public function readAction()
    {
        $data = json_decode(file_get_contents("php://input"));

        $flat = $this->getDoctrine()
                ->getRepository(Appartements::class)
                ->find($data->id);

        $flat_arr[] = array(
            "id" => $flat->getId(),
            "address" => $flat->getAddress(),
            "area" => $flat->getArea(),
            "price" => $flat->getPrice(),
            "rooms" => $flat->getRooms()
        );


        $res = new Response(json_encode($flat_arr));
        $res->headers->set('Content-Type', 'application/json');

        return $res;
    }

    /**
     * Create apartment.
     * 
     * @Route("/appartments/create")
     */
    public function createAction()
    {
        $data = json_decode(file_get_contents("php://input"));

        $flat = new Appartements();
        $flat->setAddress($data->address);
        $flat->setPrice($data->price);
        $flat->setRooms($data->rooms);
        $flat->setArea($data->area);

        $em = $this->getDoctrine()->getManager();

        // tells Doctrine you want to save an appartment
        $em->persist($flat);

        $em->flush();

        return new Response('Saved new appartment with id ' . $flat->getId());
    }

    /**
     * Create and fill in token object.
     * 
     * @param integer $id The apartment id.
     * 
     * @return Tokens
     */
    private function createToken($id)
    {
        $token = new Tokens();
        $token->setAid($id);
        $token_md5 = md5(uniqid(mt_rand(), TRUE));
        $token->setToken($token_md5);

        $em = $this->getDoctrine()->getManager();

        // tells Doctrine you want to save the apartment
        $em->persist($token);

        $em->flush();
        
        return $token;
    }
    
    /**
     * Send link for editing the apartment.
     * 
     * @param integer $id The apartment id.
     * @param string $email The email for sending a link.
     * 
     * @return $array Array with the executing status and the message.
     */
    private function sendLink($id, $email)
    {
        $request = Request::createFromGlobals();
       
        $token = $this->createToken($id);

        $host = $request->server->get('HTTP_ORIGIN');
        $link = $host . '/app_dev.php/appartments/edit?id=' . $id . '&token=' . $token->getToken();

        $res['link_msg'] = 'Link zur Bearbeitung der Wohnung mit ID ' . $id . ': ' . $link;

        $message = \Swift_Message::newInstance()
                ->setSubject('Die Wohnung bearbeiten link')
                ->setFrom('konoval.olenka@gmail.com')
                ->setTo($email)
                ->setBody(
                $this->renderView(
                        'emails/editappartmenslink.html.twig', array('link' => $link)
                ), 'text/html'
        );

        $failures = array();
        if (!$this->get('mailer')->send($message, $failures))
        {
            $res['status'] = 0;
            $res['msg'] = var_export($failures, TRUE);
        } else
        {
            $res['status'] = 1;
            $res['msg'] = 'Message was sent';
        }

        return $res;
    }

    /**
     * View an apartment with interface for sending emails.
     * 
     * @Route("/appartments/view")
     */
    public function viewAction()
    {
        $request = Request::createFromGlobals();

        // the URI being requested minus any query parameters
        $request->getPathInfo();

        // retrieve $_GET 
        $gid = $request->query->get('id');
        $pid = $request->request->get('id');
        $psend = $request->request->get('send');
        
        $id = isset($pid) ? $pid : $gid;

        $alert = array();
        
        if (isset($psend))
        {
            $pemail = $request->request->get('email');
            $alert = $this->sendLink($id, $pemail);
        }

        $flat = $this->getDoctrine()
                ->getRepository(Appartements::class)
                ->find($id);
        
        return $this->render('appartments/view.html.twig', array(
                    'appartments' => $flat,
                    'alert' => $alert,
        ));
    }

    
    /**
     * Submit edit form.
     * 
     * @param Form $form The edit form.
     * @param integer $id The apartment Id.
     * 
     */
    private function submitForm($form, $id)
    {
        $flat = $form->getData();

        $em = $this->getDoctrine()->getManager();

        if ($form->get('save')->isClicked())
        {
            $em->persist($flat);
            $em->flush();
            $msg = 'Änderungen wurden gespeichert!';
        }

        if ($form->get('delete')->isClicked())
        {
            $em->remove($flat);

            $em->createQueryBuilder()
                    ->delete('AppBundle:Tokens', 't')
                    ->where('t.aid  = :id')->setParameter("id", $id)
                    ->getQuery()->execute();

            $em->flush();

            $msg = 'Die Wohnung mit ID=' . $id . ' wurde entfernt';
        }

        return $msg;
    }

    /**
     * Edit apartment by token.
     * 
     * @Route("/appartments/edit")
     */
    public function editAction(Request $request)
    {
        $req = Request::createFromGlobals();

        // the URI being requested minus any query parameters
        $req->getPathInfo();

        // retrieve $_GET
        $id = $req->query->get('id');
        $tok = $req->query->get('token');

        $token = $this->getDoctrine()
                ->getRepository(Tokens::class)
                ->findOneByToken($tok);
        
        if ( !$token || !$token->validate($id))
        {
            return $this->render('appartments/edit.html.twig', array(
                        'msg' => 'Sie haben keinen Zugang Form oder Ihren Zugang zu bearbeiten ist abgelaufen'
            ));
        }

        $flat = $this->getDoctrine()
                ->getRepository(Appartements::class)
                ->find($id);

        $form = $this->createFormBuilder($flat)
                ->add('address', TextType::class)
                ->add('area', NumberType::class)
                ->add('price', NumberType::class)
                ->add('rooms', IntegerType::class)
                ->add('save', SubmitType::class, array('label' => 'Sparen'))
                ->add('delete', SubmitType::class, array('label' => 'Löschen'))
                ->getForm();

        $form->handleRequest($request);
        $msg = '';

        if ($form->isSubmitted() && $form->isValid())
        {
            $msg = $this->submitForm($form, $id);
        }

        return $this->render('appartments/edit.html.twig', array(
                    'form' => $form->createView(),
                    'msg' => $msg
        ));
    }

    /**
     * Update an apartment.
     * 
     * @Route("/appartments/update")
     */
    public function updateAction()
    {
        $data = json_decode(file_get_contents("php://input"));

        $flat = $this->getDoctrine()
                ->getRepository(Appartements::class)
                ->find($data->id);

        $flat->setAddress($data->address);
        $flat->setPrice($data->price);
        $flat->setRooms($data->rooms);
        $flat->setArea($data->area);

        $em = $this->getDoctrine()->getManager();

        // tells Doctrine you want to save the Flat
        $em->persist($flat);

        // actually executes the queries (i.e. the INSERT query)
        $em->flush();

        return new Response('Updated the appartment with id ' . $flat->getId());
    }

    /**
     * Delete the apartment.
     * 
     * @Route("/appartments/delete")
     */
    public function deleteAction()
    {
        $data = json_decode(file_get_contents("php://input"));

        $flat = $this->getDoctrine()
                ->getRepository(Appartements::class)
                ->find($data->id);

        $em = $this->getDoctrine()->getManager();

        $em->remove($flat);

        $token = $em->createQueryBuilder()
                        ->delete('AppBundle:Tokens', 't')
                        ->where('t.aid  = :id')->setParameter("id", $data->id)
                        ->getQuery()->execute();

        $em->flush();

        return new Response('Apartment mit ID ' . $flat->getId() . ' gelöscht!');
    }

}

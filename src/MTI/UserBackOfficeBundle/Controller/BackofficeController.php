<?php

namespace MTI\UserBackOfficeBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use MTI\UserBackOfficeBundle\Entity\Profile;
use MTI\UserBackOfficeBundle\Entity\Call;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;

class BackofficeController extends Controller
{
    public function indexAction()
    {
    	if (!$this->get('security.context')->isGranted('ROLE_USER')) {
	      throw new AccessDeniedException('Accès limité aux utilisateurs enregistrés.');
	    }
        return $this->render('MTIUserBackOfficeBundle:Backoffice:index.html.twig');
    }

    public function createAction()
	{
		$request = Request::createFromGlobals();
		$username = $request->request->get('_username', 'username');
		$lastname = $request->request->get('_lastname', 'lastname');
		$password = $request->request->get('_password', 'password');
		$email = $request->request->get('_email', 'email');

	    $profile = new Profile();
	    $profile->setUsername($username);
	    $profile->setLastname($lastname);
	    $profile->setEmail($email);
	    $secretapi = substr(base64_encode(mt_rand()), 0, 20);
	    $profile->setSecretApikey($secretapi);
	    $publicapi = substr(base64_encode(mt_rand()), 0, 20);
	    $profile->setPublicApikey($publicapi);
	    $profile->setPassword($password);
	    $profile->setSalt("");
      	$profile->setRoles(array('ROLE_USER'));
	    

	    $em = $this->getDoctrine()->getManager();
	    $em->persist($profile);
	    $em->flush();

	    return new Response('username  : '.$username);
	}

	public function showAction($id)
	{
	    $profile = $this->getDoctrine()
	        ->getRepository('MTIUserBackOfficeBundle:Profile')
	        ->find($id);

	    if (!$profile) {
	        throw $this->createNotFoundException(
	            'Aucun profil trouvé pour cet id : '.$id
	        );
	    }

	    return new Response('Profile: '.$profile->getId().' '.$profile->getFirstname());
	}

	public function testjsonAction()
	{
		$response = new Response();
		$response->setContent(json_encode(array(
		    'data' => 123,
		)));
		$response->headers->set('Content-Type', 'application/json');

		return $response;
	}

	public function addCallAction()
	{
		$call = new Call();

		$user = $this->getUser();

		$call->setUserId($user->getId());
		$call->setType(2);

		$em = $this->getDoctrine()->getManager();
	    $em->persist($call);
	    $em->flush();

	    return new Response('id : '.$call->getId());
	}
}

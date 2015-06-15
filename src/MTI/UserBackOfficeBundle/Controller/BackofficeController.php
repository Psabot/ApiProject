<?php

namespace MTI\UserBackOfficeBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use MTI\UserBackOfficeBundle\Entity\Profile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class BackofficeController extends Controller
{
    public function indexAction()
    {
    	if (!$this->get('security.context')->isGranted('ROLE_ADMIN')) {
	      throw new AccessDeniedException('Accès limité aux administrateurs.');
	    }
        return $this->render('MTIUserBackOfficeBundle:Backoffice:index.html.twig');
    }

    public function createAction()
	{
	    $profile = new Profile();
	    $profile->setUsername("user");
	    $profile->setLastname("SABOT");
	    $profile->setEmail("pierre.adfacebook@gmail.com");
	    $profile->setSecretApikey("osef");
	    $profile->setPublicApikey("osef");
	    $profile->setPassword("pwd");
	    $profile->setSalt("");
      	$profile->setRoles(array('ROLE_USER'));
	    

	    $em = $this->getDoctrine()->getManager();
	    $em->persist($profile);
	    $em->flush();

	    return new Response('Id du profil créé : '.$profile->getId());
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
}

<?php

namespace MTI\UserBackOfficeBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use MTI\UserBackOfficeBundle\Entity\Profile;
use MTI\UserBackOfficeBundle\Entity\Call;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\Query\ResultSetMapping;

class BackofficeController extends Controller
{
    public function indexAction()
    {
    	if (!$this->get('security.context')->isGranted('ROLE_USER')) {
	      throw new AccessDeniedException('Accès limité aux utilisateurs enregistrés.');
	    }

	    $user = $this->getUser();
	    $em = $this->getDoctrine()->getManager();

	    /*$rsm = new ResultSetMapping();
		$rsm->addEntityResult('MTI\UserBackOfficeBundle\Entity\Call', 'call');
		$rsm->addFieldResult('call', 'id', 'id');
		$rsm->addFieldResult('call', 'created', 'created');

		$query = $em->createNativeQuery('SELECT * FROM call c1 CROSS JOIN (SELECT call.created FROM call WHERE call.userid = ? GROUP BY call.created ORDER BY call.created ASC) as c2', $rsm);

	    query = $em->createNativeQuery('SELECT call.id, call.created FROM call WHERE call.userid = ? ORDER BY call.created ASC', $rsm);
		$query->setParameter(1,  $user->getId());*/

		/*$query = $em->createNativeQuery('SELECT call.created, count(*) FROM (SELECT * FROM call WHERE call.userid = ?) AS osef GROUP BY call.created ORDER BY call.created ASC', $rsm);
		$query->setParameter(1,  $user->getId());*/

		// Calls type
		$callTypesQuery = $em->createQuery(
		    'SELECT call.type, count(call)
		    FROM MTIUserBackOfficeBundle:Call call
		    WHERE call.userid = :user
		    GROUP BY call.type'
		)->setParameter('user', $user->getId());

		$callTypes = $callTypesQuery->getResult();

		// Calls by date
		$query = $em->createQuery(
		    'SELECT call.created as createdDate
		    FROM MTIUserBackOfficeBundle:Call call
		    WHERE call.userid = :user
		    ORDER BY createdDate ASC'
		)->setParameter('user', $user->getId());
		
		$res = $query->getResult();

		$dateCount = array();

		foreach ($res as $r) {
			$date = $r['createdDate']->format('d/m/Y');
			if (array_key_exists($date, $dateCount))
				$dateCount[$date] += 1;
			else
				$dateCount[$date] = 1;
		}

		// Total calls
		$query2 = $em->createQuery(
		    'SELECT count(call)
		    FROM MTIUserBackOfficeBundle:Call call
		    WHERE call.userid = :user'
		)->setParameter('user', $user->getId());

		$count = $query2->getSingleResult();

		// Generate token -> base64(publickey:secretkey)
		$token = base64_encode($user->getPublicapikey().':'.$user->getSecretapikey());

        return $this->render('MTIUserBackOfficeBundle:Backoffice:index.html.twig', array('countcall' => $count, 'datecount' => $dateCount, 'calltype' => $callTypes, 'token' => $token));
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
      	$profile->setSubscribe(1);
	    

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

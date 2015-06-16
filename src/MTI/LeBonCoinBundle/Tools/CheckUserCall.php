<?php

namespace MTI\LeBonCoinBundle\Tools;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class CheckUserCall extends Controller
{
	protected  $doctrine;

    public function __construct($doctrine) {
        $this->doctrine = $doctrine;
    }

	public function check()
	{
		//$token = $request->headers->get('host');
		$token = "TWpBMk1qUXpOakV4Ok1qQTNNVEUxTlRVNE53PT0=";
		$tokendecode = base64_decode($token);
		$pos = strpos(':', $tokendecode);
		if($pos > 0)
			return false;
		else
		{
			$keys = explode(':',$tokendecode);
			$apipublickey = $keys[0];
			$apisecretkey = $keys[1];

	        $em = $this->doctrine->getManager();

			$repository = $this->doctrine->getRepository('MTIUserBackOfficeBundle:Profile');

			$query = $repository->createQueryBuilder('p')
			    ->where('p.publicapikey = :public')
			    ->andWhere('p.secretapikey = :secret')
			    ->setParameters(array(
			    'public' => $apipublickey,
			    'secret'  => $apisecretkey
				))
			    ->getQuery();

			$profile = $query->getResult();

		    if (!$profile) {
		        throw $this->createNotFoundException(
		            'Aucun profil trouvé pour ces clés d\'api'
		        );
		    }
		    else
		    	$profile = $profile[0];

		    $query2 = $em->createQuery(
		    'SELECT count(call)
		    FROM MTIUserBackOfficeBundle:Call call
		    WHERE call.userid = :user'
			)->setParameter('user', $profile->getId());

			$count = $query2->getSingleResult();

			return ($count[1]+1 < $this->giveLimitation($profile->getSubscribe()));
		}
	}

	public function giveLimitation($type)
	{
		switch ($type) {
			case 1:
				return 10;
				break;
			case 2:
				return 20;
				break;
			case 3:
				return 30;
				break;
			default:
				return 10;
				break;
		}
	}
}
<?php

namespace MTI\LeBonCoinBundle\Tools;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class CheckUserCall extends Controller
{
	protected  $doctrine;

    public function __construct($doctrine) {
        $this->doctrine = $doctrine;
    }

	public function check($token)
	{
		if ($token == null) return "errorTokenMissing";
		$tokendecode = base64_decode($token);
		$pos = strpos(':', $tokendecode);
		if($pos > 0)
			return "errorBadToken";
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
		        return "errorNoAccount";
		    }
		    else
		    	$profile = $profile[0];

		    $query2 = $em->createQuery(
		    'SELECT count(call)
		    FROM MTIUserBackOfficeBundle:Call call
		    WHERE call.userid = :user'
			)->setParameter('user', $profile->getId());

			$count = $query2->getSingleResult();

			if ($count[1] < $this->giveLimitation($profile->getSubscribe()))
				return $profile;
			else
				return "errorLimit";
		}
	}

	public function giveLimitation($type)
	{
		switch ($type) {
			case 1:
				return 50;
				break;
			case 2:
				return 200;
				break;
			case 3:
				return 500;
				break;
			default:
				return 10;
				break;
		}
	}
}
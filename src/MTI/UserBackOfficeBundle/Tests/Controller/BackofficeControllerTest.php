<?php

namespace MTI\UserBackOfficeBundle\Tests\Controller;

use MTI\UserBackOfficeBundle\Controller\BackofficeController;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BackofficeControllerTest extends WebTestCase
{
	private $em;

    public function setUp()
    {
        static::$kernel = static::createKernel();
        static::$kernel->boot();
        $this->em = static::$kernel->getContainer()->get('doctrine.orm.entity_manager');
    }

    public function testShow()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/show/10');

        $this->assertTrue($crawler->filter('html:contains("pierre")')->count() > 0);
    }

    public function testCreate()
    {
    	$client = static::createClient();
    	$client->request(
    		'POST', 
    		'/addtest', 
    		array('_username' => 'test','_lastname' => 'test','_password' => 'test','_email' => 'test')
    	);

    	$client->insulate();

       	$repository = $this->em
		    ->getRepository('MTIUserBackOfficeBundle:Profile');

		$query = $repository->createQueryBuilder('p')
		    ->orderBy('p.id', 'DESC')
		    ->getQuery();

		$profiles = $query->getResult();

		if (!$profiles) {
		    throw $this->createNotFoundException(
		        'Aucun profil trouvé pour ces clés d\'api'
		    );
		}
		else
		    $profile = $profiles[0];

		$this->assertEquals('test', $profile->getUsername());
    }
}
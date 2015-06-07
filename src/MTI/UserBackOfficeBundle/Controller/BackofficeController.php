<?php

namespace MTI\UserBackOfficeBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class BackofficeController extends Controller
{
    public function indexAction()
    {
        return $this->render('MTIUserBackOfficeBundle:Backoffice:index.html.twig');
    }
}

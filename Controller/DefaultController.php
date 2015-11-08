<?php

namespace SmartCore\Bundle\DbDumperBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('DbDumperBundle:Default:index.html.twig', array('name' => $name));
    }
}

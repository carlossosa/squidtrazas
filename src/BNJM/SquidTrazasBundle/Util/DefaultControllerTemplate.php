<?php
namespace BNJM\SquidTrazasBundle\Util;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultControllerTemplate extends Controller
{
    public function setPeriod (\Symfony\Component\HttpFoundation\Request $r)
    {
        if ( $r->get('time_start') )
        {
            $d = new \DateTime($r->get('time_start'));
            $r->getSession ()
                ->set ( 'time_start', $d->format("Y-m-d H:i:s"));
        unset($d);
        }
        
        if ( $r->get('time_end') )
        {
            $d = new \DateTime($r->get('time_end'));
            $r->getSession ()
                ->set ( 'time_end', $d->format("Y-m-d H:i:s"));
        }
        
        if ( $r->get('time') == '1')
        {
            $r->getSession()->set('time_enabled', 1);
        } else if ( $r->get('time') == '0')
        {
            $r->getSession()->remove('time_enabled');
            $r->getSession()->remove('time_start');
            $r->getSession()->remove('time_end');
        }                
    }
    
    public function listadoUsuarios ()
    {
        return $this->getDoctrine()
                        ->getEntityManager()
                        ->getRepository('SquidTrazasBundle:SquidUsuario')
                        ->getUsuarios();
    }
}
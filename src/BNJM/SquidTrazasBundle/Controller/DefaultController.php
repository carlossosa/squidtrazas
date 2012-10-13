<?php

namespace BNJM\SquidTrazasBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;
use BNJM\SquidTrazasBundle\Util\DefaultControllerTemplate;

class DefaultController extends DefaultControllerTemplate
{
    /**
     * @Route("/_usuarios", name="TrazasUsuarios") 
     * @Template("SquidTrazasBundle:Default:listausuarios.html.twig")
     */
    public function ajaxlistausuariosAction ()
    {
        if ( $this->getRequest()->get('term'))
        {
            $users = $this->getDoctrine()
                            ->getEntityManager()
                            ->getRepository('SquidTrazasBundle:SquidUsuario')
                            ->searchUsuarios( $this->getRequest()->get('term'));
            $_users = array();
            foreach ( $users as $t) $_users[] = $t['username'];        
            return new Response( json_encode( $_users ) );
        } else {
            return array('usuarios'=> $this->getDoctrine()
                            ->getEntityManager()
                            ->getRepository('SquidTrazasBundle:SquidUsuario')
                            ->getUsuarios());
        }
    }
    
    /**
     * @Route("/_dominios", name="TrazasDominios") 
     */
    public function ajaxlistadominiosAction ()
    {
            $users = $this->getDoctrine()
                            ->getEntityManager()
                            ->getRepository('SquidTrazasBundle:SquidDomain')
                            ->searchDomain( $this->getRequest()->get('term'));
            $_users = array();
            foreach ( $users as $t) $_users[] = $t['domain'];        
            
            return new Response( json_encode( $_users ) );
    }
    
    /**
     * @Route("/u/{username}/{page}", name="TrazasListadoPorUsuarios", defaults={"page"="1"})
     * @Template()
     */
    public function usuariotrazasAction( $username, $page)
    {  
        $r = $this->getRequest();
        $this->setPeriod($r);
        
        return array('trazas' => $this->getDoctrine()
                                        ->getEntityManager()
                                        ->getRepository('SquidTrazasBundle:SquidTraza')
                                        ->getTrazaUsuario($page, $this->getRequest()->get( 'show', 100), $username, $this->getRequest()->get('ip', NULL), $r->getSession()->get('time_start', null), $r->getSession()->get('time_end', null))
                    );
    }  
    
    /**
     * @Route("/ip/{ip}/{page}", name="TrazasListadoPorIP", defaults={"ip"="","page"="1"})
     * @Template("SquidTrazasBundle:Default:iptrazas.html.twig")
     */
    public function iptrazasAction( $ip, $page)
    {    
        $r = $this->getRequest();
        $this->setPeriod($r);
        
        //DETERMINAR RANGO ESTILO:
        //APACHE 1.1.1 | 1.1
        if ( preg_match( "/^[0-9]{1,3}\.[0-9]{1,3}(\.[0-9]{1,3}$|$)/", $ip, $matches) )
        {
            if ( $matches[1] != NULL)
            {
                $ip_lower = sprintf("%u", ip2long($matches[0].".0"));
                $ip_upper = sprintf("%u", ip2long($matches[0].".255"));
            } else {
                $ip_lower = sprintf("%u", ip2long($matches[0].".0.0"));
                $ip_upper = sprintf("%u", ip2long($matches[0].".255.255"));
            }
            
            return array('trazas' => $this->getDoctrine()
                                        ->getEntityManager()
                                        ->getRepository('SquidTrazasBundle:SquidTraza')
                                        ->getTrazaRangoIP($page, $this->getRequest()->get( 'show', 100), $ip_upper, $ip_lower, $r->getSession()->get('time_start', null), $r->getSession()->get('time_end', null))
                    );
        } else if( preg_match( "/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}-[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/", $ip, $matches) ) {
            
            list($ip_lower,$ip_upper) = explode("-", $ip);
            
            $ip_lower = sprintf("%u", ip2long($ip_lower));
            $ip_upper = sprintf("%u", ip2long($ip_upper));
                        
            return array('trazas' => $this->getDoctrine()
                                        ->getEntityManager()
                                        ->getRepository('SquidTrazasBundle:SquidTraza')
                                        ->getTrazaRangoIP($page, $this->getRequest()->get( 'show', 100), $ip_upper, $ip_lower, $r->getSession()->get('time_start', null), $r->getSession()->get('time_end', null))
                    );
        } else {                         
            if ( !is_numeric($ip))
                $ip = sprintf("%u", ip2long($ip));
            return array('trazas' => $this->getDoctrine()
                                            ->getEntityManager()
                                            ->getRepository('SquidTrazasBundle:SquidTraza')
                                            ->getTrazaIP($page, $this->getRequest()->get( 'show', 100), $ip, $r->getSession()->get('time_start', null), $r->getSession()->get('time_end', null))
                        );
        }
    }  
    
    /**
     * @Route("/t/{page}", name="TrazasListado", defaults={"page"="1"})
     * @Template()
     */
    public function indexAction($page)
    {                            
        $r = $this->getRequest();
        $this->setPeriod($r);
        
        if ( $r->get('show') != null )
        {
            $r->getSession()->set('items_show', $r->get('show'));
        }                
        
        if ( !$r->getSession()->has('time_enabled') )
                return array('trazas' => $this->getDoctrine()
                                                ->getEntityManager()
                                                ->getRepository('SquidTrazasBundle:SquidTraza')
                                                ->getAll($page, $r->getSession()->get('items_show', 20)) 
                            );
        else 
                return array('trazas' => $this->getDoctrine()
                                                    ->getEntityManager()
                                                    ->getRepository('SquidTrazasBundle:SquidTraza')
                                                    ->getAllInPeriod($page, $r->getSession()->get('items_show', 20), $r->getSession()->get('time_start', null), $r->getSession()->get('time_end', null)) 
                                );
    }        
}

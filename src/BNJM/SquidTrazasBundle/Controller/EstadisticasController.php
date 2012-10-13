<?php

namespace BNJM\SquidTrazasBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use BNJM\SquidTrazasBundle\Util\DefaultControllerTemplate;

class EstadisticasController extends DefaultControllerTemplate {
    
   /**
     * @Route("/", name="Estadisticas") 
     * @Template()
     */
    public function indexAction() {  
        return array();
    } 
    
    /**
     * @Route("/e/top/usuarios/{cuando}/{cuandofin}", defaults={"cuando"="","cuandofin"=""}, name="TraficoUsuarios") 
     * @Template()
     */
    public function usuariosactivosAction($cuando,$cuandofin) {  
        
        $r = $this->periodManager($this->getRequest(), $cuando, $cuandofin);
        
        return ( $this->getDoctrine()
                        ->getRepository("SquidTrazasBundle:SquidTraza")
                        ->topUser( $r[0], $r[1])
                );
    }
    
    public function periodManager ( $r, $cuando, $cuandofin)
    {        
        $this->setPeriod($r);
        
        switch ($cuando) {
            case 'ahora': $cuando = 'last minute';
                break;
            case 'ultimomes': $cuando = 'last month';
                break;
            case 'ultimoano': $cuando = 'last year';
                break;
            case 'ultimasemana': $cuando = 'last week';
                break;
            case 'ultimahora': $cuando = '-1 hour';
                break;
            default:
                break;
        }
        
        
        if ( $cuando != null)
        {
            $c  = new \DateTime($cuando);        
            $cuando  = $c->format('Y-m-d H:i:s');
        }
        
        if ( $cuandofin != null)
        {
            $cf = new \DateTime($cuandofin);
            $cuandofin = $cf->format('Y-m-d H:i:s');
        }
        
        if ( $cuando == null && $r->getSession()->has('time_enabled') )
        {
            $cuando = $r->getSession()->get('time_start', null);
            $cuandofin = $r->getSession()->get('time_end', null);
        }
        
        return array($cuando,$cuandofin);
    }
    
    /**
     * @Route("/e/top/urls/{username}") 
     * @Template("SquidTrazasBundle:Estadisticas:urlsactivas.html.twig")
     */
    public function urlsactivasforuserAction ($username)
    {        
        return ( array('dominios' =>  $this->getDoctrine()
                                            ->getRepository("SquidTrazasBundle:SquidTraza")
                                            ->topDomainEverForUser($username))
                );
    }
    
    /**
     * @Route("/e/top/urls",name="TopUrls") 
     * @Template()
     */
    public function urlsactivasAction()
    {
        
        return ( array('dominios' =>  $this->getDoctrine()
                                            ->getRepository("SquidTrazasBundle:SquidTraza")
                                            ->topDomainEver())
                );
    }    
    
    /**
     * @Route("/e/top/usersdomain/{domain}/{cuando}/{cuandofin}", defaults={"domain"="","cuando"="","cuandofin"=""}, name="TopUsersToDomain") 
     * @Template()
     */
    public function topuserstodomainAction($domain,$cuando,$cuandofin)
    {        
        $r = $this->periodManager($this->getRequest(), $cuando, $cuandofin);
        
        return ( array('usuarios' =>  $this->getDoctrine()
                                            ->getRepository("SquidTrazasBundle:SquidTraza")
                                            ->topUsersToDomain( $domain, $r[0], $r[1]))
                );
    }    
    
    /**
     * @Route("/e/ipusuario/{username}") 
     * @Template("SquidTrazasBundle:Estadisticas:usuarioips.html.twig")
     */
    public function usuarioipsAction ($username)
    {        
        return  array('ips' =>  $this->getDoctrine()
                                            ->getRepository("SquidTrazasBundle:SquidTraza")
                                            ->getIpOfUser($username));
    }    
}

?>
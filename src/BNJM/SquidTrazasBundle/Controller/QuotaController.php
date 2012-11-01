<?php

namespace BNJM\SquidTrazasBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use BNJM\SquidTrazasBundle\Util\DefaultControllerTemplate;

class QuotaController extends DefaultControllerTemplate {
    
   /**
     * @Route("/", name="Quota") 
     * @Template()
     */
    public function indexAction() {  
        return array();
    } 
    
    /**
     * @Route("/q/{usuario}/{cuando}/{cuandofin}", defaults={"cuando"="","cuandofin"=""}, name="Quotausuario") 
     * @Template()
     */
    public function quotausuarioAction($usuario,$cuando,$cuandofin) {  
        
        $r = $this->periodManager($this->getRequest(), $cuando, $cuandofin);
        $usuario = $this->getDoctrine()->getRepository("SquidTrazasBundle:SquidUsuario")->getUserByUsername($usuario);
        if ( $usuario->getQuota())
        {
            $quota = array('horas'=> $usuario->getQuota()->getQuotaHoras()*1000,'bytes'=> $usuario->getQuota()->getQuotaMegas());
        } else {
            $quota = array('horas'=>0,'bytes'=>0);
        }
        return array( 'consumo' => $this->getDoctrine()
                                        ->getRepository("SquidTrazasBundle:QuotaConsumo")
                                        ->getQuotaDataForUser($usuario,
                                                              $r[0], 
                                                              $r[1]),
                        'quota' => $quota,
                    'usuario' => $usuario
                );
    }

    /**
     * @Route("/v/{usuario}/{cuando}/{cuandofin}", defaults={"cuando"="","cuandofin"=""}, name="Quotavistausuario") 
     * @Template()
     */
    public function vistausuarioAction($usuario,$cuando,$cuandofin) {  
        
        $r = $this->periodManager($this->getRequest(), $cuando, $cuandofin);
        $usuario = $this->getDoctrine()->getRepository("SquidTrazasBundle:SquidUsuario")->getUserByUsername($usuario);
        $consumo = $this->getDoctrine()->getRepository("SquidTrazasBundle:QuotaConsumo")->getQuotaDataForUser($usuario,$r[0], $r[1]);
        if ( $usuario->getQuota())
        {
            $quota = array(
                            'horas'=> $usuario->getQuota()->getQuotaHoras()*1000,
                            'bytes'=> $usuario->getQuota()->getQuotaMegas(),
                            'horaspercent' => intval( ($usuario->getQuota()->getQuotaHoras()) ? ($consumo['horas']*100)/($usuario->getQuota()->getQuotaHoras()*1000) : 0) ,
                            'megaspercent' => intval( ($usuario->getQuota()->getQuotaMegas()>0) ? ($consumo['horas']*100)/$usuario->getQuota()->getQuotaMegas() : 0 ),
                            );
        } else {
            $quota = array('horas'=>0,'bytes'=>0, 'horaspercent'=>0, 'megaspercent' => 0);
        }
        return array( 'consumo' => $consumo,
                        'quota' => $quota,
                    'usuario' => $usuario
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
            $cuando  = $c->format('Y-m-d');            
        } else {
            $cuando  = date('Y-m').'-01';
        }
        
        if ( $cuandofin != null)
        {
            $cf = new \DateTime($cuandofin);
            $cuandofin = $cf->format('Y-m-d');
        } else {
            $cuandofin = date('Y-m').'-'.cal_days_in_month( CAL_GREGORIAN, date('m'), date('Y'));            
        }
        
        if ( $cuando == null && $r->getSession()->has('time_enabled') )
        {
            $cuando = $r->getSession()->get('time_start', null);
            $cuandofin = $r->getSession()->get('time_end', null);
        }
        
        return array($cuando,$cuandofin);
    }
    
}
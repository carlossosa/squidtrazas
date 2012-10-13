<?php

namespace BNJM\SquidTrazasBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="BNJM\SquidTrazasBundle\Repository\SquidTrazaRepository")
 * @ORM\Table(name="squid_traza",indexes={@ORM\Index(name="search_idx", columns={"method", "mime_type"}), @ORM\Index(name="order_idx", columns={"size"})})
 */
class SquidTraza {

    /**
     * @ORM\Id
     * @ORM\Column(type="bigint")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(name="time",type="datetime")
     */
    private $time;
    
    /**
     * @ORM\Column(name="transfer_time", type="integer")
     */
    private $transferTime;
    
    /**
     * @ORM\Column(type="bigint")
     */
    private $size;
    
    /**
     * @ORM\Column(type="string", length=120)
     */
    private $action;
    
    /**
     * @ORM\Column(type="string", length=50)
     */
    private $method;
    
    /**
     * @ORM\ManyToOne(targetEntity="SquidUrl", cascade={"all"}, fetch="EAGER")
     * @ORM\JoinColumn(name="url_id", referencedColumnName="id")
     */
    private $url;
    
    /**
     * @ORM\Column(name="mime_type", type="string", length=100)
     */
    private $docType;
    
    /**
     * @ORM\ManyToOne(targetEntity="SquidUsuario", cascade={"all"}, fetch="EAGER")
     * @ORM\JoinColumn(name="usuario_id", referencedColumnName="id")
     */
    private $usuario;
    
    /**
     * @ORM\ManyToOne(targetEntity="SquidIPAlias", cascade={"all"}, fetch="EAGER")
     * @ORM\JoinColumn(name="ip_id", referencedColumnName="id")
     */
    private $ip;   
    
    /**
     * @ORM\ManyToOne(targetEntity="SquidDomain")
     * @ORM\JoinColumn(name="domain_id", referencedColumnName="id")
     */
    private $domain;
    

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set time
     *
     * @param DateTime $time
     */
    public function setTime(\DateTime $time)
    {
        $this->time = $time;
    }

    /**
     * Get time
     *
     * @return DateTime 
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * Set transferTime
     *
     * @param integer $transferTime
     */
    public function setTransferTime($transferTime)
    {
        $this->transferTime = $transferTime;
    }

    /**
     * Get transferTime
     *
     * @return integer 
     */
    public function getTransferTime()
    {
        return $this->transferTime;
    }

    /**
     * Set size
     *
     * @param bigint $size
     */
    public function setSize($size)
    {
        $this->size = $size;
    }

    /**
     * Get size
     *
     * @return bigint 
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Set action
     *
     * @param string $action
     */
    public function setAction($action)
    {
        $this->action = $action;
    }

    /**
     * Get action
     *
     * @return string 
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Set method
     *
     * @param string $method
     */
    public function setMethod($method)
    {
        $this->method = $method;
    }

    /**
     * Get method
     *
     * @return string 
     */
    public function getMethod()
    {
        return $this->method;
    }
   

    /**
     * Set docType
     *
     * @param string $docType
     */
    public function setDocType($docType)
    {
        $this->docType = $docType;
    }

    /**
     * Get docType
     *
     * @return string 
     */
    public function getDocType()
    {
        return $this->docType;
    }   

    /**
     * Set usuario
     *
     * @param BNJM\SquidTrazasBundle\Entity\SquidUsuario $usuario
     */
    public function setUsuario(\BNJM\SquidTrazasBundle\Entity\SquidUsuario $usuario)
    {
        $this->usuario = $usuario;
    }

    /**
     * Get usuario
     *
     * @return BNJM\SquidTrazasBundle\Entity\SquidUsuario 
     */
    public function getUsuario()
    {
        return $this->usuario;
    }
  

    /**
     * Set ip
     *
     * @param BNJM\SquidTrazasBundle\Entity\SquidIPAlias $ip
     */
    public function setIp(\BNJM\SquidTrazasBundle\Entity\SquidIPAlias $ip)
    {
        $this->ip = $ip;
    }

    /**
     * Get ip
     *
     * @return BNJM\SquidTrazasBundle\Entity\SquidIPAlias 
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * Set domain
     *
     * @param BNJM\SquidTrazasBundle\Entity\SquidDomain $domain
     */
    public function setDomain(\BNJM\SquidTrazasBundle\Entity\SquidDomain $domain)
    {
        $this->domain = $domain;
    }

    /**
     * Get domain
     *
     * @return BNJM\SquidTrazasBundle\Entity\SquidDomain 
     */
    public function getDomain()
    {
        return $this->domain;
    }
    
    public function getShorturl ()
    {
        return substr( $this->getUrl(), 0, 50);
    }

    /**
     * Set url
     *
     * @param BNJM\SquidTrazasBundle\Entity\SquidUrl $url
     */
    public function setUrl(\BNJM\SquidTrazasBundle\Entity\SquidUrl $url)
    {
        $this->url = $url;
    }

    /**
     * Get url
     *
     * @return BNJM\SquidTrazasBundle\Entity\SquidUrl 
     */
    public function getUrl()
    {
        return $this->url;
    }
}
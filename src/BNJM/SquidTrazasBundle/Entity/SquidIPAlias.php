<?php

namespace BNJM\SquidTrazasBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="squid_ip_aliases")
 */
class SquidIPAlias {

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

        /**
     * @ORM\Column(type="bigint")
     */
    private $ip;

    /**
     * @ORM\Column(type="string", length=150)
     */
    private $alias;

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
     * Set ip
     *
     * @param bigint $ip
     */
    public function setIp($ip)
    {
        $this->ip = $ip;
    }

    /**
     * Get ip
     *
     * @return bigint 
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * Set alias
     *
     * @param string $alias
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;
    }

    /**
     * Get alias
     *
     * @return string 
     */
    public function getAlias()
    {
        return $this->alias;
    }
    
    public function __toString()
    {
        return ( strlen($this->getAlias()) < 1  ) ? long2ip( $this->getIp() ) : $this->getAlias();
    }
        
}
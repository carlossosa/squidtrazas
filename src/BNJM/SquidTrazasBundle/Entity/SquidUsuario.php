<?php

namespace BNJM\SquidTrazasBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="BNJM\SquidTrazasBundle\Repository\SquidUsuarioRepository")
 * @ORM\Table(name="squid_usuarios")
 */
class SquidUsuario {

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $username;    
    
    /**
     * @ORM\ManyToOne(targetEntity="QuotaUsuario", cascade={"all"}, fetch="EAGER")
     * @ORM\JoinColumn(name="quota_id", referencedColumnName="id")
     */
    private $quota;

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
     * Set username
     *
     * @param string $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * Get username
     *
     * @return string 
     */
    public function getUsername()
    {
        return $this->username;
    }
    
    public function __toString()
    {
        return $this->getUsername();
    }

    /**
     * Set quota
     *
     * @param BNJM\SquidTrazasBundle\Entity\QuotaUsuario $quota
     * @return SquidUsuario
     */
    public function setQuota(\BNJM\SquidTrazasBundle\Entity\QuotaUsuario $quota = null)
    {
        $this->quota = $quota;
    
        return $this;
    }

    /**
     * Get quota
     *
     * @return BNJM\SquidTrazasBundle\Entity\QuotaUsuario 
     */
    public function getQuota()
    {
        return $this->quota;
    }
}
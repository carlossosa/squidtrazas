<?php
namespace BNJM\SquidTrazasBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * @author Carlos Sosa <carlitin at gmail dot com>
 */
class SquidUsuarioRepository extends EntityRepository {
    
    public function searchUsuarios($user)
    {
        return $this->getEntityManager()
                        ->createQueryBuilder()
                        ->from( $this->_entityName, 't')
                        ->select("t.username")
                        ->where("t.username LIKE :user")
                        ->setParameter("user", "%".$user."%")
                        ->getQuery()
                        ->getArrayResult();
    }  
    
    public function getUsuarios()
    {
        return $this->getEntityManager()
                        ->createQueryBuilder()
                        ->from( $this->_entityName, 't')
                        ->select("t.username")
                        ->orderBy('t.username', 'ASC')
                        ->getQuery()
                        ->getArrayResult();
    }  
    
    public function getUserByUsername($param) {
        return $this->findOneBy(array('username'=>$param));
    }
}
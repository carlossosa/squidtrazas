<?php
namespace BNJM\SquidTrazasBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * @author Carlos Sosa <carlitin at gmail dot com>
 */
class SquidDomainRepository extends EntityRepository {
    public function getListExclude ()
    {
        $exclude_list = array(  "%bnjm.cu"
                            );
        
        $_Q = $this->createQueryBuilder('d')
//                    ->from($this->getEntityName(), 'd')
                    ->select('d.id')
                ;
        for ($i=0;$i<count($exclude_list);$i++)
            if ( $i==0)
            {
                $_Q->where ("d.domain LIKE :term".$i);
                $_Q->setParameter('term'.$i, $exclude_list[$i]);
            } else {
                $_Q->orWhere("d.domain LIKE :term".$i);
                $_Q->setParameter('term'.$i, $exclude_list[$i]);
            }
            $return = array();
            
        foreach ( $_Q->getQuery()->getArrayResult() as $_D)
            $return[] = $_D['id'];
        
        return $return;
    }        
    
    public function searchDomain($user)
    {
        return $this->getEntityManager()
                        ->createQueryBuilder()
                        ->from( $this->_entityName, 't')
                        ->select("t.domain")
                        ->where("t.domain LIKE :user")
                        ->setParameter("user", "%".$user."%")
                        ->getQuery()
                        ->setMaxResults(6)
                        ->getArrayResult();
    }
}
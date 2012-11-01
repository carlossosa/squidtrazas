<?php
class dbWork 
{
    private $db;
    private $user_list;
    private $user_quota;
    private $ip_list;
    private $last_domains;
    private $quota_horas;
    private $quota_megas;
    private $banuser;

    public function __construct( $user = 'root', $password = '', $database = 'squidtrazas', $host = 'localhost') 
    {
        $this->db = new mysqli($host, $user, $password, $database);        

        if ( $this->error() != null) 
        {
            throw new \Exception("Error al conectar al Base de Datos {".$this->error()."}");            
        } else {
            $this->fillLastDomains();
            $this->updateIPAlias();
            $this->updateUserList();
            //$this->updateQuotas();
            $this->banuser = array();
            $this->quotaCheck();
        }    
    }
    
    public function error() 
    {
        if ( $this->db->connect_error != null)
        {
            return $this->db->connect_error;
        }
        if ( $this->db->errno > 0)
        {
            return $this->db->error;
        }
        return false;
    }
    
    public function getLastTime()
    {
        $_q = $this->db->query("SELECT `time` FROM `squid_traza` ORDER BY `time` DESC LIMIT 1");
        if ( !$this->error() )
        {            
            if ( $_q->num_rows == 1)
            {            
                $_r = strtotime($_q->fetch_object()->time);
            } else {
                $_r = 0;
            }
            $_q->close();
            return $_r;
        } else {
            throw new \Exception('Error! ocurrio un error al intentar consultar el MySQL {'.$this->error().'}');
        }
    }
    
    /**
     * Insertar multiples lineas de una ves al SQL
     * @param array $lines 
     */
    public function insertLines( $lines){
        $SQLs = array();
        foreach ( $lines as $line)
        {
            $mime = ( trim($line['MIME']) == '-') ? "" : trim($line['MIME']);
            $user = ( trim($line['USERNAME']) == "-") ? 'NULL' : $this->fetchUser(strtolower($line['USERNAME']));
            
	    if ( $this->stripDomainFromUrl($line['URL']) != false) 
		{
			$domain = $this->fetchDomain($this->stripDomainFromUrl($line['URL']));
		}
		else {
			$domain = "'NULL'";
		}
                
            $url =  $this->fetchURL($line['URL']);
	
            $SQLs[] = "INSERT INTO `squid_traza` (".
                   "`id` , `usuario_id` , `ip_id` , `time` ,".
                   "`transfer_time` , `size` , `action` , `method` ,".
                   "`url_id` , `mime_type`, `domain_id` ) VALUES ( ".
                   "NULL , ". //ID
                   $user.", ". //Usuario
                   $this->fetchIP($line['IP']).", ". //IP
                   "'".date( "Y-m-d H:i:s", $line['TIME'])."', ". //TIME
                   $line['TTIME'].", ". //TransferTIME
                   $line['SIZE'].", ". //Size
                   "'".$line['PEDIDO']."/".$line['CODE']."', ". //Action
                   "'".$line['METHOD']."', ". // POST GET CONNECT
                   "'".$url."', ". // URL
                   "'". $mime."', ". //mime
                   $domain.
                   ");";
            
            if ( $user != 'NULL')
            {
                  $SQLs[] = "INSERT INTO `quotas_consumo` ( `usuario_id`, `time`, `horas`, `bytes`) VALUES ( ".$user.", DATE(NOW()), ".intval($line['TTIME']).", ".intval($line['SIZE']).")".
                           "ON DUPLICATE KEY UPDATE `horas`=`horas`+".intval($line['TTIME']).", `bytes`=`bytes`+".intval($line['SIZE']).";";
            }
	//echo $debug."\r\n";
        }
        $this->db->autocommit(false);
        foreach( $SQLs as $SQL )
        {
            $this->db->query($SQL);
            //file_put_contents('/tmp/debug', $SQL."\r\n", FILE_APPEND);
        }
        $this->db->commit();
        $this->db->autocommit(true);
    }
    
    public function insertUser ( $user){
        if ( $this->db->query('INSERT INTO `squid_usuarios` ( `id`, `username`, `quota_id` ) VALUES (NULL , \''.  $user .'\', 2);') )
        {
            $this->user_list[$user] = $this->db->insert_id;
            $this->user_quota[$user] = 2;
            return $this->db->insert_id;
        } else if ( !$this->error() )
        {
                       throw new \Exception('Error! ocurrio un error al intentar consultar el MySQL {'.$this->error().'}');
        }
    }
    
    public function insertIP ( $ip){
        if ( $this->db->query('INSERT INTO `squid_ip_aliases` (`id` ,`ip` ,`alias` ) VALUES (NULL , \''.  ip2long($ip).'\', \'\');') )
        {
            $this->ip_list[floatval(ip2long($ip))] = $this->db->insert_id;
            return $this->db->insert_id;
        } else if ( !$this->error() )
        {
                       throw new \Exception('Error! ocurrio un error al intentar consultar el MySQL {'.$this->error().'}');
        }
    }
    
    public function updateUserList ()
    {        
        $_q = $this->db->query("SELECT `squid_usuarios`.`id` AS id, `squid_usuarios`.`username` AS username, `squid_usuarios`.`quota_id` AS quota FROM `squid_usuarios`");
        if ( !$this->error() )
        {            
            if ( $_q->num_rows > 0)
            {
                $this->user_list = array();
                while ( $row = $_q->fetch_object() )
                {
                    $this->user_list[$row->username] = $row->id;
                    $this->user_quota[$row->username] = $row->quota;
                }
            }    
        } else {
                throw new \Exception('Error! ocurrio un error al intentar consultar el MySQL {'.$this->error().'}');
        }
    }
    
    public function updateIPAlias ()
    {        
        $_q = $this->db->query("SELECT `id`, `ip` FROM `squid_ip_aliases`");
        if ( !$this->error() )
        {            
            if ( $_q->num_rows > 0)
            {
                $this->ip_list = array();
                while ( $row = $_q->fetch_object() )
                {
                    $this->ip_list[strval($row->ip)] = $row->id;
                }
            }    
        } else {
            throw new \Exception('Error! ocurrio un error al intentar consultar el MySQL {'.$this->error().'}');
        }
    }
    
    public function fetchIPfromSql ( $ip)
    {
        $_q = $this->db->query("SELECT `id`, `ip` FROM `squid_ip_aliases` WHERE `ip`='".ip2long($ip)."' LIMIT 1");
        if ( !$this->error() )
        {            
            if ( $_q->num_rows == 1)
            {
                $row = $_q->fetch_object();
                $_q->close();
                $this->ip_list[strval($row->ip)] = $row->id; 
                return $row->id;
            } else {
                return $this->insertIP($ip);
            }    
        } else {
             throw new \Exception('Error! ocurrio un error al intentar consultar el MySQL {'.$this->error().'}');
        }
    }
    
    public function fetchUserFromSql ( $user)
    {
        $_q = $this->db->query("SELECT `id`, `username`, `quota_id` AS quota FROM `squid_usuarios` WHERE `username` = '".$user."' LIMIT 1");
        if ( !$this->error() )
        {            
            if ( $_q->num_rows == 1)
            {
                $row = $_q->fetch_object();
                $_q->close();
                $this->user_list[$row->username] = $row->id; 
                $this->user_quota[$row->username] = $row->quota; 
                return $row->id;
            } else {
                return $this->insertUser($user);
            }    
        } else {
            throw new \Exception('Error! ocurrio un error al intentar consultar el MySQL {'.$this->error().'}');
        }
    }
    
    public function fetchIP ( $ip)
    {        
        $_ip = ip2long($ip);
        if ( isset ($this->ip_list[strval($_ip)]) )
        {
            return $this->ip_list[strval($_ip)];
        } else {
            return $this->fetchIPfromSql($ip);
        }
    }
    
    public function fetchUser ( $user)
    {        
        if ( isset ($this->user_list[$user]) )
        {
            return $this->user_list[$user];
        } else {
            return $this->fetchUserFromSql($user);
        }
    }
    
    public function fetchDomain ( $domain)
    {
        if ( is_array($this->last_domains) && ( $pos = array_search( $domain, $this->last_domains)) )
        {
            return $this->last_domains[$pos];            
        } else {
            return $this->fetchDomainFromSql( $domain);
        }
    }
    
    public function fetchDomainFromSql ( $domain)
    {
        $_q = $this->db->query("SELECT `id`, `domain` FROM `squid_domains` WHERE `domain` = '".$domain."' LIMIT 1");
        if ( !$this->error() )
        {            
            if ( $_q->num_rows == 1)
            {
                $row = $_q->fetch_object();
                $_q->close();
                return $row->id;
            } else {
                return $this->insertDomain( $domain);
            }   
        } else {
            throw new \Exception('Error! ocurrio un error al intentar consultar el MySQL {'.$this->error().'}');
        }
    }
    
    public function fillLastDomains()
    {
        //todo;
    }
    
    public function checkCon ()
    {
        if ( $this->db->ping() )
        {
            return true;
        } else {
            return false;
        }
    }
    
    public function insertDomain ( $domain){
        if ( $this->db->query('INSERT INTO `squid_domains` (`id` ,`domain` ) VALUES (NULL , \''. $domain .'\');') )
        {           
            return $this->fetchDomainFromSql($domain);
        } else if ( !$this->error() )
        {
            throw new \Exception('Error! ocurrio un error al intentar consultar el MySQL {'.$this->error().'}');
        }
    }
    
    public function stripDomainAndFromFromUrl ( $url)
    {
        $regex = "#(^|^(http|https|ftp)://)".
                  "([\w\d-\.]+\.[a-z]{1,3}|localhost|\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})".
                  "(:(\d{2,5})|)+($|/)#";
        $array = array();
        preg_match( $regex, $url, $array);
        if ( filter_var($array[3], FILTER_VALIDATE_IP) ) 
        {
            return false;
        } else { 
            return array( 'DOMAIN' => $array[3], 'PUERTO' => $array[5]);
        }
    }

    public function stripDomainFromUrl ( $url)
	{
		$_a = $this->stripDomainAndFromFromUrl ( $url);
		return ( $_a != false ) ? $_a['DOMAIN'] : $_a;
	}
       
    public function fetchURL ( $url)
    {
//        if ( $pos = array_search( $url, $this->last_domains))
//        {
//            return $this->last_domains[$pos];            
//        } else {
            return $this->fetchURLFromSql( $url);
//        }
    }
    
    public function fetchURLFromSql ( $url)
    {
        $_q = $this->db->query("SELECT `id`, `url` FROM `squid_urls` WHERE `url` = '".$url."' LIMIT 1");
        if ( !$this->error() )
        {            
            if ( $_q->num_rows == 1)
            {
                $row = $_q->fetch_object();
                $_q->close();
                return $row->id;
            } else {
                $this->insertURL($url);
            }   
        } else {
            throw new \Exception('Error! ocurrio un error al intentar consultar el MySQL {'.$this->error().'}');
        }
    }
    
    public function insertURL ( $url){
        if ( $this->db->query('INSERT INTO `squid_urls` (`id` ,`url` ) VALUES (NULL , \''. $url .'\');') )
        {           
            return $this->fetchURLFromSql($url);
        } else if ( !$this->error() )
        {
            throw new \Exception('Error! ocurrio un error al intentar consultar el MySQL {'.$this->error().'}');
        }
    }
    
    public function updateQuotas ( )
    {
        $q = $this->db->query("SELECT * FROM `quotas_user`");        
        if ( !$this->error())
        {
            if ( $q->num_rows > 0)
            {
                $this->quota_horas = array();
                $this->quota_megas = array();
                while ( $row = $q->fetch_object() )
                {
                    $this->quota_horas[$row->id] = $row->quotaMegas;
                    $this->quota_megas[$row->id] = $row->quotaMegas;
                }
            }
        } else {
            throw new \Exception('Error! ocurrio un error al intentar consultar el MySQL {'.$this->error().'}');
        }
    }
    
    public function quotaCheck ()
    {
        $_q = "SELECT h.name AS nombre FROM". 
                    " (SELECT u.username AS name, ".
                             "c.usuario_id AS user, ".
                             "(q.quotaHoras*1000) AS q_Horas, ".
                             "q.quotaMegas AS q_Megas, ".
                             "sum(c.horas) AS horas, ".
                             "sum(c.bytes) AS bytes, ".
                             "c.time ".
                    "FROM quotas_consumo c ".
                        "LEFT JOIN squid_usuarios u ".
                                "ON c.usuario_id=u.id ".
                        "LEFT JOIN quotas_user q ".
                                "ON u.quota_id=q.id ".
                    "WHERE c.time >= '".date("Y-m")."-1' ".
                            "AND c.time <= '".date("Y-m-d")."-1'".
                    "GROUP BY user) h ".
                "WHERE (h.q_Horas < h.horas ".
                        "AND h.q_Horas != 0) ".
                    "OR (h.q_Megas < h.bytes ".
                        "AND h.q_Megas != 0)";
        $q = $this->db->query($_q);
        if ( !$this->error() )
        {            
            if ( $q->num_rows > 0)
            {
                $r = array();
                while ( $row = $q->fetch_object() )
                {
                    $r[] = $row->nombre;
                }
                return $r;
             }    else {
                 return array();
             }
            
        } else {
                throw new \Exception('Error! ocurrio un error al intentar consultar el MySQL {'.$this->error().'}');
        }        
    }
}
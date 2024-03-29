<?php
class dbWork 
{
    private $db;
    private $user_list;
    private $ip_list;
    private $last_domains;

    public function __construct( $user = 'root', $password = '', $database = 'squidtrazas', $host = 'localhost') 
    {
        $this->db = new mysqli($host, $user, $password, $database);        

        if ( $this->error() != null) 
        {
            throw new \ErrorException("Error al conectar al Base de Datos {".$this->error()."}");            
        } else {
            $this->fillLastDomains();
            $this->updateIPAlias();
            $this->updateUserList();
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
            throw new \ErrorException('Error! ocurrio un error al intentar consultar el MySQL {'.$this->error().'}');
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
        if ( $this->db->query('INSERT INTO `squid_usuarios` ( `id`, `username` ) VALUES (NULL , \''.  $user .'\');') )
        {
            $this->user_list[$user] = $this->db->insert_id;
            return $this->db->insert_id;
        } else if ( !$this->error() )
        {
                       throw new \ErrorException('Error! ocurrio un error al intentar consultar el MySQL {'.$this->error().'}');
        }
    }
    
    public function insertIP ( $ip){
        if ( $this->db->query('INSERT INTO `squid_ip_aliases` (`id` ,`ip` ,`alias` ) VALUES (NULL , \''.  ip2long($ip).'\', \'\');') )
        {
            $this->ip_list[floatval(ip2long($ip))] = $this->db->insert_id;
            return $this->db->insert_id;
        } else if ( !$this->error() )
        {
                       throw new \ErrorException('Error! ocurrio un error al intentar consultar el MySQL {'.$this->error().'}');
        }
    }
    
    public function updateUserList ()
    {        
        $_q = $this->db->query("SELECT * FROM `squid_usuarios`");
        if ( !$this->error() )
        {            
            if ( $_q->num_rows > 0)
            {
                $this->user_list = array();
                while ( $row = $_q->fetch_object() )
                {
                    $this->user_list[$row->username] = $row->id;
                }
            }    
        } else {
                        throw new \ErrorException('Error! ocurrio un error al intentar consultar el MySQL {'.$this->error().'}');
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
            throw new \ErrorException('Error! ocurrio un error al intentar consultar el MySQL {'.$this->error().'}');
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
             throw new \ErrorException('Error! ocurrio un error al intentar consultar el MySQL {'.$this->error().'}');
        }
    }
    
    public function fetchUserFromSql ( $user)
    {
        $_q = $this->db->query("SELECT `id`, `username` FROM `squid_usuarios` WHERE `username` = '".$user."' LIMIT 1");
        if ( !$this->error() )
        {            
            if ( $_q->num_rows == 1)
            {
                $row = $_q->fetch_object();
                $_q->close();
                $this->user_list[$row->username] = $row->id; 
                return $row->id;
            } else {
                return $this->insertUser($user);
            }    
        } else {
            throw new \ErrorException('Error! ocurrio un error al intentar consultar el MySQL {'.$this->error().'}');
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
            throw new \ErrorException('Error! ocurrio un error al intentar consultar el MySQL {'.$this->error().'}');
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
            throw new \ErrorException('Error! ocurrio un error al intentar consultar el MySQL {'.$this->error().'}');
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
            throw new \ErrorException('Error! ocurrio un error al intentar consultar el MySQL {'.$this->error().'}');
        }
    }
    
    public function insertURL ( $url){
        if ( $this->db->query('INSERT INTO `squid_urls` (`id` ,`url` ) VALUES (NULL , \''. $url .'\');') )
        {           
            return $this->fetchURLFromSql($url);
        } else if ( !$this->error() )
        {
            throw new \ErrorException('Error! ocurrio un error al intentar consultar el MySQL {'.$this->error().'}');
        }
    }
}
?>

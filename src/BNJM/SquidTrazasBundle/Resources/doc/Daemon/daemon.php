#! /usr/bin/php
<?php
$runmode = array(
    'help' => false,
    'write-initd' => false,
);
 
// Scan command line attributes for allowed arguments
foreach ($argv as $k=>$arg) {
    if (substr($arg, 0, 2) == '--' && isset($runmode[substr($arg, 2)])) {
        $runmode[substr($arg, 2)] = true;
    }
}
 
// Help mode. Shows allowed argumentents and quit directly
if ($runmode['help'] == true) {
    echo 'Usage: '.$argv[0].' [runmode]' . "\n";
    echo 'Available runmodes:' . "\n";
    foreach ($runmode as $runmod=>$val) {
        echo ' --'.$runmod . "\n";
    }
    die();
}
 
// Make it possible to test in source directory
// This is for PEAR developers only
ini_set('include_path', ini_get('include_path').':..');
require_once "System/Daemon.php";                 // Include the Class

// Setup
$options = array(
    'appName' => 'squidlogtrazas',
    'appDir' => dirname(__FILE__),
    'appDescription' => 'Analiza los Logs de Squid en MySQL',
    'authorName' => 'BNJM',
    'authorEmail' => 'admin@bnjm.cun',
    'sysMaxExecutionTime' => '0',
    'sysMaxInputTime' => '0',
    'sysMemoryLimit' => '1024M',
    'appRunAsGID' => 0,
    'appRunAsUID' => 0,
);
 
System_Daemon::setOptions($options);

// With the runmode --write-initd, this program can automatically write a
// system startup file called: 'init.d'
// This will make sure your daemon will be started on reboot
if (!$runmode['write-initd']) {
    System_Daemon::info('not writing an init.d script this time');
} else {
    if (($initd_location = System_Daemon::writeAutoRun()) === false) {
        System_Daemon::notice('unable to write init.d script');
    } else {
        System_Daemon::info(
            'sucessfully written startup script: %s',
            $initd_location
        );
    }
}

/**CODE**/

/**
 * Manejo de la BD 
 */
class dbWork 
{
    private $db;
    private $user_list;
    private $ip_list;
    

    public function __construct( $user = 'root', $password = '', $database = 'squidtrazas', $host = 'localhost') 
    {
        $this->db = new mysqli($host, $user, $password, $database);        

        if ( $this->error() != null) 
        {
            System_Daemon::err('{{appName}} Ha ocurrido un error al conectar con la base de datos : %s', $this->error());            
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
            System_Daemon::err('{{appName}} Ha ocurrido un error con la base de datos : %s', $this->error());
            return false;
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
            
            $SQLs[] = "INSERT INTO `squid_traza` (".
                   "`id` , `usuario_id` , `ip_id` , `time` ,".
                   "`transfer_time` , `size` , `action` , `method` ,".
                   "`url` , `mime_type` ) VALUES ( ".
                   "NULL , ". //ID
                   $user.", ". //Usuario
                   $this->fetchIP($line['IP']).", ". //IP
                   "'".date( "Y-m-d H:i:s", $line['TIME'])."', ". //TIME
                   $line['TTIME'].", ". //TransferTIME
                   $line['SIZE'].", ". //Size
                   "'".$line['PEDIDO']."/".$line['CODE']."', ". //Action
                   "'".$line['METHOD']."', ". // POST GET CONNECT
                   "'".$line['URL']."', ". // URL
                   "'". $mime."'". //mime
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
           System_Daemon::err('{{appName}} Ha ocurrido un error con la base de datos : %s', $this->error()); 
           return 'NULL';
        }
    }
    
    public function insertIP ( $ip){
        if ( $this->db->query('INSERT INTO `squid_ip_aliases` (`id` ,`ip` ,`alias` ) VALUES (NULL , \''.  ip2long($ip).'\', \'\');') )
        {
            $this->ip_list[floatval(ip2long($ip))] = $this->db->insert_id;
            return $this->db->insert_id;
        } else if ( !$this->error() )
        {
           System_Daemon::err('{{appName}} Ha ocurrido un error con la base de datos : %s', $this->error()); 
           return 'NULL';
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
            System_Daemon::err('{{appName}} Ha ocurrido un error con la base de datos : %s', $this->error());            
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
            System_Daemon::err('{{appName}} Ha ocurrido un error con la base de datos : %s', $this->error());            
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
            System_Daemon::err('{{appName}} Ha ocurrido un error con la base de datos : %s', $this->error());
            return 'NULL';
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
            System_Daemon::err('{{appName}} Ha ocurrido un error con la base de datos : %s', $this->error());
            return 'NULL';
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
}

/***/
 
function lineParse ( $str) 
{
    $array = array();
    $regex = "/^(\d+)\.\d{1,3}\s+". //TIME 1
               "(\d+)\s". //SIZE 2
               "(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})\s". //IP 3
               "([\w_]+)\/". //PETICION 4
               "(\d{3,3})\s". //ERROR CODE 5
               "(\d+)\s". // TIME 6
               "(\w+)\s". // POST GET CONNECT 7
               "([a-zA-Z\d:\/\.\?\!\#\=_-]+)\s". //URL 8
               "([a-zA-Z\d\#\!\_\?\=\-]+)\s\w+\/[\w\d\._-]+\s". //USERNAME 9
               "(.*)/"; //MIME TYPE 10
    if ( preg_match($regex, $str, $array) ) {
        return array(   'RAW'  => $array[0],
                        'TIME' => floatval($array[1]),
                        'SIZE' => floatval($array[2]),
                        'IP'   => $array[3],
                        'PEDIDO' => $array[4],
                        'CODE' => $array[5],
                        'TTIME' => floatval($array[6]),
                        'METHOD' => $array[7],
                        'URL' => $array[8],
                        'USERNAME' => $array[9],
                        'MIME' => $array[10]);
    }
    return false;
}

function analizarLinea ( $line)
{
    return lineParse( $line);
}

function cargarEstado() {
    $statfile = "/tmp"."/"."daemonstat.dat";
    
    if (file_exists($statfile))
    {
        $estado = unserialize(file_get_contents($statfile));
    } else {     
        $estado = array (   'Line' => null,
                            'Time' => 0,
                            'Pos'  => 0,
                            'Size' => 0);
    }
    return $estado;
}

function guardarEstado($estado) {
    $statfile = "/tmp"."/"."daemonstat.dat";
    
    file_put_contents( $statfile, serialize($estado));
}

System_Daemon::start();
/**VARS FOR WHILE */
$estado = null; 
$log_file = "/var/log/squid3/squid.log";
$mysql = null;
while (!System_Daemon::isDying()) {
    /** DDBB */
    if ( $mysql == null)
    {
        $mysql = new dbWork('root', 'imbecil');
    }
    
    /**
     * ESTADO SECTION 
     */
    if ( $estado == null ) {
        $estado = cargarEstado();
        $estado['Time'] = $mysql->getLastTime();
        System_Daemon::info("{{appName}} Cargando estado %s.", $estado['Time']); 
    } 
    /**
     * END ESTADO 
     */
    
    /**
     * FILE SECTION 
     */
    $file = fopen($log_file, 'r');
    
    if (is_resource($file) )
    {
        if ( $estado['Pos'] > 0 && filesize($log_file) > $estado['Size']) 
            {                   
                fseek( $file, $estado['Pos']);
                //System_Daemon::info("{{appName}} Fseek: %s, pos: %s, size: %s.", $estado['Pos'], ftell($file), filesize($log_file));
            }

        $line = null; $_line = null; $lines = 0; $_proc = array();
        $lines_ciclos = 0; $lines_actualizar = 100;
        
        while ( !feof($file))
            {                
                $line = fgets($file);
                if ( $line != "")
                {
                    $_line = lineParse($line);
                    if ( floatval($_line['TIME']) > floatval($estado['Time']) )
                    {
                        if ( $lines_ciclos<$lines_actualizar)
                            {
                                $_proc[] = $_line;
                                $lines_ciclos++;
                            } else {
                                $lines_ciclos = 0;
                                $mysql->insertLines($_proc);
                                $_proc = array();
                            }
                        $lines++;
                    }                    
                }
            }
        if ( count($_proc) > 0)
            $mysql->insertLines($_proc);        
        
        //Actualiza Estado
        $estado['Line'] = $_line['RAW'];
        $estado['Pos']  = ftell($file);      
        $estado['Size'] = filesize($log_file);
        if ( $lines > 0)
        {
            $estado['Time'] = $_line['TIME'];
            System_Daemon::info("{{appName}} Actualizadas %s líneas nuevas.", $lines); 
        }
        unset($_proc,$line,$_line,$lines_ciclos,$lines_actualizar,$lines);
    } else {
        System_Daemon::err("{{appName}} No se ha podido cargar el archivo de Logs del Squid.");
    }
    /**
     * END SECTION 
     */
    
    /** UPDATE ESTADO */
    guardarEstado($estado);    
    /** END ESTADO */    
    
    fclose($file);    
    System_Daemon::iterate( 2); //NOW Ciclo cada 10Segundos pero mejor sería cada 5minutos
    //System_Daemon::stop();
}
 
// Shut down the daemon nicely
// This is ignored if the class is actually running in the foreground
System_Daemon::stop();
?>

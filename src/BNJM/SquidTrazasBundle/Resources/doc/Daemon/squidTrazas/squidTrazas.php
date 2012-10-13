#!/usr/bin/php -q
<?php
/**
 * System_Daemon squidTrazas
 *
 * If you run this code successfully, a daemon will be spawned
 * but unless have already generated the init.d script, you have
 * no real way of killing it yet.
 *
 * In this case wait 3 runs, which is the maximum for this example.
 *
 *
 * In panic situations, you can always kill you daemon by typing
 *
 * killall -9 logparser.php
 * OR:
 * killall -9 php
 *
 */
require "mysql.class.php";
require "squidTrazas.class.php";
 
// Allowed arguments & their defaults
$runmode = array(
    'no-daemon' => false,
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
 
// Include Class
error_reporting(E_STRICT);
require_once 'System/Daemon.php';
 
// Setup
$options = array(
    'appName' => 'squidtrazas',
    'appDir' => dirname(__FILE__),
    'appDescription' => 'Carga los logs del Squid en MySQL',
    'authorName' => 'BNJM',
    'authorEmail' => 'admin@bnjm.cu',
    'sysMaxExecutionTime' => '0',
    'sysMaxInputTime' => '0',
    'sysMemoryLimit' => '1024M',
    'appRunAsGID' => 0,
    'appRunAsUID' => 0,
);
 
System_Daemon::setOptions($options);
 
// This program can also be run in the forground with runmode --no-daemon
if (!$runmode['no-daemon']) {
    // Spawn Daemon
    System_Daemon::start();
}
 
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
 
// Run your code
// Here comes your own actual code
 
// This variable gives your own code the ability to breakdown the daemon:
$runningOkay = true;
 
//VARS
$trazas = null;
$file = "/var/log/squid3/access.log";
$datos = "/tmp/squidTrazas.db";
$mysql = array ( 'user' => 'root',
                 'pass' => '',
                 'host' => 'localhost',
                 'ddbb' => 'squidtrazas' );

while (!System_Daemon::isDying() && $runningOkay ) {
   
    if ( $trazas == null)    
    {
        $trazas = new squidTrazas($file, $datos, $mysql);        
    } 
    
    if ( $trazas->allOK() )
    {
        try {
            $n = $trazas->importLines();
        } catch (ErrorException $exc) {
            System_Daemon::err("{{appName Error: %s}}", $exc->getMessage());
            $trazas = null;
        }
        if ( $n > 0 )
        {
            System_Daemon::err("{{appName Lineas procesadas: %s}}", $n);
        }
     } else {
         System_Daemon::err("{{appName Error: %s}}", $exc->getMessage());
         $trazas = null;
     }
    
    System_Daemon::iterate(10);     
}
 
// Shut down the daemon nicely
// This is ignored if the class is actually running in the foreground
System_Daemon::stop();
?>

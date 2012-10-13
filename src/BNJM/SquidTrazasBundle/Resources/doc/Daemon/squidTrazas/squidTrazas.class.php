<?php
class squidTrazas {
    private $db;
    private $file;
    private $file_data;
    private $estado;
    
    public function __construct( $file, $datos, $mysql) {        
            $this->db = new dbWork( $mysq['user'], $mysq['pass'], $mysq['ddbb'], $mysq['host']);
            $this->file = $file;   
            $this->cargarEstado();
    }
    
    public function __destruct() {
        $this->guardarEstado();
    }


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

    function cargarEstado() {
        if (file_exists($this->file_data))
        {
            $this->estado = unserialize(file_get_contents($this->file_data));
        } else {     
            $this->estado = array ( 'Line' => null,
                                    'Time' => 0,
                                    'Pos'  => 0,
                                    'Size' => 0);
        }
    }   
    
    function guardarEstado() {
        file_put_contents( $this->file_data, serialize($this->estado));
    }
    
    function importLines ()
    {
        if ( !$this->db->checkCon() )
            return 0;
            
        $file = fopen($this->file, 'r');
    
        if (is_resource($file) )
        {
            if ( $this->estado['Pos'] > 0 && filesize($this->file) > $this->estado['Size']) 
                {                   
                    fseek( $file, $this->estado['Pos']);
                }

            $line = null; $_line = null; $lines = 0; $_proc = array();
            $lines_ciclos = 0; $lines_actualizar = 100;

            while ( !feof($file))
                {                
                    $line = fgets($file);
                    if ( $line != "")
                    {
                        $_line = $this->lineParse($line);
                        if ( floatval($_line['TIME']) > floatval($this->estado['Time']) )
                        {
                            if ( $lines_ciclos<$lines_actualizar)
                                {
                                    $_proc[] = $_line;
                                    $lines_ciclos++;
                                } else {
                                    $lines_ciclos = 0;
                                    $this->db->insertLines($_proc);
                                    $_proc = array();
                                }
                            $lines++;
                        }                    
                    }
                }
            if ( count($_proc) > 0)
                $this->db->insertLines($_proc);        

            //Actualiza Estado
            $this->estado['Line'] = $_line['RAW'];
            $this->estado['Pos']  = ftell($file);      
            $this->estado['Size'] = filesize($this->file);
            if ( $lines > 0)
            {
                $this->estado['Time'] = $_line['TIME'];                                
            }
            unset($_proc,$line,$_line,$lines_ciclos,$lines_actualizar);                        
            $this->guardarEstado();
            return $lines;
        } 
        return 0;        
    }
}
?>

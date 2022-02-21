<?php

namespace ApiRest\WorkerBundle\Controller;
use ApiRest\WorkerBundle\Controller\Trazas;
use ApiRest\WorkerBundle\Controller\Isonomia;

/**
 * Clase que carga los datos de los archivos de datos a memoria
 * Originalmente, el unico tipo de archivo de datos que se admitía era .csv separado por ;. Se ha modificado para que admita también archivos .json y .csv separados por ,
 */
class Csv
{
    // Carpeta donde se encuentran los archivos del proceso
    protected $carpeta;
    //Objeto de log
 	protected $trazas;

 
    // Get trazas
	public function getTrazas()
	{
		return $this->trazas;
    }
    
    /**
     * Constructor
     * Parámetro: $carpeta: Carpeta donde se encuentran los archivos del proceso
	 */
    public function __construct($carpeta, $trazas) {
    
        $this->carpeta = $carpeta;
        $this->trazas = $trazas;
        $this->trazas->setClase("Csv");
        $this->trazas->LineaDebug("__construct", "Inicia el constructor");  
    }

     /**
     * Abre el archivo de datos del objeto y devuelve un array múltiple, con  cabecera y datos  
     * Devuelve: $data
	 */
    function DameCsv(){
        $data = array();
        if (!empty($this->carpeta)) {
            $nombreFichero =  $this->carpeta . "/datos.csv";
            $nombreFicheroJson = $this->carpeta . "/datos.json";
            //Comprueba la existencia de ambos archivos, sólo puede haber uno de ellos
            if (file_exists($nombreFichero)) { 
                //Si el archivo es csv
                $this->trazas->LineaInfo("DameCsv",$nombreFichero .": Fichero CSV encontrado");      
                if (($gestor = fopen($nombreFichero, "r")) !== FALSE) {
                    $this->trazas->LineaDebug("DameCsv","Fichero CSV abierto lectura"); 
                    //Comprueba el caracter delimitador del csv
                    $header = fgetcsv($gestor, 0, ";"); 
                    $delimitador = ";";
                    if (count($header)<=1){
                        $gestor = fopen($nombreFichero, "r");  //Volvemos al punto de inicio para leer la primera fila, si no estamos leyendo la segunda
                        $header = fgetcsv($gestor, 0, ",");
                        $delimitador = ",";
                    }
                    $line_count = -1; // para ignorar el limite del bucle
                    $this->trazas->LineaInfo("DameCsv","El delimitador es: " . $delimitador); 

                    while (($row = fgetcsv($gestor, 0, $delimitador)) !== FALSE)
                    {
                        $this->trazas->LineaInfo("DameCsv","\$header[0] " . $header[0]); 
                        //Recorre el archivo para crear un array con los datos
                        foreach ($header as $i => $heading_i)
                        {
                            //$this->trazas->LineaInfo("DameCsv","El valor de header es: " . $heading_i);
                            //addslashes se ocupa de escapar los caracteres que sean necesarios
                            $row_new[$heading_i] = addslashes($row[$i]);
                            $this->trazas->LineaInfo("DameCsv","El valor añadido es: " . $row_new[$heading_i]);
                        }
                        $data[] = $row_new;
                    }
                    $this->trazas->LineaInfo("DameCsv", sprintf("Leídas %d filas", count($data)));
                    fclose($gestor);
                    $this->trazas->LineaInfo("DameCsv","Fichero CSV leído y cerrado"); 
                }
            } else if (file_exists($nombreFicheroJson)){
                //El archivo es json
                $this->trazas->LineaDebug("DameCsv", $nombreFicheroJson . ": Fichero JSON encontrado");
                if (($gestor = fopen($nombreFicheroJson, "r")) !== FALSE) {
                    $this->trazas->LineaDebug("DameCsv","Fichero JSON abierto lectura"); 
                    //La funcion decode genera un array de datos a partir del json, separados por , 
                    $stringJson = file_get_contents($nombreFicheroJson);
                    $data = json_decode($stringJson, true);
                    fclose($gestor);
                    $this->trazas->LineaInfo("DameCsv","Fichero JSON leído y cerrado"); 
                }
            } 
            else {
                $this->trazas->LineaError("DameCsv", "Fichero de datos no encontrado");
            }
        } else {
            $trazas->LineaError("DameCsv","Carpeta del proceso no encontrada");
        }
        return $data; 
    }

    //funcion que se invoca unicamente desde el servicio api de actulizacion por sujeto postEntitiesAction
    //la funcion lee y devuelve todos los sujetos de las triples del archivo datos.csv 
    //que son las url a encontrar para el borrado
    function dameUris () {
        //analogamente, busca .csv y .json
        //En este caso, solo se queda con el primer campo, que es el de las URIs de recursos a insertar
        $this->trazas->LineaDebug("dameUris"," Obtengo URIs de recursos a borrar ");   
		$uris = array ();
		if (!empty($this->carpeta)) {
            $nombreFichero =  $this->carpeta . "/datos.csv";
            $nombreFicheroJson = $this->carpeta . "/datos.json";
            if (file_exists($nombreFichero)) { 
                $this->trazas->LineaDebug("dameUris",$nombreFichero .": Fichero CSV encontrado");      
                if (($gestor = fopen($nombreFichero, "r")) !== FALSE) {
                    $this->trazas->LineaDebug("dameUris","Fichero CSV abierto lectura"); 
                    //Comprueba el caracter delimitador del csv
                    $header = fgetcsv($gestor, 0, ";"); 
                    $delimitador = ";";
                    if (count($header)<=1){
                        $header = fgetcsv($gestor, 0, ",");
                        $delimitador = ",";
                    }
                    $line_count = -1; //para ignorar el limite del bucle
                    while (($row = fgetcsv($gestor, 0, $delimitador)) !== FALSE)
                    {
                       if (!in_array ($row [0], $uris)) {
						   $uris[] = $row [0];
                           $this->trazas->LineaDebug("dameUris"," URI " . $row [0]);
					   }
                        
                    }
                    $this->trazas->LineaDebug("dameUris", sprintf("Leídas %d uris", count($uris)));
                    fclose($gestor);
                    $this->trazas->LineaDebug("dameUris","Fichero CSV leído y cerrado"); 
                }
            } else if(file_exists($nombreFicheroJson)){
                //el fichero es json
                $this->trazas->LineaDebug("dameUris",$nombreFichero .": Fichero JSON encontrado");
                if (($gestor = fopen($nombreFicheroJson, "r")) !== FALSE) {

                    $this->trazas->LineaDebug("DameCsv","Fichero JSON abierto lectura");  
                    $stringJson = file_get_contents($nombreFicheroJson);
                    $data = json_decode($stringJson, true);

                    foreach ($data as $row)
                    {
                       if (!in_array ($row ["URI"], $uris)) {
						   $uris[] = $row ["URI"];
                           $this->trazas->LineaDebug("dameUris"," URI " . $row ["URI"]);
					   }
                        
                    }
                    $this->trazas->LineaDebug("dameUris", sprintf("Leídas %d uris", count($uris)));
                    fclose($gestor);
                    $this->trazas->LineaDebug("dameUris","Fichero JSON leído y cerrado"); 
                }
            } else {
                $this->trazas->LineaError("dameUris",$nombreFichero .": Fichero de datos no encontrado");
            }
        } else {
            $trazas->LineaError("dameUris","Carpeta del proceso no encontrada");
        }	       
		return $uris;
        
	}

}
  
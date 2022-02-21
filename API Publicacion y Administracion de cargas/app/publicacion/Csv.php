<?php

require_once("Trazas.php");

class Csv
{
    /**
	 * Carpeta donde se encuentran los archivos del proceso
 	 */
    protected $carpeta;

     /**
	 * Objeto de log
 	 */
    protected $trazas;

    /**
     * Constructor
     * Parámametro: $carpeta: Carpeta donde se encuentran los archivos del proceso
	 */

    /**
	* Get Traza
	*
	*/
	public function getTrazas()
	{
		return $this->trazas;
    }
    
    public function __construct($carpeta, $trazas) {
    
        $this->carpeta = $carpeta;
        $this->trazas = $trazas;
        $this->trazas->setClase("Csv");
        $this->trazas->LineaInfo("__construct", "Inicializa el costructor");  
    }

     /**
     * Abre el archivo Csv del objeto y devuelve un array múltiple, con  cabecera y datos  
     * Ddevuelve: $data
     *     // $logger->error('Uh Oh!'); // Will be logged
    *   $logger->info('Something Happened Here'); // Will be NOT logged
	 */
    function DameCsv(){
        $data = array();
        if (!empty($this->carpeta)) {
            $nombreFichero =  $this->carpeta . "/datos.csv";
            if (file_exists($nombreFichero)) { 
                $this->trazas->LineaInfo("DameCsv",$nombreFichero .": Fichero CSV encontrado");      
                if (($gestor = fopen($nombreFichero, "r")) !== FALSE) {
                    $this->trazas->LineaInfo("DameCsv","Fichero CSV abierto lectura"); 
                    $header = fgetcsv($gestor, 1000, ";"); 
                    $line_count = -1; // so loop limit is ignored
                    while (($row = fgetcsv($gestor, 1000, ";")) !== FALSE)
                    {
                        foreach ($header as $i => $heading_i)
                        {
                            $row_new[$heading_i] = $row[$i];
                        }
                        $data[] = $row_new;
                    }
                    $this->trazas->LineaInfo("DameCsv", sprintf("Leidas %d filas", count($data)));
                    fclose($gestor);
                    $this->trazas->LineaInfo("DameCsv","Fichero CSV leido y cerrado"); 
                }
            } else {
                $this->trazas->LineaError("DameCsv",$nombreFichero .": Fichero CSV no encontrado");
            }
        } else {
            $trazas->LineaError("DameCsv","Carpeta del proceso no encontrada");
        }
        return $data; 
    }

}
  
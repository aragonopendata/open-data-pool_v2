
<?php

use Katzgrau\KLogger\Logger;
use Psr\Log\LogLevel;
/**
* Clase para dar mas soporte a la clase de log, añadiendo el nombre de la clase y la función
* También se utiliza para almacenar el log en memoria y escribirlo de una sola vez al final
* del proceso
 */
class Trazas
{

    /**
     * array de trazas de error
     */
    protected $conError;

    /**
     * Nombre de la clase donde esta el proceso
     */
    protected $clase;


    /**
     * Nombre de la clase donde esta el proceso
     */
    protected $logger;

    /**
	* Set clase
	*
	*/
	public function SetClase($clase)
	{
		return $this->clase = $clase;
    }
    

    /**
     * Constructor inicializo los Arrays
     */
    public function __construct($pathNoporcesados) {
    
        $this->conError = FALSE;
        $this->logger = new Logger($pathNoporcesados);
    }

    /**
     * Añado una fila al contenedor de trazas info
     */
    public function LineaInfo($funcion, $traza) 
    {
        $this->logger->debug($this->DameLineaLog($this->clase, $funcion, $traza)); 
    }

    /**
     * Añado una fila al contenedor de trazas error
     */
    public function LineaError($funcion, $traza) 
    {
        $this->logger->error($this->DameLineaLog($this->clase, $funcion, $traza)); 
        $this->conError = TRUE;
    }

    /**
     * Devuelve si tengo error
     */
    public function SinError() 
    {
        return !($this->conError);
    }

     /**
     * Devuelve la ruta del archivo log
     */

    public Function DamePath(){
        return $this->logger->getLogFilePath();
    }

    /**
     * Da formato al log
     */
    private function DameLineaLog($clase, $funcion, $traza)
    {
        return sprintf("[%s:%s] %s", $clase,$funcion,$traza);
    }



}
    


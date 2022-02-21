<?php

require_once("Trazas.php");

/**
* Triple contenedor de triples 
* La clase tiene las funcionalidades: 
*  1 -Almacenar las propiedades de las triples para ser parseadas por los valores
*  2.-Generar las triples parseando por los valores del CSV 
*  3.-Generar las triples sparq para pasarlas a virtuoso
*/
class Triple
{
	/**
	* sujeto de la triple
	*/
	protected $sujeto;

	/**
    * verbo de la triple
	*/
	protected $verbo;

	/**
    * predicado de la triple
	*/
	protected $predicado;

    /**
	* sujeto de la triple
	*/
	protected $sujetoParseo;

	/**
    * verbo de la triple
	*/
	protected $verboParseo;

	/**
    * predicado de la triple
	*/
	protected $predicadoParseo;

    /**
	* valor sujeto de la triple
	*/
	protected $sujetoValor;

	/**
    * valor verbo de la triple
	*/
	protected $verboValor;

	/**
    * valor predicado de la triple
	*/
	protected $predicadoValor;

	/** 
	 * fila del archivo CVS
	*/
	protected $filaCVS; 
	
	/** 
	* tripleValor
	*/
	protected $tripleValor; 

	/** 
	* objeto traza
	*/
	protected $trazas; 

	/**
	* Get Traza
	*
	*/
	public function getTrazas()
	{
		return $this->trazas;
	}

	/**
	* Get tripleValor
	*
	*/
	public function getTripleValor()
	{
		return $this->tripleValor;
	}


	/**
	* Get filaCVS
	*
	*/
	public function getFilaCVS()
	{
		return $this->filaCVS;
	}

    /**
    * Set filaCVS
	*/
	public function setFilaCVS($filaCVS)
	{
		$this->filaCVS = $filaCVS;
	}


	/**
	* Get sujeto
	*
	*/
	public function getSujeto()
	{
		return $this->sujeto;
	}

	/**
    * Set sujeto
	*/
	public function setSujeto($sujeto)
	{
		$this->sujeto = $sujeto;
	}


	/**
	* Get sujeto Parseo
	*
	*/
	public function getSujetoParseo()
	{
		return $this->sujetoParseo;
	}

	/**
    * Set sujeto Parseo
	*/
	public function setSujetoParseo($sujetoParseo)
	{
		$this->sujetoParseo = $sujetoParseo;
	}

	/**
    * Set sujeto Valor
	*/
	public function setSujetoValor($sujetoValor)
	{
		$this->sujetoValor = $sujetoValor;
	}
    /**
	* Get verbo
	*
	*/
	public function getVerbo()
	{
		return $this->verbo;
	}

	/**
	* Set verbo
	*/
	public function setVerbo($verbo)
	{
		$this->verbo = $verbo;	
    }
	
	
    /**
	* Get verbo Parseo
	*
	*/
	public function getVerboParseo()
	{
		return $this->verboParseo;
	}

	/**
	* Set verbo Parseo
	*/
	public function setVerboParseo($verboParseo)
	{
		$this->verbo = $verboParseo;	
	}
	
	/**
	* Set verbo Valor
	*/
	public function setVerboValor($verboValor)
	{
		$this->verbo = $verboValor;	
	}
	

	/**
	* Get predicado 
	*/
	public function getPredicado()
	{
		return $this->predicado;
	}

	/**
	* Set predicado
	* @param string $predicado
	*/
	public function setPredicado($predicado)
	{
        $this->predicado = $predicado;
    }

	/**
	* Get predicado Parseo
	*/
	public function getPredicadoParseo()
	{
		return $this->predicadoParseo;
	}

	/**
	* Set predicadoParseo
	*/
	public function setPredicadoParseo($predicadoParseo)
	{
        $this->predicado = $predicadoParseo;
	}
	
	/**
	* Set predicadoValor
	*/
	public function setPredicadoValor($predicadoValor)
	{
        $this->predicado = $predicadoValor;
	}

	
	/**
	* Costructor con objeto traza como parametro
	*/
	public function __construct($trazas) {
		$this->trazas = $trazas;
		$this->trazas->setClase("Triple");
		$this->trazas->LineaInfo("__construct", "Inicializa el costructor");  
	}

    /**
     * Función que se utiliza para inicializar la estructura de la triple
     * para parsearla posteriormente
	*/
	public function InformaEsquema($sujeto,$sujetoParseo,$verbo,$verboParseo,$predicado,$predicadoParseo) {
		$this->trazas->LineaInfo("InformaEsquema", "Informa los datos de la triple");  
		$this->sujeto = $sujeto;
		$this->sujetoParseo = $sujetoParseo;
		$this->verbo = $verbo;
		$this->verboParseo = $verboParseo;
		$this->predicado = $predicado;
		$this->predicadoParseo = $predicadoParseo;
	}
	
    /**
    * Función que parsea el sujeto, verbo y predicado por los valores de la filaCsv que se
    * pasa como parámetro.
    * Posteriormente construye con los tes valores la triple que ira en el sparq.
	*/
	public function ProcesaDatos($filaCVS)
	{  
	    $this->trazas->LineaInfo("ProcesaDatos", sprintf("Procesa los datos de la triple con la fila CSV: S:%s V:%s P:%s",
		 													$this->sujeto,$this->verbo,$this->predicado));  
		$this->filaCVS = $filaCVS;
		if (strlen($this->sujetoParseo)>0) {
			$this->sujetoValor = sprintf($this->sujeto, trim($this->filaCVS[$this->sujetoParseo]));
		} else {
			$this->sujetoValor = $this->sujeto;
		}
		if (strlen($this->verboParseo)>0) {
			$this->verboValor = sprintf($this->verbo, trim($this->filaCVS[$this->verboParseo]));
		} else {
			$this->verboValor = $this->verbo;
		}
		if (strlen($this->predicadoParseo)>0) {
		   $this->predicadoValor = sprintf($this->predicado, trim($this->filaCVS[$this->predicadoParseo]));
		} else {
		   $this->predicadoValor= $this->predicado;
		}
		if (!empty($this->sujetoValor) && !empty($this->verboValor) && !empty($this->predicadoValor)) {
		    $this->tripleValor = sprintf("%s %s %s .", $this->sujetoValor,$this->verboValor,$this->predicadoValor);
		} else {
			$this->trazas->LineaError("ProcesaDatos", sprintf("Linea a generado una triple con algun elemento vacio: S:%s V:%s P:%s", 
			                  $this->sujetoValor,$this->verboValor,$this->predicadoValor));  
			$this->tripleValor = null;
		}
	}

}
<?php

namespace ApiRest\AodPoolBundle\Entity;

use Swagger\Annotations as SWG;

/**
 * AodPool
 *
 * @SWG\Definition(
 *   definition="Configuracion",
 *   type="object",
 *   @SWG\Xml(name="Configuracion"),
 * )
 */
class Configuracion
{
   

	/** 
     *  @var string
	 * */
	private $nombre = "";


	/** 
     *  @var string
	 * */
	private $tipo = "";


    /** 
     *  @var string
	 * */
	private $yml = "";

	/**
	* Set xml
	* @param string
	*/
	public function setYml($yml)
	{
		$this->yml = $yml;
	}

	/**
	* Get xml
	* @return string 
	*/
	public function getYml()
	{
		return $this->yml;
	}

	
	/**
	* Set xml
	* @param string
	*/
	
	public function setNombre($nombre)
	{

		$this->nombre = $nombre;
	}

	/**
	* Get xml
	* @return string 
	*/
	
	public function getNombre()
	{
		return 	$this->nombre;
	}

		/**
	* Set xml
	* @param string
	*/
	
	public function setTipo($tipo)
	{

		$this->tipo = $tipo;
	}

	/**
	* Get xml
	* @return string 
	*/
	
	public function getTipo()
	{
		return 	$this->tipo;
	}
	
	
}

<?php

namespace ApiRest\AodPoolBundle\Entity;

use Swagger\Annotations as SWG;

/**
 * AodPool
 *
 * @SWG\Definition(
 *   definition="AodPoolEsquema",
 *   type="object",
 *   @SWG\Xml(name="AodPoolEsquema"),
 * )
 */
class Esquema
{
   

	/** 
     *  @var string
	 * */
	private $nombre = "";

    /** 
     *  @var string
	 * */
	private $xml = "";

	/**
	* Set xml
	* @param string
	*/
	public function setXml($xml)
	{
		$this->xml = $xml;
	}

	/**
	* Get xml
	* @return string 
	*/
	public function getXml()
	{
		return $this->xml;
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
		return 	$nombre = str_replace(".","_",$this->nombre);
	}
	
	
}


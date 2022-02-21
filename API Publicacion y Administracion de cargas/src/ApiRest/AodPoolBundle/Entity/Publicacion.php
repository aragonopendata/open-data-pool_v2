<?php

namespace ApiRest\AodPoolBundle\Entity;

use Swagger\Annotations as SWG;

/**
 * AodPool
 *
 * @SWG\Definition(
 *   definition="AodPool",
 *   type="object",
 *   @SWG\Xml(name="AodPool"),
 * )
 */
class Publicacion
{
    /** 
     *  
     *  @var string
	 * */
	private $idesquema;
    /** 
     * 
     *  @var string
	 * */
	private $csv;

    /*
	* Set idesquema
	* @param string
	*/
	public function setIdesquema($idesquema)
	{
	    $this->idesquema = $idesquema;
    }

	/**
	* Get idesquema 
	* @return string 
	*/
	public function getIdesquema()
	{
		return $this->idesquema;
	}


	/**
	* Set csv
	* @param string
	*/
	public function setCsv($csv)
	{
		$this->csv = $csv;
	}

	/**
	* Get csv
	* @return string 
	*/
	public function getCsv()
	{
		return $this->csv;
	}
}


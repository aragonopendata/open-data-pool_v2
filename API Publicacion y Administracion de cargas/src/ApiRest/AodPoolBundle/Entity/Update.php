<?php

namespace ApiRest\AodPoolBundle\Entity;

use Swagger\Annotations as SWG;

/**
 * AodPool
 *
 * @SWG\Definition(
 *   definition="AodPoolUpdate",
 *   type="object",
 *   @SWG\Xml(name="AodPoolUpdate"),
 * )
 */
class Update
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
	/** 
     * 
     *  @var string
	 * */
	private $dcType;	
	

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
	
	/**
	* Set dcType
	* @param string
	*/
	public function setDcType($dcType)
	{
		$this->dcType = $dcType;
	}

	/**
	* Get dcType
	* @return string 
	*/
	public function getDcType()
	{
		return $this->dcType;
	}
}


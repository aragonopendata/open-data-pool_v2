<?php

namespace ApiRest\AodPoolBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entidades
 *
 * @ORM\Table(name="configuracionweb")
 * @ORM\Entity
 */
class ConfiguracionWeb
{
    /**
     * @var integer
     *
     * @ORM\Column(name="code", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="Configuracionweb_code_seq", allocationSize=1, initialValue=1)
     */
    protected $code;

    /**
     * @var string
     *
     * @ORM\Column(name="slug", type="string", length=50, nullable=true)
     */
    protected $slug;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=100, nullable=true)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="configuracion", type="text", nullable=true)
     */
    protected $configuracion;


     /**
     * Get code
     *
     * @return integer
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="created", type="string", nullable=true)
     */
    protected $created;


    /**
     * @var bit
     *
     * @ORM\Column(name="active", type="boolean", nullable=false)
     */
    protected $active;



	public function getSlug()
	{
		return $this->slug;
	}

	public function getName()
	{
		return $this->name;
	}

	public function getConfiguracion()
	{
		return $this->configuracion;
	}


    public function getActive()
	{
		return $this->active;
	}


    /**
     * Set slug
     *
     * @param string $slug
     *
     * @return ConfiguracionWeb
     */
    public function setSlug($slug) 
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return ConfiguracionWeb
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Set configuracion
     *
     * @param string $configuracion
     *
     * @return ConfiguracionWeb
     */
    public function setConfiguracion($configuracion)
    {
        $this->configuracion = $configuracion;

        return $this;
    }

    /**
     * Set creatred
     *
     * @param date $created
     *
     * @return ConfiguracionWeb
     */
    public function setCreated()
    {
        $this->created = date('Y-m-d');
    }  

    /**
     * Set active
     *
     * @param boolean $active
     *
     * @return ConfiguracionWeb
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Get created
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }


    public function getArray()
	{
        $ConfiguracionWeb = array(
            "Code" => $this->code,
            "Slug" => $this->slug,
            "Name" => $this->name,
            "Configuracion" => $this->configuracion,
            "Active" => $this->active);
		return  $ConfiguracionWeb;
	}

}

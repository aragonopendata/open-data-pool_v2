<?php

namespace ApiRest\AodPoolBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use \Datetime;

/**
 * Entidades
 *                  
 * @ORM\Table(name="cargavistas")
 * @ORM\Entity
 */
class CargaVistas
{
    /**
     * @var integer
     *
     * @ORM\Column(name="code", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="cargavistas_code_seq", allocationSize=1, initialValue=1)
     */
    protected $code;

    /**
     * @var string
     *
     * @ORM\Column(name="nombre", type="string", length=200, nullable=true)
     */
    protected $nombre;

    /**
     * @var string
     *
     * @ORM\Column(name="criterio", type="string", length=512, nullable=true)
     */
    protected $criterio; 
 
    /**
     * @var string
     *
     * @ORM\Column(name="periodicidad", type="string", length=20, nullable=true)
     */
    protected $periodicidad;

    /**
     * @var string
     *
     * @ORM\Column(name="fecha", type="string", nullable=true)
     */
    protected $fecha;

    /**
     * @var string
     *
     * @ORM\Column(name="hora", type="string", nullable=true)
     */
    protected $hora;


     /**
     * @var string
     *
     * @ORM\Column(name="estado", type="string", length=256, nullable=true)
     */
    protected $estado;


    /**
     * @var string
     *
     * @ORM\Column(name="logs", type="string", length=128, nullable=true)
     */
    protected $logs;


    /**
     * @var string
     *
     * @ORM\Column(name="archivos", type="string", length=20, nullable=true)
     */
    protected $archivos;



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


     /**
     * Get code
     *
     * @return integer
     */
    public function getCode()
    {
        return $this->code;
    }


    public function getNombre()
    {
        return $this->nombre; 
    }

	public function getCriterio()
	{
		return $this->criterio;
	}

	public function getPeriodicidad()
	{
		return $this->periodicidad;
	}

	public function getFecha()
	{
		return $this->fecha;
	}

    public function getHora()
	{
		return $this->hora;
	}


    public function getEstado()
	{
		return $this->estado;
    }
    

    public function getLogs()
	{
		return $this->logs;
	}


    public function getArchivos()
	{
		return $this->archivos;
    }
    
    public function getActive()
	{
		return $this->active;
	}


    /**
     * Set Nombre
     *
     * @param string $nombre
     *
     * @return CargaVistas
     */
    public function setNombre($nombre) 
    {
        $this->nombre = $nombre;

        return $this;
    }


    /**
     * Set criterio
     *
     * @param string $criterio
     *
     * @return CargaVistas
     */
    public function setCriterio($criterio)
    {
        $this->criterio = $criterio;

        return $this;
    }

    /**
     * Set periodicidad
     *
     * @param string $periodicidad
     *
     * @return CargaVistas
     */
    public function setPeriodicidad($periodicidad)
    {
        $this->periodicidad = $periodicidad;

        return $this;
    }


     /**
     * Set estado
     *
     * @param string $estado
     *
     * @return CargaVistas
     */
    public function setEstado($estado)
    {
        $this->estado = $estado;

        return $this;
    }


    /**
     * Set logs
     *
     * @param string $logs
     *
     * @return CargaVistas
     */
    public function setLogs($logs)
    {
        $this->logs = $logs;

        return $this;
    }

    /**
     * Set archivos
     *
     * @param string $archivos
     *
     * @return CargaVistas
     */
    public function setArchivos($archivos)
    {
        $this->archivos = $archivos;

        return $this;
    }

    /**
     * Set fecha
     *
     * @param date $fecha
     *
     * @return CargaVistas
     */
    public function setFecha($fecha)
    {
        $this->fecha = date($fecha);

        return $this;
    }  

    /**
     * Set hora
     *
     * @param string $hora
     *
     * @return CargaVistas
     */
    public function setHora($hora)
    {
        $hora .= ":00";
        $this->hora = $hora;

        return $this;
    }  


    
    /**
     * Set active
     *
     * @param boolean $active
     *
     * @return CargaVistas */
 
    public function setActive()
    {
        $this->active = TRUE;

        return $this;
    }

        
    /**
     * Set created
     *
     * @param date $created
     *
     * @return CargaVistas */
 
    public function setCreated()
    {
        $this->created = date("Y-m-d");
        return $this;
    }



    public function getArray()
	{
        $CargaVistas = array(
            "id" => $this->code,
            "nombre" => $this->nombre,
            "periodicidad" => $this->periodicidad,
            "criterio" => $this->criterio,
            "estado" => $this->estado,
            "logs" => $this->logs,
            "archivos" => $this->archivos,        
            "fecha" => $this->fecha,
            "hora" => $this->hora,
            "active" => $this->active);
		return  $CargaVistas;
    }

    public function getJSON()
	{
       $plantilla = '{"id":"%s","values":{"nombre":"%s","periodicidad":"%s","criterio":"%s","fecha":"%s", "hora":"%s","estado":"%s","logs":"%s","archivos":"%s","action":"","valido":""}}';

       (!empty($this->nombre)) ? $nombre = $this->nombre : $nombre="";
       (!empty($this->periodicidad)) ? $periodicidad = $this->periodicidad : $periodicidad="";
       (!empty($this->criterio)) ? $criterio = $this->criterio : $criterio="";
       (!empty($this->estado)) ? $estado = $this->estado : $estado="";
       (!empty($this->logs)) ? $logs = $this->logs : $logs="";
       (!empty($this->archivos)) ? $archivos = $this->archivos : $archivos="";
       $fecha ="";
       if (!empty($this->fecha)) {         
           $date = new DateTime($this->fecha); 
           $fecha = $date->format('d\/m\/Y');
        } 
        $hora="";
        if (!empty($this->hora)) {         
            $date = new DateTime($this->hora); 
            $hora = $date->format('H:i');
        } 
   
       $JsonRegistro = sprintf($plantilla,$this->code,$nombre,$periodicidad,$criterio,$fecha,$hora,$estado,$logs,$archivos);

     
	   return  $JsonRegistro;
    }
    

}

    
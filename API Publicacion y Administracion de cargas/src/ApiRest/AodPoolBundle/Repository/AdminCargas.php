<?php

namespace ApiRest\AodPoolBundle\Repository;

use Doctrine\ORM\EntityManager;
use ApiRest\AodPoolBundle\Entity\CargaVistas;

/** 
 * Repositorio de las operaciones a BD de cargarvistas (los esquemas web)
*/
class AdminCargas
{
    /**
     * Gestor doctrine
     */
    private $em = null;

    protected function configure()
    {
        $this->setName('Repository_AdminCargas') ;
    }


    public function __construct($doctrine) {
        $this->em = $doctrine;
    }

    function GetCargaVistasDataJson() {
        $CargaVistaJSON = '"data":[';

        $consulta = $this->em->createQuery("SELECT c FROM ApiRestAodPoolBundle:CargaVistas c 
                                      WHERE c.active = '1'  
                                      ORDER BY c.nombre ASC"); 
        $CargaVistasSol = $consulta->getResult();
        foreach ($CargaVistasSol as $CargaVistas) { 
           $CargaVistaJSON .= $CargaVistas->getJSON() . ",";
        }
        $CargaVistaJSON = substr($CargaVistaJSON, 0, strlen($CargaVistaJSON) - 1);
        $CargaVistaJSON .= ']';
        return $CargaVistaJSON;
    }




    function DeleteCargaVistasByCode($code) { 
        $CargaVistasSol = array();

        $consulta = $this->em->createQuery("DELETE FROM ApiRestAodPoolBundle:CargaVistas c
                                            WHERE c.code = '$code'"); 
        $CargaVistases = $consulta->getResult();
        return $CargaVistasSol;
    }

    function DeleteCargaVistasByNombre($nombre) {
        $CargaVistasSol = array();

        $consulta = $this->em->createQuery("DELETE FROM ApiRestAodPoolBundle:CargaVistas c
                                            WHERE c.nombre = '$nombre'"); 
        $CargaVistases = $consulta->getResult();
        return $CargaVistasSol;
    }


    function GetCargaVistasbyCode($code) {

        $consulta = $this->em->createQuery("SELECT c FROM ApiRestAodPoolBundle:CargaVistas c
                                            WHERE c.active = '1' and
                                            c.code = $code"); 
        $CargaVistasSol = $consulta->getResult();
   
        return $CargaVistasSol;
    }

    function GetCodeCargaVistasbyNombre($nombre) {
        $codigo=-1;
        $consulta = $this->em->createQuery("SELECT c FROM ApiRestAodPoolBundle:CargaVistas c
                                            WHERE c.active = '1' and
                                            c.nombre = '$nombre'"); 
        $CargaVistasSol = $consulta->getResult();
        if (count($CargaVistasSol)>0) {
            $codigo= $CargaVistasSol[0]->getCode();
        }
        return $codigo;
    }

    function GetExitsCargaVistasbyNombre($nombre) {
        $exite = false;
        $consulta = $this->em->createQuery("SELECT c FROM ApiRestAodPoolBundle:CargaVistas c 
                                            WHERE
                                            c.nombre = '$nombre'"); 
        $CargaVistas = $consulta->getResult();
        $exite = (count($CargaVistas)>0);
        return $exite ;
    }

    function GetPeriodicidadCargaVistasbyNombre($nombre) {
        $periodicidad='A demanda';
        $consulta = $this->em->createQuery("SELECT c FROM ApiRestAodPoolBundle:CargaVistas c
                                            WHERE c.active = '1' and
                                            c.nombre = '$nombre'"); 
        $CargaVistasSol = $consulta->getResult();
        if (count($CargaVistasSol)>0) {
            $periodicidad= $CargaVistasSol[0]->getPeriodicidad();
        }
        return $periodicidad;
    }	
                                
    function InsertaCargaVistas($nombre,$periodicidad,$criterio,$estado,$logs,$archivos,$fecha,$hora) { 
	
	

        $respuesta="Inserta";
        $code = $this->GetCodeCargaVistasbyNombre($nombre);
		$periodicidad= $this->GetPeriodicidadCargaVistasbyNombre($nombre);
        if ($code==-1) {
            $CargaVistas = new  CargaVistas();
            $CargaVistas->setNombre($nombre);
            $CargaVistas->setPeriodicidad($periodicidad);
            $CargaVistas->setCriterio($criterio);
            $CargaVistas->setEstado($estado);
            $CargaVistas->setLogs($logs);
            $CargaVistas->setArchivos($archivos);
            $CargaVistas->setFecha($fecha);
            $CargaVistas->setHora($hora);
            $CargaVistas->setActive();
            $CargaVistas->setCreated();
            $this->em->persist($CargaVistas);
            $this->em->flush();
        } else {
            $this->ActualizaCargaVistas($nombre,$periodicidad,$criterio,$estado,$logs,$archivos,$fecha,$hora);
            $respuesta="Actualiza";
        }
        return $respuesta;
    }


    function ActualizaCampoCargaVistas($code,$nombrecampo,$valor) {
        $Vista = $this->em->getRepository('ApiRest\AodPoolBundle\Entity\CargaVistas')->find($code);
        switch ($nombrecampo) {
            case "nombre":
                $Vista->setNombre($valor);
                break;
            case "periodicidad":
                $Vista->setPeriodicidad($valor);
                break;
            case "criterio":
                $Vista->setCriterio($valor);
                break;
            case "estado":
                $Vista->setEstado($valor);
                break;
            case "logs":
                $Vista->setLogs($valor);
                break;
            case "archivos":
                $Vista->setArchivos($valor);
                break;
            case "fecha":
                $Vista->setFecha($valor);
                break;
            case "hora":
                $Vista->setHora($valor);
                break;
        }
        $this->em->persist($Vista);
        $this->em->flush();
    }

    function ActualizaCargaVistas($nombre,$periodicidad,$criterio,$estado,$logs,$archivos,$fecha,$hora) {
        $respuesta="OK";
        $code = $this->GetCodeCargaVistasbyNombre($nombre);
        if ($code!=-1) {
            $Vista = $this->em->getRepository('ApiRest\AodPoolBundle\Entity\CargaVistas')->find($code);  
            $Vista->setPeriodicidad($periodicidad);
            $Vista->setCriterio($criterio);
            $Vista->setEstado($estado);
            $Vista->setLogs($logs);
            $Vista->setArchivos($archivos);
            $Vista->setFecha($fecha);
            $Vista->setHora($hora);
        
            $this->em->persist($Vista);
            $this->em->flush();
        } else {
            $respuesta="NO";
        }
    }

}
<?php

namespace ApiRest\AodPoolBundle\Repository;

use Doctrine\ORM\EntityManager;
use ApiRest\AodPoolBundle\Entity\ConfiguracionWeb;
use Symfony\Component\Yaml\Yaml;

/** 
 * Repositorio de las operaciones a BD de configuracionWeb (los esquemas web)
*/
class WebConfig
{
    /**
     * Gestor doctrine
     */
    private $em = null;

    protected function configure()
    {
        $this->setName('Repository_WebConfig') ;
    }


    public function __construct($doctrine) {
        $this->em = $doctrine;
    }

    function GetConfiguracionWebs() {
        $configuracionWebSol = array();

        $consulta = $this->em->createQuery("SELECT t FROM ApiRestAodPoolBundle:ConfiguracionWeb t 
                                      WHERE t.active = '1'  
                                      ORDER BY t.name ASC"); 
        $configuracionWebes = $consulta->getResult();
        foreach ($configuracionWebes as $configuracionWeb) { 
           $configuracionWebSol[$configuracionWeb->getSlug()] = $configuracionWeb->getName();
        }
        return $configuracionWebSol;
    }

    function GetConfiguracionWeb($code) {
        $configuracionWebSol = array();

        $consulta = $this->em->createQuery("SELECT t FROM ApiRestAodPoolBundle:ConfiguracionWeb t 
                                      WHERE t.active = '1' and
                                            t.code = $code"); 
        $configuracionWebes = $consulta->getResult();
        foreach ($configuracionWebes as $configuracionWeb) { 
           $configuracionWebSol[$configuracionWeb->getCode()]= Yaml::parse($configuracionWeb->getConfiguracion());  
        }
        $query = $configuracionWebSol[20]['PanelCentral']['CampoValores']['CampoValor1']['Query'];
        foreach ($configuracionWebSol[20]['PanelCentral']['CampoValores'] as $campovalor) {
            if ($campovalor['Tipo']=='Virtuoso') {
               $query = $campovalor['Query'];
            }
        }
        return $configuracionWebSol;
    }

    function GetConfiguracionWebbyName($name) {
        $configuracionWebSol = array();

        $consulta = $this->em->createQuery("SELECT t FROM ApiRestAodPoolBundle:ConfiguracionWeb t 
                                            WHERE
                                            t.name = '$name'"); 
        $configuracionWebes = $consulta->getResult();
        foreach ($configuracionWebes as $configuracionWeb) { 
           $configuracionWebSol[$configuracionWeb->getCode()]= Yaml::parse($configuracionWeb->getConfiguracion());  
        }
        return $configuracionWebSol;
    }
    
    function GetConfiguracionWebbySlug($slug) {
        $configuracionWebSol = array();
        $consulta = $this->em->createQuery("SELECT t FROM ApiRestAodPoolBundle:ConfiguracionWeb t 
                                            WHERE
                                            t.slug = '$slug'"); 
        $configuracionWebes = $consulta->getResult();
        foreach ($configuracionWebes as $configuracionWeb) { 
           $configuracionWebSol[$configuracionWeb->getSlug()]= Yaml::parse($configuracionWeb->getConfiguracion());  
        }
        return $configuracionWebSol;
    }

    function GetConfiguracionWebYammerbySlug($slug) {
        $configuracionWebYammerSol = "";
        $consulta = $this->em->createQuery("SELECT t FROM ApiRestAodPoolBundle:ConfiguracionWeb t 
                                            WHERE
                                            t.slug = '$slug'"); 
        $configuracionWebes = $consulta->getResult();
        foreach ($configuracionWebes as $configuracionWeb) { 
            $configuracionWebYammerSol= $configuracionWeb->getConfiguracion();  
        }
        return $configuracionWebYammerSol;
    }

    function DeleteConfiguracionWeb($slug) {
        $configuracionWebSol = array();

        $consulta = $this->em->createQuery("DELETE FROM ApiRestAodPoolBundle:ConfiguracionWeb t
                                            WHERE t.slug = '$slug'"); 
        $configuracionWebes = $consulta->getResult();
        return $configuracionWebSol;
    }

    function GetName($code) {
        $configuracionWeb= "";
        $consulta = $this->em->createQuery("SELECT t FROM ApiRestAodPoolBundle:ConfiguracionWeb t 
                                      WHERE t.active = '1' and 
                                            t.code = $code"); 
        $query = $consulta->getResult();
        if (count($query)>0){
            $configuracionWeb = $query[0]->getName();
        }
        return $configuracionWeb;
    }

    function GetConfiguracionName($name) {
        $configuracionWeb= "";
        $consulta = $this->em->createQuery("SELECT t FROM ApiRestAodPoolBundle:ConfiguracionWeb t 
                                            WHERE t.active = '1' and 
                                            t.name = $name"); 
        $query = $consulta->getResult();
        if (count($query)>0){
            $configuracionWeb = $query[0]->getConfiguracion();
        }
        return $configuracionWeb;
    }

    function GetConfiguracion($code) {
        $configuracionWeb= "";
        $consulta = $this->em->createQuery("SELECT t FROM ApiRestAodPoolBundle:ConfiguracionWeb t 
                                      WHERE t.active = '1' and 
                                            t.code = $code"); 
        $query = $consulta->getResult();
        if (count($query)>0){
            $configuracionWeb = $query[0]->getConfiguracion();
        }
        return $configuracionWeb;
    }

    function GetExitsConfiguracionWebbySlug($slug) {
        $exite = fase;
        $consulta = $this->em->createQuery("SELECT t FROM ApiRestAodPoolBundle:ConfiguracionWeb t 
                                            WHERE
                                            t.slug = '$slug'"); 
        $configuracionWebes = $consulta->getResult();
        $exite = (count($configuracionWebes)>0);
        return $exite ;
    }
    function GetExitsConfiguracionWebbyTipo($tipo) {
        $exite = fase;
        $consulta = $this->em->createQuery("SELECT t FROM ApiRestAodPoolBundle:ConfiguracionWeb t 
                                            WHERE
                                            t.name = '$tipo'"); 
        $configuracionWebes = $consulta->getResult();
        $exite = (count($configuracionWebes)>0);
        return $exite ;
    }
    
    function SetConfiguracionWeb($nombre, $tipo, $yml) {
        $configuracionWeb = new ConfiguracionWeb();
        $configuracionWeb->setSlug($nombre);
        $configuracionWeb->setName($tipo);
        $configuracionWeb->setConfiguracion($yml);
        $configuracionWeb->setActive(true);
        $configuracionWeb->setCreated();
        $this->em->persist($configuracionWeb);
        $this->em->flush();
    }

}
<?php

namespace ApiRest\WorkerBundle\Controller;

use ApiRest\WorkerBundle\Controller\Triple;
use ApiRest\WorkerBundle\Controller\Trazas;


/**
 * Clase que realiza las tareas de procesar el esquema isonómico a una expresión parseable por spritf
 * La clase realiza las siguientes taras:
 *  1- lee y carga el esquema isonómico dado un ID
 *  2 - Trasforma CADA nodo en una triple con sujeto, verbo y predicado, de manera que
 * 		cada variable tiene una expresión parseable por sprintf a la espera de los campos del CVS
 *  3.- Realiza una gestión m,anual de los name spaces ya que php solo maneja el primer xmlns
 */
class Isonomia
{
	
	// Identificar del triple
	protected $id;

    // Objeto de log
	protected $traza;

	// Ruta relativa principal
	protected $appPath;

	// Get idEsquema
	public function getId()
	{
		return $this->id;
	}

    // Set idEsquema
	 public function setId()
	{
		return $this->id;
	}

	// Get Traza
	public function getTrazas()
	{
		return $this->trazas;
	}

	// Constructor con identificador de esquema isonómico e inicia del objeto de trazas
	public function __construct($id,
								$trazas,
								$appPath) {
		$this->id = $id;
		$this->appPath = $appPath;
		$this->trazas = $trazas;

		$this->trazas->setClase("Isonomia");
		$this->trazas->LineaDebug("__construct", "Inicia el constructor");  
    }

	/** 
	 * Función principal que carga el esquema y lo procesa
	 * Devuelve unn array de elementos Triple preparado para parsear
	*/
	function ProcesaEsquema()
    {   
		//Inicia variables
		$pilaTriples = array();
		$sujeto = "";
		$sujetoParseo = array();
		$xml = "";
		//carga esquema TEXTO isonomico por ID
		$isonomia = $this->DameIsonomia(urldecode($this->id));
		$this->trazas->LineaInfo("ProcesaEsquema",'Isonomia cargada con Identificador:' . urldecode($this->id));
		//carga en un DOM xml el texto
		if (empty($isonomia)){
			$this->trazas->LineaError("ProcesaEsquema",'Isonomia cargada con Identificador:' . urldecode($this->id));									  
		}
		if ($this->trazas->SinError()) {	
			$xml = simplexml_load_string($isonomia,'SimpleXMLElement', LIBXML_NOCDATA);
			if (false === $xml) {
				$this->trazas->LineaError("ProcesaEsquema",'Isonomia no validada por simplexml');
			} else {
				$this->trazas->LineaDebug("ProcesaEsquema",'Isonomia validada por simplexml');
			}

			if ($this->trazas->SinError()) {
				//carga manual de los namespaces en un array (prefijo=>valor http//...)
				$this->trazas->LineaDebug("ProcesaEsquema",'Carga manual de los namespaces');
				$nameSpaces = $this->DameNameSpaces($xml->asXML());
			}

			if ($this->trazas->SinError()) {
				//llamada a la funcion reursiva por todos los nodos y subnodos
				$this->trazas->LineaDebug("ProcesaEsquema",'Llamada a la función recursiva por todos los nodos y subnodos');
				$this->ProcesaEntity($xml, $sujeto, $sujetoParseo, "" , $pilaTriples,$nameSpaces);
				//devuelve la pila de triples preparada para parsear con sprintf
				return $pilaTriples;   
			}
		}
	}
	/**
	 * Función que dado un Id devuelve el texto de la esquema isonómico
	 */
	function DameIsonomia($id){
		$isonomia = "";
		$directoryPath = str_replace("app","src/ApiRest/WorkerBundle/Resources/Files/Isonomias/",$this->appPath);	
		
		$nombreFichero = $directoryPath . $id. ".xml";
		$this->trazas->LineaDebug("DameIsonomia",'xml -> '.  $nombreFichero);
		if (file_exists($nombreFichero)) {
			 $isonomia = file_get_contents($nombreFichero);
		}
		return $isonomia;
	}
	
	/**
	 * Función recursiva que pasa por todos los nodos y subnodos del esquema
	 * Parámetros :
	 * $entity: Nodo a tratar
	 * $sujeto, $sujetoParseo: se pasa porque es el mismo para todos los subnodos primer nivel de un nodo dado
	 * &$PilaTriples: Contenedor de Triples por referencia para que cargue todas las triples en la misma pila
	 * &$nameSpaces: Manejador de Namespaces por referencia simple es el mismo
	 */
	function ProcesaEntity($entity, $sujeto, $sujetoParseo, $condicionPadre, &$PilaTriples, &$nameSpaces){
		//por cada uno de los subnosdos del nodo principal
		$this->trazas->LineaDebug("ProcesaEntity",sprintf('Entra en Entity del nodo %s', $entity->getName()));
		//pregunto por el campo condition antes del for para que sea heredado por todos los subnodos 
		$condition  = $this->DameCondicion($entity, $condicionPadre);
		foreach ($entity->children() as $node) {
			$tipoNodo="";
			//de cualquer forma se escapa el caso de uso donde el ultimo nodo hijo tiene condición
			if (isset($node['condition'])){
				$condition = $this->DameCondicion($node, $condicionPadre);
			}
		    $this->trazas->LineaDebug("ProcesaEntity",sprintf('Entra en sunbnodo %s => %s',$entity->getName(),$node->getName()));
		    //inicializo
		    $verbo="";
		    $verboParseo="";
		    $predicado="";
			$predicadoParseo=array(); 
			$tipoPredicadoParseo="";
		    //dependiendo del nombre del elemento manejo de una u otra manera los las tres variables: 
			//sujeto verbo predicado
			switch((string) $node->getName()) { // Obtener los atributos como índices del elemento
				case 'Entity': 
				   $tipoNodo ='Entity';
				   //cojo el name space del Nodo
				   $ns =  $this->DameNameSpace($node->asXML()); 
				   if ($this->trazas->SinError()) 
				   {
					    //informo el sujeto
						$sujeto = trim($node['URI']);
					    //informo porque valor he de parsear
					    $sujetoParseo = $this->DameParseo($sujeto);
						//remplazo por %s para printf
						foreach($sujetoParseo as $parseo) {
							$sujeto = str_replace('{'. $parseo .'}', "%s", $sujeto);
						}
					    //pongo los tag de nodo
					    $sujeto = sprintf("<%s>",$sujeto);
						$verbo = array_search($ns[0],$nameSpaces);
						//busco el tipo
						$tipo = $this->DameTipoEntidad($node->asXML());
						$verbo = $verbo . $tipo ;
					    //pongo los tag de nodo
						$verbo = sprintf("<%s>",$verbo);

					    //informo el predicado
					    $predicado =array_search($ns[1][0],$nameSpaces) . $ns[1][1];
					    //pongo las comillas al predicado
					    $predicado = sprintf("<%s>",$predicado);
				    }
				break;
				case 'Property':
					//cuatro posibilidades value, field, fieldLink y link
					if (isset($node['value'])) {
						$tipoNodo ='value';
						$this->trazas->LineaDebug("ProcesaEntity",sprintf('Nodo tipo value: %s ', $node['value']->asXML()));
						//cargo el atributo para coger el nameEspace y ponerlo en el verbo
						$ns= explode(":",$node['attribute']);
						if (count($ns)==2) 
						{
							$verbo = array_search($ns[0],$nameSpaces) . $ns[1];
							//pongo los tag de nodo dependiendo de si es string o no
							$verbo = sprintf("<%s>",$verbo);
							 
							//cargo el tipo de predicado
							$tipoPredicadoParseo = trim($node['type']);							
				 
							$predicado = trim($node['value']);
							$predicado = sprintf("\"%s\"",$predicado);
							 
						 } else {
							$this->trazas->LineaError("ProcesaEntity", "No devuelve un array de dos valores:". $node['attribute']->asXML());
						 }
					}
					else if (isset($node['field']))
					{
					   $tipoNodo ='field';
					   $this->trazas->LineaDebug("ProcesaEntity",sprintf('Nodo tipo field: %s ',$node['field']->asXML()));
					   //cargo el atributo para coger el nameEspace y ponerlo en el verbo
					   $ns= explode(":",$node['attribute']);
					   if (count($ns)==2) 
					   {
						    $verbo = array_search($ns[0],$nameSpaces) . $ns[1];
						    //pongo los tag de nodo dependiendo de si es string o no
							$verbo = sprintf("<%s>",$verbo);
							
							//cargo el tipo de predicado
							$tipoPredicadoParseo = trim($node['type']);							

							$predicado = trim($node['field']);

							$predicadoParseo = $this->DameParseo($predicado);

							//el predicado viene en el CSV
							if ($tipoPredicadoParseo=="string") 
							{   
								foreach($predicadoParseo as $parseo) {
								    $predicado = str_replace('{'. $parseo .'}', "%s", $predicado);
								}
								$predicado = "\"" . $predicado . "\"";
							} else {
								$predicado = "\"%s\"^^xsd:" . $tipoPredicadoParseo ;
							}
					    } else {
						    $this->trazas->LineaError("ProcesaEntity", "No devuelve un array de dos valores:". $node['attribute']->asXML());
					    }
					} else if (isset($node['fieldLink'])) 
					{
						$tipoNodo ='fieldLink';
						$this->trazas->LineaDebug("ProcesaEntity",sprintf('Nodo tipo fieldLink: %s ',$node['fieldLink']->asXML()));
					    //cargo el namespace para el verbo
					    $ns =  explode(":",$node['attribute']);
					    if (count($ns)==2) 
					    {
						   $verbo =  array_search($ns[0],$nameSpaces) . $ns[1];
						   //pongo los tag de nodo
						   $verbo = sprintf("<%s>",$verbo);
					   } else {
						   $this->trazas->LineaError("ProcesaEntity", "No devuelve un array de dos valores:". $node['attribute']->asXML());  
					   }
					   if ($this->trazas->SinError()) 
					   {
					      //cargo el namespace para el predicado
							 $predicado = $node['fieldLink'];
							 $predicadoParseo =  $this->DameParseo($predicado);
							 foreach($predicadoParseo as $parseo) {
								$predicado = str_replace('{'. $parseo .'}', "%s", $predicado);
							 }
							 //pongo los tag de nodo
							 $predicado = sprintf("<%s>",$predicado);
					   }
				   } else if (isset($node['link'])){ 
					   $tipoNodo ='link';
					   $this->trazas->LineaDebug("ProcesaEntity",sprintf('Nodo tipo link: %s ',$node['link']->asXML()));
					   //cargo el namespace para el verbo
					   $ns =  explode(":",$node['attribute']);
					   if (count($ns)==2) 
					   {
						   $verbo =  array_search($ns[0],$nameSpaces) . $ns[1];
						    //pongo los tag de nodo
						   $verbo = sprintf("<%s>",$verbo);
						   //cargo el namespace para el predicado
						   $ns =  explode(":",$node['link']);
						   if (count($ns)==2) 
						   {
						       $predicado = array_search($ns[0],$nameSpaces) . $ns[1];
					           //pongo los tag de nodo
							   $predicado = sprintf("<%s>",$predicado);
					       } else {
						       $this->trazas->LineaError("ProcesaEntity", "No devuelve un array de dos valores:". $node['attribute']->asXML());  
						   }
					    }
					} 
			    break;
			}
			if ($this->trazas->SinError()) 
			{
				$sujetoParseoTraza="";
				$predicadoParseoTraza ="";
				foreach($sujetoParseo as $parseo) {
					$sujetoParseoTraza = $sujetoParseoTraza . '[' . $parseo . ']';
				}

				foreach($predicadoParseo as $parseo) {
					$predicadoParseoTraza = $predicadoParseoTraza . '[' . $parseo . ']';
				}
				$this->trazas->LineaDebug("ProcesaEntity",sprintf('Subnodo procesado: S: [%s=>%s] V:[%s=>%s] P:[%s=>%s=>%s]',
														$sujeto,$sujetoParseoTraza,$verbo,$verboParseo,$predicado,$predicadoParseoTraza,$tipoPredicadoParseo));
				// Creo un objeto Triples iniciadas con las trazas
				$esquemaTriple = new Triple($this->trazas);
				//Informo el objeto triple
				$esquemaTriple->InformaEsquema($sujeto, $sujetoParseo, $verbo, $verboParseo, $predicado, $predicadoParseo, $tipoPredicadoParseo, $tipoNodo, $condition);
				//cargo el objeto en la pila de triples
				$PilaTriples[]=$esquemaTriple;		
				$this->ProcesaEntity($node, $sujeto,$sujetoParseo,$condition, $PilaTriples, $nameSpaces);	
			}	
		}
	}

    /**
	 * Función que  nos devuelve el valor actual del campo Condicion en base a
	 * El valor actual del campo depende del $literal y el valor que se le pasa del padre $condicionPadre
	 */
	function DameCondicion($node,$condicionPadre)
	{
		$condicion="";
		$children = count($node->children());
		//si esta informado en la isonomia
		if (isset($node['condition'])){
			$condition = $this->DameParseo($node['condition']);
			if (count($condition)>0) {
				$condicion = $condition[0];
			}
		} else {
		  if (!empty($condicionPadre)){
			$condicion = $condicionPadre;
		  }		
		}
		return $condicion;
	}

    /**
	 * Dado un literal encuentra todas las expresiones {algo}
	 * Se utiliza para sacar los nombres de los campos por los que he de parsear
	 */
	function DameParseo($literal)
	{   $error = false;
		preg_match_all('~{(.*?)}~', $literal, $output);
		if (count($output)==2) {
		   if (count($output[1])>0) {
			 return $output[1];
		   } else {
			 $error=true;
		   }
		} else {
		  $error=true;
		}
		if ($error)
	    {
			$this->trazas->LineaError("DameParseo", "No devuelve un array de dos valores:". $literal);
			return array();
		} 
	}

	/**
	* Dado un literal encuentra todas las expresiones de  definición del nameespaces del XML
	* Devuelve un array ("prefijo" => namespace)
	*/
	function DameNameSpaces($literal)
	{
		$Items = array();
		try
		{
			preg_match_all('~xmlns:(.*?)="(.*?)"~', $literal, $output);
			if (count($output)>0) {
				$space = $output[0];
				$cuenta = count($space);
				if ($cuenta>0) {
					for($i=0; $i<$cuenta; $i++) {
						$Items[trim($output[2][$i])] =  trim($output[1][$i]);
					}
				}
			} else {
				$this->trazas->LineaError("DameNameSpaces", "No encuentra NameSpaces". $literal);
			}
	    } catch (Exception $e) {
			$this->trazas->LineaError("DameNameSpaces",'Excepción capturada: ' .  $e->getMessage());
	    }
		return  $Items; 
	}


	/**
	* Funcion que busca el tipo para poner en la entidad es decir de rdf:type ha de devolver type para ponerlo al final del namespace
	*  xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" como http://www.w3.org/1999/02/22-rdf-syntax-ns#type"
	*/
	function DameTipoEntidad($literal)
	{
		$tipo="";
		try
		{
			preg_match_all('~Entity (.*?)=~', $literal, $output);
			if (count($output)>0) {
				if (count($output[0])>0) {
					$n = explode(":",$output[0][0]);
					if (count($n>1)) {
						$tipo =$n[1];
						$tipo = str_replace("=","",$tipo);			
					}
				}
			}
	    } catch (Exception $e) {
			$this->trazas->LineaError("DameTipoEntidad",'Excepción tipo: ' .  $e->getMessage());
	    }
		return $tipo; 
	}

	/**
	 * Convierte algo="prefijo:atributo" en un array(prefijo=>atributo)
	 * Se utiliza para sacar los namespaces de field, fieldlink y link
	 */
	function DameNameSpace($literal)
	{
		$Items = array();
		try
		{
			preg_match_all('~ (.*?):type="(.*?)"~', $literal, $output);
		
			if (count($output)>0) {
				$space = trim($output[0][0]);
				$Items = explode("=",$space);
				if (count($Items)==2) {
					$Items[0]= trim(str_replace(":type","",$Items[0]));
					$Items[1]= trim(str_replace("\"","",$Items[1]));
					$Items[1]= explode(":", $Items[1]);
				}
			} 
		} catch (Exception $e) {
			$this->trazas->LineaError("DameNameSpace",'Excepción capturada: ' .  $e->getMessage());
		}
		return  $Items; 
	}
}
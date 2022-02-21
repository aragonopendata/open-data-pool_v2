<?php

namespace ApiRest\AodPoolBundle\Controller;

use Katzgrau\KLogger\Logger;
use Psr\Log\LogLevel;

/**
 * Clase que realiza las tareas de la isonomia
 * La clase realiza las siguientes tareas:
 *  1 - Comprueba que el head del CSV pertenece al esquema seleccionado
 */
class Isonomia
{
	
	/**
    * Identificador de la triple
	*/
	protected $id;

    /**
	 * Objeto de log
 	 */
	protected $logger;

	/**
	 * ruta relativa principal 
 	*/
	protected $appPath;

	/**
	 * array head del CSV
 	*/
	 protected $headCSV;
 
	/**
	 * booleano escribe trazas debug
 	*/
	 protected $debug;
 
	/**
	 * esquema de la isonomia DOM
 	*/
	 protected $xmlIsonomia=null;

    /**
	* Set headCSV
	*
	*/
	public function setHeadCSV($headCSV)
	{
		$this->headCSV = $headCSV;
	}
	
	/**
	 * array head de la isonomia (varibles a encontrar en el head del CSV)
 	*/

     protected $error;

	/** 
	 * Constructor con identificador de esquema isonómico e inicia del objeto de trazas
	*/
    public function __construct($id,$logger,$appPath, $debug) {
		$this->id = $id;
		$this->appPath = $appPath;
		$this->logger = $logger;
		$this->debug = $debug;
		$this->EscribeLog("__construct","Inicia el constructor", "info"); 
		$this->error=false;
    }


	private function DameXMLIsonomia() {
		if ($this->xmlIsonomia==null) {
			$isonomia = $this->DameIsonomia();
			if (empty($isonomia)){
				$this->EscribeLog("CompruebaEsquema",  
								"No existe isonomia con nombre:". $this->id, "error"); 									  
			}
			if (!$this->error) {
				$this->EscribeLog("CompruebaEsquema",  
								"Isonomia cargada con Identificador: " . $this->id, "info");  
				//carga en un DOM xml el texto
				$this->xmlIsonomia = simplexml_load_string($isonomia,'SimpleXMLElement', LIBXML_NOCDATA);
				if (false === $this->xmlIsonomia) {
					$this->EscribeLog("CompruebaEsquema","Isonomimía no validada por simplexml","error"); 
				} else {
					$this->EscribeLog("CompruebaEsquema",  "Isonomia validada por simplexml", "info");  
				}
			}
		}
	}
	/** 
	 * Función principal que carga el esquema y lo comprueba
	 * Devuelve booleano
	*/
	public function CompruebaEsquema()
    {   
		//Inicia variables
		$csvCorrecto = true;	
		//carga esquema TEXTO isonomico por ID
	/*	$isonomia = $this->DameIsonomia();
		if (empty($isonomia)){
			$this->EscribeLog("CompruebaEsquema",  
			                  "No existe isonomia con nombre:". $this->id, "error"); 									  
		}*/
		if (!$this->error) { 
			$this->DameXMLIsonomia();
			if (!$this->error) {
				//llamada a la funcion recursiva por todos los nodos y subnodos para encontrar las variables de parseo
				$this->EscribeLog("CompruebaEsquema",  
								  "Llamada a la función recursiva por todos los nodos y subnodos", "info");  
				$this->ProcesaEntity($this->xmlIsonomia); 
			}
		}
		$csvCorrecto = !$this->error; 
		return $csvCorrecto;
	}

		/**
	* Funcion que devuelve si el dcType es correcto en la isonomia
	*/
	public function ComprobarDcType($dcType) {
		$correcto=false;
		$isonomia = $this->DameIsonomia();
		if (empty($isonomia)){
			$this->EscribeLog("ComprobarDcType",  
			                  "No existe isonomia con nombre:". $this->id, "error"); 									  
		}
		if (!$this->error) {
			$this->EscribeLog("CompruebaEsquema",  
			"Isonomia cargada con Identificador: " . $this->id, "info"); 
			$this->DameXMLIsonomia();
			if (!$this->error) {
				$xmlEntity = $this->xmlIsonomia->{"Entity"};
				$elementos = $xmlEntity->{"Property"}->count();
				$parar = false;
				$esdctype=false;
				$esmidctype = false;
				for ($i = 0; $i < $elementos && !$parar; $i ++) {
					$propiedad =  $xmlEntity->Property[$i];
					$atributos =  (array) $propiedad->attributes ();
					foreach ($atributos["@attributes"] as $clave => $valor) {
						if (!$esdctype) {
							$esdctype = (($clave == "attribute") && ($valor == "dc:type"));
						}
						if (!$esmidctype) {
							$esmidctype =  (($clave == "link") && ($valor == $dcType));
						}
						$parar = $esdctype && $esmidctype;
						$correcto=$parar;
					}
				}
			}
			if (!$correcto) {
				$this->EscribeLog("ComprobarDcType","DcType validado","error"); 
			} else {
				$this->EscribeLog("ComprobarDcType",  "DcType validado", "info");  
			}		
		}
		return $correcto;	
	}

	public function CompruebaEntityCarga($file){
		$CompruebaEntityError="";
		$isonomia=file_get_contents($file);
		$nameSpaces = $this->DameNameSpaces($isonomia,$CompruebaEntityError);
		$this->DameXMLIsonomia();
		if (!$this->error) {
			$this->CompruebaEntity($this->xmlIsonomia ,$nameSpaces, $CompruebaEntityError);
		}
		return $CompruebaEntityError;
	}
	
    /**
	 * Función que realiza el analisis orgánico de que el esquema es valido nodo nodo
	 */
	function CompruebaEntity($entity, &$nameSpaces, &$CompruebaEntityError){
		//pregunto por el campo condition antes del for para que sea heredado por todos los subnodos 
		foreach ($entity->children() as $node) {
			$this->EscribeLog("CompruebaEntity",  sprintf('Entra en subnodo %s => %s',$entity->getName(),$node->getName()), "debug"); 
	        //todos pueden tener condition
			if (isset($node['condition'])) {
				if (!$this->CompruebaParseo($node['condition'])) {
					$error = sprintf("La condición: '%s' no tiene valor bien fomado compruebe: {NOMBRE_CAMPO} ",trim($node['condition']));
					$this->EscribeLog("CompruebaEntity", $error , "error"); 
					$CompruebaEntityError = $CompruebaEntityError . $error;
				}
			}
			//dependiendo del nombre del elemento manejo de una u otra manera las tres variables: 
			//sujeto verbo predicado
			switch((string) $node->getName()) { // Obtener los atributos como índices del elemento
			    case 'Entity':  
				   //cojo el namespace del Nodo
				   $CompruebaEntityError =  $CompruebaEntityError . $this->CompruebaNameSpace($node->asXML(), $nameSpaces); 
				   if (!$this->error) 
				   {
					    if (!isset($node['URI'])){
							$error  = sprintf('La entidad: %s no tiene URI .',$node->getName());
							$this->EscribeLog("CompruebaEntity",  $error, "error"); 
							$CompruebaEntityError = $CompruebaEntityError . $error;
						}
						if (!$this->CompruebaTipoEntidad($node->asXML())) {
							$error  = sprintf('La entidad: %s tiene el tipo mal formado .',$node->getName());
							$this->EscribeLog("CompruebaEntity", $error , "error"); 
							$CompruebaEntityError = $CompruebaEntityError . $error;
						}
				   }
				break;
				case 'Property':
					//tres posibilidades field fieldLink y link
					if (isset($node['value'])) {
						$this->EscribeLog("CompruebaEntity",sprintf('Nodo tipo Property con value: %s .',trim($node['value'])), "debug"); 
						if (!isset($node['attribute'])){
							$error  = sprintf('La Propiedad con Value: %s no tiene attribute .',$node->getName());
							$this->EscribeLog("CompruebaEntity", $error  , "error"); 
							$CompruebaEntityError = $CompruebaEntityError . $error;
						} else {
							$CompruebaEntityError = $CompruebaEntityError . $this->CompruebaAtributo($node['attribute'],$nameSpaces,"Value");
						}
						if (!isset($node['type'])){
							$error  = sprintf('La Propiedad con Value: %s no tiene type .', trim($node['value']));
							$this->EscribeLog("CompruebaEntity", $error  , "error"); 
							$CompruebaEntityError = $CompruebaEntityError . $error;
						}
					} else if (isset($node['field'])) {
					   $this->EscribeLog("CompruebaEntity",sprintf('Nodo tipo Property con Field: %s .',trim($node['field'])), "debug"); 
					   if (!$this->CompruebaParseo($node['field'])) {
						    $error  = sprintf("La Propiedad con Field: '%s' no tiene valor bien formado compruebe: {NOMBRE_CAMPO} ", trim($node['field']));
							$this->EscribeLog("CompruebaEntity",  $error  , "error");
							$CompruebaEntityError = $CompruebaEntityError . $error;
					   } 
					   if (!isset($node['attribute'])){
							$error  = sprintf('La Propiedad Field con : %s no tiene attribute .',$node->getName());
							$this->EscribeLog("CompruebaEntity", $error  , "error"); 
							$CompruebaEntityError = $CompruebaEntityError . $error;
						} else {
							$CompruebaEntityError = $CompruebaEntityError . $this->CompruebaAtributo($node['attribute'], $nameSpaces,"Field");
						}  
					    if (!isset($node['type']))
					    {
						   $error  = sprintf('La Propiedad con Field: %s no tiene type .', trim($node['field']));
						   $this->EscribeLog("CompruebaEntity",  $error  , "error"); 
						   $CompruebaEntityError = $CompruebaEntityError . $error;
					    }
					} else if (isset($node['fieldLink']))  {
						$this->EscribeLog("CompruebaEntity",sprintf('Nodo tipo Property con FieldLink: %s .',trim($node['fieldLink'])), "debug"); 
					    if (!isset($node['attribute'])){
							$error  =  sprintf('La Propiedad con Value: %s no tiene attribute .',$node->getName());
							$this->EscribeLog("CompruebaEntity", $error , "error"); 
							$CompruebaEntityError = $CompruebaEntityError . $error;
					    } else {
							$CompruebaEntityError = $CompruebaEntityError . $this->CompruebaAtributo($node['attribute'],$nameSpaces,"FieldLink");
						} 					
				    } else if (isset($node['link'])){ 
						$this->EscribeLog("CompruebaEntity",sprintf('Nodo tipo Property con Link: %s .',trim($node['link'])), "debug"); 
						if (!isset($node['attribute'])){
							$error  = sprintf('La Propiedad con Value: %s no tiene attribute .',$node->getName());
							$this->EscribeLog("CompruebaEntity", $error  , "error"); 
							$CompruebaEntityError = $CompruebaEntityError . $error;
					    } else {
							$CompruebaEntityError= $CompruebaEntityError . $this->CompruebaAtributo($node['attribute'],$nameSpaces,"Link");
					    } 			
					} 
			    break;
			}
			if (!$this->error) 
			{
				$this->CompruebaEntity($node, $nameSpaces,$CompruebaEntityError);	
			}			
		}
	}

	function CompruebaAtributo($literal,$nameSpaces,$tipo){
		$error ="";
		$ns= explode(":",$literal);
		if (count($ns)!=2) {
			  $error  = sprintf("La Propiedad con %s: tiene el valor attribute  '%s' mal formado. ",$tipo, $literal);
			  $this->EscribeLog("CompruebaAtributo",  $error , "error"); 
		} else {
		  if (!in_array($ns[0],$nameSpaces)){
			 $error  =  $error . sprintf("La Propiedad con %s con attribute: '%s' no está en el namespaces. ",$tipo, $literal);
			 $this->EscribeLog("CompruebaAtributo", $error ,"error"); 
		  }
		}
		return $error ;
	}
    /**
	 * Dado un literal encuentra todas las expresiones {algo}
	 * Se utiliza para sacar los nombres de los campos por los que he de parsear
	 */
	function CompruebaParseo($literal)
	{   $comprobacion = false;
		preg_match_all('~{(.*?)}~', $literal, $output);
		if (count($output)==2) {
		   if (count($output[1])>0) {
			$comprobacion = true;
		   } 
		} 
		return $comprobacion; 
	}

	/**
	 * Funcion que busca el tipo para poner en la entidad es decir de rdf:type ha de devolver type para ponerlo al final del namespace
	 *  xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" como http://www.w3.org/1999/02/22-rdf-syntax-ns#type"
	 */
	function CompruebaTipoEntidad($literal)
	{
		$comprobacion=false;
		try
		{
			preg_match_all('~Entity (.*?)=~', $literal, $output);
			if (count($output)>0) {
				if (count($output[0])>0) {
					$n = explode(":",$output[0][0]);
					if (count($n>1)) {
						$comprobacion=true;			
					}
				}
			}
	    } catch (Exception $e) {
			$this->EscribeLog("CompruebaTipoEntidad", 'Excepción capturada: ' .  $e->getMessage(), "error");
	    }
		return $comprobacion; 
	}


	/**
	 * Convierte algo="prefijo:atributo" en un array(prefijo=>atributo)
	 * Se utiliza para sacar los namespaces de field, fieldlink y link
	 */
	function CompruebaNameSpace($literal,$nameSpaces)
	{
		$error="";
		try
		{
			preg_match_all('~ (.*?):type="(.*?)"~', $literal, $output);
		
			if (count($output)>0) {
				if (count($output[0])>0) {
					$space = trim($output[0][0]);
					$Items = explode("=",$space);
					if (count($Items)!=2) {
						$error  = $error. 'El namespace no es correcto:' . $space  . ". ";;
					}
					else {
						$Items[0]= trim(str_replace(":type","",$Items[0]));
						$Items[1]= trim(str_replace("\"","",$Items[1]));
						$Items[1]= explode(":", $Items[1]);
						if (!in_array($Items[0],$nameSpaces)){
							$error  = $error. 'El namespace no es correcto: "'. $Items[0] . '" en ' . $space . ". ";
						}
						if (count($Items[1])!=2) {
							$error  = $error. 'El namespace no es correcto: ' . $space .  ". ";;
						} else {
							if (!in_array($Items[1][0],$nameSpaces)){
								$error  = $error. 'El namespace no es correcto: "'. $Items[1][0] . '" en ' . $space  . ". ";;
							}
						}
					}
				} else {
					$error  = $error. 'El namespace no es correcto: "'. $literal . " .";
				}
			}  else {
				$error  = $error. 'El namespace no es correcto: "'. $literal . " .";	
			}
			if (!empty($error)) {
				$this->EscribeLog("CompruebaNameSpace", $error , "error"); 
			}
		} catch (Exception $e) {
			$error  = $error. 'Excepción capturada: ' .  $e->getMessage();
			$this->EscribeLog("CompruebaNameSpace", $error , "error"); 
		}
		return $error;
	}

	 /**
	 * Dado un literal encuentra todas las expresiones de  definición del namespaces del XML
	 * Devuelve un array ("prefijo" => namespace)
	 */
	function DameNameSpaces($literal, &$CompruebaEntityError)
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
				$error  = "No encuentra NameSpaces ." . $literal;
				$this->EscribeLog("DameNameSpaces", $error ,"error");
				$CompruebaEntityError = $CompruebaEntityError . $error;
			}
	    } catch (Exception $e) {
			$error= 'Excepción capturada: ' . $e->getMessage() . " .";
			$this->EscribeLog("DameNameSpaces", $error ,"error");
			$CompruebaEntityError = $CompruebaEntityError . $error;
	    }
		return  $Items; 
	}

	/**
	 * Función que dado un Id devuelve el texto de la esquema isonimico
	 */
    function DameIsonomia(){
		$isonomia = "";
		$directoryPath = str_replace("app","src/ApiRest/WorkerBundle/Resources/Files/Isonomias/",urldecode($this->appPath));	
		$nombreFichero = sprintf("%s%s%s",$directoryPath , urldecode($this->id) , ".xml");
		if (file_exists($nombreFichero)) {
			 $isonomia = file_get_contents($nombreFichero);
		}
		return $isonomia;
	}
	
	function DameIsonomiaTemporal($directoryPath){
		$isonomia = "";
		$nombreFichero = sprintf("%s%s%s",$directoryPath , $this->id , ".xml");
		if (file_exists($nombreFichero)) {
			 $isonomia = file_get_contents($nombreFichero);
		}
		return $isonomia;
	}

	public function ExiteIsonomia(){
		$isonomia = "";
		$directoryPath = str_replace("app","src/ApiRest/WorkerBundle/Resources/Files/Isonomias/",$this->appPath);	
		$nombreFichero = sprintf("%s%s%s",$directoryPath , urldecode($this->id) , ".xml");
		return file_exists($nombreFichero);
	}

	/**
	 * Función recursiva que pasa por todos los nodos y subnodos del esquema cargando el array de varibles parseo del esquema
	 * Parámetros :
	 * $entity: Nodo a tratar
	 */
	private function ProcesaEntity($entity) {
		//por cada uno de los subnosdos del nodo principal
		$this->EscribeLog("ProcesaEntity", 
						  sprintf('Entra en Entity del nodo %s', $entity->getName()),
						  "debug");
		foreach ($entity->children() as $node) {
			$this->EscribeLog("ProcesaEntity", 
							  sprintf('Entra en sunbnodo %s => %s',$entity->getName(),$node->getName()),
							  "debug");
		    //dependiendo del nombre del elemento manejo de una u otra manera los las tres variables: 
		    //sujeto verbo predicado
			switch((string) $node->getName()) { // Obtener los atributos como índices del elemento
			    case 'Entity':  
				   //cojo el name space del Nodo
				   $ns =  $this->DameNameSpace($node->asXML()); 
				   if (!$this->error) 
				   {
					    //informo el sujeto
						$sujeto = trim($node['URI']);
						if (isset($sujeto)) {
							//informo porque valor he de parsear
						   $campos = $this->DameParseo($sujeto);
						   if (!$this->error) {
								foreach($campos as $campo) {
									if (!in_array($campo,$this->headCSV)) {
										$this->EscribeLog("ProcesaEntity",  
										sprintf('Nodo tipo Entity no tiene campo asociado al CSV: %s ', $campo), "error"); 	
									}
								}	
						   }
					   }
					}
				break;
			    case 'Property':
			        //tres posibilidades field fieldLink y link
					if (isset($node['field']))
					{
						$this->EscribeLog("ProcesaEntity", 
						                   sprintf('Nodo tipo Property atributo field: %s ', $node['field']->asXML()),"debug");													
					    //cargo el campo field
						$campo = $node['field'];
						if (isset($campo)) {
							$campos =  $this->DameParseo($campo);	
							if (!$this->error) {
								foreach($campos as $campo) {
									if (!in_array($campo,$this->headCSV)) {
										$this->EscribeLog("ProcesaEntity",  
										sprintf('Nodo tipo Property no tiene atributo field con campo asociado al CSV: %s ', $campo), "error"); 	
									}
								}
							}			
						}	    
					} else if (isset($node['fieldLink'])) 
					{
						$this->EscribeLog("ProcesaEntity", 
						                  sprintf('Nodo tipo Property atributo fieldLink: %s ',$node['fieldLink']->asXML()),"debug");	
						$predicado = trim($node['fieldLink']);
						if (isset($predicado)){
							$campos =  $this->DameParseo($predicado);
							if (!$this->error) {
								foreach($campos as $campo) {
									if (!in_array($campo,$this->headCSV)) {
										$this->EscribeLog("ProcesaEntity",  
										sprintf('Nodo tipo Property no tiene atributo fieldLink con campo asociado al CSV: %s ', $campo), "error"); 	
									}
								}
							}
						}
					} 
					if (isset($node['condition'])){
						$this->EscribeLog("ProcesaEntity", 
						sprintf('Nodo tipo Property atributo condition: %s ',$node['condition']->asXML()),"debug");	
						$condition = trim($node['condition']);
						if (isset($condition)){
							$campos =  $this->DameParseo($condition);
							if (!$this->error) {
								foreach($campos as $campo) {
									if (!in_array($campo,$this->headCSV)) {
										$this->EscribeLog("ProcesaEntity",  
										sprintf('Nodo tipo Property no tiene atributo condition con campo asociado al CSV: %s ', $campo), "error"); 	
									}
								}
							}
						}
					}
			    break;
			}
			if ($this->error) 
			{
				$this->EscribeLog("ProcesaEntity",  
				                  sprintf('Campo encontrado: %s ',$campo), "debug");   
			} else {
								//llamo de manera recursiva hasta completar todos los subnodos
				$this->ProcesaEntity($node);
			}
		}
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
			$this->EscribeLog("DameParseo",  
							   sprintf("El literal : %s da error al parseo, confirme expresión '{NOMBRE_CAMPO_EXCEL}'", $literal), "error"); 
			return array();
		} 
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
			$this->EscribeLog("DameNameSpace", 'Excepción capturada: ' .  $e->getMessage(), "error"); 
		}
		return  $Items; 
	}

	private function DameLineaLog($clase, $funcion, $traza)
    {
        return sprintf("[%s:%s] %s", $clase, $funcion, $traza);
	}
	
	private function EscribeLog($funcion, $traza, $tipo){
		switch ($tipo) {
			case "debug":
				if ($this->debug) {
					$this->logger->debug($this->DameLineaLog("Isonomia",$funcion,$traza));
				}
				break;
			case "info":
			    $this->logger->info($this->DameLineaLog("Isonomia",$funcion,$traza));
				break;
			case "error":
				$this->logger->error($this->DameLineaLog("Isonomia",$funcion,$traza));
				$this->error= true;
				break;
		}	
	}
   
}
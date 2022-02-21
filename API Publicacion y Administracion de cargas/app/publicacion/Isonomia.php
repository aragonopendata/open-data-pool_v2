<?php

require_once("Triple.php");
require_once("Trazas.php");

/**
 * Clase que realiza las tareas de procesar el esquema isonomico a una expresion parseble por spritf
 * La clase realiza las siguientes tareas:
 *  1 - Lee y carga el esquema isonomico dado un ID
 *  2 - Trasforma cada nodo en una triple con sujeto, verbo y predicado, de manera que
 * 		cada variable tiene una expresion parseable por sprintf a la espera de los campos del CVS
 *  3.- Realiza una gestion manual de los namespaces ya que php solo maneja el primer xmlns
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
	protected $traza;

	/**
	* Get idEsquema
	*
	*/
	public function getId()
	{
		return $this->id;
	}

    /**
	* Get idEsquema
	*/
	 public function setId()
	{
		return $this->id;
	}

	/**
	* Get Traza
	*
	*/
	public function getTrazas()
	{
		return $this->trazas;
	}

	/** 
	 * Constructor con identificador de esquema isonomico e inicializacion del objeto de trazas
	*/
    public function __construct($id,$trazas) {
		$this->id = $id;
		$this->trazas = $trazas;
		$this->trazas->setClase("Isonomia");
		$this->trazas->LineaInfo("__construct", "Inicializa el costructor");  
    }

	/** 
	 * Funcion principal que carga el esquema y lo procesa
	 * Devuelve unn array de elementos Triple preparado para parsear
	*/
	function ProcesaEsquema()
    {   
		//Inicliza variables
		$pilaTriples = array();
		$sujeto = "";
		$sujetoParseo = "";
		$xml = "";
		//carga esquema TEXTO isonomico por ID
		$isonomia = $this->DameIsonomia($this->id);
		$this->trazas->LineaInfo("ProcesaEsquema",'Isonomia cargada con Identificador:' . $this->id);
		//carga en un DOM xml el texto
		try 
		{
			 $xml = simplexml_load_string($isonomia);
			 $this->trazas->LineaInfo("ProcesaEsquema",'Isonomia validada por simplexml');
		} catch (Exception $e) {
			 $this->trazas->LineaError("ProcesaEsquema",'Excepción capturada: ',  $e->getMessage());
		} 
		if ($this->trazas->SinError()) {
			//carga manual de los namespaces en un array (prefijo=>valor http//...)
			$this->trazas->LineaInfo("ProcesaEsquema",'Carga manual de los namespaces');
			$nameSpaces = $this->DameNameSpaces($xml->asXML());
		}

		if ($this->trazas->SinError()) {
			//llamada a la funcion recursiva por todos los nodos y subnodos
			$this->trazas->LineaInfo("ProcesaEsquema",'Llamada a la funcion recursiva por todos los nodos y subnodos');
			$this->ProcesaEntity($xml, $sujeto, $sujetoParseo, $pilaTriples,$nameSpaces);
		
			//devuelve la pila de triples preparada para parsear con sprintf
			return $pilaTriples;   
		}
	}
	/**
	 * Funcion que dado un Id devuelve el texto del esquema isonomico
	 */
	function DameIsonomia($id){
		$isonomia = "";
		$nombreFichero = "/var/www/AodPool/src/AppBundle/Command/taxonomia.xml";
		if (file_exists($nombreFichero)) {
			 $isonomia = file_get_contents($nombreFichero);
		}
		return $isonomia;
	}
	
	/**
	 * Funcion recursiva que pasa por todos los nodos y subnodos del esquema
	 * Parametros :
	 * $entity: Nodo a tratar
	 * $sujeto, $sujetoParseo: se pasa porque es el mismo para todos los subnodos primer nivel de un nodo dado
	 * &$PilaTriples: Contenedor de Triples por referencia para que cargue todas las triples en la misma pila
	 * &$nameSpaces: Manejador de Namespaces por referencia simple es el mismo
	 */
	function ProcesaEntity($entity, $sujeto, $sujetoParseo, &$PilaTriples, &$nameSpaces){
		//por cada uno de los subnodos del nodo principal
		$this->trazas->LineaInfo("ProcesaEntity",sprintf('Entra en Entity del nodo %s', $entity->getName()));
		foreach ($entity->children() as $node) {
		    $this->trazas->LineaInfo("ProcesaEntity",sprintf('Entra en sunbnodo %s => %s',$entity->getName(),$node->getName()));
		    //inicializo
		    $verbo="";
		    $verboParseo="";
		    $predicado="";
		    $predicadoParseo=""; 
		    //dependiendo del nombre del elemento manejo de una u otra manera las tres variables: 
		    //sujeto verbo predicado
			switch((string) $node->getName()) { // Obtener los atributos como indices del elemento
			    case 'Entity':  
				   //cojo el name space del Nodo
				   $ns =  $this->DameNameSpace($node->asXML()); 
				   if ($this->trazas->SinError()) 
				   {
					    //informo el sujeto
						$sujeto = trim($node['URI']);
					    //informo por que valor he de parsear
					    $sujetoParseo = $this->DameParseo($sujeto);
					    //reemplazo por %s para dprintf
					    $sujeto=str_replace('{'.$sujetoParseo.'}',"%s",$sujeto);
					    //pongo los tags de nodo
					    $sujeto = sprintf("<%s>",$sujeto);
					    $verbo = array_search($ns[0],$nameSpaces);
					    //pongo los tags de nodo
					    $verbo = sprintf("<%s>",$verbo);
					    //informo el predicado
					    $predicado =array_search($ns[1][0],$nameSpaces) . $ns[1][1];
					    //pongo las comillas al predicado
					    $predicado = sprintf("\"%s\"",$predicado);
				    }
				break;
			    case 'Property':
			        //tres posibilidades field fieldLink y link
					if (isset($node['field']))
					{
					   $this->trazas->LineaInfo("ProcesaEntity",sprintf('Nodo tipo field: %s ',$node['field']->asXML()));
					   //cargo el atributo para coger el namespace y ponerlo en el verbo
					   $ns= explode(":",$node['attribute']);
					   if (count($ns)==2) 
					   {
						    $verbo = array_search($ns[0],$nameSpaces) . $ns[1];
						    //pongo los tags de nodo dependiendo de si es string o no
						    $verbo = sprintf("<%s>",$verbo);
						    //el predicado viene en el CSV
						    if ($node['type']=="string") 
						    {
						       $predicado = "\"%s\"";
						    } else {
						       $predicado = "%s";
						    }
						    //cargo el campo por el que he de parsear
						    $predicadoParseo = str_replace("\"", "", explode("=",$node['field']->asXML())[1]);
					    } else {
						    $this->trazas->LineaError("ProcesaEntity", "No devuelve un array de dos valores:". $node['attribute']->asXML());
					    }
					} else if (isset($node['fieldLink'])) 
					{
						$this->trazas->LineaInfo("ProcesaEntity",sprintf('Nodo tipo fieldLink: %s ',$node['fieldLink']->asXML()));
					    //cargo el namespace para el verbo
					    $ns =  explode(":",$node['attribute']);
					    if (count($ns)==2) 
					    {
						   $verbo =  array_search($ns[0],$nameSpaces) . $ns[1];
						   //pongo los tags de nodo
						   $verbo = sprintf("<%s>",$verbo);
					   } else {
						   $this->trazas->LineaError("ProcesaEntity", "No devuelve un array de dos valores:". $node['attribute']->asXML());  
					   }
					   if ($this->trazas->SinError()) 
					   {
					      //cargo el namespace para el predicado
						  
							 $predicado = $node['fieldLink'];
							 $predicadoParseo =  $this->DameParseo($predicado);
							 $predicado=str_replace('{'.$predicadoParseo.'}',"%s",$predicado);
							 //pongo los tags de nodo
							 $predicado = sprintf("<%s>",$predicado);
					   }
				   } else if (isset($node['link'])){ 
					   $this->trazas->LineaInfo("ProcesaEntity",sprintf('Nodo tipo link: %s ',$node['link']->asXML()));
					   //cargo el namespace para el verbo
					   $ns =  explode(":",$node['attribute']);
					   if (count($ns)==2) 
					   {
						   $verbo =  array_search($ns[0],$nameSpaces) . $ns[1];
						    //pongo los tags de nodo
						   $verbo = sprintf("<%s>",$verbo);
						   //cargo el namespace para el predicado
						   $ns =  explode(":",$node['link']);
						   if (count($ns)==2) 
						   {
						       $predicado = array_search($ns[0],$nameSpaces) . $ns[1];
					           //pongo los tags de nodo
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
				$this->trazas->LineaInfo("ProcesaEntity",sprintf('Subnodo procesado: S: [%s=>%s] V:[%s=>%s] P:[%s=>%s]',
														$sujeto,$sujetoParseo,$verbo,$verboParseo,$predicado,$predicadoParseo));
				// Creo un objeto Triples inicializando con las trazas
				$esquemaTriple = new Triple($this->trazas);
				//Informo el objeto triple
				$esquemaTriple->InformaEsquema($sujeto,$sujetoParseo,$verbo,$verboParseo,$predicado,$predicadoParseo);
				//cargo el objeto en la pila de triples
				$PilaTriples[]=$esquemaTriple;
				//llamo recursivamente hasta completar todos los subnodos
				$this->ProcesaEntity($node, $sujeto,$sujetoParseo, $PilaTriples,$nameSpaces);
			}
		}
	}

    /**
	 * Dado un literal encuentra todas las expresiones {algo}
	 * Se utiliza para sacar los nombres de los campos por los que he de parsear
	 */
	function DameParseo($literal)
	{
		preg_match('~{(.*?)}~', $literal, $output);
		if (count($output)==2)
		   return $output[1];
		else {
			$this->trazas->LineaError("DameParseo", "No devuelve un array de dos valores proveniente de procesaEntity :". $literal);
			return null;
		} 
	}

	 /**
	 * Dado un literal encuentra todas las expresiones de definicion del namespaces del XML
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
				$this->trazas->LineaError("DameNameSpaces", "No encuentra NameSpaces". $$literal);
			}
	    } catch (Exception $e) {
			$this->trazas->LineaError("DameNameSpaces",'Excepción capturada: ',  $e->getMessage());
	    }
		return  $Items; 
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
			$this->trazas->LineaError("DameNameSpaces",'Excepción capturada: ',  $e->getMessage());
		}
		return  $Items; 
	}
}
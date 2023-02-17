<?php
namespace ApiRest\WorkerBundle\Controller;
//include_once('./geoPHP/geoPHP.inc');
//include_once('/var/www/html3/src/ApiRest/WorkerBundle/Controller/geoPHP/geoPHP.inc');

use ApiRest\WorkerBundle\Controller\Trazas;
//use ApiRest\WorkerBundle\Controller\index;
use \Datetime;
use \Math;
/**
* Triple contenedor de triples 
* La clase tiene las funcionalidades: 
*  1 -Almacenar las propiedades de las triples para ser parsedas por los valores
*  2.-Generar las triples parseando por los valores del CSV 
*  3.-Generar las triples sparq para pasarlas a virtuoso
*/
class Triple
{
	//sujeto de la triple
	protected $sujeto;
	//verbo de la triple
	protected $verbo;
	//predicado de la triple
	protected $predicado;
    //sujeto de la triple
	protected $sujetoParseo;
	//verbo de la triple
	protected $verboParseo;
	//predicado de la triple
	protected $predicadoParseo;
	//tipo de predicado de la triple
	protected $tipoPredicadoParseo;
    //valor sujeto de la triple
	protected $sujetoValor;
	//valor verbo de la triple
	protected $verboValor;
	//valor predicado de la triple
	protected $predicadoValor;
	//fila del archivo CVS
	protected $filaCVS; 
	//tripleValor
	protected $tripleValor; 
	//condition
	protected $condition; 
	//objeto traza
	protected $trazas; 
	//Get Traza
	public function getTrazas()
	{
		return $this->trazas;
	}
	//Get tripleValor
	public function getTripleValor()
	{
		return $this->tripleValor;
	}
	//Get filaCVS
	public function getFilaCVS()
	{
		return $this->filaCVS;
	}
	//Set filaCVS
	public function setFilaCVS($filaCVS)
	{
		$this->filaCVS = $filaCVS;
	}
	//Get sujeto
	public function getSujeto()
	{
		return $this->sujeto;
	}
	//Set sujeto
	public function setSujeto($sujeto)
	{
		$this->sujeto = $sujeto;
	}
	//Get sujeto Parseo
	public function getSujetoParseo()
	{
		return $this->sujetoParseo;
	}
	//Set sujeto Parseo
	public function setSujetoParseo($sujetoParseo)
	{
		$this->sujetoParseo = $sujetoParseo;
	}
	//Get sujeto valor
	public function getSujetoValor()
	{
		return $this->sujetoValor;
	}
	// Set sujeto Valor
	public function setSujetoValor($sujetoValor)
	{
		$this->sujetoValor = $sujetoValor;
	}
    //Get verbo
	public function getVerbo()
	{
		return $this->verbo;
	}
	//Set verbo
	public function setVerbo($verbo)
	{
		$this->verbo = $verbo;	
    }
    //Get verbo Parseo
	public function getVerboParseo()
	{
		return $this->verboParseo;
	}
	//Set verbo Parseo
	public function setVerboParseo($verboParseo)
	{
		$this->verbo = $verboParseo;	
	}
	//Set verbo Valor
	public function getVerboValor()
	{
		return $this->verboValor;
	}
	// Set verbo valor
	public function setVerboValor($verboValor)
	{
		$this->verboValor = $verboValor;	
	}
	// Get predicado
	public function getPredicado()
	{
		return $this->predicado;
	}
	// Set predicado
	public function setPredicado($predicado)
	{
        $this->predicado = $predicado;
    }
	//Get predicado parseo
	public function getPredicadoParseo()
	{
		return $this->predicadoParseo;
	}
	//Set predicadoParseo
	public function setPredicadoParseo($predicadoParseo)
	{
        $this->predicadoParseo = $predicadoParseo;
	}	
	// Set predicadoValor
	public function getPredicadoValor()
	{
        return $this->predicadoValor;
	}
	//Set predicadoValor
	public function setPredicadoValor($predicadoValor)
	{
        $this->predicadoValor = $predicadoValor;
	}
	//Get tipo predicado Parseo
	public function getTipoPredicadoParseo()
	{
		return $this->tipoPredicadoParseo;
	}
	// Set tipo predicadoParseo
	public function setTipoPredicadoParseo($tipoPredicadoParseo)
	{
        $this->tipoPredicadoParseo = $tipoPredicadoParseo;
	}
	// Get condicion de mostrar o no
	public function getCondition()
	{
		return $this->condition;
	}
	// Set condicion de mostrar o no
	public function setCondition($condition)
	{
        $this->condition = $condition;
	}
	// Get donde tipo de nodo
	public function getTipoNodo()
	{
		return $this->tipoNodo;
	}
	//Set donde tipo de nodo
	public function setTipoNodo($tipoNodo)
	{
        $this->tipoNodo = $tipoNodo;
	}
	// Constructor con objeto traza como parámetro
	public function __construct($trazas) {
		$this->trazas = $trazas;
		$this->trazas->setClase("Triple");
		$this->trazas->LineaDebug("__construct", "Inicializa el costructor"); 		 
	}
    //Función que se utiliza para inicia la estructura de la triple para parsearla posteriormente
	public function InformaEsquema($sujeto, $sujetoParseo, $verbo, $verboParseo, $predicado, $predicadoParseo, $tipoPredicadoParseo, $tipoNodo, $condition) {
		$this->trazas->LineaDebug("InformaEsquema", "Informa los datos de la triple");  
		$this->sujeto = $sujeto;
		$this->sujetoParseo = $sujetoParseo;
		$this->verbo = $verbo;
		$this->verboParseo = $verboParseo;
		$this->predicado = $predicado;
		$this->predicadoParseo = $predicadoParseo;
		$this->tipoPredicadoParseo = $tipoPredicadoParseo;
		$this->tipoNodo=$tipoNodo;
		$this->condition = $condition;
	}
	public function ProcesaDatos($filaCVS)
	{ 
		$this->sujetoValor ="";
		$this->verboValor ="";
		$this->predicadoValor ="";
		$this->tripleValor = "";
		if ($this->verbo != "<>")
		{			
			$this->setFilaCVS($filaCVS);
			//analizo la condicion
			$conditionVerdadera = $this->DameCondicion($this->condition,$filaCVS);
			$this->trazas->LineaDebug("ProcesaDatos", sprintf("Procesa los datos de la triple con la fila CSV: S:%s V:%s P:%s",
																	$this->condition,$this->verbo,$this->predicado)); 

			if ($conditionVerdadera) 
			{
				$this->trazas->LineaDebug("ProcesaDatos", sprintf("Procesa los datos de la triple con la fila CSV: S:%s V:%s P:%s",
																	$this->sujeto,$this->verbo,$this->predicado));  
				if (count($this->sujetoParseo)>0) {
					$this->sujetoValor = $this->MultiParseo($this->sujeto,$this->sujetoParseo,"S");
				} else {
					$this->sujetoValor = $this->sujeto;
				}
				if (strlen($this->verboParseo)>0) {
					$this->verboValor = sprintf($this->verbo, trim($filaCVS[$this->verboParseo]));
				} else {
					$this->verboValor = $this->verbo;
				}
				if (count($this->predicadoParseo)>0) {
					$this->predicadoValor = $this->MultiParseo($this->predicado,$this->predicadoParseo,"P");
				} else {
					$this->predicadoValor = $this->predicado;
				}
				if ($this->sujetoValor=="\"\""){
					$this->sujetoValor="";
				}
				if ($this->verboValor=="\"\"") {
					$this->verboValor="";
				}
				if ($this->predicadoValor=="\"\"") {
					$this->predicadoValor="";
				}
				$this->trazas->LineaDebug("ProcesaDatos", "El triple generado: " . $this->sujetoValor . $this->verboValor . $this->predicadoValor);				
				if (!empty($this->sujetoValor) && !empty($this->verboValor) && !empty($this->predicadoValor)) 
				{
					//evitar valores "none" en sujetos y objetos
					$this->trazas->LineaDebug("ProcesaDatos", sprintf("comprobar ". $this->predicadoValor .   " " . $this->sujetoValor ));
					if (!strpos(strtolower($this->predicadoValor), "none")) 
					{							
						if (!strpos($this->sujetoValor, "none")) 
						{							
							$this->tripleValor = sprintf("%s %s %s .", $this->sujetoValor,$this->verboValor,$this->predicadoValor);
						} 
						else
						{							
							$this->trazas->LineaDebug("ProcesaDatos", sprintf("Alerta: Linea ha generado una triple con algún elemento 'none': S:%s ", $this->sujetoValor));
						}
					} 
					else
					{							
						$this->trazas->LineaDebug("ProcesaDatos", sprintf("Alerta: Linea ha generado una triple con algún elemento 'none': S:%s V:%s P:%s", 
															$this->sujetoValor,$this->verboValor,$this->predicadoValor));
					}
							
				} 

				else 
				{
					$this->trazas->LineaDebug("ProcesaDatos", sprintf("Alerta: Linea ha generado una triple con algún elemento vacío: S:%s V:%s P:%s", 
									$this->sujetoValor,$this->verboValor,$this->predicadoValor));  

				}
			} 
			else 
			{
				$this->trazas->LineaDebug("ProcesaDatos", sprintf("No Procesa los datos de la triple con la fila CSV: S:%s V:%s P:%s por condition(false)",
																	$this->sujeto,$this->verbo,$this->predicado)); 
			}
		}
		else
		{
			$this->trazas->LineaDebug("ProcesaDatos", sprintf("no se procesa porque el verbo es vacío",
																	$this->condition,$this->verbo,$this->predicado));
		}
		$this->trazas->LineaInfo("__construct", "Inicia el constructor del worker para la vista: " . $id); 
		$this->trazas->setEscribeTrazasDebug($trazasDebug);
		$this->trazas->setEscribeTrazasInfo(true);
	}

	private function DameCondicion($condition,$filaCVS){
		$codicion=true;
        //si la expresion condicion no esta vacía
		if (!empty($condition)){
			$valor = $filaCVS[$condition];
			$codicion = filter_var($valor, FILTER_VALIDATE_BOOLEAN);
		}
		return $codicion;
	}

    /**
	 * Función que realiza un parseo multiple, es decir:
	 * dado un "{valor1}-{valor2}", campo1=1 campo2=2 devuelve "1-2"
	 * El parámetro donde se utiliza para:
	 * En el caso del sujeto y verbo donde se quitan espacios y se realiza un urlencode
	 */
	private function MultiParseo($valorIso, $valorParseo, $donde){
		$valor ="";
				$this->trazas->LineaDebug("MultiParseo", "Informa de los datos de MultiParseo valorIso ". $valorIso); 
		if($this->tipoNodo=="field" &&  $donde=="P") 
		{
			$filaCVS =  $this->DameValorFormato($this->filaCVS,$valorParseo);
		} 
		else
		{
			if ($donde=="S") 
			{
				$filaCVS = $this->DameValorNamespaceUri($this->filaCVS,$valorParseo);
			}
			else
			{
				$filaCVS = $this->DameValorNamespace($this->filaCVS,$valorParseo);
			}			
		}	
		if($this->tipoNodo=="fieldLink" && $valorParseo != "http://www.w3.org/2002/07/owl#sameAs")
		{			
			$filaCVS = $this->DameValorNamespaceUri($this->filaCVS,$valorParseo);
		}
		/*if($this->tipoNodo=="field" && $valorParseo == "https://www.geonames.org/ontology#SpatialThing")
		{			
			$filaCVS = $this->DameValorNamespaceShape($this->filaCVS,$valorParseo);
		}*/
		$arrayvacio = $this->CompruebaVacio($filaCVS);
		if (!$arrayvacio) {
			$cuantos = count($valorParseo);
			switch ($cuantos) {
				case 1:
					//$valorFormato = DameValorFormato($valorIso,$tipo);
					$this->trazas->LineaDebug("MultiParseo", "Informa de los datos de ValorParseo ". $valorParseo[0]); 
					$valor = sprintf($valorIso, trim($filaCVS[$valorParseo[0]]));
					break;
				case 2:
					$valor = sprintf($valorIso, trim($filaCVS[$valorParseo[0]]),
												trim($filaCVS[$valorParseo[1]]));
					break;
				case 3:
					$valor = sprintf($valorIso, trim($filaCVS[$valorParseo[0]]),
												trim($filaCVS[$valorParseo[1]]),
												trim($filaCVS[$valorParseo[2]]));
					break;
				case 4:
					$valor = sprintf($valorIso, trim($filaCVS[$valorParseo[0]]),
												trim($filaCVS[$valorParseo[1]]),
												trim($filaCVS[$valorParseo[2]]),
												trim($filaCVS[$valorParseo[3]]));
					break;
				case 5:
					$valor = sprintf($valorIso, trim($filaCVS[$valorParseo[0]]),
												trim($filaCVS[$valorParseo[1]]),
												trim($filaCVS[$valorParseo[2]]),
												trim($filaCVS[$valorParseo[3]]),
												trim($filaCVS[$valorParseo[4]]));
					break;
				case 6:
					$valor = sprintf($valorIso, trim($filaCVS[$valorParseo[0]]),
												trim($filaCVS[$valorParseo[1]]),
												trim($filaCVS[$valorParseo[2]]),
												trim($filaCVS[$valorParseo[3]]),
												trim($filaCVS[$valorParseo[4]]),
												trim($filaCVS[$valorParseo[5]]));
					break;
				case 7:
					$valor = sprintf($valorIso, trim($filaCVS[$valorParseo[0]]),
												trim($filaCVS[$valorParseo[1]]),
												trim($filaCVS[$valorParseo[2]]),
												trim($filaCVS[$valorParseo[3]]),
												trim($filaCVS[$valorParseo[4]]),
												trim($filaCVS[$valorParseo[5]]),
												trim($filaCVS[$valorParseo[6]]));
					break;
				case 8:
					$valor = sprintf($valorIso, trim($filaCVS[$valorParseo[0]]),
												trim($filaCVS[$valorParseo[1]]),
												trim($filaCVS[$valorParseo[2]]),
												trim($filaCVS[$valorParseo[3]]),
												trim($filaCVS[$valorParseo[4]]),
												trim($filaCVS[$valorParseo[5]]),
												trim($filaCVS[$valorParseo[6]]),
												trim($filaCVS[$valorParseo[7]]));
					break;
				case 9:
					$valor = sprintf($valorIso, trim($filaCVS[$valorParseo[0]]),
												trim($filaCVS[$valorParseo[1]]),
												trim($filaCVS[$valorParseo[2]]),
												trim($filaCVS[$valorParseo[3]]),
												trim($filaCVS[$valorParseo[4]]),
												trim($filaCVS[$valorParseo[5]]),
												trim($filaCVS[$valorParseo[6]]),
												trim($filaCVS[$valorParseo[7]]),
												trim($filaCVS[$valorParseo[8]]));
					break;
				case 10:
					$valor = sprintf($valorIso, trim($filaCVS[$valorParseo[0]]),
												trim($filaCVS[$valorParseo[1]]),
												trim($filaCVS[$valorParseo[2]]),
												trim($filaCVS[$valorParseo[3]]),
												trim($filaCVS[$valorParseo[4]]),
												trim($filaCVS[$valorParseo[5]]),
												trim($filaCVS[$valorParseo[6]]),
												trim($filaCVS[$valorParseo[7]]),
												trim($filaCVS[$valorParseo[8]]),
												trim($filaCVS[$valorParseo[9]]));
					break;
			}
		}
		return $valor;
	}


	//funcion que compruebas si la fila CSV no tiene nada
	function CompruebaVacio($filaCVS){
		$vacio = true;
		foreach ($filaCVS as $value) {
			$vacio &= empty($value);
		}
		return $vacio;
	}

	//funcion que da formato tipo Virtuoso al dato segun el tipo del esquema XML indicado
	function DameValorFormato($filaCVS,$valorParseo) {
		$filaCVSNamespace = array();
        foreach ($valorParseo as $value) {
			if (empty($filaCVS[$value])) {
				$filaCVSNamespace[$value] = "";
			} else if (($this->tipoNodo=="field") || ($this->tipoNodo=="value")) {
				$this->trazas->LineaInfo("field", "field ". $this->tipoNodo ); 
				
				$this->trazas->LineaInfo("value", "value ". $this->tipoNodo ); 
				$tipo = strtolower($this->tipoPredicadoParseo);
				$this->trazas->LineaInfo("tipoPredicadoParseo", "tipoPredicadoParseo ". $this->tipoPredicadoParseo );
				if (empty($tipo)){
					$valor = $filaCVS[$value];
					$filaCVSNamespace[$value] = $valor;
				} else {
					switch ($tipo) {
						case 'float': 
							$valor = $filaCVS[$value];
							$valor = str_replace(".","",$valor);
							$valor = str_replace(",",".",$valor);
							$filaCVSNamespace[$value] = $valor;
						break;
						case 'decimal':
							$valor = $filaCVS[$value];
							$valor = str_replace(".","",$valor);
							$valor = str_replace(",",".",$valor);
							$filaCVSNamespace[$value] = $valor;
						break;
						case 'boolean':
							$valor = $filaCVS[$value];
							if (filter_var($valor, FILTER_VALIDATE_BOOLEAN)) {
								$filaCVSNamespace[$value] = "true";
							} else {
								$filaCVSNamespace[$value] = "false";
							}
						break;
						case 'datetime':
						    $formatoVirtuoso = 'Y-m-d H:i:s';
							$formato = 'Y-m-d H:i:s';
							$valor="";
							$date = str_replace(" 0:00", "", $filaCVS[$value]);
							$date = str_replace("T00:00:00Z", "", $date);
							$date = str_replace("T00:00:00", "", $date);
							$this->trazas->LineaInfo("datetime", "Fecha transformada ". $date . " " . $filaCVS[$value]); 
							$Esfecha = true;
							if (DateTime::createFromFormat($formato, $date) === false) {
								$formato = 'd-m-Y H:i:s';
								if (DateTime::createFromFormat($formato, $date) === false) {
									$formato = 'Y-m-d';
									if (DateTime::createFromFormat($formato, $date) === false) {
										$formato = 'd-m-Y';
										if (DateTime::createFromFormat($formato, $date) === false) {
											$formato = 'd/m/Y';
											if (DateTime::createFromFormat($formato, $date) === false) {
												$formato = 'd/m/Y H:i:s';
												if (DateTime::createFromFormat($formato, $date) === false) {
													$formato = 'd/m/Y H:i';
													if (DateTime::createFromFormat($formato, $date) === false) {
														$Esfecha = false;
													}
												}
											}
										}
											
									}
								}
							} 
							
							$this->trazas->LineaInfo("datetime", "Fecha transformada ". $Esfecha ); 
							if ($Esfecha)
							{
								if (strtotime(str_replace("/", "-",$date)))
								{
									$fecha = new DateTime(str_replace("/", "-", $date));
									$valor = date_format($fecha,  $formatoVirtuoso);
									$valor = str_replace(" ", "T", $valor);
								}
								else
								{
									$this->trazas->LineaInfo("datetime", "Error al crear fecha basada en: ". $date );
								}
							}
							$filaCVSNamespace[$value] = $valor;	
						break;
						case 'shape':
							$valorShape = $this->ObtenerShapeDeSQL ($filaCVS[$value]);
							$this->trazas->LineaDebug("shape", "Shape transformada ". $valorShape . " " . $filaCVS[$value]); 							
							
							$filaCVSNamespace[$value] = $valorShape;	
						break;
						case 'time':
							$formato = 'H:i:s';
							$valor="";
							$date = $filaCVS[$value];
							if (DateTime::createFromFormat($formato, $date) !== false) {
								$fecha = new DateTime($filaCVS[$value]);
								$valor = date_format($fecha, $formato);
							} 
							$filaCVSNamespace[$value] = $valor;	
						break;			  
						default:
						$filaCVSNamespace[$value] =  $filaCVS[$value];
						break;
					}
				}
			} else {
				$filaCVSNamespace[$value] = $filaCVS[$value];
			}
		}
		return $filaCVSNamespace;
	}

	function DameValorNamespace($filaCVS,$valorParseo) {
		$filaCVSNamespace = array();
        foreach ($valorParseo as $value) {
			$valor = $filaCVS[$value];
			$valor = str_replace(" ", "-", $valor);
			$valor = str_replace("/", "-", $valor);
			//se comprueba que si no es url para escaparlo. Hasta ahora los que son url son expresiones completas del tipo {$valor}
			//OJO Purde que eun futuro pueda fallar en expesiones de plantilla tipo http://http://opendata.aragon.es?url={$valor} 
			if(filter_var($valor, FILTER_VALIDATE_URL) === FALSE)
			{
				if(strpos($valor,"http://opendata.aragon.es/recurso/territorio/Municipio") !== 0 ){
					if (strpos($valor,"http://opendata.aragon.es/recurso/territorio") !== 0)
						{
							$valor = urlencode($valor);
						}
				}
			}
			
			$filaCVSNamespace[$value] = $valor;
		}
		return $filaCVSNamespace;
	}
	function DameValorNamespaceUri($filaCVS,$valorParseo) {
		$filaCVSNamespace = array();
        foreach ($valorParseo as $value) {
			$this->trazas->LineaInfo("valorParseo", "valorParseo ". $value ); 
			$this->trazas->LineaDebug("DameValorNamespaceUri", sprintf("Valor parseo S:%s  ",$value)); 
			$this->trazas->LineaDebug("DameValorNamespaceUri", sprintf("filaCSV Valor parseo S:%s  ",$filaCVS[$value])); 
			if(strpos($value, 'ARAGOPEDIA') !== false or strpos($value, 'datosgobes') !== false or strpos($filaCVS[$value], 'http://') !== false or  strpos($value, 'aragopedia') !== false or strpos($value, 'wiki') !== false or strpos($value, 'dbPedia') !== false)
			{ 
				$this->trazas->LineaInfo("valorParseo", "ARAGOPEDIA ". $value ); 
				$valor = $filaCVS[$value];	
				$filaCVSNamespace[$value] = $valor;
			}
			else
			{
				if(strpos($value, 'provincia') !== false or strpos($value, 'Provincia') !== false or strpos($value, 'PROVINCIA') !== false or strpos($value, 'PROVINCIA_ESTABLECIMIENTO') !== false or strpos($value, 'COD_PROV') !== false or strpos($value, 'CPROVI') !== false or strpos($value, 'COD_PROV') !== false or strpos($value, 'cprovi') !== false or strpos($value, 'cprov') !== false)
				{
					$this->trazas->LineaDebug("DameValorNamespaceUri", sprintf("Es diputación  ",$filaCVS[$value])); 
					$valor = $filaCVS[$value];	
					if (is_numeric($valor[0])) 
					{
						$filaCVSNamespace[$value] = $valor;
						if ($valor == "50")
						{
							$filaCVSNamespace[$value] = "7823";
						}
						if ($valor == "22")
						{
							$filaCVSNamespace[$value] = "7824";
						}
						if ($valor == "44")
						{
							$filaCVSNamespace[$value] = "7825";
						}
					}
					else
					{
						$this->trazas->LineaDebug("DameValorNamespaceUri", sprintf("No es número: provincia  S:%s  ",$valor)); 
						$valor = $this->ObtenerIdentificadorDeSQL ($valor ,"P");
						
						$filaCVSNamespace[$value] = $valor;
					}
					
				}
				elseif(strpos($value, 'municipio') !== false or strpos($value, 'Municipio') !== false or strpos($value, 'localidad_establecimiento') !== false  
				or strpos($value, 'LOCALIDAD') !== false or strpos($value, 'MUNICIPIO') !== false or 
				strpos($value, 'LOCA_MUN') !== false or strpos($value, 'LOCALIDAD_ESTABLECIMIENTO') !== false or 
				strpos($value, 'MUNICIPIO_ESTABLECIMIENTO') !== false or strpos($value, 'DLOCAL') !== false or strpos($value, 'dlocal') !== false
				or strpos($value, 'localidad') !== false or strpos($value, 'Localidad') !== false or strpos($value, 'loca_mun') !== false or strpos($value, 'municipio_establecimiento') !== false
				or strpos($value, 'Localidad_establecimiento') !== false or  strpos($value, 'Municipio_establecimiento') !== false
				or strpos($value, 'nombre_municipio') !== false or strpos($value, 'NOMBRE_MUNICIPIO') !== false)

				{
					$this->trazas->LineaDebug("DameValorNamespaceUri", sprintf("Es municipio  ",$filaCVS[$value])); 
					$valor = $filaCVS[$value];	
					if (is_numeric($valor[0])) 
					{
						$filaCVSNamespace[$value] = $valor;
					}
					else
					{
						$this->trazas->LineaDebug("DameValorNamespaceUri", sprintf("No es número: municipio  S:%s  ",$valor)); 
						
						$valor = $this->ObtenerIdentificadorDeSQL ($valor ,"M");
						
						$filaCVSNamespace[$value] = $valor;
					}

				}

				elseif(strpos($value, 'comarca') !== false  or strpos($value, 'NOMBRE_COMARCA') !== false or strpos($value, 'codigo_comarc') !== false or strpos($value, 'CODIGO_COMARC') !== false or strpos($value, 'CCOMAR') !== false or strpos($value, 'ccomar') !== false)
				{
					$this->trazas->LineaDebug("DameValorNamespaceUri", sprintf("Es comarca  ",$filaCVS[$value])); 
					$valor = $filaCVS[$value];	
					if (is_numeric($valor[0])) 
					{
						$filaCVSNamespace[$value] = (int)$valor;
					}
					else
					{
						$this->trazas->LineaDebug("DameValorNamespaceUri", sprintf("No es número: comarca  S:%s  ",$valor)); 
						$valor = $this->ObtenerIdentificadorDeSQL ($valor ,"C");
						
						$filaCVSNamespace[$value] = (int)$valor;
					}
						

				}
			
				else
				{
					$this->trazas->LineaDebug("DameValorNamespaceUri", sprintf("Valor sin modificar S:%s  ",$filaCVS[$value])); 

					$valor = $filaCVS[$value];	
					$valor = str_replace(
						array(' a ', ' de ', ' al ', ' del ', ' y ', ' o ', ' u ', ' e ', 'con ', ' ni ', ' los ', ' en ', ' de ', ' la', ' las ', ' '),
						"-",
						$valor
					);			
					$valor = str_replace(
						array('á', 'à', 'ä', 'â', 'ª', 'Á', 'À', 'Â', 'Ä','é', 'è', 'ë', 'ê', 'É', 'È', 'Ê', 'Ë','í', 'ì', 'ï', 'î', 'Í', 'Ì', 'Ï', 'Î','ó', 'ò', 'ö', 'ô', 'Ó', 'Ò', 'Ö', 'Ô','ú', 'ù', 'ü', 'û', 'Ú', 'Ù', 'Û', 'Ü','ñ', 'Ñ', 'ç', 'Ç'),
						array('a', 'a', 'a', 'a', 'a', 'A', 'A', 'A', 'A','e', 'e', 'e', 'e', 'E', 'E', 'E', 'E','i', 'i', 'i', 'i', 'I', 'I', 'I', 'I','o', 'o', 'o', 'o', 'O', 'O', 'O', 'O','u', 'u', 'u', 'u', 'U', 'U', 'U', 'U','n', 'N', 'c', 'C'),
						$valor
					);
										
					$valor = str_replace("-", "aa789bb", $valor);
					$valor = preg_replace('([^A-Za-z0-9])', '', $valor);
					$valor = str_replace( "aa789bb","-", $valor);			
					$valor = strtolower($valor);
					$this->trazas->LineaInfo("ProcesaDatos", "Valor Modificado ". $valor ); 
					$this->trazas->LineaDebug("ProcesaDatos", sprintf("Valor Modificado S:%s  ",$valor)); 
					$filaCVSNamespace[$value] = $valor;
				}
			}
		}
		return $filaCVSNamespace;
	}
	function DameValorNamespaceShape($filaCVS,$valorParseo) {
		
        foreach ($valorParseo as $value) {
			$this->trazas->LineaDebug("DameValorNamespaceShape", sprintf("Valor parseo S:%s  ",$value)); 
			if(strpos($value, 'shape') !== false )
			{
				/*$serverName="biv-aodback-01.aragon.local,5432";
				$connectionInfo=array("Database"=>"aodpool2", "UID"=>"gnoss_pg","PWD"=>"gn0ss_pg");
				$conn = sqlsrv_connect( $serverName, $connectionInfo);
				if( $dbconn3 ) {
					LineaInfo("DameValorNamespaceShape", sprintf("Conectado S:%s  ",$value));
				}else{
					LineaInfo("DameValorNamespaceShape", sprintf("Error S:%s  ",$value));
					die( print_r( sqlsrv_errors(), true));
				}	*/
				$this->trazas->LineaDebug("DameValorNamespaceShape", sprintf("Dentro shape S:%s  ",$filaCVS[$value])); 
				$conn = pg_connect("host=biv-aodback-01.aragon.local port=5432 dbname=aodpool2 user=gnoss_pg password=gn0ss_pg");
				$this->trazas->LineaDebug("DameValorNamespaceShape", sprintf("Despues conn S:%s  ",$conn)); 
							
				//$sql="SELECT ST_AsText('$filaCVS[$value]');";
				
				$sql="select * from public.facetas;";
				$result = pg_query($conn, $sql);
				if (!$result) 
				{
					
					$this->trazas->LineaDebug("DameValorNamespaceShape", sprintf("Error consulta S:%s  ",$conn)); 
				}
				else
				{

					while ($row = pg_fetch_row($result)) 
					{
						$this->trazas->LineaDebug("DameValorNamespaceShape", sprintf("resultado consulta  %s  ",$row[1] )); 
						
					}
				}
				
				//$valor = $filaCVS[$value];	
				//$filaCVSNamespace[$value] = $valor;
			}
			else
			{
				$valor = $filaCVS[$value];	
				$filaCVSNamespace[$value] = $valor;
				$this->trazas->LineaDebug("DameValorNamespaceShape", sprintf("No es  shape S:%s  ",$filaCVS[$value])); 
			}
		}
		return $filaCVSNamespace;
	}
	function ObtenerIdentificadorDeSQL($nombre ,$tipo) 
	{	$slug = str_replace(
		array('á', 'à', 'ä', 'â', 'ª', 'Á', 'À', 'Â', 'Ä','é', 'è', 'ë', 'ê', 'É', 'È', 'Ê', 'Ë','í', 'ì', 'ï', 'î', 'Í', 'Ì', 'Ï', 'Î','ó', 'ò', 'ö', 'ô', 'Ó', 'Ò', 'Ö', 'Ô','ú', 'ù', 'ü', 'û', 'Ú', 'Ù', 'Û', 'Ü','ñ', 'Ñ', 'ç', 'Ç'),
		array('a', 'a', 'a', 'a', 'a', 'A', 'A', 'A', 'A','e', 'e', 'e', 'e', 'E', 'E', 'E', 'E','i', 'i', 'i', 'i', 'I', 'I', 'I', 'I','o', 'o', 'o', 'o', 'O', 'O', 'O', 'O','u', 'u', 'u', 'u', 'U', 'U', 'U', 'U','n', 'N', 'c', 'C'),
		$nombre
		);							
		$slug = preg_replace('([^A-Za-z0-9])', '', $slug);		
		$slug = strtolower($slug);
		#tratamos las excepciones de los municipios Almunia de Doña Godina (La) / Quinto (Quinto de Ebro)
		if ($slug == 'almuniadedonagodina') {
			$slug = 'almuniadedonagodinala';
		}
		if ($slug == 'quintodeebro') {
			$slug = 'quinto';
		}
		if ($slug == 'jacetania') {
			$slug = 'lajacetania';
		}
		
	
		$this->trazas->LineaDebug("ObtenerIdentificadorDeSQL", sprintf("Valores Tipo:%s  nombre:%s slug:%s ",$tipo, $nombre, $slug)); 
						 
		$conn = pg_connect("host=biv-aodback-01.aragon.local port=5432 dbname=aodpool2 user=gnoss_pg password=gn0ss_pg");
		//$this->trazas->LineaDebug("ObtenerIdentificadorDeSQL", sprintf("Despues conn S:%s  ",$conn)); 
									
		$sql="SELECT code FROM public.lugares WHERE slug='" . $slug . "' and type='" . $tipo . "';";		
		
		$this->trazas->LineaDebug("ObtenerIdentificadorDeSQL", sprintf("Consulta S:%s  ",$sql));
		$result = pg_query($conn, $sql);
		if (!$result)
		{			
			$this->trazas->LineaDebug("ObtenerIdentificadorDeSQL", sprintf("Error consulta S:%s  ",$result)); 
			return "";
		}
		else
		{
			while ($row = pg_fetch_row($result)) 
			{
				$this->trazas->LineaDebug("ObtenerIdentificadorDeSQL", sprintf("Resultado consulta  %s - %s ",$row[0], $row[1] )); 

				return $row[0];
			}
		}
	}
	function ObtenerShapeDeSQL($shape ) 
	{	
		$this->trazas->LineaDebug("ObtenerShapeDeSQL", sprintf("Valores shape:%s   ",$shape)); 
						 
		$conn = pg_connect("host=biv-aodback-01.aragon.local port=5432 dbname=aodpool2 user=gnoss_pg password=gn0ss_pg connect_timeout=5");
		//$this->trazas->LineaInfo("ObtenerShapeDeSQL", sprintf("Despues conn S:%s  ",$conn)); 							
		//$sql="SELECT ST_AsText(ST_Transform('". $shape ."',4258));";
		$sql="SELECT ST_AsText(ST_Transform(ST_GeometryFromText(ST_AsText('". $shape ."'),25830),4258));";		
		//$sql="select version();";	
		$this->trazas->LineaDebug("ObtenerShapeDeSQL", sprintf("Consulta S:%s  ",$sql));
		$result = pg_query($conn, $sql);
		if (!$result)
		{
			$this->trazas->LineaDebug("ObtenerShapeDeSQL", sprintf("Error consulta S:%s  ",$result)); 
			return "";
		}
		else
		{
			while ($row = pg_fetch_row($result)) 
			{
				$this->trazas->LineaDebug("ObtenerShapeDeSQL", sprintf("Resultado consulta  %s  " . $row[0] )); 

				return $row[0];
			}
		}
	}

	
}


	

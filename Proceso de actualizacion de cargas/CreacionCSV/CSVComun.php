<?php
$idVista = $argv[1];
$path = "/data/apps/ActualizarAODv2/VistasXml/Vista$idVista/Vista_".$idVista."_1.xml";
$CSVDestino = "/data/apps/ActualizarAODv2/VistasCsv/Vista$idVista/Vista_$idVista.csv";
$csv = fopen($CSVDestino, 'w+');

//$path = "../VistasXML/Vista$idVista/Vista_".$idVista."_1.xml";
//echo $path;

//read entire file into string
$xmlfile = file_get_contents($path);
if (is_string($xmlfile)){
    $xml = simplexml_load_string($xmlfile);

    $json  = json_encode($xml);
    $xmlArr = json_decode($json, true);
    //Le quito una dimension al array 
    $arrayDatos = $xmlArr["list-item"];
    echo print_r($arrayDatos);
    //Primero introducimos las cabeceras, despues los datos
    $arrayCabecera = array();
    foreach (array_keys($arrayDatos[0]) as $cabecera){
        array_push($arrayCabecera, $cabecera);
    }
    fputcsv($csv, $arrayCabecera, ";");

    foreach ($arrayDatos as $fila){
        $datos = array();
        foreach ($fila as $dato){
            //Si es un array, esta vacio
            if (is_array($dato) or empty($dato)){
                array_push($datos, "");
            }
            else{
                array_push($datos, $dato);
            }
        }
        @fputcsv($csv, $datos, ";");
    }

}



//echo "\n". print_r($arrayDatos);

?>
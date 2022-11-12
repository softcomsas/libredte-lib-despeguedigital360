<?php

// respuesta en texto plano
header('Content-type: text/plain; charset=ISO-8859-1');

// incluir archivos php de la biblioteca y configuraciones
include 'inc.php';

// datos
$nota_credito = [
    'Encabezado' => [
        'IdDoc' => [
            'TipoDTE' => 61,
            'Folio' => 4,
            // 'MntBruto' => 1,
        ],
        'Emisor' => [
            'RUTEmisor' => '76192083-9',
            'RznSoc' => 'SASCO SpA',
            'GiroEmis' => 'Servicios integrales de informática',
            'Acteco' => 726000,
            'DirOrigen' => 'Santiago',
            'CmnaOrigen' => 'Santiago',
        ],
        'Receptor' => [
            'RUTRecep' => '60803000-K',
            'RznSocRecep' => 'Servicio de Impuestos Internos',
            'GiroRecep' => 'Gobierno',
            'DirRecep' => 'Alonso Ovalle 680',
            'CmnaRecep' => 'Santiago',
        ],
        'Totales' => [
            // estos valores serán calculados automáticamente
            'MntNeto' => 0,
            'TasaIVA' => \sasco\LibreDTE\Sii::getIVA(),
            'IVA' => 0,
            'MntTotal' => 0,
        ],
    ],
    'Detalle' => [
        [
            'NmbItem' => 'Conectores RJ45',
            'QtyItem' => 450,
            'PrcItem' => 70,
        ],
    ],
    'Referencia' => [
        'TpoDocRef' => 33,
        'FolioRef' => 1,
        "FchRef" => "2016-01-01",
        'CodRef' => 1,
        'RazonRef' => 'Anula factura',
    ],
];
$caratula = [
    //'RutEnvia' => '11222333-4', // se obtiene de la firma
    'RutReceptor' => '60803000-K',
    'FchResol' => '2014-12-05',
    'NroResol' => 80,
];

// :::::::::::::::::::::::::::::::::::::::::::::::::::: GLOSARIO :::::::::::::::::::::::::::::::::::::::::::::::::::::::
/*
'MntBruto'  : Indica si las líneas de detalle, descuentos y recargos se expresan en montos brutos. (Sólo para documentos sin impuestos adicionales) .
    (Posibles valores, No es obligatorio poner esta línea si no cumple con el criterio)
    # 1: Montos de líneas de detalle vienen expresados en montos brutos

'Totales'   : Se dejan con los valores ceros ya que la librería calcula automático todo.
'Detalle'   : Se debe cargar los productos que tenía la factura a la que se hace nota de crédito.
'TpoDocRef' : Se pone 33 ya que se hace nota de crédito a una factura electronica, se puede hacer a boleta tambien.
'FolioRef'  : Folio que tenía la factura a la que se hace nota de crédito.
"FchRef"    : Fecha en que se emitió la factura o boleta a la que se le hace la nota de crédito.
'CodRef'    : Código de referenica por lo que se hace la nota de crédito. 
    (Posibles valores)
    # 1: Anula documento de referencia
    # 2: Corrige texto documento referencia
    # 3: Corrige montos

'RazonRef'  : Es un texto que se debe aclarar dependiente del CodRef seleccionado. Este texto lo debe mandar el usuario desde formulario.
    (Ejemplo de valores)
    # Para CodRef 1: "ANULA FACTURA"
    # Para CodRef 2: "CORRIGE GIRO DEL RECEPTOR"
    # Para CodRef 3: "DEVOLUCION DE MERCADERIAS"
*/

// solicitar ambiente desarrollo con configuración
\sasco\LibreDTE\Sii::setAmbiente(\sasco\LibreDTE\Sii::CERTIFICACION);

// Objetos de Firma y Folios
$Firma = new \sasco\LibreDTE\FirmaElectronica($config["firma"]);

$Folios = new \sasco\LibreDTE\Sii\Folios(file_get_contents('folios/61.xml'));

// generar XML del DTE timbrado y firmado
$DTE = new \sasco\LibreDTE\Sii\Dte($nota_credito);
$DTE->timbrar($Folios);
$DTE->firmar($Firma);

// generar sobre con el envío del DTE y enviar al SII
$EnvioDTE = new \sasco\LibreDTE\Sii\EnvioDte();
$EnvioDTE->agregar($DTE);
$EnvioDTE->setFirma($Firma);
$EnvioDTE->setCaratula($caratula);
$EnvioDTE->generar();
if ($EnvioDTE->schemaValidate()) {
    echo $EnvioDTE->generar();
    $track_id = $EnvioDTE->enviar();
    var_dump($track_id);
}

// si hubo algún error se muestra
foreach (\sasco\LibreDTE\Log::readAll() as $log)
    echo $log, "\n";
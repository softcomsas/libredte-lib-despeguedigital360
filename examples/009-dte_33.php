<?php

/**
 * LibreDTE
 * Copyright (C) SASCO SpA (https://sasco.cl)
 *
 * Este programa es software libre: usted puede redistribuirlo y/o
 * modificarlo bajo los términos de la Licencia Pública General Affero de GNU
 * publicada por la Fundación para el Software Libre, ya sea la versión
 * 3 de la Licencia, o (a su elección) cualquier versión posterior de la
 * misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero
 * SIN GARANTÍA ALGUNA; ni siquiera la garantía implícita
 * MERCANTIL o de APTITUD PARA UN PROPÓSITO DETERMINADO.
 * Consulte los detalles de la Licencia Pública General Affero de GNU para
 * obtener una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General Affero de GNU
 * junto a este programa.
 * En caso contrario, consulte <http://www.gnu.org/licenses/agpl.html>.
 */

/**
 * @file 009-dte_33.php
 *
 * CASO 1
 * DOCUMENTO    FACTURA ELECTRONICA
 *
 * ITEM                    CANTIDAD        PRECIO UNITARIO
 * Cajón AFECTO               123             923
 * Relleno AFECTO               53            1473
 *
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2015-12-15
 */

// respuesta en texto plano
header('Content-type: text/plain; charset=ISO-8859-1');

// incluir archivos php de la biblioteca y configuraciones
include 'inc.php';

// solicitar ambiente desarrollo con configuración
\sasco\LibreDTE\Sii::setAmbiente(\sasco\LibreDTE\Sii::CERTIFICACION);

// datos
$factura = [
    'Encabezado' => [
        'IdDoc' => [
            'TipoDTE' => 39,
            'Folio' => 1,
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
    ],
    'Detalle' => [
        [
            'NmbItem' => 'Cajón AFECTO',
            'QtyItem' => 123,
            'PrcItem' => 923,
        ],
        [
            'NmbItem' => 'Relleno AFECTO',
            'QtyItem' => 53,
            'PrcItem' => 1473,
        ],
    ],
];
$caratula = [
    //'RutEnvia' => '11222333-4', // se obtiene de la firma
    'RutReceptor' => '60803000-K',
    'FchResol' => '2014-08-22',
    'NroResol' => 80,
];

// Objetos de Firma y Folios
$Firma = new \sasco\LibreDTE\FirmaElectronica($config["firma"]);

$Folios = new \sasco\LibreDTE\Sii\Folios(file_get_contents('folios/39.xml'));

// generar XML del DTE timbrado y firmado
$DTE = new \sasco\LibreDTE\Sii\Dte($factura);
$DTE->timbrar($Folios);
$DTE->firmar($Firma);

// generar sobre con el envío del DTE y enviar al SII
$EnvioDTE = new \sasco\LibreDTE\Sii\EnvioDte();
$EnvioDTE->agregar($DTE);
$EnvioDTE->setFirma($Firma);
$EnvioDTE->setCaratula($caratula);
$EnvioDTE->generar();
if ($EnvioDTE->schemaValidate()) {

    // Esta es la acción solo para cuando es una factura, que se obtiene el trackid
    // echo $EnvioDTE->generar();
    // $track_id = $EnvioDTE->enviar();
    // var_dump($track_id);

    //  Aqui arranca el detalle para cuando es boleta
    $xml = $EnvioDTE->generar();
    if (is_writable('xml/EnvioBOLETA.xml'))
        file_put_contents('xml/EnvioBOLETA.xml', $xml);

    $boletas = 'xml/EnvioBOLETA.xml';

    // cargar XML boletas
    $EnvioBOLETA = new \sasco\LibreDTE\Sii\EnvioDte();
    $EnvioBOLETA->loadXML(file_get_contents($boletas));

    // crear objeto para consumo de folios
    $ConsumoFolio = new \sasco\LibreDTE\Sii\ConsumoFolio();
    $ConsumoFolio->setFirma(new \sasco\LibreDTE\FirmaElectronica($config));
    $ConsumoFolio->setDocumentos([39, 41, 61]);

    // agregar detalle de boletas
    foreach ($EnvioBOLETA->getDocumentos() as $Dte) {
        $ConsumoFolio->agregar($Dte->getResumen());
    }

    // crear carátula para el envío (se hace después de agregar los detalles ya que
    // así se obtiene automáticamente la fecha inicial y final de los documentos)
    $CaratulaEnvioBOLETA = $EnvioBOLETA->getCaratula();
    $ConsumoFolio->setCaratula([
        'RutEmisor' => $CaratulaEnvioBOLETA['RutEmisor'],
        'FchResol' => $CaratulaEnvioBOLETA['FchResol'],
        'NroResol' => $CaratulaEnvioBOLETA['NroResol'],
    ]);

    // generar, validar schema y mostrar XML
    $ConsumoFolio->generar();
    if ($ConsumoFolio->schemaValidate()) {
        //echo $ConsumoFolio->generar();
        $track_id = $ConsumoFolio->enviar();
        var_dump($track_id);
    }

    // si hubo errores mostrar
    foreach (\sasco\LibreDTE\Log::readAll() as $error)
        echo $error, "\n";
}

// si hubo algún error se muestra
foreach (\sasco\LibreDTE\Log::readAll() as $log)
    echo $log, "\n";
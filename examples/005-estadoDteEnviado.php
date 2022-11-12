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
 * @file 005-estado_envio_dte.php
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2015-09-16
 */

// respuesta en texto plano
header('Content-type: text/plain');

// incluir archivos php de la biblioteca y configuraciones
include 'inc.php';

// solicitar ambiente producción
\sasco\LibreDTE\Sii::setAmbiente();
echo \sasco\LibreDTE\Sii::wsdl('CrSeed'), "\n";

// // solicitar ambiente desarrollo con configuración
// \sasco\LibreDTE\Sii::setAmbiente(\sasco\LibreDTE\Sii::CERTIFICACION);
// echo \sasco\LibreDTE\Sii::wsdl('CrSeed'), "\n";
// echo \sasco\LibreDTE\Sii::wsdl('GetTokenFromSeed'), "\n\n";

// solicitar token
$token = \sasco\LibreDTE\Sii\Autenticacion::getToken($config["firma"]);
if (!$token) {
    foreach (\sasco\LibreDTE\Log::readAll() as $error)
        echo $error, "\n";
    exit;
}


// consultar estado enviado
$rut = '7555986';
$dv = '0';
$trackID = '7929930948';
$estado = \sasco\LibreDTE\Sii::request('QueryEstUp', 'getEstUp', [$rut, $dv, $trackID, $token]);

// si el estado se pudo recuperar se muestra estado y glosa
if ($estado !== false) {
    print_r([
        'codigo' => (string)$estado->xpath('/SII:RESPUESTA/SII:RESP_HDR/ESTADO')[0],
        'glosa' => (string)$estado->xpath('/SII:RESPUESTA/SII:RESP_HDR/GLOSA')[0],
    ]);
}

// mostrar error si hubo
foreach (\sasco\LibreDTE\Log::readAll() as $error)
    echo $error, "\n";
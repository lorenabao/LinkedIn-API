<?php

include_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor/autoload.php';



// import client class
use LinkedIn\Client;
use LinkedIn\Scope;
use LinkedIn\AccessToken;


session_start();

$client = new Client('78catwn5u7d4ob', 'zZ2RrkbBMsWeAGBV');

$tokenString = file_get_contents('token.json');
$tokenData = json_decode($tokenString, true);
// instantiate access token object from stored data
$accessToken = new AccessToken($tokenData['token'], $tokenData['expiresAt']);

// set token for client
$client->setAccessToken($accessToken);


$mysqli = new mysqli("localhost", "ipxes_linkedin", "Tecontrol.2021", "ipxes_linkedin");
$result_acc = $mysqli->query('SELECT * FROM cuenta where cuenta_id=4');

// google sheets
$clientGoogle = new \Google_Client();

$clientGoogle->setApplicationName('Google Sheets and PHP');

$clientGoogle->setScopes([\Google_Service_Sheets::SPREADSHEETS]);

$clientGoogle->setAccessType('offline');

$clientGoogle->setAuthConfig(__DIR__ . '/credentials.json');

$service = new Google_Service_Sheets($clientGoogle);

$spreadsheetId = "1Iqyva8ZXGvkeo-_-LDPrb_xWgMxQW-jM2j3xi_RltZ0"; // Fish Farm Feeder

$update_range  = "Empresas!A2:Z";


$fecha_inicio = '2021/01/01';
$fecha_fin = date('Y-m-d');

// datos
while ($row = $result_acc->fetch_assoc()) {
    $idxcuenta = $row['cuenta_id'] . '-' . $row['codigo'];
    $resultados[$idxcuenta]['nombre'] = $row['nombre'];
    $codigo = $row['codigo'];
    $nombre = $row['nombre'];
    $acumulado = 0;

    try {
        $campania = $client->getReportAds('507211983', 'MEMBER_COMPANY', 'DAILY', $fecha_inicio, $fecha_fin);

        $result_grupo_lim = $mysqli->query('SELECT * FROM subgrupo WHERE cuenta_id="' . $row['cuenta_id'] . '"');
        $grupos = array();
        if ($result_grupo_lim) {
            while ($row_g = $result_grupo_lim->fetch_assoc()) {
                $row_g['acumulado'] = 0;
                $grupos[] = $row_g;
            }
        }

        if (is_array($campania) && is_array($campania['elements'])) {

            foreach ($campania['elements'] as $el) {
                foreach ($grupos as $key => $g) {
                    $grb = explode(',', $g['filtro']);
                    foreach ($grb as $gr) {
                        if (stripos($el['pivotValue~']['name'], $gr) !== false) {
                            $resultados[$idxcuenta]['grupos'][$key]['nombre'] = $g['nombre'];
                            $resultados[$idxcuenta]['grupos'][$key]['gasto'][$el['dateRange']['start']['month']] += $el['costInLocalCurrency'];
                            // $grupos[$key]['acumulado'] += $el['costInLocalCurrency'];
                            break;
                        }
                    }
                }

                $acumulado += $el['costInLocalCurrency'];

                $resultados[$idxcuenta]['gasto'][$el['dateRange']['start']['month']] += $el['costInLocalCurrency'];

                $fecha_calculos = $el['dateRange']['start']['year'] . '/' . $el['dateRange']['start']['month'] . '/' . $el['dateRange']['start']['day'];
            }

            if (count($campania['elements']) == 0) {
                unset($resultados[$idxcuenta]);
            }
        }
    } catch (\LinkedIn\Exception $exception) {
        // in case of failure, provide with details
        pp($exception);
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Data Studio</title>
</head>

<body>
    <div class="row">
        <div class="col-md-12">
            <h1>Report Data Studio</h1>
        </div>
    </div>

</body>

</html>

<?

$resultadosCompanies = [];
$resultadosExport = array();

foreach ($campania['elements'] as $el) {
    
    foreach($el['pivotValue~']['name']['localized'] as $localizacion => $empresa) {
        // if(!is_array($resultadosCompanies[$key])) {
        //     $resultadosCompanies[$key] = array();
        // }

        // $resultadosCompanies[$key][] = $value;

        $fecha = date($el['dateRange']['start']['year'] . "/" . $el['dateRange']['start']['month'] . "/" . $el['dateRange']['start']['day']);

        $resultadosExport[] = array($fecha, $empresa, $localizacion, $el['impressions'], $el['clicks']);
    }
}
echo '<pre>';
print_r($resultadosExport);
echo '</pre>';


// $resultadosExport = array();
// foreach($resultadosCompanies as $key=>$value){
//     array_unshift($value,$key);
//     $resultadosExport[] = $value;
// }

$body = new Google_Service_Sheets_ValueRange([

    'values' => $resultadosExport
    
]);

$params = [

    'valueInputOption' => 'RAW'

];

$update_sheet = $service->spreadsheets_values->update($spreadsheetId, $update_range, $body, $params);

echo '<pre>', var_export($update_sheet, true), '</pre>', "\n";

?>
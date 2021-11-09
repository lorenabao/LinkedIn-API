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
$result_acc = $mysqli->query('SELECT * FROM cuenta where activo=1');



$year = isset($_GET['y']) ? $_GET['y'] : date('Y');

if ($year != '') {
    $inicio = $year . '/01/01';
    $fecha_inicio = $year . '/01/01';
    $fecha_fin = $year . '/12/31';
} else {
    $fecha_inicio = date('Y/01/01');
    $fecha_fin = date('Y/12/31');
}

// Google sheets

//Reading data from spreadsheet.

$clientGoogle = new \Google_Client();

$clientGoogle->setApplicationName('Google Sheets and PHP');

$clientGoogle->setScopes([\Google_Service_Sheets::SPREADSHEETS]);

$clientGoogle->setAccessType('offline');

$clientGoogle->setAuthConfig(__DIR__ . '/credentials.json');

$service = new Google_Service_Sheets($clientGoogle);

$spreadsheetId = "1L3V93vVfowd53YdWwvwWARKqLnvYkGtmWYVKC01X0mY"; // Santos
$spreadsheetIdFFF = "1egQO_ih8-yiFU2eOe_BA1XTLny4EeC4c_iGFC3KxwIU"; // Fish Farm Feeder
$spreadsheetIdGaitech = "1Iqyva8ZXGvkeo-_-LDPrb_xWgMxQW-jM2j3xi_RltZ0"; // Gaitech

// $get_range = "A:R";
$update_rangeSantos = "A2:Z";
$update_range  = "Costes!A2:Z";
$update_range_Gaitech  = "Ads!A2:Z";

?>


<!doctype html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">

    <title>Informe anual linkedin</title>
</head>

<body>
    <div class="row">
        <div class="col-md-12">

            <?
            $meses = array('1' => 'Enero', '2' => 'Febrero', '3' => 'Marzo', '4' => 'Abril', '5' => 'Mayo', '6' => 'Junio', '7' => 'Julio', '8' => 'Agosto', '9' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre')
            ?>

            <h2>Resumen de gasto Anual <?= $year ?> a día <?= date('d/m/Y') ?></h2>

            <form class="form-inline" method="get" action="">

                <label class="sr-only" for="inlineFormInputGroupUsername2">Año</label>
                <select name="y" id="y" class="form-control">
                    <?
                    for ($i = 2015; $i < 2030; $i++) {
                    ?>
                        <option value="<?= $i ?>" <?= ($year != '' && $year == $i) || ($year == '' && $i == date('Y')) ? 'selected' : '' ?>><?= $i ?></option>
                    <?
                    }
                    ?>
                </select>

                <button style="margin: 0 !important" type="submit" class="btn btn-primary mb-2">Consultar</button>
            </form>


            <?

            // foreach($cuentas as $codigo=>$nombre){
            while ($row = $result_acc->fetch_assoc()) {
                $idxcuenta = $row['cuenta_id'] . '-' . $row['codigo'];
                $resultados[$idxcuenta]['nombre'] = $row['nombre'];
                // $resultados[$row['codigo']]['gasto']
                $codigo = $row['codigo'];
                $nombre = $row['nombre'];
                $acumulado = 0;

                try {
                    $campania = $client->getReport($codigo, 'CAMPAIGN', 'MONTHLY', $fecha_inicio, $fecha_fin);
                    // pp($campania);

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
                            // $pos1 = strrpos($el['pivotValue'],':');   
                            // $campana_code = substr($el['pivotValue'],$pos1+1);
                            // $result_lim_camp = $mysqli->query('SELECT * FROM campana LEFT JOIN limite_gasto_campana ON(campana.campana_id=limite_gasto_campana.campana_id) where codigo="'.$campana_code.'" AND mes = '.$el['dateRange']['start']['month']);
                            // if($limites = $result_lim_camp->fetch_assoc()){
                            //     $limite = $limites['limite'];
                            // }
                            // else{
                            //     $limite = 0;
                            // }

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
            ?>

            <?
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
            <table class="table">
                <thead class="thead-light">
                    <tr>
                        <th>Cuenta</th>
                        <?
                        foreach ($meses as $m) {
                        ?>
                            <th><?= $m ?></th>
                        <?
                        }
                        ?>
                        <th>Total</th>
                    </tr>
                </thead>
                <?
                foreach ($resultados as $c) {
                ?>
                    <tr class="table-active">
                        <td><?= $c['nombre'] ?></td>
                        <?
                        $total = 0;
                        foreach ($meses as $key => $m) {
                        ?>
                            <td class="text-right"><?= isset($c['gasto'][$key]) ? round($c['gasto'][$key], 2) . ' €' : 0 ?></td>

                        <?
                            if (isset($c['gasto'][$key])) {
                                $total += $c['gasto'][$key];
                            }
                        }
                        ?>
                        <td class="text-right"><?= round($total, 2) . ' €' ?></td>
                    </tr>
                    <?
                    if (isset($c['grupos']) && count($c['grupos']) > 0) {
                        $total = 0;
                        foreach ($c['grupos'] as $cg) {
                    ?>
                            <tr>
                                <td class="text-right"><?= $cg['nombre'] ?></td>
                                <?
                                foreach ($meses as $key => $m) {
                                ?>
                                    <td class="text-right"><?= isset($cg['gasto'][$key]) ? round($cg['gasto'][$key], 2) . ' €' : 0 ?></td>
                                <?
                                    if (isset($cg['gasto'][$key])) {
                                        $total += $cg['gasto'][$key];
                                    }
                                }
                                ?>
                                <td class="text-right"><?= round($total, 2) . ' €' ?></td>
                            </tr>
                    <?
                        }
                    }
                    ?>
                <?
                }

                ?>
            </table>
        </div>
    </div>
</body>

</html>
<?

$latamSantos = [];
$omSantos = [];
$enirSantos = [];
$fishFarmFeeder = [];
$gaitech = [];


for ($i = 1; $i <= 12; $i++) {
    $yearSheets = isset($year) ? $year : '-';
    $fecha = $i . '/01' . "/" . $year;

    $nameSantos = isset($resultados['3-508183626']['nombre']) ? $resultados['3-508183626']['nombre'] : '-';
    $namefishFarmFeeder = isset($resultados["4-503770537"]['nombre']) ? $resultados["4-503770537"]['nombre'] : '-';
    $nameGaitech = isset($resultados["5-507211983"]['nombre']) ? $resultados["4-503770537"]['nombre'] : '-';

    $grupoSantosLat = isset($resultados['3-508183626']['grupos'][0]['nombre']) ? $resultados['3-508183626']['grupos'][0]['nombre'] : '-';
    $grupoSantosOM = isset($resultados['3-508183626']['grupos'][1]['nombre']) ? $resultados['3-508183626']['grupos'][1]['nombre'] : '-';
    $grupoSantosEnir = isset($resultados['3-508183626']['grupos'][2]['nombre']) ? $resultados['3-508183626']['grupos'][2]['nombre'] : '-';
    $grupofishFarmFeeder = isset($resultados["4-503770537"]['grupos'][0]['nombre']) ? $resultados['3-508183626']['grupos'][0]['nombre'] : $resultados["4-503770537"]['nombre'];
    $grupoGaitech = isset($resultados["5-507211983"]['grupos'][0]['nombre']) ? $resultados['5-507211983']['grupos'][0]['nombre'] : $resultados["5-507211983"]['nombre'];

    $month = $i;
    $costeMesLat = isset($resultados['3-508183626']['grupos'][0]['gasto'][$i]) ? $resultados['3-508183626']['grupos'][0]['gasto'][$i] : "0";
    $costeMesOM = isset($resultados['3-508183626']['grupos'][1]['gasto'][$i]) ? $resultados['3-508183626']['grupos'][1]['gasto'][$i] : "0";
    $costeMesEnir = isset($resultados['3-508183626']['grupos'][2]['gasto'][$i]) ? $resultados['3-508183626']['grupos'][2]['gasto'][$i] : "0";
    $costeMesfishFarmFeeder = isset($resultados["4-503770537"]['grupos'][0]['gasto'][$i]) ? $resultados["4-503770537"]['grupos'][0]['gasto'][$i] : $resultados["4-503770537"]['gasto'][$i];
    $costeMesfishFarmFeeder = isset($resultados["4-503770537"]['gasto'][$i]) ? $resultados["4-503770537"]['gasto'][$i] : "0";
    $costeMesGaitech = isset($resultados["5-507211983"]['gasto'][$i]) ? $resultados["5-507211983"]['gasto'][$i] : "0";

    array_push($latamSantos, $fecha, $nameSantos, $grupoSantosLat, $month, $costeMesLat);
    array_push($omSantos, $fecha, $nameSantos, $grupoSantosOM, $month, $costeMesOM);
    array_push($enirSantos, $fecha, $nameSantos, $grupoSantosEnir, $month, $costeMesEnir);
    array_push($fishFarmFeeder, $fecha, $namefishFarmFeeder, $grupofishFarmFeeder, $month, $costeMesfishFarmFeeder);
    array_push($gaitech, $fecha, $nameGaitech, $grupoGaitech, $month, $costeMesGaitech);

};

$body = new Google_Service_Sheets_ValueRange([

    'values' => [
        [$latamSantos[0], $latamSantos[1], $latamSantos[2], $latamSantos[3], $latamSantos[4]],
        [$latamSantos[5], $latamSantos[6], $latamSantos[7], $latamSantos[8], $latamSantos[9]],
        [$latamSantos[10], $latamSantos[11], $latamSantos[12], $latamSantos[13], $latamSantos[14]],
        [$latamSantos[15], $latamSantos[16], $latamSantos[17], $latamSantos[18], $latamSantos[19]],
        [$latamSantos[20], $latamSantos[21], $latamSantos[22], $latamSantos[23], $latamSantos[24]],
        [$latamSantos[25], $latamSantos[26], $latamSantos[27], $latamSantos[28], $latamSantos[29]],
        [$latamSantos[30], $latamSantos[31], $latamSantos[32], $latamSantos[33], $latamSantos[34]],
        [$latamSantos[35], $latamSantos[36], $latamSantos[37], $latamSantos[38], $latamSantos[39]],
        [$latamSantos[40], $latamSantos[41], $latamSantos[42], $latamSantos[43], $latamSantos[44]],
        [$latamSantos[45], $latamSantos[46], $latamSantos[47], $latamSantos[48], $latamSantos[49]],
        [$latamSantos[50], $latamSantos[51], $latamSantos[52], $latamSantos[53], $latamSantos[54]],
        [$latamSantos[55], $latamSantos[56], $latamSantos[57], $latamSantos[58], $latamSantos[59]],
        [$omSantos[0], $omSantos[1], $omSantos[2], $omSantos[3], $omSantos[4]],
        [$omSantos[5], $omSantos[6], $omSantos[7], $omSantos[8], $omSantos[9]],
        [$omSantos[10], $omSantos[11], $omSantos[12], $omSantos[13], $omSantos[14]],
        [$omSantos[15], $omSantos[16], $omSantos[17], $omSantos[18], $omSantos[19]],
        [$omSantos[20], $omSantos[21], $omSantos[22], $omSantos[23], $omSantos[24]],
        [$omSantos[25], $omSantos[26], $omSantos[27], $omSantos[28], $omSantos[29]],
        [$omSantos[30], $omSantos[31], $omSantos[32], $omSantos[33], $omSantos[34]],
        [$omSantos[35], $omSantos[36], $omSantos[37], $omSantos[38], $omSantos[39]],
        [$omSantos[40], $omSantos[41], $omSantos[42], $omSantos[43], $omSantos[44]],
        [$omSantos[45], $omSantos[46], $omSantos[47], $omSantos[48], $omSantos[49]],
        [$omSantos[50], $omSantos[51], $omSantos[52], $omSantos[53], $omSantos[54]],
        [$omSantos[55], $omSantos[56], $omSantos[57], $omSantos[58], $omSantos[59]],
        [$enirSantos[0], $enirSantos[1], $enirSantos[2], $enirSantos[3], $enirSantos[4]],
        [$enirSantos[5], $enirSantos[6], $enirSantos[7], $enirSantos[8], $enirSantos[9]],
        [$enirSantos[10], $enirSantos[11], $enirSantos[12], $enirSantos[13], $enirSantos[14]],
        [$enirSantos[15], $enirSantos[16], $enirSantos[17], $enirSantos[18], $enirSantos[19]],
        [$enirSantos[20], $enirSantos[21], $enirSantos[22], $enirSantos[23], $enirSantos[24]],
        [$enirSantos[25], $enirSantos[26], $enirSantos[27], $enirSantos[28], $enirSantos[29]],
        [$enirSantos[30], $enirSantos[31], $enirSantos[32], $enirSantos[33], $enirSantos[34]],
        [$enirSantos[35], $enirSantos[36], $enirSantos[37], $enirSantos[38], $enirSantos[39]],
        [$enirSantos[40], $enirSantos[41], $enirSantos[42], $enirSantos[43], $enirSantos[44]],
        [$enirSantos[45], $enirSantos[46], $enirSantos[47], $enirSantos[48], $enirSantos[49]],
        [$enirSantos[50], $enirSantos[51], $enirSantos[52], $enirSantos[53], $enirSantos[54]],
        [$enirSantos[55], $enirSantos[56], $enirSantos[57], $enirSantos[58], $enirSantos[59]],
    ]

  ]);

  $bodyFFF = new Google_Service_Sheets_ValueRange([

    'values' => [
        [$fishFarmFeeder[0], $fishFarmFeeder[1], $fishFarmFeeder[2], $fishFarmFeeder[3], $fishFarmFeeder[4]],
        [$fishFarmFeeder[5], $fishFarmFeeder[6], $fishFarmFeeder[7], $fishFarmFeeder[8], $fishFarmFeeder[9]],
        [$fishFarmFeeder[10], $fishFarmFeeder[11], $fishFarmFeeder[12], $fishFarmFeeder[13], $fishFarmFeeder[14]],
        [$fishFarmFeeder[15], $fishFarmFeeder[16], $fishFarmFeeder[17], $fishFarmFeeder[18], $fishFarmFeeder[19]],
        [$fishFarmFeeder[20], $fishFarmFeeder[21], $fishFarmFeeder[22], $fishFarmFeeder[23], $fishFarmFeeder[24]],
        [$fishFarmFeeder[25], $fishFarmFeeder[26], $fishFarmFeeder[27], $fishFarmFeeder[28], $fishFarmFeeder[29]],
        [$fishFarmFeeder[30], $fishFarmFeeder[31], $fishFarmFeeder[32], $fishFarmFeeder[33], $fishFarmFeeder[34]],
        [$fishFarmFeeder[35], $fishFarmFeeder[36], $fishFarmFeeder[37], $fishFarmFeeder[38], $fishFarmFeeder[39]],
        [$fishFarmFeeder[40], $fishFarmFeeder[41], $fishFarmFeeder[42], $fishFarmFeeder[43], $fishFarmFeeder[44]],
        [$fishFarmFeeder[45], $fishFarmFeeder[46], $fishFarmFeeder[47], $fishFarmFeeder[48], $fishFarmFeeder[49]],
        [$fishFarmFeeder[50], $fishFarmFeeder[51], $fishFarmFeeder[52], $fishFarmFeeder[53], $fishFarmFeeder[54]],
        [$fishFarmFeeder[55], $fishFarmFeeder[56], $fishFarmFeeder[57], $fishFarmFeeder[58], $fishFarmFeeder[59]]
    ]

  ]);

  $bodyGaitech = new Google_Service_Sheets_ValueRange([

    'values' => [
        [$gaitech[0], $gaitech[1], $gaitech[2], $gaitech[3], $gaitech[4]],
        [$gaitech[5], $gaitech[6], $gaitech[7], $gaitech[8], $gaitech[9]],
        [$gaitech[10], $gaitech[11], $gaitech[12], $gaitech[13], $gaitech[14]],
        [$gaitech[15], $gaitech[16], $gaitech[17], $gaitech[18], $gaitech[19]],
        [$gaitech[20], $gaitech[21], $gaitech[22], $gaitech[23], $gaitech[24]],
        [$gaitech[25], $gaitech[26], $gaitech[27], $gaitech[28], $gaitech[29]],
        [$gaitech[30], $gaitech[31], $gaitech[32], $gaitech[33], $gaitech[34]],
        [$gaitech[35], $gaitech[36], $gaitech[37], $gaitech[38], $gaitech[39]],
        [$gaitech[40], $gaitech[41], $gaitech[42], $gaitech[43], $gaitech[44]],
        [$gaitech[45], $gaitech[46], $gaitech[47], $gaitech[48], $gaitech[49]],
        [$gaitech[50], $gaitech[51], $gaitech[52], $gaitech[53], $gaitech[54]],
        [$gaitech[55], $gaitech[56], $gaitech[57], $gaitech[58], $gaitech[59]]
    ]
    
    ]);

$params = [

    'valueInputOption' => 'RAW'

];

// $update_sheet_Santos = $service->spreadsheets_values->update($spreadsheetId, $update_rangeSantos, $body, $params);

$update_sheet_FFF = $service->spreadsheets_values->update($spreadsheetIdFFF, $update_range, $bodyFFF, $params);

// $update_sheet_Gaitech = $service->spreadsheets_values->update($spreadsheetIdGaitech, $update_range_Gaitech, $bodyGaitech, $params);

// echo '<pre>', var_export($update_sheet_Santos, true), '</pre>', "\n";
// echo '<pre>', var_export($update_sheet_FFF, true), '</pre>', "\n";
// echo '<pre>', var_export($update_sheet_Gaitech, true), '</pre>', "\n";

/**
 * Pretty print whatever passed in
 *
 * @param mixed $anything
 */
function pp($anything)
{
    echo '<pre>' . print_r($anything, true) . '</pre>';
}

/**
 * Add header
 *
 * @param string $h
 */
function h1($h)
{
    echo '<h1>' . $h . '</h1>';
}

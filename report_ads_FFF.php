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

$spreadsheetId = "1egQO_ih8-yiFU2eOe_BA1XTLny4EeC4c_iGFC3KxwIU"; // Fish Farm Feeder

$update_range  = "Campaigns!A:Z";


$filasExport = array();
$filasExport[] = ['Texto', 'Fecha Inicio', 'Fecha Fin', 'Coste', 'Impresiones', 'Impresiones Unicas', 'Shares', 'Likes', 'Clicks', 'Commentarios', 'Total Engagement', 'Impresiones Video', 'Video 1%', 'Video 100%', 'oneClickLeadFormOpens', 'oneClickLeads'];

$fecha_inicio = date('2021/01/01');
$fecha_fin = date('2021/12/31');

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <title>Report Data Studio</title>
</head>

<body>
    <div class="row">
        <div class="col-md-12">
            <h1>Report Share Data 43213716 - Gaitech</h1>

            <table class="table">
                <tr>
                    <th>Campaña</th>
                    <th>Fecha inicio</th>
                    <th>Fecha final</th>
                    <th>Coste</th>
                    <th>Impresiones</th>
                    <th>Impresiones Unicas</th>
                    <th>Share</th>
                    <th>Like</th>
                    <th>Clicks</th>
                    <th>Comentarios</th>
                    <th>Total Engament</th>
                    <!-- The count of video ads that played 97-100% of the video. This includes watches that skipped to this point if the serving location is ON_SITE. -->
                    <th>Video Impresiones</th>
                    <th>Video 1%</th>
                    <th>Video 100%</th>
                    <th>oneClickLeadFormOpens</th>
                    <th>oneClickLeads</th>
                </tr>
                <?

                $result = $client->getReportAds('503770537', 'CAMPAIGN', 'DAILY', $fecha_inicio, $fecha_fin);
                // echo '<pre>';
                // var_dump($result);
                // echo '</pre>';

                foreach ($result["elements"] as $el) {
                    $fecha = date('d/m/Y H:i:s', $el['created']['time'] / 1000);
                    $text = $el["pivotValue~"]["name"];
                    $dayStart = $el["dateRange"]["start"]["day"];
                    $monthStart = $el["dateRange"]["start"]["month"];
                    $yearStart = $el["dateRange"]["start"]["year"];
                    $startDate = date($yearStart . "/" . $monthStart . "/" . $dayStart);


                    $dayEnd = $el["dateRange"]["end"]["day"];
                    $monthEnd = $el["dateRange"]["end"]["month"];
                    $yearEnd = $el["dateRange"]["end"]["year"];
                    $endDate = date($yearEnd . "/" . $monthEnd . "/" . $dayEnd);

                    $cost = $el['costInLocalCurrency'];
                    $shares = $el['shares'];
                    $impressions = $el['impressions'];
                    $uniqueImpressions = $el['approximateUniqueImpressions'];
                    $clicks = $el['clicks'];
                    $comments = $el['comments'];
                    $likes = $el['likes'];
                    $totalEngagement = $el['totalEngagements'];
                    $videoViews = $el['videoViews'];
                    $videoStarts = $el['videoStarts'];
                    $videoCompletations = $el['videoCompletions'];
                    $oneClickLeadFormOpens = $el['oneClickLeadFormOpens'];
                    $oneClickLeads = $el['oneClickLeads'];
                ?>
                    <tr>
                        <td><?= $text ?></td>
                        <td><?= $startDate ?></td>
                        <td><?= $endDate ?></td>
                        <td><?= round($cost, 2) ?></td>
                        <td><?= $impressions ?></td>
                        <td><?= $uniqueImpressions ?></td>
                        <td><?= $shares ?></td>
                        <td><?= $likes ?></td>
                        <td><?= $clicks ?></td>
                        <td><?= $comments ?></td>
                        <td><?= $totalEngagement ?></td>
                        <td><?= $videoViews ?></td>
                        <td><?= $videoStarts ?></td>
                        <td><?= $videoCompletations ?></td>
                        <td><?= $oneClickLeadFormOpens ?></td>
                        <td><?= $oneClickLeads ?></td>
                    </tr>
                <?
                    $filasExport[] = array($text, $startDate, $endDate, round($cost, 2), $impressions, $uniqueImpressions, $shares, $likes, $clicks, $comments, $totalEngagement, $videoViews, $videoStarts, $videoCompletations, $oneClickLeadFormOpens, $oneClickLeads);
                    // echo '<pre>';
                    // var_dump($filasExport);
                    // echo '</pre>';
                }
                ?>
            </table>
        </div>
    </div>

</body>

</html>

<!-- Google  -->

<?

$body = new Google_Service_Sheets_ValueRange([

    'values' => $filasExport

]);

$params = [

    'valueInputOption' => 'RAW'

];

$update_sheet = $service->spreadsheets_values->update($spreadsheetId, $update_range, $body, $params);

echo '<pre>', var_export($update_sheet, true), '</pre>', "\n";

?>
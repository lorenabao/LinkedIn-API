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

$spreadsheetId = "1egQO_ih8-yiFU2eOe_BA1XTLny4EeC4c_iGFC3KxwIU"; // FFF

$update_range  = "Organic!A:Z";


$filasExport = array();
$filasExport[] = ['Texto', 'Fecha', 'Tipo', 'Videoviews', 'Reactions', 'Share', 'Like', 'Engagement', 'Click', 'Impression', 'Comment'];

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
            <h1>Report Share Data 15221148 - FFF</h1>

            <table class="table">
                <tr>
                    <th>Texto</th>
                    <th>Fecha</th>
                    <th>Tipo</th>
                    <th>Videoviews</th>
                    <th>Reactions</th>
                    <th>Share</th>
                    <th>Like</th>
                    <th>Engagement</th>
                    <th>Click</th>
                    <th>Impression</th>
                    <th>Comment</th>
                </tr>
                <?
                //$result = $client->getReportOrganicShareData('43213716');

                // $result = $client->getReportOrganicShares('43213716');

                // echo '<pre>';
                // var_dump($result['paging']);
                // echo '</pre>';
                // echo json_encode($result);

                $result = $client->getSocialPosts('15221148');
                // echo '<pre>';
                // var_dump($result);
                // echo '</pre>';
                // exit;

                foreach ($result['elements'] as $el) {
                    $fecha = date('d/m/Y H:i:s', $el['created']['time'] / 1000);
                    $text = $el['specificContent']['com.linkedin.ugc.ShareContent']["shareCommentary"]["text"];
                    $tipo = $el['specificContent']['com.linkedin.ugc.ShareContent']['shareMediaCategory'];
                    // echo 'creado: '.date('d/m/Y H:i:s',$el['created']['time']/1000);
                    // echo '<br>';
                    // echo $text = $el['specificContent']['com.linkedin.ugc.ShareContent']["shareCommentary"]["text"];
                    // echo '<pre>';
                    // var_dump($el);                
                    // echo '</pre>';
                    // echo '<br>';

                    // echo '-------------';

                    // $activity = $el['activity'];
                    $id = $el['id'];
                    $result1 = $client->getReportOrganicShareData('43213716', $id);
                    $stats = $result1['elements'][0]['totalShareStatistics'];
                    // echo '<pre>';
                    // var_dump($result1);
                    // echo '</pre>';
                    // echo '-------------';
                    // echo '<br>';

                    // $result_action = $client->getDataShare($activity);
                    // echo '<pre>';
                    // var_dump($result_action);                
                    // echo '</pre>';
                    // echo '-------------';
                    $reactions = $client->getSocialDataShare($id);
                    // echo '<pre>';
                    // var_dump($reactions);                
                    // echo '</pre>';
                    $reacciones = 0;

                    foreach ($reactions['results'][$id]['reactionSummaries'] as $r) {

                        $reacciones += $r['count'];
                    }


                    // echo '-------------';
                    // $id = $el['id'];
                    // $result_action3 = $client->getReactionsShare($id);
                    // echo '<pre>';
                    // var_dump($result_action3);                
                    // echo '</pre>';
                    // echo '-------------';
                    $videoviews = 0;
                    if ($tipo == 'VIDEO') {
                        $datavideo = $client->getVideoAnalisis($id);
                        $videoviews = $datavideo['elements'][0]['value'];
                        // echo '<pre>';
                        // var_dump($datavideo);                
                        // echo '</pre>';
                    }
                ?>
                    <tr>
                        <td><?= $text ?></td>
                        <td><?= $fecha ?></td>
                        <td><?= $tipo ?></td>
                        <td><?= $videoviews ?></td>
                        <td><?= $reacciones ?></td>
                        <td><?= $stats['shareCount'] ?></td>
                        <td><?= $stats['likeCount'] ?></td>
                        <td><?= round($stats['engagement'], 2) ?></td>
                        <td><?= $stats['clickCount'] ?></td>
                        <td><?= $stats['impressionCount'] ?></td>
                        <td><?= $stats['commentCount'] ?></td>
                    </tr>
                <?
                $filasExport[] = array($text, $fecha, $tipo, $videoviews, $reacciones, $stats['shareCount'], $stats['likeCount'], round($stats['engagement'], 2), $stats['clickCount'], $stats['impressionCount'], $stats['commentCount'] );

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
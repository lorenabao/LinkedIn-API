<?php

include_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor/autoload.php';

// import client class
use LinkedIn\Client;
use LinkedIn\Scope;
use LinkedIn\AccessToken;


session_start();

$client = new Client('78catwn5u7d4ob','zZ2RrkbBMsWeAGBV');

$tokenString = file_get_contents('token.json');
$tokenData = json_decode($tokenString, true);
// instantiate access token object from stored data
$accessToken = new AccessToken($tokenData['token'], $tokenData['expiresAt']);

// set token for client
$client->setAccessToken($accessToken);

$pivots = array(
    'COMPANY',
'ACCOUNT',
'SHARE',
'CAMPAIGN',
'CREATIVE',
'CAMPAIGN_GROUP',
'CONVERSION',
'CONVERSATION_NODE',
'CONVERSATION_NODE_OPTION_INDEX',
'SERVING_LOCATION',
'CARD_INDEX',
'MEMBER_COMPANY_SIZE',
'MEMBER_INDUSTRY',
'MEMBER_SENIORITY',
'MEMBER_JOB_TITLE',
'MEMBER_JOB_FUNCTION',
'MEMBER_COUNTRY_V2',
'MEMBER_REGION_V2',
'MEMBER_COMPANY'
);

$agrupacion = 'CAMPAIGN';
if(isset($_GET['g']) && in_array($_GET['g'],$pivots)){
    $agrupacion = $_GET['g'];
}


$mysqli = new mysqli("localhost", "ipxes_linkedin", "Tecontrol.2021", "ipxes_linkedin");
$result_acc = $mysqli->query('SELECT * FROM cuenta where activo=1');


$mes = isset($_GET['m'])?$_GET['m']:'';
$year = isset($_GET['y'])?$_GET['y']:'';

$fecha_fin = '';
$fecha_inicio = '';

if($mes!='' && $year!=''){
    $inicio = $year.'/'.$mes.'/01';
    $fecha_inicio = date('Y/m/d',strtotime($inicio));
    $fecha_fin = date('Y/m/t',strtotime($inicio));
}
else{
    $fecha_inicio = date('Y/m/01');
    $fecha_fin = date('Y/m/t');
}

?>

<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">

    <title>Informe mensual linkedin</title>
  </head>
  <body>
  <div class="row">
  <div class="col-md-12">
  
  
  <h2>Resumen de gasto mensual <?=$fecha_inicio!=''?date('m/Y',strtotime($fecha_inicio)):date('m/Y')?> a día <?=date('d/m/Y')?></h2>

  <form class="form-inline" method="get" action="" >
  <label class="sr-only" for="inlineFormInputName2">Mes</label>
  <?
  $meses = array('01'=>'Enero','02'=>'Febrero','03'=>'Marzo','04'=>'Abril','05'=>'Mayo','06'=>'Junio','07'=>'Julio','08'=>'Agosto','09'=>'Septiembre','10'=>'Octubre','11'=>'Noviembre','12'=>'Diciembre')
  ?>
  <select name="m" id="m" class="form-control">
  <?
    foreach ($meses as $key => $value) {
    ?>
    <option value="<?=$key?>" <?=($mes!='' && $mes==$key) || ($mes=='' && $key==date('m'))?'selected':''?>><?=$value?></option>
    <?    
    }
  ?>
  </select>

  <label class="sr-only" for="inlineFormInputGroupUsername2">Año</label>
  <select name="y" id="y" class="form-control">
<?
    for ($i=2015; $i < 2030; $i++) { 
    ?>
    <option value="<?=$i?>" <?=($year!='' && $year==$i) || ($year=='' && $i==date('Y'))?'selected':''?>><?=$i?></option>
    <?
    }
?>
  </select>

  <button style="margin: 0 !important" type="submit" class="btn btn-primary mb-2">Consultar</button>
</form>

<table  class="table">
<tr>
    <th>Cuenta</th>
    <th>Campaña</th>
    <th>Campaña</th>
    <th>Mes</th>
    <th>Presupuesto</th>
    <th>Gasto</th>
    <th>Estimación</th>
    <th>Desviación</th>
    <th>Clicks</th>
    <th>Impresiones</th>
    <th>Shares</th>
    <th>Likes</th>
</tr>
<?

// foreach($cuentas as $codigo=>$nombre){
while($row = $result_acc->fetch_assoc()){
    $codigo = $row['codigo'];
    $nombre = $row['nombre'];
$acumulado = 0;
try {
$campania= $client->getReport($codigo,$agrupacion,'MONTHLY',$fecha_inicio,$fecha_fin);
// pp($campania);
if($agrupacion=='CAMPAIGN'){
    $result_grupo_lim = $mysqli->query('SELECT * FROM subgrupo WHERE cuenta_id="'.$row['cuenta_id'].'"');
    $grupos = array();
    if($result_grupo_lim){
        while($row_g = $result_grupo_lim->fetch_assoc()){
            $row_g['acumulado'] = 0;
            $grupos[] = $row_g;
        }
    }
}

if(is_array($campania) && is_array($campania['elements'])){

foreach($campania['elements'] as $el){
    $pos1 = strrpos($el['pivotValue'],':');   
    $campana_code = substr($el['pivotValue'],$pos1+1);
    $result_lim_camp = $mysqli->query('SELECT * FROM campana LEFT JOIN limite_gasto_campana ON(campana.campana_id=limite_gasto_campana.campana_id) where codigo="'.$campana_code.'" AND mes = '.$el['dateRange']['start']['month']);
    if($limites = $result_lim_camp->fetch_assoc()){
        $limite = $limites['limite'];
    }
    else{
        $limite = 0;
    }
if($agrupacion=='CAMPAIGN'){
    foreach ($grupos as $key => $g) {
        $grb = explode(',',$g['filtro']);
        foreach($grb as $gr){
            if(stripos($el['pivotValue~']['name'],$gr)!==false){
                $grupos[$key]['acumulado'] += $el['costInLocalCurrency'];
                break;
            }
        }
    }
}
$fecha_calculos = $el['dateRange']['start']['year'].'/'.$el['dateRange']['start']['month'].'/'.$el['dateRange']['start']['day'];
    $dias_calculos = $el['dateRange']['end']['day'];
?>
<tr>
    <td><?=$nombre?></td>
    <td><?=$el['pivotValue']?></td>
    <td><?=$el['pivotValue~']['name']?></td>
    <td><?=$el['dateRange']['start']['month'].'/'.$el['dateRange']['start']['year']?></td>
    <td><?=$limite?></td>
    <td><?=round($el['costInLocalCurrency'],2)?> €</td>
    <td><?=round(calcula_estimacion($limite,$el['dateRange']['start']['year'].'/'.$el['dateRange']['start']['month'].'/'.$el['dateRange']['start']['day'], $el['dateRange']['end']['day']),2)?> €</td>
    <?
    $acumulado+=$el['costInLocalCurrency'];
    ?>
    <td><?=calcula_desviacion($el['costInLocalCurrency'],$limite,$el['dateRange']['start']['year'].'/'.$el['dateRange']['start']['month'].'/'.$el['dateRange']['start']['day'], $el['dateRange']['end']['day'])?></td>
    <td><?=$el['landingPageClicks']?></td>
    <td><?=$el['impressions']?></td>
    <td><?=$el['shares']?></td>
    <td><?=$el['likes']?></td>
</tr>
<?

}
?>
<tr class="table-active">
<?
if(count($campania['elements'])>0){
    if($fecha_inicio!=''){
        $mesc = date('n',strtotime($fecha_inicio));
    }
    else{
        $mesc = date('n');
    }
$result_lim_cuenta = $mysqli->query('SELECT * FROM limite_gasto_cuenta where cuenta_id="'.$row['cuenta_id'].'" AND mes = '.$mesc);
if($limites = $result_lim_cuenta->fetch_assoc()){
    $limite_cuenta = $limites['limite'];
}
else{
    $limite_cuenta = 0;
}

?>
    <td align="right" colspan="4">Gasto acumulado <?=$nombre?></td>
    <td><?=$limite_cuenta?></td>
    <td><?=round($acumulado,2)?></td>
    <td><?=round(calcula_estimacion($limite_cuenta,$fecha_calculos,$dias_calculos),2)?></td>
    <td><?=round(calcula_desviacion($acumulado,$limite_cuenta,$fecha_calculos,$dias_calculos),2)?></td>
    <td colspan="4"></td>
</tr>
<?
if($agrupacion=='CAMPAIGN'){
if(count($grupos)>0){
    foreach($grupos as $g){
        $result_lim_g = $mysqli->query('SELECT * FROM limite_gasto_subgrupo where subgrupo_id="'.$g['subgrupo_id'].'" AND mes = '.$mesc);
if($limites_g = $result_lim_g->fetch_assoc()){
    $limite_g = $limites_g['limite'];
}
else{
    $limite_g = 0;
}
?>
<tr class="table-active">
    <td align="right" colspan="4">Subgrupo: <?=$g['nombre']?></td>
    <td><?=$limite_g?></td>
    <td><?=round($g['acumulado'],2)?></td>
    <td><?=round(calcula_estimacion($limite_g,$fecha_calculos,$dias_calculos),2)?></td>
    <td><?=round(calcula_desviacion($g{'acumulado'},$limite_g,$fecha_calculos,$dias_calculos),2)?></td>
    <td colspan="4"></td>
</tr>
<?    
    }
}
}
}
}
}catch (\LinkedIn\Exception $exception) {
    // in case of failure, provide with details
    pp($exception);
    
}

}

?>
</table>
</div></div>
</body>
</html>
<?

function calcula_desviacion($actual,$total,$fecha='', $dias=''){
    if($total==0)
        return 0;
    if($fecha==''){
        $maxDays=date('t');
        $currentDayOfMonth=date('j');   
    }
    else{
        $maxDays=date('t',strtotime($fecha));
        $currentDayOfMonth=date('j',strtotime($fecha));   
    }
    if($dias == '')
        $dias = date('j');

    $calculado = ($dias*$total)/$maxDays;

    $desviacion = $actual/$calculado;
    
    return $desviacion;
}

function calcula_estimacion($total,$fecha='', $dias=''){
    if($total==0)
        return 0;
    
    if($fecha==''){
        $maxDays=date('t');
    }
    else{
        $maxDays=date('t',strtotime($fecha));
    }
    if($dias == '')
        $dias = date('j');

    $calculado = ($dias*$total)/$maxDays;

    return $calculado;
}


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
function h1($h) {
    echo '<h1>' . $h . '</h1>';
}
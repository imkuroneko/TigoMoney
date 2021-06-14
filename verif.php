<pre>
<?php

try {
    require './tigomoney.class.php';

    # API Data quitado de la documentación...
    $api_token  = 'htRAbnnNGS854nPhhSodH6bnV8fHGv1y';
    $api_secret = 'aq6IcKECjGHxHKBR';
    
    # Datos de Tienda quitado de la documentación... 
    $agent_msisdn = '0986777961';
    $agent_pin    = '1234';
    $nom_tienda   = 'NombreTienda';
    
    ## Para qué esperar la aprobación de la solicitud para empezar a probar? =========
    
    $tigoMoney = new TigoMoney($api_token, $api_secret, $agent_msisdn, $agent_pin, $nom_tienda);
    $tigoMoney->isSandbox(true);
    
    # === Proceso Verificación
    $genToken = $tigoMoney->generateToken();
    if($genToken->status == 200) {
        $token = $genToken->data->accessToken;
        
        $tx_id = "querty1234";
    
        $refundPayment = $tigoMoney->refundPayment($token, $tx_id);
    
        if($refundPayment->status == 200) {
            print_r($refundPayment->data);
        } else {
            echo "<b>[Error {$refundPayment->data->error}]</b> {$refundPayment->data->error_description}";
        }
    } else {
        echo "<b>[Error {$genToken->data->error}]</b> {$genToken->data->error_description}";
    }
} catch (\Throwable $th) {
    echo $th->getMessage();
}

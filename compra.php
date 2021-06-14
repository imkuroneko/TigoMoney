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
    
    # === Proceso Compra
    $genToken = $tigoMoney->generateToken();
    if($genToken->status == 200) {
        $token = $genToken->data->accessToken;
        
        $cl_number = '0981941311';
        $cl_email = 'asd@adasd.com';
        $amount = '1000';
        $tx_id = hash('crc32b', date('Y-m-d H:i:s'));
    
        $newPayment = $tigoMoney->createPayment($token, $cl_number, $cl_email, $amount, $tx_id);
    
        if($newPayment->status == 200) {
            echo "<b>[merchantTransactionId]</b> {$newPayment->data->merchantTransactionId}<br>";
            echo "<b>[redirectUrl]</b> {$newPayment->data->redirectUrl}<br>";
            echo "<b>[authCode]</b> {$newPayment->data->authCode}<br>";
            echo "<b>[creationDateTime]</b> {$newPayment->data->creationDateTime}<br>";
            # en caso de transacción exitosa; callback va a recibir esto {body}
            #     transactionStatus=[success|fail]
            #     &merchantTransactionId=[merchantTransactionId]
            #     &mfsTransactionId=[mfsTransactionId]
            #     &accessToken=[access_token]
            #     &transactionCode=[statusCode]
            #     &transactionDescription=[status_description]
    
            # pero igual, la redirección enviará todo esto en el parámetro redirectURI
        } else {
            echo "<b>[Error Payment | {$newPayment->data->error}]</b> {$newPayment->data->error_description}";
        }
    } else {
        echo "<b>[Error Token | {$genToken->data->error}]</b> {$genToken->data->error_description}";
    }
} catch (\Throwable $th) {
    echo $th->getMessage();
}
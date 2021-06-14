<?php

/**
 * Tigo Money
 * Mini Librería para realizar consultas a la API de Tigo Money
 * 
 * @version 1.0
 * @author  KuroNeko <kuroinekowitch@gmail.com>
 */
class TigoMoney {

    public $live = false;
    public $api_token  = '';
    public $api_secret = '';
    public $agent_msisdn = '';
    public $agent_pin = '';
    public $nom_tienda = '';
    public $token_url = '';
    public $payment_url = '';

    /**
     * Recibir los datos necesarios
     *
     * @param string $api_token API Key de Tigo Money
     * @param string $api_secret Token Secreto de la API Key
     * @param string $agent_msisdn Numero Telefónico de la Billetera Vinculada (ej: 0981123456)
     * @param string $agent_pin PIN de la Billetera
     * @param string $nom_tienda Nombre de la Tienda
     */ 
    function __construct($api_token = '', $api_secret = '', $agent_msisdn = '', $agent_pin = '', $nom_tienda = ''){
        if(empty($api_token)) { throw new Exception("Param 'api_token' is required."); }
        if(empty($api_secret)) { throw new Exception("Param 'api_secret' is required."); }
        if(empty($agent_msisdn)) { throw new Exception("Param 'agent_msisdn' is required."); }
        if(empty($agent_pin)) { throw new Exception("Param 'agent_pin' is required."); }
        if(empty($nom_tienda)) { throw new Exception("Param 'nom_tienda' is required."); }

        $this->api_token = $api_token;
        $this->api_secret = $api_secret;
        $this->agent_msisdn = $agent_msisdn;
        $this->agent_pin = $agent_pin;
        $this->nom_tienda = $nom_tienda;
    }

    /**
     * Habilitar modo SandBox
     *
     * @param boolean $sandbox Habilitar o Deshabilitar el modo sandbox
     */ 
    public function isSandbox($sandbox = true) {
        $endpoint = ($sandbox) ? 'securesandbox' : 'prod.api'; 
        $this->token_url = "https://{$endpoint}.tigo.com/v1/oauth/mfs/payments/tokens";
        $this->payment_url = "https://{$endpoint}.tigo.com/v2/tigo/mfs/payments";
    }

    /**
     * Realizar todas las solicitudes vía cURL
     *
     * @param string $method GET|POST|DELETE
     * @param string $url URL a donde realizar la solicitud 
     * @param string $header Header con los parámetros y tokens de acceso necesarios
     * @param string $body Contenido enviado a la solicitud
     * @return array Datos de la solicitud realizada
     */ 
    public function apiRequest($method, $url, $header, $body = '') {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if($method == "POST") { curl_setopt($ch, CURLOPT_POSTFIELDS, $body); }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT ,3);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close ($ch);

        return ['status' => $httpCode, 'data' => json_decode($response)];
    }

    /**
     * Generar token transaccional (de un solo uso) para una solicitud
     *
     * @return array Datos de la solicitud realizada
     */ 
    public function generateToken() {
        $header = [ "Content-Type: application/x-www-form-urlencoded", "Authorization: Basic {$this->encodedToken()}" ];
        $body = "grant_type=client_credentials";
        $data = $this->apiRequest('POST', $this->token_url, $header, $body);

        return $this->classThis($data);
    }

    /**
     * Formatear datos a objeto
     *
     * @return string
     */ 
    public function classThis($data) {
        return (object) $data;
    }

    /**
     * Generar hash en base64 de la API Key y PrivKey para poder utilizar en la generación de token transaccional
     *
     * @return string 
     */ 
    public function encodedToken() {
        return base64_encode("{$this->api_token}:{$this->api_secret}");
    }

    /**
     * Crear la transacción en la pasarela de Tigo Money según los datos proveidos
     *
     * @param string $token Token Transaccional para realizar la consulta
     * @param string $cl_number Numero Telefónico del Usuario (ej: 0982132456)
     * @param string $cl_email Correo del Usuario
     * @param string $amount Importe de la transacción (sin ceros ni comas)
     * @param string $tx_id ID de la Transacción
     * @return array
     */ 
    public function createPayment($token, $cl_number, $cl_email, $amount, $tx_id) {
        if(empty($token)) { throw new Exception("Param 'token' is required."); }
        if(empty($cl_number)) { throw new Exception("Param 'cl_number' is required."); }
        if(empty($cl_email)) { throw new Exception("Param 'cl_email' is required."); }
        if(empty($amount)) { throw new Exception("Param 'amount' is required."); }
        if(empty($tx_id)) { throw new Exception("Param 'tx_id' is required."); }

        $body = json_encode([
            "MasterMerchant" => [ "account" => $this->agent_msisdn, "pin" => $this->agent_pin, "id" => $this->nom_tienda ],
            "Subscriber" => [ "account" => $cl_number, "countryCode" => "595", "country" => "PRY", "emailId" => $cl_email ],
            "redirectUri" => "https://test.api.tigo.com/v1/tigo/diagnostics/callback",
            "callbackUri" => "https://test.api.tigo.com/v1/tigo/diagnostics/callback",
            "language" => "spa",
            "OriginPayment" => [ "amount" => $amount, "currencyCode" => "PYG", "tax" => "0.00", "fee" => "0.00" ],
            "exchangeRate" => "1",
            "LocalPayment" => [ "amount" => $amount, "currencyCode" => "PYG" ],
            "merchantTransactionId" => $tx_id
        ]);

        $header = [ "Content-Type: application/json", "Authorization: Bearer {$token}" ];
        $data = $this->apiRequest('POST', "{$this->payment_url}/authorizations", $header, $body);
        return $this->classThis($data);
    }

    /**
     * Verificar el estado de una transacción
     *
     * @param string $token Token Transaccional para realizar la consulta
     * @param string $tx_id ID de la Transacción a Revertir
     * @return array
     */ 
    public function verifyPayment($token, $tx_id) {
        if(empty($token)) { throw new Exception("Param 'token' is required."); }
        if(empty($tx_id)) { throw new Exception("Param 'tx_id' is required."); }

        $header = [ "Content-Type: application/json", "Authorization: Bearer {$token}" ];
        $data = $this->apiRequest('GET', "{$this->payment_url}/transactions/PRY/{$this->nom_tienda}/{$tx_id}", $header);
        return $this->classThis($data);
    }
 
    /**
     * Realizar la reversión de una transacción aprobada
     *
     * @param string $token Token Transaccional para realizar la consulta
     * @param string $tx_id ID de la Transacción a Revertir
     * @return array
     */ 
    public function refundPayment($token, $tx_id) {
        if(empty($token)) { throw new Exception("Param 'token' is required."); }
        if(empty($tx_id)) { throw new Exception("Param 'tx_id' is required."); }

        $header = [ "Content-Type: application/json", "Authorization: Bearer {$token}" ];
        $data = $this->apiRequest('DELETE', "{$this->payment_url}/transactions/PRY/{$this->nom_tienda}/{$tx_id}", $header);
        return $this->classThis($data);
    }

}
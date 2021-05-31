<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Xendit_PG_API {
    const DEFAULT_TIME_OUT = 70;

    function __construct() {
        $this->api_server_domain = 'https://api.xendit.co';
        $this->tpi_server_domain = 'https://tpi.xendit.co';

        $main_settings = get_option('woocommerce_xendit_gateway_settings');
        $this->developmentmode = $main_settings['developmentmode'];

        $this->secret_api_key = $this->developmentmode == 'yes' ? $main_settings['secret_key_dev'] : $main_settings['secret_key'];
        $this->public_api_key = $this->developmentmode == 'yes' ? $main_settings['api_key_dev'] : $main_settings['api_key'];
        $this->for_user_id    = isset($main_settings['on_behalf_of']) ? $main_settings['on_behalf_of'] : '';
    }

    /*******************************************************************************
        Virtual Accounts
     *******************************************************************************/
    function createInvoice($body, $header) {
        $end_point = $this->tpi_server_domain.'/payment/xendit/invoice';

        $payload = json_encode($body);
        $default_header = $this->defaultHeader();

        $args = array(
            'headers' => array_merge($default_header, $header),
            'timeout' => WC_Xendit_PG_API::DEFAULT_TIME_OUT,
            'body' => $payload
        );

        try {
            $response = wp_remote_post($end_point, $args);
            $this->handleNetworkError($response);
            $jsonResponse = json_decode($response['body'], true);

            return $jsonResponse;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    function getInvoice($invoice_id) {
        $end_point = $this->tpi_server_domain.'/payment/xendit/invoice/'.$invoice_id;

        $args = array(
            'headers' => $this->defaultHeader(),
            'timeout' => WC_Xendit_PG_API::DEFAULT_TIME_OUT
        );

        try {
            $response = wp_remote_get($end_point, $args);
            $this->handleNetworkError($response);
            $jsonResponse = json_decode($response['body'], true);

            return $jsonResponse;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    function trackOrderCancellation($body) {
        $end_point = $this->tpi_server_domain.'/payment/xendit/invoice/bulk-cancel';

        $payload = array(
            'invoice_data' => json_encode($body)
        );

        $args = array(
            'headers' => $this->defaultHeader(),
            'timeout' => WC_Xendit_PG_API::DEFAULT_TIME_OUT,
            'body' => json_encode($payload)
        );
        $response = wp_remote_post($end_point, $args);

        if (is_wp_error($response) || empty($response['body'])) {
            return array();
        }

        $jsonResponse = json_decode($response['body'], true);
        return $jsonResponse;
    }

    /*******************************************************************************
        e-Wallet
     *******************************************************************************/
    function createEwalletPayment($body, $header) {
        $end_point = $this->tpi_server_domain.'/payment/xendit/ewallets';

        $payload = json_encode($body);
        $default_header = $this->defaultHeader();

        $args = array(
            'headers' => array_merge($default_header, $header),
            'timeout' => WC_Xendit_PG_API::DEFAULT_TIME_OUT,
            'body' => $payload
        );

        try {
            $response = wp_remote_post($end_point, $args);
            $jsonResponse = json_decode($response['body'], true);

            if (is_wp_error($response) || empty($response['body'])) { //CURL error
                $jsonResponse['is_paid'] = 0;

                $ewallet = $this->getEwallet($body['ewallet_type'], $body['external_id']);
                if ('COMPLETED' == $ewallet['status']) {
                    $jsonResponse['is_paid'] = 1;
                }
            }

            if (isset($jsonResponse['error_code'])) {
                if (
                    $jsonResponse['error_code'] === "DUPLICATE_PAYMENT_REQUEST_ERROR" || 
                    $jsonResponse['error_code'] === "DUPLICATE_PAYMENT_ERROR" || 
                    $jsonResponse['error_code'] === "DUPLICATE_ERROR"
                ) { // retry with unique external id
                    $external_id = uniqid().'-'.$body['external_id'];
                    $body['external_id'] = $external_id;
                    
                    return $this->createEwalletPayment($body, $header);
                }
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        return $jsonResponse;
    }
    
    function getEwallet($ewallet_type, $external_id) {
        $end_point = $this->tpi_server_domain.'/payment/xendit/ewallets?ewallet_type='.$ewallet_type.'&external_id='.$external_id;

        $default_header = $this->defaultHeader();
        $args = array(
            'headers' => array_merge($default_header, array('x-api-version' => '2020-02-01')),
            'timeout' => WC_Xendit_PG_API::DEFAULT_TIME_OUT
        );
        
        try {
            $response = wp_remote_get($end_point, $args);
            $this->handleNetworkError($response);
            $jsonResponse = json_decode($response['body'], true);

            $status_list = array("COMPLETED", "PAID", "SUCCESS_COMPLETED"); //OVO, DANA, LINKAJA
            if (in_array($jsonResponse['status'], $status_list)) {
                $jsonResponse['status'] = "COMPLETED";
            }      
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        return $jsonResponse;
    }

    /*******************************************************************************
        QRIS
     *******************************************************************************/
    function createQRIS ($body, $header) {
        $end_point = $this->tpi_server_domain.'/payment/xendit/qris';

        $payload = json_encode($body);
        $default_header = $this->defaultHeader();

        $args = array(
            'headers' => array_merge($default_header, $header),
            'timeout' => WC_Xendit_PG_API::DEFAULT_TIME_OUT,
            'body' => $payload
        );

        try {
            $response = wp_remote_post($end_point, $args);
            $this->handleNetworkError($response);
            $jsonResponse = json_decode($response['body'], true);

            if ($jsonResponse['error_code'] === 'DUPLICATE_ERROR') {
                $body['external_id'] = uniqid().'-'.$body['external_id'];
                return $this->createQRIS($body, $header);
            }

            return $jsonResponse;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    function getQRIS($external_id) {
        $end_point = $this->tpi_server_domain.'/payment/xendit/qris?external_id='.$external_id;

        $args = array(
            'headers' => $this->defaultHeader(),
            'timeout' => WC_Xendit_PG_API::DEFAULT_TIME_OUT
        );

        try {
            $response = wp_remote_get($end_point, $args);
            $this->handleNetworkError($response);
            $jsonResponse = json_decode($response['body'], true);

            return $jsonResponse;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    /*******************************************************************************
        Credit Cards
     *******************************************************************************/
    /**
     * Send the request to Xendit's API
     *
     * @param array $payload
     * @return object|Exception
     */
    function createCharge($payload) {
        $end_point = $this->tpi_server_domain.'/payment/xendit/credit-card/charges';

        $args = array(
            'headers' => $this->defaultHeader(),
            'timeout' => WC_Xendit_PG_API::DEFAULT_TIME_OUT,
            'body' => json_encode($payload),
            'user-agent' => 'WooCommerce ' . WC()->version
        );
        
        try {
            $response = wp_remote_post($end_point, $args);
            $this->handleNetworkError($response);
            $jsonResponse = json_decode($response['body'], true);

            return $jsonResponse;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    /**
     * Get CC Setting
     * Note: the return will be array, but if value is boolean (true) json_decode will convert to "1" otherwise if value is boolean (false) json_decode will convert to ""
     * @return array|WP_Error
     */
    function getCCSettings() {
        $end_point = $this->tpi_server_domain.'/payment/xendit/settings/credit-card';

        $args = array(
          'headers' => $this->defaultHeader(),
          'timeout' => WC_Xendit_PG_API::DEFAULT_TIME_OUT
        );
        $response = wp_remote_get($end_point, $args);

        if (is_wp_error($response) || empty($response['body'])) {
            return array();
        }

        $jsonResponse = json_decode($response['body'], true);
        return $jsonResponse;
    }

    /**
     * Get credit card charge callback data
     * 
     * @param string $charge_id
     * @return array
     */
    function getCharge($charge_id) {
        $end_point = $this->tpi_server_domain.'/payment/xendit/credit-card/charges/'.$charge_id;

        $args = array(
            'headers' => $this->defaultHeader(),
            'timeout' => WC_Xendit_PG_API::DEFAULT_TIME_OUT
        );

        try {
            $response = wp_remote_get($end_point, $args);
            $this->handleNetworkError($response);
            $jsonResponse = json_decode($response['body'], true);

            return $jsonResponse;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Get hosted 3DS data
     * 
     * @param string $hosted_3ds_id
     * @return object
     */
    function getHostedThreeDS($hosted_3ds_id) {
        $end_point = $this->tpi_server_domain.'/payment/xendit/credit-card/hosted-3ds/' . $hosted_3ds_id;

        $args = array(
            'headers' => $this->defaultHeader(),
            'timeout' => WC_Xendit_PG_API::DEFAULT_TIME_OUT
        );

        try {
            $response = wp_remote_get($end_point, $args);
            $this->handleNetworkError($response);
            $jsonResponse = json_decode($response['body'], true);
            
            return $jsonResponse;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Create hosted 3DS using bundled charge
     * API Version: 2020-02-14
     * 
     * @param array $payload
     * @return object
     */
    function createHostedThreeDS($payload) {
        $end_point = $this->tpi_server_domain . '/payment/xendit/credit-card/hosted-3ds';

        $default_header = array(
            'Authorization' => 'Basic ' . base64_encode($this->public_api_key . ':'),
            'content-type' => 'application/json',
            'x-plugin-name' => 'WOOCOMMERCE',
            'x-plugin-version' => WC_XENDIT_PG_VERSION,
            'x-api-version' => '2020-02-14'
        );
        if ($this->for_user_id) {
            $default_header['for-user-id'] = $this->for_user_id;
        }

        $args = array(
            'headers' => $default_header,
            'timeout' => WC_Xendit_PG_API::DEFAULT_TIME_OUT,
            'body' => json_encode($payload)
        );

        try {
            $response = wp_remote_post($end_point, $args);
            $this->handleNetworkError($response);
            $jsonResponse = json_decode($response['body'], true);
    
            return $jsonResponse;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Initiate credit card refund through TPI service
     * 
     * @param array $payload
     * @param string $charge_id
     * @return object
     */
    function createRefund($payload, $charge_id) {
        $end_point = $this->tpi_server_domain . '/payment/xendit/credit-card/charges/' . $charge_id . '/refund';

        $args = array(
            'headers' => $this->defaultHeader(),
            'timeout' => WC_Xendit_PG_API::DEFAULT_TIME_OUT,
            'body' => json_encode($payload)
        );
        $response = wp_remote_post($end_point, $args);
        $jsonResponse = json_decode($response['body'], true);

        return $jsonResponse;
    }

    /**
     * Get credit card charges option for promotion & installment
     * 
     * @param string $token_id
     * @param float $amount
     * @param string $currency
     * @return object
     */
    function getChargeOption($token_id, $amount, $currency) {
        $end_point = $this->api_server_domain . '/credit_card_charges/option?token_id='.$token_id.'&amount='.$amount.'&currency='.$currency;

        $default_header = array(
            'Authorization' => 'Basic ' . base64_encode($this->public_api_key . ':'),
            'content-type' => 'application/json',
            'x-plugin-name' => 'WOOCOMMERCE',
            'x-plugin-version' => WC_XENDIT_PG_VERSION
        );
        if ($this->for_user_id) {
            $default_header['for-user-id'] = $this->for_user_id;
        }

        $args = array(
            'headers' => $default_header,
            'timeout' => WC_Xendit_PG_API::DEFAULT_TIME_OUT
        );

        $response = wp_remote_get($end_point, $args);
        $jsonResponse = json_decode($response['body'], true);

        return $jsonResponse;
    }

    /*******************************************************************************
        Cardless
     *******************************************************************************/
    /**
     * Initiate Kredivo payment through TPI service
     * 
     * @param array $body
     * @param array $header
     * @return array
     */
    function createCardlessPayment($body, $header) {
        $end_point = $this->tpi_server_domain . '/payment/xendit/cardless-credit';

        $payload = json_encode($body);
        $default_header = $this->defaultHeader();

        $args = array(
            'headers' => array_merge($default_header, $header),
            'timeout' => WC_Xendit_PG_API::DEFAULT_TIME_OUT,
            'body' => $payload
        );

        try {
            $response = wp_remote_post($end_point, $args);
            $this->handleNetworkError($response);
            $jsonResponse = json_decode($response['body'], true);

            if (isset($jsonResponse['error_code']) && $jsonResponse['error_code'] == 'DUPLICATE_PAYMENT_ERROR') {
                $body['external_id'] = $body['external_id'] . '_' . uniqid(); //generate a unique external id
                return $this->createCardlessPayment($body, $header);
            }

            return $jsonResponse;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /*******************************************************************************
        Tracking & Logging
     *******************************************************************************/

    function trackEvent($payload) {
        $end_point = $this->tpi_server_domain.'/payment/xendit/tracking';
        $args = array(
            'headers' => $this->defaultHeader(),
            'timeout' => WC_Xendit_PG_API::DEFAULT_TIME_OUT,
            'body' => json_encode($payload)
        );

        $response = wp_remote_post( $end_point, $args );

        if (is_wp_error($response) || empty($response['body'])) {
            return array();
        }

        $jsonResponse = json_decode( $response['body'], true );
        return $jsonResponse;
    }

    /**
     * Log metrics to Datadog for monitoring
     * 
     * @return boolean
     */
    function trackMetricCount($body) {
        $end_point = $this->tpi_server_domain . '/log/metrics/count';

        $args = array(
            'headers' => $this->defaultHeader(),
            'timeout' => WC_Xendit_PG_API::DEFAULT_TIME_OUT,
            'body' => json_encode($body)
        );

        try {
            $response = wp_remote_post($end_point, $args);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    function constructMetricPayload($name, $type, $payment_method, $error_code = '') {
        $metrics = array(
            'name'              => $name,
            'additional_tags'   => array(
                'type' => $type, 
                'payment_method' => strtoupper($payment_method)
            )
        );

        if ($error_code) {
            $metrics['additional_tags']['error_code'] = $error_code;
        }

        return $metrics;
    }

    /*******************************************************************************
        Customer Object
     *******************************************************************************/
    
    function getCustomerByReferenceId($ref_id) {
        $end_point = $this->tpi_server_domain.'/payment/xendit/customers?reference_id='.urlencode($ref_id);

        $args = array(
            'headers' => $this->defaultHeader(),
            'timeout' => WC_Xendit_PG_API::DEFAULT_TIME_OUT
        );

        try {
            $response = wp_remote_get($end_point, $args);
            $this->handleNetworkError($response);
            $jsonResponse = json_decode($response['body'], true);

            return $jsonResponse["data"][0];
        } catch (Exception $e) {
            return true; // do not throw error
        }
    }

    function createCustomer($payload) {
        $end_point = $this->tpi_server_domain.'/payment/xendit/customers';

        $body = json_encode($payload);
        $args = array(
            'headers' => $this->defaultHeader(),
            'timeout' => WC_Xendit_PG_API::DEFAULT_TIME_OUT,
            'body' => $body
        );

        try {
            $response = wp_remote_post($end_point, $args);
            $this->handleNetworkError($response);
            $jsonResponse = json_decode($response['body'], true);

            return $jsonResponse;
        } catch (Exception $e) {
            return true; // do not throw error
        }
    }

    function updateCustomer($customer_id, $payload) {
        $end_point = $this->tpi_server_domain.'/payment/xendit/customers/'.$customer_id;

        $body = json_encode($payload);
        $args = array(
            'headers' => $this->defaultHeader(),
            'timeout' => WC_Xendit_PG_API::DEFAULT_TIME_OUT,
            'body' => $body,
            'method' => 'PATCH'
        );

        try {
            $response = wp_remote_request($end_point, $args);
            $this->handleNetworkError($response);
            $jsonResponse = json_decode($response['body'], true);

            return $jsonResponse;
        } catch (Exception $e) {
            return true; // do not throw error
        }
    }    

    /*******************************************************************************
        General
     *******************************************************************************/
    /**
     * Default Header
     * 
     * @return array
     */
    function defaultHeader() {
        $default_header = array(
            'Authorization' => 'Basic ' . base64_encode($this->secret_api_key . ':'),
            'content-type' => 'application/json',
            'x-plugin-name' => 'WOOCOMMERCE',
            'x-plugin-version' => WC_XENDIT_PG_VERSION
        );
        if ($this->for_user_id) {
            $default_header['for-user-id'] = $this->for_user_id;
        }

        return $default_header;
    }

    /**
     * Post a site info
     * 
     * @param array $body
     * @return array
     */
    function createPluginInfo($body) {
        $end_point = $this->tpi_server_domain . '/log/plugin-info';

        $args = array(
            'headers' => $this->defaultHeader(),
            'timeout' => WC_Xendit_PG_API::DEFAULT_TIME_OUT,
            'body' => json_encode([
                'data' => (object) $body
            ])
        );
        try {
            $response = wp_remote_post($end_point, $args);
            $jsonResponse = json_decode($response['body'], true);

            if (isset( $jsonResponse['error_code'] )) {
                $jsonResponse['message'] = array($jsonResponse['message']);
                throw new Exception(json_encode($jsonResponse));
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        return $jsonResponse;
    }

    function handleNetworkError($response) {
        if (is_wp_error($response) || empty($response['body'])) {
            throw new Exception('We encountered an issue while processing the checkout. Please contact us. Code: 100007');
        }
    }
}

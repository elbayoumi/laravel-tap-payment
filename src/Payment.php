<?php

namespace Ashraf\LaravelTapPayment;

use InvalidArgumentException;
use Exception;

class Payment extends Reference implements TapInterface
{
    protected bool $CARD_SET = false;

    public function __construct(array $config = [])
    {
        foreach ($this->REQUIRED_CONFIG_VARS as $param => $req_status) {
            if (array_key_exists($param, $config)) {
                $this->CONFIG_VARS[$param] = $config[$param];
            } else {
                if ($req_status) {
                    throw new InvalidArgumentException("InvalidArgumentException $param field");
                }
            }
        }
    }

    public function card(array $data)
    {
        $this->cardValidator($data);
        $IP = request()->ip();
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.tap.company/v2/tokens",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode([
                "card" => [
                    "number" => $this->CARD_VARS['number'],
                    "exp_month" => $this->CARD_VARS['exp_month'],
                    "exp_year" => $this->CARD_VARS['exp_year'],
                    "cvc" => $this->CARD_VARS['cvc'],
                    "name" => $this->CARD_VARS['name'],
                    "address" => [
                        "country" => $this->CARD_VARS['country'],
                        "line1" => $this->CARD_VARS['line1'],
                        "city" => $this->CARD_VARS['city'],
                        "street" => $this->CARD_VARS['street'],
                        "avenue" => $this->CARD_VARS['avenue']
                    ]
                ],
                "client_ip" => $IP
            ]),
            CURLOPT_HTTPHEADER => [
                "authorization: Bearer " . $this->CONFIG_VARS['secret_api_Key'] . " ",
                "content-type: application/json"
            ]
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            throw new InvalidArgumentException("InvalidArgumentException  $err");
        } else {
            $json_response = json_decode($response);
            if (isset($json_response->errors) && is_array($json_response->errors) && count($json_response->errors) > 0) {
                throw new InvalidArgumentException("Error : " . $json_response->errors[0]->code . " ");
            }

            if (isset($json_response->object) && $json_response->object == "token") {
                $this->CHARGE_VARS['source']['id'] = $json_response->id;
                $this->CARD_SET = true;
            }
        }
    }

    public function charge(array $data = [], bool $redirect = true)
    {
        $this->chargeValidator($data);
        $curl = curl_init();
        $url = "https://api.tap.company/v2/charges";

        if ($this->CARD_SET) {
            $url = "https://api.tap.company/v2/charges";
        } else {
            $url = "https://api.tap.company/v2/charges";
        }

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode([]), // add your payload here
            CURLOPT_HTTPHEADER => [
                "authorization: Bearer " . $this->CONFIG_VARS['secret_api_Key'] . " ",
                "content-type: application/json"
            ]
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            throw new Exception("Exception  $err");
        } else {
            $json_response = json_decode($response);
            if (isset($json_response->errors) && is_array($json_response->errors) && count($json_response->errors) > 0) {
                throw new Exception("Error : " . $json_response->errors[0]->code . "");
            }
            if (isset($json_response->object) && $json_response->object == "charge" && isset($json_response->transaction->url)) {
                if ($redirect) {
                    return redirect($json_response->transaction->url);
                }
                return $json_response;
            } else {
                throw new Exception("Error : " . $response . " ");
            }
        }
    }
    public function getCharge($charge_id)
    {
        if ($charge_id != null) {
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://api.tap.company/v2/charges/$charge_id",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_POSTFIELDS => "{}",
                CURLOPT_HTTPHEADER => array(
                    "authorization: Bearer " . $this->CONFIG_VARS['secret_api_Key'] . " ",
                ),
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);

            if ($err) {
                throw new \Exception("Exception  $err");
            } else {
                $json_response = json_decode($response);
                if (isset($json_response->errors) && is_array($json_response->errors) && count($json_response->errors) > 0) {
                    throw new \Exception("Error : " . $json_response->errors[0]->code . " ");
                }
                if (isset($json_response->object) && $json_response->object == "charge" && isset($json_response->id)) {
                    return $json_response;
                } else {
                    throw new \Exception("Error : " . $response . " ");
                }
            }
        }
        return false;
    }

    public function chargesList($options = array())
    {
        $this->chargesListValidator($options);
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.tap.company/v2/charges/list",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "{\"period\":{\"date\":{\"from\":" . $this->CHARGES_FILTER['period']['date']['from'] . ",\"to\":" . $this->CHARGES_FILTER['period']['date']['to'] . "}},\"status\":\" " . $this->CHARGES_FILTER['status'] . " \",\"limit\":" . $this->CHARGES_FILTER['limit'] . "}",
            CURLOPT_HTTPHEADER => array(
                "authorization: Bearer " . $this->CONFIG_VARS['secret_api_Key'] . " ",
                "content-type: application/json"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            throw new \Exception("Exception  $err");
        } else {
            $json_response = json_decode($response);
            if (isset($json_response->errors) && is_array($json_response->errors) && count($json_response->errors) > 0) {
                throw new \Exception("Error : " . $json_response->errors[0]->code . " ");
            }
            if (isset($json_response->object_type) && $json_response->object_type == "list") {
                return $json_response;
            } else {
                throw new \Exception("Error : " . $response . " ");
            }
        }

        return false;
    }

    public function refund($data = [])
    {
        $this->refungValidator($data);
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.tap.company/v2/refunds",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "{\"charge_id\":\"" . $this->REFUND_VARS['charge_id'] . "\",\"amount\":" . $this->REFUND_VARS['amount'] . ",\"currency\":\"" . $this->REFUND_VARS['currency'] . "\",\"description\":\"" . $this->REFUND_VARS['description'] . "\",\"reason\":\"" . $this->REFUND_VARS['reason'] . "\",
            \"reference\":{\"merchant\":\"" . $this->REFUND_VARS['reference']['merchant'] . "\"},\"metadata\":{\"udf1\":\"" . $this->REFUND_VARS['metadata']['udf1'] . "\",\"udf2\":\"" . $this->REFUND_VARS['metadata']['udf2'] . "\"},\"post\":{\"url\":\"" . $this->REFUND_VARS['post']['url'] . "\"}}",
            CURLOPT_HTTPHEADER => array(
                "authorization: Bearer " . $this->CONFIG_VARS['secret_api_Key'] . " ",
                "content-type: application/json"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            throw new \Exception("Exception  $err");
        } else {
            $json_response = json_decode($response);
            if (isset($json_response->errors) && is_array($json_response->errors) && count($json_response->errors) > 0) {
                throw new \Exception("Error : " . $response . " ");
            }
            if (isset($json_response->object) && $json_response->object == "refund") {
                return $json_response;
            } else {
                throw new \Exception("Error : " . $response . " ");
            }
        }

        return false;
    }

    public function getRefund($refund_id)
    {
        if ($refund_id == null) {
            throw new \InvalidArgumentException("InvalidArgumentException refund_id required");
        }
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.tap.company/v2/refunds/$refund_id",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_POSTFIELDS => "{}",
            CURLOPT_HTTPHEADER => array(
                "authorization: Bearer " . $this->CONFIG_VARS['secret_api_Key'] . " ",
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            throw new \Exception("Exception  $err");
        } else {
            $json_response = json_decode($response);
            if (isset($json_response->errors) && is_array($json_response->errors) && count($json_response->errors) > 0) {
                throw new \Exception("Error : " . $json_response->errors[0]->code . " ");
            }
            if (isset($json_response->object) && $json_response->object == "refund") {
                return $json_response;
            } else {
                throw new \Exception("Error : " . $response . " ");
            }
        }

        return false;
    }

    public function refundList($options = [])
    {
        $this->refundsListValidator($options);
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.tap.company/v2/refunds/list",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "{\"period\":{\"date\":{\"from\":" . $this->REFUNDS_FILTER['period']['date']['from'] . ",\"to\":" . $this->REFUNDS_FILTER['period']['date']['to'] . "}},\"starting_after\":\"\",\"limit\":" . $this->REFUNDS_FILTER['limit'] . "}",
            CURLOPT_HTTPHEADER => array(
                "authorization: Bearer " . $this->CONFIG_VARS['secret_api_Key'] . " ",
                "content-type: application/json"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            throw new \Exception("Exception  $err");
        } else {
            $json_response = json_decode($response);
            if (isset($json_response->errors) && is_array($json_response->errors) && count($json_response->errors) > 0) {
                throw new \Exception("Error : " . $json_response->errors[0]->code . " ");
            }
            if (isset($json_response->object) && $json_response->object == "list") {
                return $json_response;
            } else {
                throw new \Exception("Error : " . $response . " ");
            }
        }

        return false;
    }
    // Implement other methods similarly
}

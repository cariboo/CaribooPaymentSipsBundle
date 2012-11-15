<?php

namespace Cariboo\Payment\SipsBundle\Client;

use Symfony\Component\HttpFoundation\ParameterBag;

/*
 * Copyright 2012 Stephane Decleire <sdecleire@cariboo-networks.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

class Response
{
    const CODE_OK       = 0;
    const CODE_ERROR    = -1;

    public $body;

    public function __construct(array $parameters)
    {
        // The Sips binary returns : $result=!code!error!buffer!
        //      - code=0    : The HTML code to display the buttons is returned in the buffer part of the response
        //      - code=-1   : The error message is returned in the error part of the response
        $this->body = new ParameterBag();
        $this->body->set('code', $parameters[1]);
        $this->body->set('error', $parameters[2]);
        if (5 == count($parameters))
        {
            // Request call
            $this->body->set('message', $parameters[3]);
        }
        else
        {
            // Response call
            $this->body->set('merchant_id', $parameters[3]);
            $this->body->set('merchant_country', $parameters[4]);
            $this->body->set('amount', $parameters[5]);
            $this->body->set('transaction_id', $parameters[6]);
            $this->body->set('payment_means', $parameters[7]);
            $this->body->set('transmission_date', $parameters[8]);
            $this->body->set('payment_time', $parameters[9]);
            $this->body->set('payment_date', $parameters[10]);
            $this->body->set('response_code', $parameters[11]);
            $this->body->set('payment_certificate', $parameters[12]);
            $this->body->set('authorisation_id', $parameters[13]);
            $this->body->set('currency_code', $parameters[14]);
            $this->body->set('card_number', $parameters[15]);
            $this->body->set('cvv_flag', $parameters[16]);
            $this->body->set('cvv_response_code', $parameters[17]);
            $this->body->set('bank_response_code', $parameters[18]);
            $this->body->set('complementary_code', $parameters[19]);
            $this->body->set('complementary_info', $parameters[20]);
            $this->body->set('return_context', $parameters[21]);
            $this->body->set('caddie', $parameters[22]);
            $this->body->set('receipt_complement', $parameters[23]);
            $this->body->set('merchant_language', $parameters[24]);
            $this->body->set('language', $parameters[25]);
            $this->body->set('customer_id', $parameters[26]);
            $this->body->set('order_id', $parameters[27]);
            $this->body->set('customer_email', $parameters[28]);
            $this->body->set('customer_ip_address', $parameters[29]);
            $this->body->set('capture_day', $parameters[30]);
            $this->body->set('capture_mode', $parameters[31]);
            $this->body->set('data', $parameters[32]);
            $this->body->set('order_validity', $parameters[33]);
            $this->body->set('transaction_condition', $parameters[34]);
            $this->body->set('statement_reference', $parameters[35]);
            $this->body->set('card_validity', $parameters[36]);
            $this->body->set('score_value', $parameters[37]);
            $this->body->set('score_color', $parameters[38]);
            $this->body->set('score_info', $parameters[39]);
            $this->body->set('score_threshold', $parameters[40]);
            $this->body->set('score_profile', $parameters[41]);
        }
    }

    public function isSuccess()
    {
        return self::CODE_OK == $this->body->get('code');
    }

    public function isError()
    {
        return self::CODE_ERROR == $this->body->get('code');
    }

    public function getResponseCode()
    {
        If (isSuccess()) {
            return 'Success';
        }
        return 'Failed';
    }

    public function getError()
    {
        return $this->body->get('error');
    }

    public function __toString()
    {
        if ($this->isError()) {
            $str = 'Debug-Token: '.$this->body->get('error')."\n";
        }
        else {
            $str = var_export($this->body->all(), true);
        }

        return $str;
    }
}
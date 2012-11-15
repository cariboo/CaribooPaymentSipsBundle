<?php
namespace Cariboo\Payment\SipsBundle\Client;

use JMS\Payment\CoreBundle\Plugin\Exception\CommunicationException;
use Cariboo\Payment\SipsBundle\Client\Response;

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

class Client
{
    const API_VERSION = '6.15';

    const DEFAULT_CURRENCY_CODE = '978';

    private $currencies = array(
        'EUR' => '978',         // Euro
        'USD' => '840',         // US Dollar
        'JPY' => '392',         // Yen
        'GBP' => '826'          // Pound Sterling
    );

    protected $merchantId;      // Merchant ID assigned by SIPS
    protected $country;         // Merchant country code ISO 3166
    protected $pathfile;        // Path of the configuration file
    protected $requestPath;     // Path of the request binary from the SIPS API
    protected $responsePath;    // Path of the response binary from the SPIS API

    public function __construct($merchantId, $country, $pathfile, $requestPath, $responsePath)
    {
        $this->merchantId = $merchantId;
        $this->country = $country;
        $this->pathfile = $pathfile;
        $this->requestPath = $requestPath;
        $this->responsePath = $responsePath;
    }

    /**
     * Get the SIPS checkout buttons
     *
     * @param float $amount
     * @param string $currency
     * @param array $config
     * @param array $parameters
     * @return Cariboo\Payment\SipsBundle\Client\Response
     */
    public function requestGetCheckoutToken($amount, $currency, array $parameters = array())
    {
        return $this->sendApiRequest($this->requestPath, array_merge($parameters, array(
            "merchant_id" => $this->merchantId,
            "merchant_country" => $this->country,
            "pathfile" => $this->pathfile,
            "amount" => $this->convertAmountToSipsFormat($amount),
            "currency_code" => $this->getCurrencyCode($currency)
        )));
    }

    /**
     * Do SIPS checkout payment
     *
     * @param array $config
     * @param string $encryptedData
     * @return Cariboo\Payment\SipsBundle\Client\Response
     */
    public function requestDoCheckoutPayment($encryptedData)
    {
        return $this->sendApiRequest($this->responsePath, array(
            "pathfile" => $this->pathfile,
            "message" => $encryptedData
        ));
    }

    /**
     * Send the request to the Sips API
     *
     * @param string $action
     * @return array $parameters
     */
    protected function sendApiRequest($action, array $parameters)
    {
        // Call the SIPS API
        $result = exec($action.' '.$this->encodeArray($parameters));
        $response = new Response(explode('!', $result));

        if ($response->isError()) {
            throw new CommunicationException('The API request was not successful (Status: '.$response->getError().')');
        }

        return $response;
    }

    /**
     * Convert amounts in the format waited by Sips
     *
     * @param float $amount
     * @return string
     */
    protected function convertAmountToSipsFormat($amount)
    {
        return number_format($amount, 2, '.', '');
    }

    /**
     * Get the currency code
     *
     * @param string $currency
     * @return string
     */
    protected function getCurrencyCode($currency)
    {
        $code = self::DEFAULT_CURRENCY_CODE;

        if (array_key_exists($currency, $this->currencies)) {
            $code = $this->currencies[$currency];
        }

        return $code;
    }

    /**
     * Encode an array into shell parameters
     *
     * @param array $encode
     * @return string
     */
    protected function encodeArray(array $encode)
    {
        $encoded = '';
        foreach ($encode as $name => $value) {
            $encoded .= ' '.$name.'='.escapeshellarg($value);
        }

        return substr($encoded, 1);
    }
}

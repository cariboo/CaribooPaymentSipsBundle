<?php

namespace Cariboo\Payment\SipsBundle\Tests\Client;

use Cariboo\Payment\SipsBundle\Client\Client;

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

class ClientTest extends \PHPUnit_Framework_TestCase
{
    public static function provideExpectedCallPaymentUrlDependsOnDebugFlag()
    {
        return array(
            array(true, 'https://payment.sips-atos.com:443/cgis-payment/demo/callpayment'),
            array(false, 'https://payment.sips-atos.com:443/cgis-payment/prod/callpayment'),
        );
    }

    public function testShouldAllowGetCallPaymentUrlInDebugMode()
    {
        $expectedUrl = 'https://payment.sips-atos.com:443/cgis-payment/demo/callpayment';

        $client = new Client(
            $_SERVER['MERCHANT_ID'],
            $_SERVER['MERCHANT_COUNTRY'],
            $_SERVER['PATHFILE'],
            $_SERVER['REQUEST_PATH'],
            $_SERVER['RESPONSE_PATH'],
            $debug = true
        );

        $this->assertEquals($expectedUrl, $client->getCallPaymentUrl());
    }

    public function testShouldAllowGetCallPaymentUrlInProdMode()
    {
        $expectedUrl = 'https://payment.sips-atos.com:443/cgis-payment/prod/callpayment';

        $client = new Client(
            $_SERVER['MERCHANT_ID'],
            $_SERVER['MERCHANT_COUNTRY'],
            $_SERVER['PATHFILE'],
            $_SERVER['REQUEST_PATH'],
            $_SERVER['RESPONSE_PATH'],
            $debug = false);

        $this->assertEquals($expectedUrl, $client->getCallPaymentUrl());
    }
}

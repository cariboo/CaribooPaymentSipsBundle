<?php

namespace Cariboo\Payment\SipsBundle\Tests\Functional\Client;

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
    /**
     * @var \Cariboo\Payment\SipsBundle\Client\Client
     */
    protected $client;

    protected function setUp()
    {
        if (empty($_SERVER['MERCHANT_ID']) || empty($_SERVER['MERCHANT_COUNTRY']) || empty($_SERVER['PATHFILE']) || empty($_SERVER['REQUEST_PATH']) || empty($_SERVER['RESPONSE_PATH'])) {
            $this->markTestSkipped('In order to run these tests you have to configure: MERCHANT_ID, MERCHANT_COUNTRY, PATHFILE, REQUEST_PATH and RESPONSE_PATH parameters in phpunit.xml file');
        }

        $this->client = new Client(
            $_SERVER['MERCHANT_ID'],
            $_SERVER['MERCHANT_COUNTRY'],
            $_SERVER['PATHFILE'],
            $_SERVER['REQUEST_PATH'],
            $_SERVER['RESPONSE_PATH'],
            $debug = true
        );
    }

    public function testRequestGetCheckoutToken()
    {
        $response = $this->client->requestGetCheckoutToken(123.43, 'EUR', $parameters = array());

        $this->assertInstanceOf('Cariboo\Payment\SipsBundle\Client\Response', $response);
        $this->assertTrue($response->body->has('message'));
        $this->assertTrue($response->isSuccess());
        $this->assertEquals(0, $response->body->get('code'));
    }

    public function testRequestDoCheckoutPayment()
    {
        // encrypted positive answer from the SIPS API
        $data = "2020343430603028502c2360542d2360532d2360522e2360502c4360502c3334502c3324522c432c532d2330552d3324512c33242a2c2360532c2360502c5338502c6048502c2334502c2360532e3338565c224324502d3360502c23313632352d215c224360502d4360502c3330522c2324522c3324522d5324542c3360512d3048512c232c502c2360562c3334512c2330565c224324502d2360502c2340522c2324522c3324522d5048512c2324502c2360522c23602a2c3360522c2360512c2324532d3330502c4334542d23382a2c3360502c2360502d4360522d3330542d4048502c4340502c2360523947282a2c2328592c2360502c4639525c224360502e2360502c232c592d53402a2c3360562c2360502d5324512c43284e2c23602a2c3324542c2360502d4328502c3330502c3048502c2344502c2360523947282a2c2328542c2360512e2328502c3328512c3328572c3334502e3334523454352b335048502c3338502c23605334552d2c5c224360502d5360502c2328502c4048502c433c502c2338522f25353331355d2334552c5e2e5641543d27605a2b525d573d573c4e384635423932554e3b57354e3b57344e38565d4d2b562d533c525d493c26414f3b46344e38572d532f5c225d353454353f30552d332f434c2a2c232c582c2360502c5344562d4048502d2360502c2324543035353432245d3237542d21342531353444342a2c232c592c2360502c33602a2c3360592c2360502c4331245c224324512c2360502c2324515c224324512c3360502c2328502c6048512c4328502c2360512e3048512c432c502c2360512c6048609f22786aed1a53a9";

        $response = $this->client->requestDoCheckoutPayment($data);
        $this->assertInstanceOf('Cariboo\Payment\SipsBundle\Client\Response', $response);
        $this->assertTrue($response->body->has('response_code'));
        $this->assertEquals('00', $response->body->get('code'));
    }
}
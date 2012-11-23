<?php

namespace Cariboo\Payment\SipsBundle\Plugin;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use JMS\Payment\CoreBundle\Model\ExtendedDataInterface;
use JMS\Payment\CoreBundle\Model\FinancialTransactionInterface;
use JMS\Payment\CoreBundle\Plugin\PluginInterface;
use JMS\Payment\CoreBundle\Plugin\AbstractPlugin;
use JMS\Payment\CoreBundle\Plugin\Exception\PaymentPendingException;
use JMS\Payment\CoreBundle\Plugin\Exception\FinancialException;
use JMS\Payment\CoreBundle\Plugin\Exception\Action\VisitUrl;
use JMS\Payment\CoreBundle\Plugin\Exception\ActionRequiredException;
use JMS\Payment\CoreBundle\Util\Number;
use Cariboo\Payment\SipsBundle\Client\Client;
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

class SipsCheckoutPlugin extends AbstractPlugin
{
    const MAX_TRANSACTION_ID = 999999;       // Maximal transaction Id supported by SIPS

    protected $container;

    /**
     * @var \Cariboo\Payment\SipsBundle\Client\Client
     */
    protected $client;

    /**
     * @var string
     */
    protected $returnUrl;

    /**
     * @var string
     */
    protected $cancelUrl;

    /**
     * @var string
     */
    protected $chooseUrl;

    /**
     * @var string
     */
    protected $notifyUrl;

    /**
     * @param string $returnUrl
     * @param string $cancelUrl
     * @param string $notifyUrl
     * @param \Cariboo\Payment\SipsBundle\Client\Client $client
     */
    public function __construct(ContainerInterface $container, Client $client , $returnUrl, $cancelUrl, $chooseUrl, $notifyUrl)
    {
        $this->container    = $container;
        $this->client       = $client;
        $this->returnUrl    = $returnUrl;
        $this->cancelUrl    = $cancelUrl;
        $this->chooseUrl    = $chooseUrl;
        $this->notifyUrl    = $notifyUrl;
    }

    public function approveAndDeposit(FinancialTransactionInterface $transaction, $retry)
    {
        $request = $this->container->get('request');

        if ($request->isMethod('POST')) {
            $data = $request->get('DATA');

            // Process the transaction
            $response = $this->client->requestDoCheckoutPayment($data);
            $this->throwUnlessSuccessResponse($response, $transaction);
            $this->process($transaction, $response);
        }
        else
        {
            $data = $transaction->getExtendedData();
            $token = $this->obtainSipsCheckoutToken($transaction);

            $actionRequest = new ActionRequiredException('User has not yet chosen his credit card type.');
            $actionRequest->setFinancialTransaction($transaction);
            $url = $this->getChooseUrl($data).'?url='.urlencode($this->client->getCallPaymentUrl()).'&token='.$token;
            $actionRequest->setAction(new VisitUrl($url));

            throw $actionRequest;
        }
    }

    public function processNotify(Request $request)
    {
        $data = $request->request->get('DATA');

        // Process the transaction
        $response = $this->client->requestDoCheckoutPayment($data);

        return $response;
    }

    public function processes($paymentSystemName)
    {
        return 'sips_checkout' === $paymentSystemName;
    }

    public function isIndependentCreditSupported()
    {
        return false;
    }

     /**
     * @param \JMS\Payment\CoreBundle\Model\FinancialTransactionInterface $transaction
     * @return string
     */
    protected function obtainSipsCheckoutToken(FinancialTransactionInterface $transaction)
    {
        $data = $transaction->getExtendedData();

        if ($transaction->getTrackingId() == null) {
            $em = $this->container->get('doctrine')->getEntityManager();
            $transaction->setTrackingId($data->get('tracking_id'));
            $em->persist($transaction);
            $em->flush($transaction);
        }

        if ($data->has('sips_checkout_token')) {
            return $data->get('sips_checkout_token');
        }

        $opts = array();
        $opts['normal_return_url'] = $this->getReturnUrl($data);
        $opts['cancel_return_url'] = $this->getCancelUrl($data);
        $opts['automatic_response_url'] = $this->getNotifyUrl($data);
        $opts['order_id'] = $data->get('order_id');
        $opts['caddie'] = $data->get('tracking_id');

        // SIPS default transaction Id should not be used (based on time : can't manage more than 1 transaction per second !)
        if ($data->has('transaction_id')) {
            $opts['transaction_id'] = $data->get('transaction_id');
        }

    //      $parm="$parm transaction_id=123456";
    //      $parm="$parm language=fr";
    //      $parm="$parm payment_means=CB,2,VISA,2,MASTERCARD,2";
    //      $parm="$parm header_flag=no";
    //      $parm="$parm capture_day=";
    //      $parm="$parm capture_mode=";
    //      $parm="$parm bgcolor=";
    //      $parm="$parm block_align=";
    //      $parm="$parm block_order=";
    //      $parm="$parm textcolor=";
    //      $parm="$parm receipt_complement=";
    //      $parm="$parm caddie=mon_caddie";
    //      $parm="$parm customer_id=";
    //      $parm="$parm customer_email=";
    //      $parm="$parm customer_ip_address=";
    //      $parm="$parm data=";
    //      $parm="$parm return_context=";
    //      $parm="$parm target=";
    //      $parm="$parm order_id=";

    //      $parm="$parm normal_return_logo=";
    //      $parm="$parm cancel_return_logo=";
    //      $parm="$parm submit_logo=";
    //      $parm="$parm logo_id=";
    //      $parm="$parm logo_id2=";
    //      $parm="$parm advert=";
    //      $parm="$parm background_id=";
    //      $parm="$parm templatefile=";

        $amount = $transaction->getRequestedAmount();
        $currency = $transaction->getPayment()->getPaymentInstruction()->getCurrency();

        $response = $this->client->requestGetCheckoutToken($amount, $currency, $opts);
        $this->throwUnlessSuccessResponse($response, $transaction);


        // Extract encrypted string
        preg_match('/VALUE="([^"]+)"/i', $response->body->get('message'), $matches);
        $encrypted = $matches[1];

        $data->set('sips_checkout_token', $encrypted);

        // $transaction->setState(FinancialTransactionInterface::STATE_PENDING);

        return $encrypted;
    }

   /**
     * @param \JMS\Payment\CoreBundle\Model\FinancialTransactionInterface $transaction
     * @param \Cariboo\Payment\SipsBundle\Client\Response $response
     * @return null
     * @throws \JMS\Payment\CoreBundle\Plugin\Exception\FinancialException
     */
    protected function process($transaction, $response)
    {
        switch ($response->body->get('response_code')) {
            case '00':      // Autorisation acceptée
                $transaction->setReferenceNumber($response->body->get('transaction_id'));
                $transaction->setProcessedAmount($this->client->convertAmountFromSipsFormat($response->body->get('amount'), $response->body->get('currency_code')));
                $transaction->setResponseCode(PluginInterface::RESPONSE_CODE_SUCCESS);
                $transaction->setReasonCode(PluginInterface::REASON_CODE_SUCCESS);
                break;
            
            case '17':      // Annulation de l'internaute
                $ex = new PaymentPendingException('PaymentAction cancelled.');
                $transaction->setReferenceNumber($response->body->get('transaction_id'));
                $transaction->setResponseCode('Cancelled');
                $transaction->setReasonCode($response->body->get('response_code').': '.$response->body->get('complementary_info'));
                $ex->setFinancialTransaction($transaction);
                throw $ex;
            
            case '02':      // Dépassement de plafond. Forçage possible par téléphone (selon contrat)
            case '05':      // Autorisation refusée
                $ex = new FinancialException('PaymentAction failed.');
                $transaction->setReferenceNumber($response->body->get('transaction_id'));
                $transaction->setResponseCode('Failed');
                $transaction->setReasonCode($response->body->get('response_code').': '.$response->body->get('complementary_info'));
                $ex->setFinancialTransaction($transaction);
                throw $ex;
            
            case '34':      // Suspicion de fraude
            case '75':      // Nombre de tentatives de saisie du numéro de carte dépassé
                $ex = new FinancialException('PaymentAction failed.');
                $transaction->setReferenceNumber($response->body->get('transaction_id'));
                $transaction->setResponseCode('Failed');
                $transaction->setReasonCode($response->body->get('response_code').': '.$response->body->get('complementary_info'));
                $ex->setFinancialTransaction($transaction);
                throw $ex;
            
            case '03':      // Champ merchant_id invalide ou contrat VAD inexistant
            case '12':      // Transaction invalide : vérifier les paramètres de la requête
            case '30':      // Erreur de format
            case '90':      // Service temporairement indisponible
            default:
                $ex = new InternalErrorException('PaymentAction failed.');
                $transaction->setReferenceNumber($response->body->get('transaction_id'));
                $transaction->setResponseCode('Failed');
                $transaction->setReasonCode($response->body->get('response_code').': '.$response->body->get('complementary_info'));
                $ex->setFinancialTransaction($transaction);
                throw $ex;
        }
    }

   /**
     * @param \JMS\Payment\CoreBundle\Model\FinancialTransactionInterface $transaction
     * @param \JMS\Payment\SipsBundle\Client\Response $response
     * @return null
     * @throws \JMS\Payment\CoreBundle\Plugin\Exception\FinancialException
     */
    protected function throwUnlessSuccessResponse(Response $response, FinancialTransactionInterface $transaction)
    {
        if ($response->isSuccess()) {
            return;
        }

        $transaction->setResponseCode('Failed');
        $transaction->setReasonCode('APICallFailed');

        $ex = new FinancialException('SIPS-Response was not successful: '.$response);
        $ex->setFinancialTransaction($transaction);

        throw $ex;
    }

    protected function getReturnUrl(ExtendedDataInterface $data)
    {
        if ($data->has('normal_return_url')) {
            return $data->get('normal_return_url');
        }
        else if (0 !== strlen($this->returnUrl)) {
            return $this->returnUrl;
        }

        throw new \RuntimeException('You must configure a return url.');
    }

    protected function getCancelUrl(ExtendedDataInterface $data)
    {
        if ($data->has('cancel_return_url')) {
            return $data->get('cancel_return_url');
        }
        else if (0 !== strlen($this->cancelUrl)) {
            return $this->cancelUrl;
        }

        throw new \RuntimeException('You must configure a cancellation url.');
    }

    protected function getChooseUrl(ExtendedDataInterface $data)
    {
        if ($data->has('choose_card_url')) {
            return $data->get('choose_card_url');
        }
        else if (0 !== strlen($this->chooseUrl)) {
            return $this->chooseUrl;
        }

        throw new \RuntimeException('You must configure a url to let the user choose his credit card type.');
    }

    protected function getNotifyUrl(ExtendedDataInterface $data)
    {
        if ($data->has('automatic_response_url')) {
            return $data->get('automatic_response_url');
        }
        else if (0 !== strlen($this->notifyUrl)) {
            return $this->notifyUrl;
        }

        throw new \RuntimeException('You must configure a notification url.');
    }
}
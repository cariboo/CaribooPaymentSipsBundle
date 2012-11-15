<?php

namespace Cariboo\Payment\SipsBundle\Plugin;

use Symfony\Component\DependencyInjection\ContainerInterface;
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
        $logger = $this->container->get('logger');

        if ($request->isMethod('POST')) {
            $postData = $request->request->get('data');
            $data = $postData['DATA'];
        }
        else
        {
            $data = $transaction->getExtendedData();
            $token = $this->obtainSipsCheckoutToken($transaction);

            $actionRequest = new ActionRequiredException('User has not yet chosen his credit card type.');
            $actionRequest->setFinancialTransaction($transaction);
            $url = $this->getChooseUrl($data).'?url='.$this->client->getCallPaymentUrl().'&token='.$token;
            $logger->info('URL:'.$url);
            $actionRequest->setAction(new VisitUrl($url));

            throw $actionRequest;
        }

        // $form = $this->container->get('form.factory')->createFormBuilder()
        //     ->add('DATA', 'text')
        //     ->getForm();

        // if ($request->isMethod('POST')) {
        //     $form->bind($request);
        //     $data = $form->getData();
        // }



        // $details = $this->client->requestGetExpressCheckoutDetails($token);
        // $this->throwUnlessSuccessResponse($details, $transaction);

        // // verify checkout status
        // switch ($details->body->get('CHECKOUTSTATUS')) {
        //     case 'PaymentActionFailed':
        //         $ex = new FinancialException('PaymentAction failed.');
        //         $transaction->setResponseCode('Failed');
        //         $transaction->setReasonCode('PaymentActionFailed');
        //         $ex->setFinancialTransaction($transaction);

        //         throw $ex;

        //     case 'PaymentCompleted':
        //         break;

        //     case 'PaymentActionNotInitiated':
        //         break;

        //     default:
        //         $actionRequest = new ActionRequiredException('User has not yet authorized the transaction.');
        //         $actionRequest->setFinancialTransaction($transaction);
        //         $actionRequest->setAction(new VisitUrl($this->client->getAuthenticateExpressCheckoutTokenUrl($token)));

        //         throw $actionRequest;
        // }

        // // complete the transaction
        // $data->set('sips_payer_id', $details->body->get('PAYERID'));

        // $opts = $data->has('checkout_params') ? $data->get('checkout_params') : array();
        // $opts['PAYMENTREQUEST_0_CURRENCYCODE'] = $transaction->getPayment()->getPaymentInstruction()->getCurrency();

        // if ($data->has('checkout_items'))
        // {
        //     $itemsAmount = 0.00;
        //     $idx = 0;
        //     foreach($data->get('checkout_items') as $item)
        //     {
        //         $opts['L_PAYMENTREQUEST_0_ITEMCATEGORY' . $idx] = $item['category'];
        //         $opts['L_PAYMENTREQUEST_0_NAME' . $idx] = $item['label'];
        //         $opts['L_PAYMENTREQUEST_0_QTY' . $idx] = $item['quantity'];
        //         $opts['L_PAYMENTREQUEST_0_AMT' . $idx] = $item['unit_price'];
        //         $itemsAmount += $item['unit_price'] * $item['quantity'];
        //         $idx++;
        //     }
        //     $opts['PAYMENTREQUEST_0_ITEMAMT'] = $itemsAmount;
        // }
        // $response = $this->client->requestDoExpressCheckoutPayment(
        //     $data->get('sips_checkout_token'),
        //     $transaction->getRequestedAmount(),
        //     $paymentAction,
        //     $details->body->get('PAYERID'),
        //     $opts
        // );
        // $this->throwUnlessSuccessResponse($response, $transaction);

        // switch($response->body->get('PAYMENTINFO_0_PAYMENTSTATUS')) {
        //     case 'Completed':
        //         break;

        //     case 'Pending':
        //         $transaction->setReferenceNumber($response->body->get('PAYMENTINFO_0_TRANSACTIONID'));
                
        //         throw new PaymentPendingException('Payment is still pending: '.$response->body->get('PAYMENTINFO_0_PENDINGREASON'));

        //     default:
        //         $ex = new FinancialException('PaymentStatus is not completed: '.$response->body->get('PAYMENTINFO_0_PAYMENTSTATUS'));
        //         $ex->setFinancialTransaction($transaction);
        //         $transaction->setResponseCode('Failed');
        //         $transaction->setReasonCode($response->body->get('PAYMENTINFO_0_PAYMENTSTATUS'));

        //         throw $ex;
        // }

        // $transaction->setReferenceNumber($response->body->get('PAYMENTINFO_0_TRANSACTIONID'));
        // $transaction->setProcessedAmount($response->body->get('PAYMENTINFO_0_AMT'));
        // $transaction->setResponseCode(PluginInterface::RESPONSE_CODE_SUCCESS);
        // $transaction->setReasonCode(PluginInterface::REASON_CODE_SUCCESS);
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
        if ($data->has('sips_checkout_token')) {
            return $data->get('sips_checkout_token');
        }

        $opts = array();
        $opts['normal_return_url'] = $this->getReturnUrl($data);
        $opts['cancel_return_url'] = $this->getCancelUrl($data);
        $opts['automatic_response_url'] = $this->getNotifyUrl($data);

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

        // $trackingId = $this->getTrackingId();
        // $transaction->setTrackingId($trackingId);
        // $this->container->get('logger')->info('session: '.$transaction->getTrackingId());    

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
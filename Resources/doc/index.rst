============
Installation
============
Dependencies
------------
This plugin depends on the JMSPaymentCoreBundle_, so you'll need to add this to your kernel
as well even if you don't want to use its persistence capabilities.

Configuration
-------------
::

    // YAML
    cariboo_payment_sips:
        pathfile: the path to the pathfile configuration file of the SIPS API
        request_path: the path to the request binary of the SIPS API
        reponse_path: the path to the response binary of the SIPS API
        merchant_id: your Merchant ID
        merchant_country: your country code (ISO 3166)


=====
Usage
=====
With the Payment Plugin Controller (Recommended)
------------------------------------------------
http://jmsyst.com/bundles/JMSPaymentCoreBundle/master/usage

Without the Payment Plugin Controller
-------------------------------------
The Payment Plugin Controller is made available by the CoreBundle and basically is the 
interface to a persistence backend like the Doctrine ORM. It also performs additional 
integrity checks to validate transactions. If you don't need these checks, and only want 
an easy way to communicate with the SIPS API, then you can use the plugin directly::

    $plugin = $container->get('payment.plugin.sips_checkout');

.. _JMSPaymentCoreBundle: https://github.com/schmittjoh/JMSPaymentCoreBundle/blob/master/Resources/doc/index.rst

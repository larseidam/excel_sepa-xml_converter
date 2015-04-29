<?php

require_once 'vendor/autoload.php';
use Digitick\Sepa\TransferFile\CustomerCreditTransferFile;
use Digitick\Sepa\TransferInformation\CustomerCreditTransferInformation;
use Digitick\Sepa\GroupHeader;
use Digitick\Sepa\PaymentInformation;
use Digitick\Sepa\DomBuilder\DomBuilderFactory;

// Create the initiating information
$groupHeader = new GroupHeader('SEPA File Identifier', 'Your Company Name');
$sepaFile = new CustomerCreditTransferFile($groupHeader);

$transfer = new CustomerCreditTransferInformation(
    '0.02', // Amount
    'FI1350001540000056', //IBAN of creditor
    'Their Corp' //Name of Creditor
);
$transfer->setBic('OKOYFIHH'); // Set the BIC explicitly
$transfer->setRemittanceInformation('Transaction Description');

$transfer1 = new CustomerCreditTransferInformation(
    '200.02', // Amount
    'FI1350001540000046', //IBAN of creditor
    'Their Corp1' //Name of Creditor
);
$transfer1->setBic('OKOYFIHH'); // Set the BIC explicitly
$transfer1->setRemittanceInformation('Transaction Description1');

// Create a PaymentInformation the Transfer belongs to
$payment1 = new PaymentInformation(
    'Payment Info ID',
    'DE68210501700012345678', // IBAN the money is transferred from
    'ESSEDE5F100', // BIC
    'SEB Berlin' // Debitor Name
);

// Create a PaymentInformation the Transfer belongs to
$payment = new PaymentInformation(
    'Payment Info ID',
    'DE98100400000200039600', // IBAN the money is transferred from
    'COBADEBBXXX', // BIC
    'Commerzbank -West- Berlin' // Debitor Name
);
// It's possible to add multiple Transfers in one Payment
$payment->addTransfer($transfer);
$payment1->addTransfer($transfer1);

// It's possible to add multiple payments to one SEPA File
$sepaFile->addPaymentInformation($payment);
$sepaFile->addPaymentInformation($payment1);

// Attach a dombuilder to the sepaFile to create the XML output
$domBuilder = DomBuilderFactory::createDomBuilder($sepaFile);

// Or if you want to use the format 'pain.001.001.03' instead
// $domBuilder = DomBuilderFactory::createDomBuilder($sepaFile, 'pain.001.001.03');

echo $domBuilder->asXml();

?>

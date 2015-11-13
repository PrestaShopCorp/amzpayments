<?php

/*******************************************************************************
 *  Copyright 2013 Amazon.com, Inc. or its affiliates. All Rights Reserved.
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *
 *  You may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at:
 *  http://aws.amazon.com/apache2.0
 *  This file is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR
 *  CONDITIONS OF ANY KIND, either express or implied. See the License
 *  for the
 *  specific language governing permissions and limitations under the
 *  License.
 * *****************************************************************************
 */

require_once 'OffAmazonPaymentsNotifications/Impl/Message.php';
require_once 'OffAmazonPaymentsNotifications/Impl/VerifySignature.php';
require_once 'OffAmazonPaymentsNotifications/InvalidMessageException.php';

/**
 * Performs validation of the sns message to
 * make sure signatures match and is signed by 
 * Amazon
 * 
 */
class SnsMessageValidator
{
    /**
     * Implementation of the signature verification algorithm
     *
     * @var VerifySignature
     */
    private $_verifySignature = null;
    
   /**
    * @var string  A pattern that will match all regional SNS endpoints, e.g.:
    *                  - sns.<region>.amazonaws.com        (AWS)
    *                  - sns.us-gov-west-1.amazonaws.com   (AWS GovCloud)
    *                  - sns.cn-north-1.amazonaws.com.cn   (AWS China)
    */
    private static $defaultHostPattern = '/^sns\.[a-zA-Z0-9\-]{3,}\.amazonaws\.com(\.cn)?$/';    
    
    /**
     * Create new instance of the SnsMessageValidator
     * 
     * @param VerifySignature $verifySignature implementation of the 
     *                                         verify signature algorithm
     *                                         
     * @throws OffAmazonPaymentsNotifications_InvalidMessageException if verification fails
     * 
     * @return void
     */
    public function __construct(VerifySignature $verifySignature)
    {
        $this->_verifySignature = $verifySignature;
    }
    
    /**
     * Validate that the given sns message is valid
     * defined as being signed by Amazon and that the
     * signature matches the message contents
     * 
     * @param Message $snsMessage sns message to check
     * 
     * @throws OffAmazonPaymentsNotifications_InvalidMessageException if the
     *                                                                validation
     *                                                                fails
     *
     * @return void
     */
    public function validateMessage(Message $snsMessage)
    {
        switch($snsMessage->getMandatoryField("SignatureVersion")) {
        case "1":
            $this->_verifySignatureWithVersionOneAlgorithm($snsMessage);
            break;
        default:
            throw new OffAmazonPaymentsNotifications_InvalidMessageException(
                "Error with signature verification - " .
                "unable to handle signature version " .
                $snsMessage->getMandatoryField("SignatureVersion")
            );
        }
    }
    
    /**
     * Implement the version one signature verification algorithm
     * 
     * @param Message $snsMessage sns message
     * 
     * @throws OffAmazonPaymentsNotifications_InvalidMessageException if the
     *                                                                validation
     *                                                                fails
     *
     * @return void
     */
    private function _verifySignatureWithVersionOneAlgorithm(Message $snsMessage)
    {   
        
        $this->validateUrl($snsMessage->getMandatoryField("SigningCertURL"));
        
        $result = $this->_verifySignature->verifySignatureIsCorrect(
            $this->_constructSignatureFromSnsMessage($snsMessage),
            base64_decode($snsMessage->getMandatoryField("Signature")),
            $snsMessage->getMandatoryField("SigningCertURL")
        );
        
        if (!$result) {
            throw new OffAmazonPaymentsNotifications_InvalidMessageException(
                "Unable to match signature from remote server: signature of " .
                $this->_constructSignatureFromSnsMessage($snsMessage) . 
                " , SigningCertURL of " . 
                $snsMessage->getMandatoryField("SigningCertURL") . 
                " , SignatureOf " . 
                $snsMessage->getMandatoryField("Signature")
            );
        }
    }
    
    /**
     * Recreate the signature based on the field values for the
     * sns message
     * 
     * @param Message $snsMessage sns message
     * 
     * @throws OffAmazonPaymentsNotifications_InvalidMessageException if the
     *                                                                validation
     *                                                                fails
     * 
     * @return string signature string
     */
    private function _constructSignatureFromSnsMessage(Message $snsMessage)
    {
        if (strcmp($snsMessage->getMandatoryField("Type"), "Notification") != 0) {
            throw new OffAmazonPaymentsNotifications_InvalidMessageException(
                "Error with signature verification - unable to verify " .
                $snsMessage->getMandatoryField("Type") . " message"
            );
        }
        
        // get the list of fields that we are interested in
        $fields = array(
            "Timestamp" => true,
            "Message" => true,
            "MessageId" => true,
            "Subject" => false,
            "TopicArn" => true,
            "Type" => true
        );
        
        // sort the fields into byte order based on the key name(A-Za-z)
        ksort($fields);
        
        // extract the key value pairs and sort in byte order
        $signatureFields = array();
        foreach ($fields as $fieldName => $mandatoryField) {
            if ($mandatoryField) {
                $value = $snsMessage->getMandatoryField($fieldName);
            } else {
                $value = $snsMessage->getField($fieldName);
            }
            
            if (!is_null($value)) {
                array_push($signatureFields, $fieldName);
                array_push($signatureFields, $value);
            }
        }
        
        // create the signature string - key / value in byte order
        // delimited by newline character + ending with a new line character
        return implode("\n", $signatureFields) . "\n";
    }    
    
    /**
     * Ensures that the URL of the certificate is one belonging to AWS, and not
     * just something from the amazonaws domain, which could include S3 buckets.
     *
     * @param string $url Certificate URL
     *
     * @throws InvalidSnsMessageException if the cert url is invalid.
     */
    private function validateUrl($url)
    {
        $parsed = parse_url($url);
        if (empty($parsed['scheme'])
            || empty($parsed['host'])
            || $parsed['scheme'] !== 'https'
            || substr($url, -4) !== '.pem'
            || !preg_match(self::$defaultHostPattern, $parsed['host'])
            ) {
                throw new OffAmazonPaymentsNotifications_InvalidMessageException(
                    'The certificate is located on an invalid domain.'
                    );
            }
    }    
    
}
?>

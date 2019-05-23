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


/**
 *  @see OffAmazonPaymentsService_Model
 */
require_once 'OffAmazonPaymentsService/Model.php';  

    

/**
 * OffAmazonPaymentsService_Model_ConfirmOrderReferenceRequest
 * 
 * Properties:
 * <ul>
 * 
 * <li>AmazonOrderReferenceId: string</li>
 * <li>SellerId: string</li>
 *
 * </ul>
 */ 
class OffAmazonPaymentsService_Model_ConfirmOrderReferenceRequest extends OffAmazonPaymentsService_Model
{

    /**
     * Construct new OffAmazonPaymentsService_Model_ConfirmOrderReferenceRequest
     * 
     * @param mixed $data DOMElement or Associative Array to construct from. 
     * 
     * Valid properties:
     * <ul>
     * 
     * <li>AmazonOrderReferenceId: string</li>
     * <li>SellerId: string</li>
     *
     * </ul>
     */
    public function __construct($data = null)
    {
        $this->_fields = array (
        'AmazonOrderReferenceId' => array('FieldValue' => null, 'FieldType' => 'string'),
        'SellerId' => array('FieldValue' => null, 'FieldType' => 'string'),
        'SuccessUrl' => array('FieldValue' => null, 'FieldType' => 'string'),
        'FailureUrl' => array('FieldValue' => null, 'FieldType' => 'string'),
        'AuthorizationAmount.Amount' => array('FieldValue' => null, 'FieldType' => 'string'),
        'AuthorizationAmount.CurrencyCode' => array('FieldValue' => null, 'FieldType' => 'string'),
        );
        parent::__construct($data);
    }

        /**
     * Gets the value of the AmazonOrderReferenceId property.
     * 
     * @return string AmazonOrderReferenceId
     */
    public function getAmazonOrderReferenceId() 
    {
        return $this->_fields['AmazonOrderReferenceId']['FieldValue'];
    }

    /**
     * Sets the value of the AmazonOrderReferenceId property.
     * 
     * @param string AmazonOrderReferenceId
     * @return this instance
     */
    public function setAmazonOrderReferenceId($value) 
    {
        $this->_fields['AmazonOrderReferenceId']['FieldValue'] = $value;
        return $this;
    }

    /**
     * Sets the value of the AmazonOrderReferenceId and returns this instance
     * 
     * @param string $value AmazonOrderReferenceId
     * @return OffAmazonPaymentsService_Model_ConfirmOrderReferenceRequest instance
     */
    public function withAmazonOrderReferenceId($value)
    {
        $this->setAmazonOrderReferenceId($value);
        return $this;
    }


    /**
     * Checks if AmazonOrderReferenceId is set
     * 
     * @return bool true if AmazonOrderReferenceId  is set
     */
    public function isSetAmazonOrderReferenceId()
    {
        return !is_null($this->_fields['AmazonOrderReferenceId']['FieldValue']);
    }

    /**
     * Gets the value of the SellerId property.
     * 
     * @return string SellerId
     */
    public function getSellerId() 
    {
        return $this->_fields['SellerId']['FieldValue'];
    }

    /**
     * Sets the value of the SellerId property.
     * 
     * @param string SellerId
     * @return this instance
     */
    public function setSellerId($value) 
    {
        $this->_fields['SellerId']['FieldValue'] = $value;
        return $this;
    }

    /**
     * Sets the value of the SellerId and returns this instance
     * 
     * @param string $value SellerId
     * @return OffAmazonPaymentsService_Model_ConfirmOrderReferenceRequest instance
     */
    public function withSellerId($value)
    {
        $this->setSellerId($value);
        return $this;
    }


    /**
     * Checks if SellerId is set
     * 
     * @return bool true if SellerId  is set
     */
    public function isSetSellerId()
    {
        return !is_null($this->_fields['SellerId']['FieldValue']);
    }
    
    
    
    /**
     * Gets the value of the SuccessUrl property.
     *
     * @return string SuccessUrl
     */
    public function getSuccessUrl()
    {
        return $this->_fields['SuccessUrl']['FieldValue'];
    }
    
    /**
     * Sets the value of the SuccessUrl property.
     *
     * @param string SuccessUrl
     * @return this instance
     */
    public function setSuccessUrl($value)
    {
        $this->_fields['SuccessUrl']['FieldValue'] = $value;
        return $this;
    }
    
    /**
     * Sets the value of the SuccessUrl and returns this instance
     *
     * @param string $value SuccessUrl
     * @return OffAmazonPaymentsService_Model_ConfirmOrderReferenceRequest instance
     */
    public function withSuccessUrl($value)
    {
        $this->setSuccessUrl($value);
        return $this;
    }
    
    
    /**
     * Checks if SuccessUrl is set
     *
     * @return bool true if SuccessUrl  is set
     */
    public function isSetSuccessUrl()
    {
        return !is_null($this->_fields['SuccessUrl']['FieldValue']);
    }
    
    
    /**
     * Gets the value of the FailureUrl property.
     *
     * @return string FailureUrl
     */
    public function getFailureUrl()
    {
        return $this->_fields['FailureUrl']['FieldValue'];
    }
    
    /**
     * Sets the value of the FailureUrl property.
     *
     * @param string FailureUrl
     * @return this instance
     */
    public function setFailureUrl($value)
    {
        $this->_fields['FailureUrl']['FieldValue'] = $value;
        return $this;
    }
    
    /**
     * Sets the value of the FailureUrl and returns this instance
     *
     * @param string $value FailureUrl
     * @return OffAmazonPaymentsService_Model_ConfirmOrderReferenceRequest instance
     */
    public function withFailureUrl($value)
    {
        $this->setFailureUrl($value);
        return $this;
    }
    
    
    /**
     * Checks if FailureUrl is set
     *
     * @return bool true if FailureUrl  is set
     */
    public function isSetFailureUrl()
    {
        return !is_null($this->_fields['FailureUrl']['FieldValue']);
    }
    
    
    /**
     * Gets the value of the AuthorizationAmount.Amount property.
     *
     * @return string AuthorizationAmount.Amount
     */
    public function getAmount()
    {
        return $this->_fields['AuthorizationAmount.Amount']['FieldValue'];
    }
    
    /**
     * Sets the value of the AuthorizationAmount.Amount property.
     *
     * @param string AuthorizationAmount.Amount
     * @return this instance
     */
    public function setAmount($value)
    {
        $this->_fields['AuthorizationAmount.Amount']['FieldValue'] = $value;
        return $this;
    }
    
    /**
     * Sets the value of the AuthorizationAmount.Amount and returns this instance
     *
     * @param string $value AuthorizationAmount.Amount
     * @return OffAmazonPaymentsService_Model_ConfirmOrderReferenceRequest instance
     */
    public function withAmount($value)
    {
        $this->setAmount($value);
        return $this;
    }
    
    
    /**
     * Checks if AuthorizationAmount.Amount is set
     *
     * @return bool true if AuthorizationAmount.Amount  is set
     */
    public function isSetAmount()
    {
        return !is_null($this->_fields['AuthorizationAmount.Amount']['FieldValue']);
    }
    
    
    /**
     * Gets the value of the AuthorizationAmount.CurrencyCode property.
     *
     * @return string AuthorizationAmount.CurrencyCode
     */
    public function getCurrencyCode()
    {
        return $this->_fields['AuthorizationAmount.CurrencyCode']['FieldValue'];
    }
    
    /**
     * Sets the value of the AuthorizationAmount.CurrencyCode property.
     *
     * @param string AuthorizationAmount.CurrencyCode
     * @return this instance
     */
    public function setCurrencyCode($value)
    {
        $this->_fields['AuthorizationAmount.CurrencyCode']['FieldValue'] = $value;
        return $this;
    }
    
    /**
     * Sets the value of the AuthorizationAmount.CurrencyCode and returns this instance
     *
     * @param string $value AuthorizationAmount.CurrencyCode
     * @return OffAmazonPaymentsService_Model_ConfirmOrderReferenceRequest instance
     */
    public function withCurrencyCode($value)
    {
        $this->setCurrencyCode($value);
        return $this;
    }
    
    
    /**
     * Checks if AuthorizationAmount.CurrencyCode is set
     *
     * @return bool true if AuthorizationAmount.CurrencyCode  is set
     */
    public function isSetCurrencyCode()
    {
        return !is_null($this->_fields['AuthorizationAmount.CurrencyCode']['FieldValue']);
    }
    
}
?>
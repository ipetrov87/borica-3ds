<?php
/*
 * Copyright (c) 2021. Venelin Iliev.
 * https://veneliniliev.com
 */

namespace VenelinIliev\Borica3ds;

use VenelinIliev\Borica3ds\Enums\TransactionType;
use VenelinIliev\Borica3ds\Exceptions\ParameterValidationException;
use VenelinIliev\Borica3ds\Exceptions\SendingException;

class StatusCheckRequest extends Request implements RequestInterface
{

    /**
     * Original transaction type / TRAN_TRTYPE
     *
     * @var TransactionType
     */
    private $originalTransactionType;

    /**
     * @var array
     */
    private $sendResponse;

    /**
     * StatusCheckRequest constructor.
     */
    public function __construct()
    {
        $this->setTransactionType(TransactionType::TRANSACTION_STATUS_CHECK());
    }

    /**
     * Send data to borica
     *
     * @return StatusCheckResponse
     * @throws Exceptions\SignatureException|ParameterValidationException|SendingException
     */
    public function send()
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->getEnvironmentUrl());
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($this->getData()));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        if (curl_error($ch)) {
            throw new SendingException(curl_error($ch));
        }
        curl_close($ch);

        return (new StatusCheckResponse())
            ->setResponseData(json_decode($response, true))
            ->setPublicKey($this->getPublicKey());
    }

    /**
     * @return array
     * @throws Exceptions\SignatureException
     * @throws ParameterValidationException
     */
    public function getData()
    {
        return [
            'TERMINAL' => $this->getTerminalID(),
            'TRTYPE' => $this->getTransactionType()->getValue(),
            'ORDER' => $this->getOrder(),
            'TRAN_TRTYPE' => $this->getOriginalTransactionType()->getValue(),

            'NONCE' => $this->getNonce(),
            'P_SIGN' => $this->generateSignature(),
        ];
    }

    /**
     * @return TransactionType
     */
    public function getOriginalTransactionType()
    {
        return $this->originalTransactionType;
    }

    /**
     * Set original transaction type
     *
     * @param TransactionType $tranType Original transaction type.
     *
     * @return StatusCheckRequest
     */
    public function setOriginalTransactionType(TransactionType $tranType)
    {
        $this->originalTransactionType = $tranType;
        return $this;
    }

    /**
     * @return string
     * @throws Exceptions\SignatureException
     * @throws ParameterValidationException
     */
    public function generateSignature()
    {
        $this->validateRequiredParameters();
        return $this->getPrivateSignature([
            $this->getTerminalID(),
            $this->getTransactionType()->getValue(),
            $this->getOrder(),
            $this->getNonce()
        ]);
    }

    /**
     * @return void
     * @throws ParameterValidationException
     */
    public function validateRequiredParameters()
    {
        if (empty($this->getTransactionType())) {
            throw new ParameterValidationException('Transaction type is empty!');
        }

        if (empty($this->getOriginalTransactionType())) {
            throw new ParameterValidationException('Original transaction type is empty!');
        }

        if (empty($this->getOrder())) {
            throw new ParameterValidationException('Order is empty!');
        }

        if (empty($this->getPublicKey())) {
            throw new ParameterValidationException('Please set public key for validation response!');
        }

        if (empty($this->getTerminalID())) {
            throw new ParameterValidationException('TerminalID is empty!');
        }
    }

    /**
     * @return mixed|void
     * @throws Exceptions\SignatureException
     * @throws ParameterValidationException
     */
    public function generateForm()
    {
        return $this->getData();
    }
}

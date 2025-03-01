<?php
/*
 * Copyright (c) 2023. Venelin Iliev.
 * https://veneliniliev.com
 */

namespace VenelinIliev\Borica3ds;

use VenelinIliev\Borica3ds\Enums\Language;
use VenelinIliev\Borica3ds\Enums\TransactionType;
use VenelinIliev\Borica3ds\Exceptions\ParameterValidationException;

/**
 * Borica request
 */
abstract class Request extends Base
{
    /**
     * @var mixed
     */
    private $signatureTimestamp;

    /**
     * @var string
     */
    private $backRefUrl;

    /**
     * @var string|float|integer
     */
    private $amount = null;

    /**
     * @var string
     */
    private $currency = 'BGN';

    /**
     * @var string
     */
    private $description;

    /**
     * @var TransactionType
     */
    private $transactionType;

    /**
     * @var mixed
     */
    private $order;

    /**
     * @var string
     */
    private $nonce;

    /**
     * @array
     */
    private $mInfo;

    /**
     * Transaction language.
     *
     * @var null|string
     */
    private $lang = null;

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set description
     *
     * @param  string $description Описание на поръчката.
     *
     * @return Request
     * @throws ParameterValidationException
     */
    public function setDescription($description)
    {
        if (mb_strlen($description) > 50) {
            throw new ParameterValidationException('Description must be max 50 digits');
        }
        $this->description = $description;
        return $this;
    }

    /**
     * Get back ref url
     *
     * @return string
     */
    public function getBackRefUrl()
    {
        return $this->backRefUrl;
    }

    /**
     * Set back ref url
     *
     * @param  string $backRefUrl URL на търговеца за изпращане на резултата от авторизацията.
     *
     * @return Request
     * @throws ParameterValidationException
     */
    public function setBackRefUrl($backRefUrl)
    {
        if (!filter_var($backRefUrl, FILTER_VALIDATE_URL)) {
            throw new ParameterValidationException('Backref url is not valid!');
        }

        $this->backRefUrl = $backRefUrl;
        return $this;
    }

    /**
     * Get order
     *
     * @return mixed
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Set order
     *
     * @param  string|integer $order Номер на поръчката за търговеца, 6 цифри, който трябва да бъде уникален за деня.
     *
     * @return Request
     * @throws ParameterValidationException
     */
    public function setOrder($order)
    {
        if (mb_strlen($order) > 6) {
            throw new ParameterValidationException('Order must be max 6 digits');
        }

        $this->order = str_pad($order, 6, "0", STR_PAD_LEFT);
        return $this;
    }

    /**
     * Get transaction type
     *
     * @return TransactionType
     */
    public function getTransactionType()
    {
        return $this->transactionType;
    }

    /**
     * Set transaction type
     *
     * @param  TransactionType $transactionType Тип на транзакцията.
     *
     * @return Request
     */
    public function setTransactionType(TransactionType $transactionType)
    {
        $this->transactionType = $transactionType;
        return $this;
    }

    /**
     * Get amount
     *
     * @return float|null
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set amount
     *
     * @param  string|float|integer $amount Обща стойност на поръчката по стандарт ISO_4217 с десетичен разделител точка.
     *
     * @return Request
     */
    public function setAmount($amount)
    {
        $this->amount = number_format($amount, 2, '.', '');
        return $this;
    }

    /**
     * Get currency
     *
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Set currency
     *
     * @param  string $currency Валута на поръчката: три буквен код на валута по стандарт ISO 4217.
     *
     * @return Request
     * @throws ParameterValidationException
     */
    public function setCurrency($currency)
    {
        if (mb_strlen($currency) != 3) {
            throw new ParameterValidationException('3 character currency code');
        }
        $this->currency = mb_strtoupper($currency);
        return $this;
    }

    /**
     * Get signature timestamp
     *
     * @return string
     */
    public function getSignatureTimestamp()
    {
        if (empty($this->signatureTimestamp)) {
            $this->setSignatureTimestamp();
        }

        return $this->signatureTimestamp;
    }

    /**
     * Set signature timestamp
     *
     * @param  string|null $signatureTimestamp Дата на подпис/изпращане на данните.
     *
     * @return Request
     */
    public function setSignatureTimestamp($signatureTimestamp = null)
    {
        if (empty($signatureTimestamp)) {
            $this->signatureTimestamp = gmdate('YmdHis');
            return $this;
        }

        $this->signatureTimestamp = $signatureTimestamp;
        return $this;
    }

    /**
     * @return string
     */
    public function getNonce()
    {
        if (!empty($this->nonce)) {
            return $this->nonce;
        }
        $this->setNonce(strtoupper(bin2hex(openssl_random_pseudo_bytes(16))));
        return $this->nonce;
    }

    /**
     * @param  string $nonce Nonce.
     *
     * @return Request
     */
    public function setNonce($nonce)
    {
        $this->nonce = $nonce;
        return $this;
    }

    /**
     * @return string
     */
    public function getMInfo()
    {
        if (!empty($this->mInfo)) {
            return base64_encode(json_encode($this->mInfo));
        }
        return '';
    }

    /**
     * @param  array $mInfo
     *
     * @return Request
     * @throws ParameterValidationException
     */
    public function setMInfo($mInfo)
    {
        // Check for required fields (cardholderName and email or mobilePhone)
        if (!isset($mInfo['cardholderName']) ||
            (!isset($mInfo['email']) && !isset($mInfo['mobilePhone']))) {
            throw new ParameterValidationException('CardholderName and email or MobilePhone must be provided');
        }

        // Check the maximum length of cardholderName
        if (strlen($mInfo['cardholderName']) > 45) {
            throw new ParameterValidationException('CardHolderName must be at most 45 characters');
        }

        // Check for a valid email address format
        if (isset($mInfo['email']) && !filter_var($mInfo['email'], FILTER_VALIDATE_EMAIL)) {
            throw new ParameterValidationException('Email must be a valid email address');
        }

        // Check the structure for the mobile phone
        if (isset($mInfo['mobilePhone'])) {
            if (!isset($mInfo['mobilePhone']['cc']) || !isset($mInfo['mobilePhone']['subscriber'])) {
                throw new ParameterValidationException('MobilePhone must contain both cc and subscriber');
            }
        }

        $this->mInfo = $mInfo;
        return $this;
    }

    /**
     * Get the language of the transaction.
     *
     * @return string
     */
    public function getLang()
    {
        return $this->lang;
    }

    /**
     * Set the language of the transaction.
     *
     * The selected language of the form where the user will enter the payment info.
     * Supported languages are listed in VenelinIliev\Borica3ds\Enums\Language.
     *
     * @param  string|null $lang Two letter language code.
     * @return $this
     * @throws ParameterValidationException
     */
    public function setLang($lang)
    {
        if (empty($lang)) {
            $this->lang = null;
        } else {
            if (mb_strlen($lang) != 2) {
                throw new ParameterValidationException('2 character language code');
            }
            $lang = mb_strtoupper($lang);
            if (!Language::isValid($lang)) {
                throw new ParameterValidationException('Not a valid language code');
            }
            $this->lang = $lang;
        }
        return $this;
    }
}

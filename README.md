# PHP Borica EMV 3DS

## Requirements

- PHP >= 5.6 (including 8.2)
- ext-mbstring
- ext-openssl
- ext-curl
- ext-json

## Installation

| version | Supported signing schemas                       | Default signing schema |
|---------|-------------------------------------------------|------------------------|
| ^2.0    | MAC_EXTENDED <br/>MAC_ADVANCED <br/>MAC_GENERAL | **MAC_GENERAL**       
| ^1.0    | MAC_EXTENDED <br/>MAC_ADVANCED                     | **MAC_ADVANCED**       

```shell script
composer require veneliniliev/borica-3ds
```

For more methods, read [api documentation](API.md).

## Certificates

### Generate private key

```shell script
# Production key
openssl genrsa -out production.key -aes256 2048
# Development key
openssl genrsa -out development.key -aes256 2048
```

### Generate CSR

**IMPORTANT**: in `Organizational Unit Name (eg, section)` enter your terminal ID and
in `Common Name (eg, fully qualified host name)` enter your domain name.

```shell script
# Production csr
openssl req -new -key production.key -out VNNNNNNN_YYYYMMDD_P.csr
# Development csr
openssl req -new -key development.key -out VNNNNNNN_YYYYMMDD_D.csr
```

Имената на файловете се създават по следната конвенция: **VNNNNNNN_YYYYMMDD_T**, където:

- **VNNNNNNN** – TID на терминала, предоставен от Финансовата Институция
- **YYYYMMDD** – дата на заявка
- **T** – тип на искания сертификат, значения – **D** – за development среда, **Р** – за продукционна среда

## Usage

**IMPORTANT**: Switch signing schema MAC_EXTENDED / MAC_ADVANCED / MAC_GENERAL with methods:

````php
$saleRequest->setSigningSchemaMacGeneral(); // use MAC_GENERAL
$saleRequest->setSigningSchemaMacExtended(); // use MAC_EXTENDED
$saleRequest->setSigningSchemaMacAdvanced(); // use MAC_ADVANCED
````

Default signing schema is **MAC_GENERAL**!

### Sale request

````php
use VenelinIliev\Borica3ds\SaleRequest;
// ...
$saleRequest = (new SaleRequest())
    ->setAmount(123.32)
    ->setOrder(123456)
    ->setDescription('test')
    ->setMerchantUrl('https://test.com') // optional
    ->setBackRefUrl('https://test.com/back-ref-url') // optional / required for development
    ->setTerminalID('<TID - V*******>')
    ->setMerchantId('<MID - 15 chars>')
    ->setPrivateKey('\<path to certificate.key>', '<password / or use method from bottom>')
    //->setSigningSchemaMacGeneral(); // use MAC_GENERAL
    //->setSigningSchemaMacExtended(); // use MAC_EXTENDED
    //->setSigningSchemaMacAdvanced(); // use MAC_ADVANCED
    ->setPrivateKeyPassword('test');

$formHtml = $saleRequest->generateForm(); // only generate hidden html form with filled inputs 
// OR
$saleRequest->send(); // generate and send form with js 
````

### Sale response

Catch response from borica on `BACKREF` url (*$saleRequest->setBackRefUrl('\<url>')*)

```php
use VenelinIliev\Borica3ds\SaleResponse;
// ....
$isSuccessfulPayment = (new SaleResponse())
            ->setPublicKey('<path to public certificate.cer>')
            ->setResponseData($_POST) //Set POST data from borica response
            //->setSigningSchemaMacGeneral(); // use MAC_GENERAL
            //->setSigningSchemaMacExtended(); // use MAC_EXTENDED
            //->setSigningSchemaMacAdvanced(); // use MAC_ADVANCED
            ->isSuccessful();
```

#### Get response code

```php
use VenelinIliev\Borica3ds\SaleResponse;
// ...
$saleResponse= (new SaleResponse())
               ->setPublicKey('<path to public certificate.cer>')
               //->setSigningSchemaMacGeneral(); // use MAC_GENERAL
               //->setSigningSchemaMacExtended(); // use MAC_EXTENDED
               //->setSigningSchemaMacAdvanced(); // use MAC_ADVANCED

// ...
// automatic fill data from $_POST or can be set by ->setResponseData(<array>)
// ...

$saleResponse->getResponseCode(); // return RC from response
$saleResponse->getVerifiedData('<key from post request ex: RC>'); // return verified data from post by key
$saleResponse->isSuccessful(); // RC === 00 and data is verified
```

Response codes table

| Response Code (RC) | RC DESCRIPTION                  |    
|--------------------|---------------------------------|   
| 00                 | Sucessfull                      |
|                    | => Timeout                      |
| "01"               | Refer to card issuer            |
| "04"               | Pick Up                         |
| "05"               | Do not Honour                   |
| "13"               | Invalid amount                  |
| "30"               | Format error                    |
| "65"               | Soft Decline                    |
| "91"               | Issuer or switch is inoperative |
| "96"               | System Malfunction              |   

### Transaction status check

```php
 use VenelinIliev\Borica3ds\Enums\TransactionType;
 use VenelinIliev\Borica3ds\StatusCheckRequest;
 // ...
 $statusCheckRequest = (new StatusCheckRequest())
    //->inDevelopment()
    ->setPrivateKey('\<path to certificate.key>', '<password / or use method from bottom>')
    ->setPublicKey('<path to public certificate.cer>')
    ->setTerminalID('<TID - V*******>')
    ->setOrder('<order>')
    ->setOriginalTransactionType(TransactionType::SALE()) // transaction type
    //->setSigningSchemaMacGeneral(); // use MAC_GENERAL
    //->setSigningSchemaMacExtended(); // use MAC_EXTENDED
    //->setSigningSchemaMacAdvanced(); // use MAC_ADVANCED

        
//send to borica
$statusCheckResponse = $statusCheckRequest->send();
 
// get data from borica response
$verifiedResponseData = $statusCheckResponse->getResponseData();

// get field from borica response
$statusCheckResponse->getVerifiedData('<field from response. ex: ACTION');

```

### Reversal request

```php
 use VenelinIliev\Borica3ds\ReversalRequest;
 // ...
 $reversalRequest = (new ReversalRequest())
        //->inDevelopment()
        ->setPrivateKey('\<path to certificate.key>', '<password / or use method from bottom>')
        ->setPublicKey('<path to public certificate.cer>')
        ->setTerminalID('<TID - V*******>')
        ->setAmount(123.32)
        ->setOrder(123456)
        ->setDescription('test reversal')
        ->setMerchantId('<MID - 15 chars>')
        ->setRrn('<RRN - Original transaction reference (From the sale response data)>')
        ->setIntRef('<INT_REF - Internal reference (From the sale response data)>')
        //->setSigningSchemaMacGeneral(); // use MAC_GENERAL
        //->setSigningSchemaMacExtended(); // use MAC_EXTENDED
        //->setSigningSchemaMacAdvanced(); // use MAC_ADVANCED
        
//send reversal request to borica
$reversalRequestResponse = $reversalRequest->send();

// get data from borica reversal response
$verifiedResponseData = $reversalRequestResponse->getResponseData();

// get field from borica reversal response
$reversalRequestResponse->getVerifiedData('STATUSMSG');
```

### Methods

#### Set environments

Default environment is **production**!

```php
$saleRequest->setEnvironment(true); // set to production
$saleRequest->setEnvironment(false); // set to development
$saleRequest->inDevelopment(); // set to development
$saleRequest->inProduction(); // set to production

$saleRequest->isProduction(); // check is production environment?
$saleRequest->isDevelopment(); // check is development environment?
```

### Credit cards for testing

#### Cards

| Тип на карта | Номер на карта (PAN) | Реакция на APGW / Reponse code                                                          | Response Code Описание          | Изисква тестов ACS    |
|--------------|----------------------|-----------------------------------------------------------------------------------------|---------------------------------|-----------------------|
| Mastecard    | 5100770000000022     | Response code = 00                                                                      | Successfully completed          | Не                    |
| Mastecard    | 5555000000070019     | Response code = 04                                                                      | Pick Up                         | Не                    |
| Mastecard    | 5555000000070027     | Системата се забавя 30 сек. за авторизация, Response code = 13                          | Invalid amount                  | Не                    |
| Mastecard    | 5555000000070035     | Timeout, Response code = 91                                                             | Issuer or switch is inoperative | Не                    |
| Visa         | 4341792000000044     | Response code = 00 Това е пълен тест с автентификация от тестов Visa ACS и авторизация. | Successfully Completed          | Да, паролата е 111111 |

#### Карти, за които се получава съответен резултат при транзакция според сумата

| Тип на карта | Номер на карта (PAN) | Реакция на APGW / RC                     | Изисква тестов ACS    |     |
|--------------|----------------------|------------------------------------------|-----------------------|-----|
| Visa         | 4010119999999897     | Зависи от сумата. Виж таблицата по-долу. | Не                    |     |
| Mastecard    | 5100789999999895     |                                          | Да, паролата е 111111 |     |

| Сума от | Сума до | Реакция на APGW / Reponse code | RC Описание                     | Коментар              |
|---------|---------|--------------------------------|---------------------------------|-----------------------|
| 1.00    | 1.99    | 01                             | Refer to card issuer            |                       |
| 2.00    | 2.99    | 04                             | Pick Up                         |                       |
| 3.00    | 3.99    | 05                             | Do not Honour                   |                       |
| 4.00    | 4.99    | 13                             | Invalid amount                  | Response after 30 sec |
| 5.00    | 5.99    | 30                             | Format error                    |                       |
| 6.00    | 6.99    | 91                             | Issuer or switch is inoperative |                       |
| 7.00    | 7.99    | 96                             | System Malfunction              |                       |
| 8.00    | 8.99    |                                | Timeout                         |                       |
| 30.00   | 40.00   | 01                             | Refer to card issuer            |                       |
| 50.00   | 70.00   | 04                             | Pick Up                         |                       |
| 80.00   | 90.00   | 05                             | Do not Honour                   |                       |
| 100.00  | 110.00  | 13                             | Invalid amount                  | Response after 30 sec |
| 120.00  | 130.00  | 30                             | Format error                    |                       |
| 140.00  | 150.00  | 91                             | Issuer or switch is inoperative |                       |
| 160.00  | 170.00  | 96                             | System Malfunction              |                       |
| 180.00  | 190.00  |                                | Timeout                         |                       |

## Todo

- laravel integration
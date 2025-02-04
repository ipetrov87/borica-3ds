<?php
/*
 * Copyright (c) 2023. Venelin Iliev.
 * https://veneliniliev.com
 */

namespace VenelinIliev\Borica3ds\Enums;

use MyCLabs\Enum\Enum;

/**
 * Class TransactionType
 * @package VenelinIliev\Borica3ds\Enums
 * @method static SALE()
 * @method static TRANSACTION_STATUS_CHECK()
 * @method static REVERSAL()
 * @method static REVERSAL_REQUEST()
 * @method static PRE_AUTHORISATION()
 */
class TransactionType extends Enum
{
    const SALE = 1;

    //TODO Update with correct version
    /**
     * @deprecated 2.1.1 Name in sync with the Borica documentation
     * @see PRE_AUTHORISATION
     */
    const DEFERRED_AUTHORIZATION = 12;
    const PRE_AUTHORISATION = 12;
    const COMPLETION_DEFERRED_AUTHORIZATION = 21;
    const REVERSAL_REQUEST = 22;
    const TRANSACTION_STATUS_CHECK = 90;
    const REVERSAL = 24;
}

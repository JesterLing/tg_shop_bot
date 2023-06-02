<?php

namespace AdminPanel\Module;

abstract class PaymentStatus
{
    const PENDING = 1;
    const PAID = 2;
    const PROCESSING = 3;
    const EXPIRED = 4;
    const REJECTED = 5;
    const CANCELED = 6;
    const ERROR = 7;
    const UNKNOWN = 8;
}

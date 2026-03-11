<?php

namespace App\Notification\Domain\ValueObject;

enum NotificationType: string
{
    case TransactionCreated = 'transaction_created';
    case TransactionCompleted = 'transaction_completed';
    case TransactionFailed = 'transaction_failed';
}

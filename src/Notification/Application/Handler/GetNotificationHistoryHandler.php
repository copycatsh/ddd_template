<?php

declare(strict_types=1);

namespace App\Notification\Application\Handler;

use App\Notification\Application\Query\GetNotificationHistoryQuery;
use App\Notification\Application\Query\Response\NotificationHistoryItem;
use App\Notification\Application\Query\Response\NotificationHistoryResponse;
use App\Notification\Domain\Port\NotificationHistoryQuery;

class GetNotificationHistoryHandler
{
    public function __construct(
        private NotificationHistoryQuery $notificationHistoryQuery,
    ) {
    }

    public function handle(GetNotificationHistoryQuery $query): NotificationHistoryResponse
    {
        $data = $this->notificationHistoryQuery->getByUserId(
            $query->getUserId(),
            $query->getPage(),
            $query->getPerPage(),
        );

        $total = $this->notificationHistoryQuery->countByUserId($query->getUserId());

        $items = array_map(
            fn ($entry) => new NotificationHistoryItem(
                $entry->id,
                $entry->transactionId,
                $entry->accountId,
                $entry->userId,
                $entry->recipientEmail,
                $entry->notificationType,
                $entry->sentAt->format('Y-m-d H:i:s'),
            ),
            $data
        );

        return new NotificationHistoryResponse(
            $query->getUserId(),
            $items,
            $total,
            $query->getPage(),
            $query->getPerPage(),
        );
    }
}

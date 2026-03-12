# Task 7 — Final Verification: cs-fix + PHPStan + Full Test Suite

**Agent:** `symfony-ddd-developer`
**Parallel with:** none — depends on all previous tasks (Tasks 1-6)

## Instructions

Run the full quality pipeline and fix any issues.

### Steps

1. Code style fix:
```bash
make cs-fix
```

2. Static analysis (level 6):
```bash
make phpstan
```

3. Unit tests for the new handler:
```bash
docker compose exec php vendor/bin/phpunit tests/Unit/Notification/Application/Handler/GetNotificationHistoryHandlerTest.php --testdox
```

4. Full test suite:
```bash
make test
```

### Fix any failures

If PHPStan reports errors in the new files, fix them. Common issues:
- Missing return type declarations
- Array shape docblocks for `@return` annotations
- Parameter type mismatches between port and adapter

If tests fail, review the handler implementation against the test expectations from Task 3.

### Files created in this feature (checklist)

- [ ] `src/Notification/Domain/Port/NotificationHistoryData.php`
- [ ] `src/Notification/Domain/Port/NotificationHistoryQuery.php`
- [ ] `src/Notification/Application/Query/GetNotificationHistoryQuery.php`
- [ ] `src/Notification/Application/Query/Response/NotificationHistoryItem.php`
- [ ] `src/Notification/Application/Query/Response/NotificationHistoryResponse.php`
- [ ] `src/Notification/Application/Handler/GetNotificationHistoryHandler.php`
- [ ] `src/Notification/Infrastructure/Query/DoctrineNotificationHistoryQuery.php`
- [ ] `src/Notification/Infrastructure/ApiPlatform/StateProvider/NotificationHistoryStateProvider.php`
- [ ] `src/Notification/Infrastructure/ApiPlatform/Dto/NotificationHistoryDto.php`
- [ ] `tests/Unit/Notification/Application/Handler/GetNotificationHistoryHandlerTest.php`
- [ ] `config/services.yaml` (updated with port alias)

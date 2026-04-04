# API Testing Guide

Quick guide for testing the Fintech DDD API.

## Public Endpoints (No Authentication)

### Home / Welcome
```bash
curl http://localhost:8028/
```

Response:
```json
{
  "message": "Welcome to Fintech DDD API",
  "documentation": "/api/docs",
  "health": "/health",
  "api": "/api"
}
```

### Health Check
```bash
curl http://localhost:8028/health
```

Response:
```json
{
  "status": "ok",
  "service": "Fintech DDD API",
  "version": "1.0.0",
  "timestamp": "2025-11-14T12:00:00+00:00"
}
```

### API Documentation (Swagger UI)
Open in browser:
```bash
open http://localhost:8028/api/docs
```

Or get OpenAPI schema:
```bash
# JSON format
curl http://localhost:8028/api/docs.json

# JSON-LD format
curl http://localhost:8028/api/docs.jsonld
```

## Protected Endpoints (JWT Required)

### Step 1: Create a User

First, you need to access the PHP container and create a user:

```bash
# Enter PHP container
make bash

# Inside container, create user via console command
bin/console app:create-user test@example.com password123

exit
```

### Step 2: Get JWT Token

```bash
curl -X POST http://localhost:8028/api/login_check \
  -H "Content-Type: application/json" \
  -d '{
    "username": "test@example.com",
    "password": "password123"
  }'
```

Response:
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

Save the token to a variable:
```bash
export TOKEN="eyJ0eXAiOiJKV1QiLCJhbGc..."
```

### Step 3: Access Protected Endpoints

#### Create Account
```bash
curl -X POST http://localhost:8028/api/accounts \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "userId": "user-123",
    "currency": "USD"
  }'
```

#### Get Account Balance
```bash
curl http://localhost:8028/api/accounts/{account-id} \
  -H "Authorization: Bearer $TOKEN"
```

#### Deposit Money
```bash
curl -X PUT http://localhost:8028/api/accounts/{account-id}/deposit \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": "100.00",
    "currency": "USD"
  }'
```

#### Withdraw Money
```bash
curl -X PUT http://localhost:8028/api/accounts/{account-id}/withdraw \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": "50.00",
    "currency": "USD"
  }'
```

#### Get User Accounts
```bash
curl http://localhost:8028/api/users/{user-id}/accounts \
  -H "Authorization: Bearer $TOKEN"
```

## Inspecting the Event Store

### View Event Store

```bash
# Access database
make mysql

# View all events
SELECT * FROM event_store ORDER BY id DESC LIMIT 10;

# View events for specific aggregate
SELECT * FROM event_store WHERE aggregate_id = 'your-account-id';

# View event types
SELECT event_type, COUNT(*) FROM event_store GROUP BY event_type;
```

## Common Issues

### 401 Unauthorized
- Make sure you're using the JWT token in Authorization header
- Token format: `Authorization: Bearer YOUR_TOKEN_HERE`
- Check token hasn't expired (default: 1 hour)

### 404 Not Found on /api
- `/api` endpoint requires authentication
- Use `/api/docs` for documentation (public)
- Use specific resources like `/api/accounts` (with JWT)

### JWT Token not found
- Endpoint requires authentication but no token provided
- Check security.yaml for public access rules

### Currency mismatch
- Make sure deposit/withdrawal currency matches account currency
- Each account supports only one currency

### Insufficient funds
- Check account balance before withdrawal
- Use `/api/accounts/{id}` to see current balance

## Useful Make Commands

```bash
make help          # List all available commands
make ps            # Show container status
make logs          # View logs (add SERVICE=php for specific service)
make bash          # Enter PHP container
make mysql         # Enter MySQL CLI
make cache-clear   # Clear Symfony cache
```

## Example: Complete Flow

```bash
# 1. Create user
make bash
bin/console app:create-user john@example.com secret123
exit

# 2. Get JWT token
TOKEN=$(curl -s -X POST http://localhost:8028/api/login_check \
  -H "Content-Type: application/json" \
  -d '{"username":"john@example.com","password":"secret123"}' \
  | jq -r '.token')

# 3. Create account
ACCOUNT=$(curl -s -X POST http://localhost:8028/api/accounts \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"userId":"user-001","currency":"USD"}' \
  | jq -r '.id')

# 4. Deposit money
curl -X PUT http://localhost:8028/api/accounts/$ACCOUNT/deposit \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"amount":"500.00","currency":"USD"}'

# 5. Check balance
curl http://localhost:8028/api/accounts/$ACCOUNT \
  -H "Authorization: Bearer $TOKEN"

# 6. Withdraw money
curl -X PUT http://localhost:8028/api/accounts/$ACCOUNT/withdraw \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"amount":"200.00","currency":"USD"}'
```

## Next Steps

- Explore Swagger UI at http://localhost:8028/api/docs
- Check Event Store in database to see Event Sourcing in action
- Try different currencies (UAH, USD)
- Test validation errors (negative amounts, currency mismatch, etc.)

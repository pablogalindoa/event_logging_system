# Event Logging System

A full-stack event logging application inspired by platforms such as Sentry.

The system allows applications to submit structured events through an API and provides a responsive dashboard where users can inspect, filter, paginate, and monitor events in near real time.

## Live Demo

Frontend:

https://d12aj4vsoo4714.cloudfront.net

API:

https://nhsgmp7toauui6mkmcdc6d5dxa0avwya.lambda-url.us-east-2.on.aws

## Features

### Backend

- `POST /events` event ingestion
- `GET /events` event listing
- Filtering by level and date range
- Pagination
- Structured JSON validation errors
- Request IDs for traceability
- UTC timestamps with millisecond precision
- JSONB event context
- PostgreSQL persistence
- CORS configuration
- Database constraints and indexes

### Frontend

- React + TypeScript dashboard
- Event level filtering
- Date filtering
- Pagination
- URL-driven filter state
- Automatic refresh every 5 seconds
- Loading, empty, and error states
- Responsive desktop and mobile layout

## Tech Stack

### Backend

- PHP 8.3
- Laravel 12
- PostgreSQL 16
- Pest / PHPUnit
- Bref

### Frontend

- React
- TypeScript
- Vite
- Tailwind CSS
- TanStack Query
- Vitest
- React Testing Library

### AWS

- AWS Lambda
- Amazon RDS PostgreSQL
- Amazon S3
- Amazon CloudFront
- CloudWatch Logs

## Architecture

```text
                         ┌────────────────────┐
                         │      Browser       │
                         └─────────┬──────────┘
                                   │
                    ┌──────────────┴──────────────┐
                    │                             │
                    ▼                             ▼
             CloudFront                    Lambda Function URL
                    │                             │
                    ▼                             ▼
              Private S3                  Laravel 12 / Bref
                                                  │
                                                  ▼
                                             Private VPC
                                                  │
                                                  ▼
                                          RDS PostgreSQL
```

The frontend is served through CloudFront from a private S3 bucket.

The API runs on AWS Lambda using Bref PHP 8.3 and connects privately to PostgreSQL running on Amazon RDS.

## Event Model

Each event contains:

| Field         | Description                                        |
| ------------- | -------------------------------------------------- |
| `id`          | Unique event identifier                            |
| `level`       | `debug`, `info`, `warning`, `error`, or `critical` |
| `message`     | Event message                                      |
| `source`      | Optional event source                              |
| `context`     | Optional structured JSON metadata                  |
| `occurred_at` | Timestamp supplied by the event producer           |
| `created_at`  | Timestamp when the server persisted the event      |

Events are treated as immutable records and therefore do not contain `updated_at`.

## API

### POST `/events`

Creates a new event.

Example request:

```json
{
  "level": "error",
  "message": "Payment provider unavailable",
  "source": "checkout-service",
  "context": {
    "order_id": 12345
  },
  "occurred_at": "2026-08-28T06:52:00.123Z"
}
```

Successful response:

```text
201 Created
```

### GET `/events`

Returns events ordered by newest occurrence first.

Supported query parameters:

- `level`
- `from`
- `to`
- `page`
- `per_page`

Example:

```text
GET /events?level=critical&page=1&per_page=25
```

The backend accepts `per_page` values from 1 to 100.

The frontend intentionally exposes only:

- 25
- 50
- 100

This keeps the UI simple while keeping the API flexible.

## Validation

The API validates incoming events before persistence.

Validation includes:

- valid event level
- required message
- maximum message length
- optional source length
- valid structured JSON context
- valid ISO 8601 timestamps
- valid pagination values
- valid date ranges

Invalid requests return structured JSON errors.

## Error Handling

Errors use a consistent response structure and include a request ID for traceability.

The same request ID is also returned through response headers where applicable.

This makes production errors easier to correlate with application logs.

## Local Development

### Requirements

- PHP 8.3+
- Composer
- Node.js
- npm
- Docker / Docker Compose
- PostgreSQL

### Backend Setup

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
```

Configure the PostgreSQL connection in `.env`.

Then run:

```bash
php artisan migrate
php artisan serve
```

The API will normally be available at:

```text
http://localhost:8000
```

### Frontend Setup

```bash
cd frontend
npm install
cp .env.example .env
npm run dev
```

Configure:

```text
VITE_API_BASE_URL=http://localhost:8000
```

The frontend will normally be available at:

```text
http://localhost:5173
```

## Testing

### Backend

Run the standard backend test suite:

```bash
cd backend
php artisan test
```

A PostgreSQL-specific integration suite is also included to validate behavior that SQLite cannot fully reproduce.

The PostgreSQL test suite verifies areas such as:

- JSONB behavior
- database constraints
- `TIMESTAMPTZ`
- timestamp precision
- indexes
- deterministic ordering
- filtering
- pagination

Measured backend test coverage during development was approximately 93%.

### Frontend

Run:

```bash
cd frontend
npm test
npm run typecheck
npm run lint
npm run build
```

Frontend tests cover important dashboard interactions, including filtering and pagination behavior.

## Production Deployment

The application is deployed entirely on AWS.

### Backend

The backend runs on:

- AWS Lambda
- Bref PHP 8.3
- Laravel 12

The Lambda connects privately to Amazon RDS PostgreSQL through a VPC.

### Database

The production database uses:

- Amazon RDS PostgreSQL 16
- private access
- Single-AZ deployment
- encrypted storage
- security-group-based access

### Frontend

The production frontend is hosted using:

- private Amazon S3 bucket
- CloudFront
- Origin Access Control
- HTTPS

The S3 bucket is not publicly accessible.

### Observability

Laravel writes production logs to stderr.

AWS Lambda forwards the logs to CloudWatch Logs.

CloudWatch log retention is configured to 7 days.

For more detail, see:

[DEPLOYMENT.md](./DEPLOYMENT.md)

## Production Verification

The deployed environment was tested end-to-end.

### API

- `GET /events` → HTTP 200
- `POST /events` → HTTP 201
- CORS preflight → HTTP 204
- PostgreSQL persistence verified
- JSONB context verified
- UTC millisecond timestamps verified
- filtering verified
- pagination verified
- request ID headers verified

### Frontend

- CloudFront HTTPS delivery verified
- private S3 access verified
- persisted production events rendered
- level filtering verified
- URL-driven filters verified
- pagination behavior verified
- 5-second polling verified
- desktop rendering verified
- mobile rendering verified
- true 390px viewport verified
- no overflowing mobile elements after responsive fixes

## Security

The production deployment includes several security controls:

- private RDS database
- private S3 bucket
- CloudFront Origin Access Control
- dedicated Lambda execution role
- dedicated Lambda security group
- dedicated RDS security group
- PostgreSQL traffic restricted to Lambda
- HTTPS frontend
- HTTPS API
- `APP_DEBUG=false`
- no credentials committed to Git
- temporary secret files removed after deployment
- CloudWatch log retention limited to 7 days

The deployment intentionally does not use:

- public RDS access
- public S3 access
- NAT Gateway
- RDS Proxy
- Application Load Balancer
- Route 53
- WAF
- additional Lambda functions

## AWS IAM

The Lambda function uses a dedicated execution role instead of a broad administrative role.

The execution role uses:

- `AWSLambdaBasicExecutionRole`
- `AWSLambdaVPCAccessExecutionRole`

This provides only the permissions required for logging and VPC networking.

The application itself does not require AWS administrative permissions to access PostgreSQL.

## CORS

CORS is configured through environment variables.

Local development can allow:

```text
http://localhost:5173
```

Production allows only the deployed CloudFront origin:

```text
https://d12aj4vsoo4714.cloudfront.net
```

## Real-Time Behavior

The dashboard refreshes automatically every 5 seconds.

Polling was intentionally chosen over WebSockets because it is simpler, reliable, and sufficient for the scope of this assessment.

A larger production system could replace polling with WebSockets or Server-Sent Events.

## Database Design

The event table uses PostgreSQL-specific features where appropriate.

Key decisions include:

- `JSONB` for flexible event context
- `TIMESTAMPTZ` for timezone-aware timestamps
- database constraints for event level and data integrity
- indexes supporting timestamp and level filtering
- deterministic ordering using `occurred_at` and `id`

The main indexes support:

```text
occurred_at DESC, id DESC
```

and:

```text
level, occurred_at DESC, id DESC
```

## Repository Structure

```text
event_logging_system/
├── backend/
│   ├── app/
│   ├── config/
│   ├── database/
│   ├── routes/
│   └── tests/
│
├── frontend/
│   ├── src/
│   │   ├── api/
│   │   └── features/
│   └── test/
│
├── README.md
├── DECISIONS.md
└── DEPLOYMENT.md
```

## Technical Decisions

The reasoning behind the main architectural decisions is documented in:

[DECISIONS.md](./DECISIONS.md)

Topics include:

- Laravel 12
- PostgreSQL and JSONB
- immutable events
- `occurred_at` vs `created_at`
- page-based pagination
- polling vs WebSockets
- TanStack Query
- URL-driven filters
- Lambda + Bref
- private RDS networking
- IAM least privilege
- private S3 + CloudFront
- testing strategy

## AI Usage

AI-assisted development tools were used during the assessment to accelerate:

- implementation planning
- scaffolding
- code review
- debugging
- AWS deployment preparation
- deployment verification
- documentation

All generated code and infrastructure changes were reviewed before being applied.

The final implementation was validated through:

- backend unit and integration tests
- frontend tests
- type checking
- linting
- production builds
- PostgreSQL integration testing
- API smoke tests
- browser-based production testing
- AWS resource audits

## Known Limitations

The implementation intentionally focuses on the scope of the assessment.

It currently does not include:

- authentication
- authorization
- multi-tenant organizations
- project separation
- WebSocket streaming
- advanced full-text search
- event aggregation dashboards
- configurable retention policies
- CI/CD automation
- infrastructure as code
- automated load testing
- advanced distributed tracing

These would be natural next steps for a larger production implementation.

## Future Improvements

Given more time, possible improvements would include:

- authentication and authorization
- cursor-based pagination for very large datasets
- WebSockets or Server-Sent Events
- charts and aggregation
- event retention policies
- rate limiting
- Sentry integration
- CloudWatch alarms
- AWS Secrets Manager or Parameter Store
- Terraform or AWS CDK
- CI/CD pipeline
- load and performance testing

## Assessment Requirements

| Requirement               | Implementation                     |
| ------------------------- | ---------------------------------- |
| Event ingestion API       | `POST /events`                     |
| Persistent database       | PostgreSQL                         |
| Event listing API         | `GET /events`                      |
| Filtering                 | Level and timestamps               |
| Pagination                | API pagination + frontend controls |
| TypeScript frontend       | React + TypeScript                 |
| Near real-time dashboard  | 5-second polling                   |
| Responsive UI             | Desktop and mobile verified        |
| AWS deployment            | Lambda + RDS + S3 + CloudFront     |
| Error handling            | Structured JSON error responses    |
| Observability             | stderr + CloudWatch Logs           |
| Unit tests                | Backend and frontend test suites   |
| PostgreSQL-specific tests | Dedicated integration suite        |
| Documentation             | README, DECISIONS, DEPLOYMENT      |

## Documentation

- [Technical Decisions](./DECISIONS.md)
- [AWS Deployment Guide](./DEPLOYMENT.md)

## Author

Pablo Galindo Altamirano

# Technical Decisions

This document explains the main technical and architectural decisions made for the Event Logging System.

## 1. Backend Framework: Laravel 12

Laravel 12 was selected because it provides a clear structure for routing, validation, database access, API resources, middleware, error handling, and testing.

The goal was to keep the application simple and easy to understand rather than introduce unnecessary abstractions.

The backend follows a straightforward flow:

Request
→ Middleware
→ Form Request validation
→ Controller
→ Eloquent
→ PostgreSQL
→ API Resource response

A repository layer or service layer was intentionally not added because the current business logic does not justify the additional complexity.

## 2. PostgreSQL

PostgreSQL was selected because it provides strong support for structured and semi-structured data.

The `context` field is stored as `JSONB`, which allows events to contain additional structured metadata without forcing a rigid schema.

PostgreSQL `TIMESTAMPTZ` is used for timestamps to preserve timezone-aware values and normalize them consistently.

The production database runs on AWS RDS PostgreSQL 16.

## 3. Event Data Model

Events are treated as immutable records.

The main fields are:

- `id`
- `level`
- `message`
- `source`
- `context`
- `occurred_at`
- `created_at`

There is intentionally no `updated_at` field or update endpoint.

An event represents something that happened at a specific moment, so modifying historical events would make the event log less trustworthy.

## 4. `occurred_at` vs `created_at`

Two timestamps are stored intentionally.

`occurred_at` represents when the event happened according to the client.

`created_at` represents when the server received and persisted the event.

This distinction is useful when events arrive late or are sent asynchronously.

All API timestamps are normalized to UTC and preserve millisecond precision.

## 5. Event Levels

The API accepts the following levels:

- debug
- info
- warning
- error
- critical

The backend uses an enum so the allowed values are defined in one place and invalid levels are rejected during validation.

Database constraints also protect the data independently of application validation.

## 6. Validation and API Errors

Laravel Form Requests are used for request validation.

The API rejects malformed or invalid input with a consistent JSON error structure.

Requests also receive a request ID that is returned in response headers and error responses.

This makes failures easier to trace during debugging and production monitoring.

## 7. Pagination

The API supports page-based pagination.

Supported backend parameters include:

- `page`
- `per_page`

The API allows values from 1 to 100.

The frontend intentionally exposes only:

- 25
- 50
- 100

This keeps the user interface simple while allowing the API to remain more flexible.

Cursor pagination would be a better option for a very large event stream, but page-based pagination is sufficient for the scope of this assessment.

## 8. Filtering

The event listing supports filtering by:

- level
- from timestamp
- to timestamp

Results are ordered by:

1. `occurred_at DESC`
2. `id DESC`

The ID provides deterministic ordering when two events have the same timestamp.

PostgreSQL indexes were added to support the expected access patterns.

## 9. Frontend State Management

TanStack Query is used for server state.

Filters and pagination are stored in URL query parameters instead of only component state.

Example:

`?level=critical&page=2&per_page=25`

This makes filtered views reloadable and shareable.

Local state management libraries such as Redux were intentionally avoided because the application does not require complex global client state.

## 10. Polling Instead of WebSockets

The frontend refreshes events every 5 seconds.

Polling was chosen because:

- it is simple
- it is reliable
- it satisfies the real-time requirement for this assessment
- it avoids introducing WebSocket infrastructure

For a larger production system with high event volume, WebSockets or Server-Sent Events could reduce unnecessary polling traffic.

## 11. AWS Lambda and Bref

The backend is deployed to AWS Lambda.

Because AWS Lambda does not provide a native managed PHP runtime, Bref is used to provide PHP 8.3 support.

The Laravel application runs through Bref PHP-FPM.

A Bref PostgreSQL extension layer provides the required `pdo_pgsql` extension.

The public API is exposed using a Lambda Function URL.

This avoided introducing API Gateway or a load balancer for a small assessment project.

## 12. Private RDS Networking

The production PostgreSQL database is not publicly accessible.

Lambda is attached to the same VPC as RDS.

Two dedicated security groups are used:

- `event-logging-lambda-sg`
- `event-logging-db-sg`

The Lambda security group has no inbound rules.

Its outbound access is restricted to TCP 5432 toward the RDS security group.

The RDS security group accepts PostgreSQL traffic only from the Lambda security group.

No NAT Gateway, public database access, RDS Proxy, or load balancer is required.

## 13. Least-Privilege IAM

The initial Lambda blueprint used a broad `PowerUserRole`.

During deployment this was identified as excessive and replaced with a dedicated execution role.

The final role only uses:

- `AWSLambdaBasicExecutionRole`
- `AWSLambdaVPCAccessExecutionRole`

The application itself does not need AWS administrative permissions to connect to PostgreSQL.

Database authentication is handled using PostgreSQL credentials and network security groups.

## 14. Frontend Hosting

The frontend is deployed as static assets to a private Amazon S3 bucket.

The bucket is not publicly accessible.

CloudFront serves the application over HTTPS using Origin Access Control (OAC).

Only the specific CloudFront distribution is allowed to read objects from the bucket.

The application uses CloudFront's default HTTPS domain, avoiding unnecessary Route 53 or custom certificate configuration.

## 15. SPA Fallback

CloudFront maps S3 403 and 404 responses to `/index.html`.

This allows client-side React routes to work correctly when a user opens a URL directly.

## 16. CORS

The production backend accepts requests only from the deployed CloudFront frontend origin.

The CORS origin is configured through an environment variable rather than hard-coded application logic.

This allows local and production environments to use different origins.

## 17. Logging and Observability

Laravel logs to stderr in production.

AWS Lambda captures the output in CloudWatch Logs.

The CloudWatch log group has a 7-day retention period.

Application errors are also returned using a structured API error format with request IDs.

A more advanced production system could integrate Sentry, metrics, alarms, or distributed tracing.

## 18. Testing Strategy

Backend tests cover:

- event ingestion
- validation
- filtering
- pagination
- timestamp behavior
- JSONB constraints
- CORS
- PostgreSQL-specific behavior

The fast development test suite can run with SQLite.

A separate PostgreSQL integration suite validates production-specific behavior such as JSONB, constraints, and `TIMESTAMPTZ`.

Frontend tests cover the primary dashboard interactions.

Type checking, linting, and production builds are also part of the verification process.

## 19. Responsive Design

The dashboard was verified at desktop and mobile sizes.

During production browser testing, a mobile layout issue was detected and corrected.

A true 390px viewport was then verified through Chrome DevTools device emulation with no overflowing elements.

## 20. Trade-offs and Future Improvements

Given more time, I would consider:

- authentication and authorization
- organization/project separation
- cursor-based pagination
- WebSockets or Server-Sent Events
- charts and event aggregation
- event retention policies
- rate limiting
- dead-letter handling
- infrastructure as code
- automated CI/CD
- CloudWatch alarms
- Sentry or distributed tracing
- automated load testing
- secret storage using AWS Secrets Manager or Parameter Store

The current implementation intentionally focuses on the requested assessment scope while maintaining production-oriented security and deployment practices.

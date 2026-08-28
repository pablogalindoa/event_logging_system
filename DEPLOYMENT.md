# Deployment Guide

This document describes the production AWS deployment of the Event Logging System.

## Architecture

The production architecture is:

Browser
→ CloudFront
→ Private S3 bucket

Browser
→ Lambda Function URL
→ Laravel 12 / Bref PHP 8.3
→ Private VPC connection
→ Amazon RDS PostgreSQL

CloudWatch Logs receives Lambda application logs.

## AWS Region

The deployment uses:

`us-east-2` — Ohio

## Production URLs

Frontend:

`https://d12aj4vsoo4714.cloudfront.net`

API:

`https://nhsgmp7toauui6mkmcdc6d5dxa0avwya.lambda-url.us-east-2.on.aws`

## Backend Deployment

### RDS

The database uses Amazon RDS PostgreSQL.

Configuration:

- PostgreSQL 16
- Single-AZ
- private access
- PostgreSQL port 5432
- encrypted storage
- no RDS Proxy
- no public database access

The application database is:

`event_logging`

Production credentials are not stored in the repository.

### Networking

Lambda and RDS use the same VPC.

Lambda is attached to two existing subnets in separate Availability Zones.

Dedicated security groups are used:

`event-logging-lambda-sg`

- inbound: none
- outbound: TCP 5432 to the RDS security group

`event-logging-db-sg`

- inbound: TCP 5432 from the Lambda security group

RDS remains private.

No NAT Gateway, load balancer, RDS Proxy, public database access, or additional VPC was created.

## Lambda

The existing function is:

`event-logging-api`

Runtime configuration:

- AWS custom runtime: `provided.al2023`
- PHP 8.3 through Bref
- Bref PHP-FPM runtime
- PostgreSQL PHP extension layer
- handler: `public/index.php`
- memory: 512 MB
- timeout: 30 seconds

The function is exposed using an AWS Lambda Function URL.

## IAM

Lambda uses a dedicated execution role:

`event-logging-api-execution-role`

Attached AWS managed policies:

- `AWSLambdaBasicExecutionRole`
- `AWSLambdaVPCAccessExecutionRole`

No broad application-level AWS administration permissions are required.

## Environment Variables

Production configuration is supplied through Lambda environment variables.

Required variables include:

```text
APP_NAME
APP_ENV
APP_KEY
APP_DEBUG
APP_URL
APP_TIMEZONE

LOG_CHANNEL
LOG_LEVEL
LOG_STDERR_FORMATTER

DB_CONNECTION
DB_HOST
DB_PORT
DB_DATABASE
DB_USERNAME
DB_PASSWORD
DB_SSLMODE

CACHE_STORE
SESSION_DRIVER
QUEUE_CONNECTION
FILESYSTEM_DISK

CORS_ALLOWED_ORIGINS
BREF_RUNTIME
```

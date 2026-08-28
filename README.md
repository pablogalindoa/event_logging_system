# AWS Deployment

## Architecture

CloudFront → private S3
Browser → Lambda Function URL
Lambda → private RDS PostgreSQL

## Region

us-east-2

## Backend

### RDS

- PostgreSQL 16
- private
- Single-AZ
- security group restricted to Lambda SG

### Lambda

- PHP 8.3 through Bref
- Bref PostgreSQL extension
- Function URL
- dedicated IAM execution role

### Environment variables

List the required variable names, NOT their production values.

## Database migrations

Explain migration through Bref console runtime.

## Frontend

### Build

VITE_API_BASE_URL=...

### S3

Private bucket.

### CloudFront

OAC + SPA fallback.

## CORS

Production CloudFront origin.

## Observability

CloudWatch / stderr
7-day retention.

## Verification

Commands or checks.

## Cleanup

Resources that can be removed after review.

# ShopFlow — Development Roadmap

ShopFlow will be developed over **12 weeks**.

The project is intentionally divided into weekly milestones so that every feature introduces a new backend engineering concept.

The goal is:

`
Learn Concept
     ↓
Design
     ↓
Implement
     ↓
Test
     ↓
Refactor
     ↓
Document
`

---

# Week 1 — Project Foundation & Database Architecture

## Backend Focus

* Project architecture
* Laravel application structure
* PostgreSQL
* Database normalization
* ERD
* Primary keys
* Foreign keys
* Constraints
* Indexes
* Migrations
* Seeders
* Factories

## Learn

Understand:

* What makes a good relational database design?
* What is normalization?
* When should a table be separated?
* What is a foreign key?
* What is an index?
* How do indexes affect reads and writes?
* Difference between `hasMany`, `belongsTo`, and `belongsToMany`
* Why monetary values must use `DECIMAL` (or integer minor units) instead of `float`

## Implement

Set up:

`
Laravel
PostgreSQL
Redis
Docker
Git
`

Create initial database schema:

`
users
products        (with soft deletes, correct price column type)
categories
product_categories
carts
cart_items
orders          (with order_number, human-readable and unique)
order_items
payments
inventories
coupons
coupon_usages
reviews
`

Create:

* migrations
* factories
* seeders
* initial models
* relationships

## Testing

Verify:

* migrations work
* database can be recreated
* factories generate valid data
* relationships work correctly

## Deliverables

* Initial Laravel project
* Docker environment
* PostgreSQL database
* ERD
* Initial migrations
* Seeders
* README setup instructions

---

# Week 2 — Authentication & Authorization

## Backend Focus

* Authentication
* Authorization
* Sanctum
* Password hashing
* Policies
* Gates
* Middleware

## Learn

Understand:

`
Authentication
=
Who are you?
`
`
Authorization
=
What are you allowed to do?
`

Study:

* Token authentication
* Password hashing
* Session/token lifecycle
* Middleware
* Policies
* Role-based authorization

## Implement

Endpoints:

`
POST /api/v1/auth/register
POST /api/v1/auth/login
POST /api/v1/auth/logout
GET  /api/v1/auth/me
`

Implement:

* registration
* login
* logout
* Sanctum authentication
* user roles
* admin authorization
* protected routes

## Testing

Test:

* successful registration
* duplicate email
* invalid password
* successful login
* invalid credentials
* unauthenticated access
* admin-only access

## Deliverables

Working authentication and authorization system.

---

# Week 3 — Product & Category API

## Backend Focus

* REST API
* Controllers
* Form Requests
* API Resources
* Validation
* Pagination
* Filtering
* Sorting
* Searching
* API versioning

## Learn

Understand:

* REST principles
* HTTP methods
* HTTP status codes
* Request validation
* Resource transformation
* Pagination
* Query builders
* Eloquent relationships
* Why APIs are versioned (`/api/v1`) and how that protects existing clients

## Implement

Products:

```text
GET    /api/v1/products
GET    /api/v1/products/{id}
POST   /api/v1/products
PUT    /api/v1/products/{id}
DELETE /api/v1/products/{id}
```

Categories:

```text
GET    /api/v1/categories
POST   /api/v1/categories
PUT    /api/v1/categories/{id}
DELETE /api/v1/categories/{id}
```

Add:

```text
?page=
?category=
?min_price=
?max_price=
?sort=
?search=
```

Add a `GET /api/health` endpoint that checks database and Redis connectivity.

## Testing

Test:

* validation
* pagination
* filtering
* sorting
* authorization
* product CRUD
* category CRUD
* soft-deleted products excluded from listings by default

## Deliverables

Complete Product and Category REST API.

---

# Week 4 — Shopping Cart & Business Logic

## Backend Focus

* Business logic
* Service Layer
* DTO concepts
* Eloquent relationships
* Data consistency

## Learn

Understand:

* Why controllers should stay thin
* Where business logic belongs
* Service classes
* DTOs
* Domain/business rules
* Separation of concerns

## Implement

Cart endpoints:

```text
GET    /api/v1/cart
POST   /api/v1/cart/items
PATCH  /api/v1/cart/items/{id}
DELETE /api/v1/cart/items/{id}
DELETE /api/v1/cart
```

Rules:

* User can only access their own cart
* Product must exist
* Quantity must be valid
* Quantity cannot exceed available stock
* Duplicate products should update quantity rather than create unnecessary duplicates

## Testing

Test:

* add item
* update quantity
* remove item
* clear cart
* unauthorized cart access
* invalid quantity
* unavailable product

## Deliverables

Complete cart system.

---

# Week 5 — Orders & Database Transactions

## Backend Focus

# Transactions and ACID

This is one of the most important weeks.

## Learn

Study:

```text
Atomicity
Consistency
Isolation
Durability
```

Understand:

* database transactions
* commit
* rollback
* transaction boundaries
* failure scenarios
* isolation levels
* why the checkout endpoint must be idempotent (introduce the concept now, implement fully in Week 10)

## Implement

Checkout:

```text
Cart
 ↓
Checkout
 ↓
Generate order_number
 ↓
Create Order
 ↓
Create Order Items
 ↓
Create Payment
 ↓
Update Inventory
 ↓
Clear Cart
```

Everything that must succeed together should happen inside an appropriate transaction.

Example concept:

```text
BEGIN

Create Order
Create Order Items
Update Inventory
Create Payment
Clear Cart

COMMIT
```

If something fails:

```text
ROLLBACK
```

## Testing

Test:

* successful checkout
* failed checkout
* rollback
* cart consistency
* order creation
* order item creation
* payment creation

## Deliverables

Complete order creation and checkout flow.

---

# Week 6 — Inventory & Concurrency

## Backend Focus

# Race Conditions

This week moves from basic Laravel development into real backend engineering.

## Learn

Study:

* race conditions
* concurrent requests
* row-level locks
* pessimistic locking
* optimistic locking
* `SELECT ... FOR UPDATE`
* transaction isolation
* deadlocks
* the trade-offs between pessimistic and optimistic locking (contention level, retry handling, complexity) — a common interview question

## Problem

Imagine:

```text
Stock = 1

User A → Checkout
User B → Checkout
```

Both requests may read:

```text
stock = 1
```

The system must guarantee that only one purchase succeeds.

## Implement

Inventory operations must safely handle concurrent checkout attempts.

Investigate and implement:

```php
lockForUpdate()
```

where appropriate.

Checkout flow:

```text
BEGIN
   ↓
Lock inventory row
   ↓
Check stock
   ↓
Decrease stock
   ↓
Create order
   ↓
COMMIT
```

As a comparison exercise, prototype the same problem using an optimistic locking approach (a `version` column, checked and incremented on update) to understand when each strategy is more appropriate.

## Testing

Create tests for:

* insufficient stock
* exact stock purchase
* concurrent checkout
* inventory rollback
* cancelled order inventory restoration

## Deliverables

Reliable inventory system that prevents overselling.

---

# Week 7 — Redis & Caching

## Backend Focus

# Redis

## Learn

Understand:

* Redis architecture
* key-value storage
* TTL
* cache-aside pattern
* cache hit
* cache miss
* cache invalidation
* rate limiting
* denormalized/aggregate caching (e.g. cached rating fields on a row) vs. request-level caching

## Implement

Cache:

```text
Product details
Product listings
Categories
```

Concept:

```text
Request
 ↓
Redis
 ↓
Cache Hit?
 ├── Yes → Response
 └── No
       ↓
   PostgreSQL
       ↓
     Redis
       ↓
    Response
```

Implement cache invalidation when products/categories change.

Example keys:

```text
product:{id}
products:featured
products:category:{id}
category:{id}
```

Add denormalized `average_rating` / `reviews_count` columns to `products` (used fully once Reviews are implemented in Week 9), and make sure product cache invalidation also fires when these change.

## Rate Limiting

Protect selected endpoints against excessive requests.

## Testing

Test:

* cache hit
* cache miss
* cache expiration
* cache invalidation
* rate limiting

## Deliverables

Redis integration and caching strategy.

---

# Week 8 — Queues, Jobs & Workers

## Backend Focus

# Asynchronous Processing

## Learn

Understand:

* queues
* jobs
* workers
* retry
* backoff
* failed jobs
* synchronous vs asynchronous processing
* idempotency

## Implement

Background jobs:

```text
SendOrderConfirmation
GenerateInvoice
NotifyAdmin
```

Flow:

```text
HTTP Request
    ↓
Create Order
    ↓
Dispatch Job
    ↓
Return Response
```

Worker:

```text
Queue
 ↓
Worker
 ↓
Job
 ↓
External/slow operation
```

Configure:

* queue connection
* worker
* retry policy
* backoff
* failed jobs

## Testing

Test:

* job dispatch
* job execution
* failed jobs
* retry behavior
* idempotent job execution

## Deliverables

Working queue infrastructure.

---

# Week 9 — Events, Notifications, Coupons & Reviews

## Backend Focus

* Events
* Listeners
* Notifications
* Domain events
* Business rules

## Learn

Understand:

```text
Event
=
Something happened.

Listener
=
React to what happened.
```

Example:

```text
OrderCreated
      |
      +----> SendConfirmation
      |
      +----> NotifyAdmin
      |
      +----> GenerateInvoice
```

## Implement

Events:

```text
OrderCreated
OrderPaid
OrderShipped
OrderDelivered
```

Notifications:

* Order confirmation
* Order status updates
* Admin notifications

## Coupons

Implement:

* percentage discount
* fixed discount
* expiration
* minimum order value
* maximum usage
* per-user usage

Coupon usage-count increments must be protected against the same concurrency issues as inventory stock (two users racing to use the last remaining coupon use).

## Reviews

Implement rules:

* User must be authenticated
* User must have purchased the product
* User cannot review the same product twice

Wire review create/update/delete to update the `products.average_rating` and `products.reviews_count` columns introduced in Week 7, and invalidate the relevant product cache entry.

## Testing

Test all important business rules.

## Deliverables

Events, notifications, coupons and review system.

---

# Week 10 — Payment Abstraction & Order State Machine

## Backend Focus

* Service abstraction
* Interfaces
* Dependency Injection
* State management
* Idempotency
* Error handling

## Learn

Understand:

* interfaces
* dependency injection
* abstraction
* state transitions
* idempotent operations
* implementing idempotency with a client-supplied `Idempotency-Key` header

## Implement

Create a payment abstraction.

Concept:

```text
PaymentService
      |
      v
PaymentProviderInterface
      |
      +---- FakePaymentProvider
      |
      +---- Future real provider
```

Do not connect real money processing yet.

Implement payment states:

```text
Pending
Paid
Failed
Refunded
```

Order states:

```text
Pending
Confirmed
Processing
Shipped
Delivered
Cancelled
```

Prevent invalid transitions.

Example:

```text
Delivered → Pending
```

should not be allowed.

Fully implement `Idempotency-Key` handling on the checkout endpoint: store the key against the created order, and return the original response if the same key is replayed.

## Testing

Test:

* successful payment
* failed payment
* duplicate payment request
* invalid order transition
* cancellation
* refund logic
* replayed checkout request with the same idempotency key does not duplicate the order

## Deliverables

Reliable payment abstraction and order lifecycle.

---

# Week 11 — Testing, Performance & Security

## Backend Focus

# Production Quality

## Learn

Study:

* Unit testing
* Feature testing
* Integration testing
* N+1 queries
* eager loading
* database indexing
* query optimization
* API performance
* security principles

## Testing

Increase test coverage for:

```text
Auth
Products
Categories
Cart
Orders
Inventory
Coupons
Payments
Reviews
Queues
Events
```

Critical scenarios:

```text
User cannot access another user's order

User cannot oversell inventory

User cannot use invalid coupon

User cannot review unpurchased product

Cancelled order cannot be shipped

Duplicate payment cannot create duplicate payment

Duplicate checkout request (same idempotency key) does not create a duplicate order
```

## Performance

Investigate:

```text
N+1 queries
Missing indexes
Slow queries
Unnecessary database requests
Large API responses
```

Use:

* eager loading
* pagination
* indexes
* caching
* query optimization

## Security Review

Check:

* authentication
* authorization
* validation
* mass assignment
* SQL injection protection
* rate limiting
* secret management
* error responses

## Deliverables

Stable, tested and optimized backend.

---

# Week 12 — Docker, Deployment & Final Production Review

## Backend Focus

* Docker
* Docker Compose
* Nginx
* Environment configuration
* Production deployment
* Logging
* Error handling
* CI/CD concepts

## Learn

Understand:

* containerization
* services
* networks
* volumes
* environment variables
* production configuration
* deployment workflow

## Docker Architecture

```text
             Nginx
               |
               v
            Laravel
           /       \
          v         v
     PostgreSQL   Redis
                    |
                    v
                  Worker
```

## Implement

Dockerize:

```text
nginx
php
postgres
redis
queue worker
```

Create:

```text
Dockerfile
docker-compose.yml
.env.example
```

Verify the `/api/health` endpoint (added in Week 3) works correctly inside the containerized environment and can be used for Docker/monitoring health checks.

## Production Review

Check:

* environment configuration
* logs
* error handling
* database indexes
* cache configuration
* queue workers
* failed jobs
* API security
* test suite
* README documentation

## Final Documentation

Document:

* Architecture
* Database schema
* API endpoints
* Authentication
* Order lifecycle
* Inventory strategy
* Redis strategy
* Queue architecture
* Testing strategy
* Docker setup
* Deployment instructions
* Known issues

## Final Deliverable

A production-oriented e-commerce backend with:

```text
Authentication
Authorization
       ↓
Products
Categories
       ↓
Cart
       ↓
Orders
       ↓
Transactions
       ↓
Inventory
       ↓
Concurrency Control
       ↓
Payments
       ↓
Redis
       ↓
Queues
       ↓
Events
       ↓
Notifications
       ↓
Testing
       ↓
Docker
       ↓
Deployment
```

---

# Final Learning Checklist

After completing ShopFlow, the developer should be able to explain:

## PHP / Laravel

* MVC
* Dependency Injection
* Service Container
* Middleware
* Form Requests
* API Resources
* Policies
* Events
* Listeners
* Jobs
* Queues
* Notifications

## PostgreSQL

* Relationships
* Normalization
* Foreign keys
* Constraints
* Indexes
* Transactions
* ACID
* Isolation levels
* Row-level locking (pessimistic and optimistic)
* Query optimization
* N+1 problem

## Redis

* Caching
* TTL
* Cache invalidation
* Rate limiting
* Queue backend

## Backend Engineering

* REST API design
* API versioning
* Authentication
* Authorization
* Business logic
* Race conditions
* Concurrency
* Idempotency (endpoint-level and payment-level)
* Asynchronous processing
* Error handling
* Performance optimization

## Testing

* Unit tests
* Feature tests
* Integration tests
* Database testing
* Mocking
* Queue testing
* Business-rule testing

## DevOps

* Docker
* Docker Compose
* Nginx
* Environment variables
* Deployment
* Logging
* CI/CD fundamentals

---

# Definition of Done

ShopFlow is considered complete only when:

* Core functionality works
* Authentication is secure
* Authorization is enforced
* Database relationships are correct
* Monetary values use `DECIMAL`/integer minor units, never `float`
* Checkout uses proper transaction boundaries
* Checkout is idempotent (safe against duplicate requests)
* Inventory cannot be oversold
* Redis caching works correctly
* Queue workers process background jobs
* Failed jobs are handled
* Events and notifications work
* Payment flow is idempotent
* Critical business rules have tests
* N+1 queries are reviewed
* Important database indexes exist
* A health-check endpoint verifies DB/Redis connectivity
* Docker environment works
* `.env` secrets are not committed
* README is complete
* API documentation is available
* Known issues are documented
* Project can be explained from an architectural perspective

---

# Important Rule

Do not implement all features at once.

Follow the roadmap sequentially.

For every week:

```text
1. Learn
2. Design
3. Implement
4. Test
5. Refactor
6. Document
7. Commit
```

The objective is not merely to finish ShopFlow.

The objective is to understand why each architectural decision was made and to be able to defend those decisions during a backend developer interview.

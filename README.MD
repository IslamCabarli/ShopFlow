# ShopFlow

ShopFlow is a production-oriented e-commerce platform built to strengthen backend engineering skills through realistic business logic,
scalable architecture, database design, concurrency control, caching, asynchronous processing, testing, and deployment.

The project is primarily backend-focused, with a React-based frontend used to consume and demonstrate the API.

The goal is not to build a simple CRUD application. ShopFlow is designed to simulate
the architecture and engineering challenges of a real-world e-commerce system.

---

## Project Goals

The main goals of ShopFlow are:

* Build a production-oriented REST API
* Design a normalized relational database
* Implement authentication and authorization
* Work with complex E-commerce business logic
* Properly handle database transactions
* Understand and solve race conditions
* Implement inventory management
* Use Redis for caching and rate limiting
* Use queues for asynchronous processing
* Implement events and listeners
* Build a reliable order and payment flow
* Write meaningful automated tests
* Containerize the application with Docker
* Optimize database queries and API performance
* Follow clean and maintainable backend architecture
* Deploy the application in a production-like environment

---

# Tech Stack

## Backend

* PHP 8.4+
* Laravel 13
* Laravel Sanctum
* PostgreSQL
* Redis
* PHPUnit

## Frontend

* React
* TypeScript
* Vite
* Axios

## Infrastructure

* Docker
* Docker Compose
* Nginx
* Redis
* PostgreSQL

## Development Tools

* Git
* GitHub
* Composer
* npm

---

# Architecture

ShopFlow follows a layered backend architecture built around Laravel.

The initial structure will follow Laravel's MVC architecture while gradually introducing additional layers when the business logic becomes complex.

Conceptually:

```text
Client
   |
   v
API Routes
   |
   v
Controllers
   |
   v
Form Requests
   |
   v
Services
   |
   v
Models / Repositories
   |
   v
PostgreSQL
```

Infrastructure and asynchronous processing:

```text
Laravel Application
       |
       +------------------> PostgreSQL
       |
       +------------------> Redis
       |
       +------------------> Queue
                              |
                              v
                           Worker
                              |
                              v
                     Jobs / Notifications
```

The project should avoid unnecessary abstraction.

New architectural layers should only be introduced when they solve a real problem.

---

# Core Features

## Authentication

* User registration
* Login
* Logout
* Current user endpoint
* Password hashing
* Email verification
* Password reset
* Sanctum authentication

---

## Authorization

The system will contain different user roles.

Example:

```text
User
Admin
```

Admin users can:

* Create products
* Update products
* Delete products
* Manage categories
* Manage inventory
* Manage orders
* Manage coupons

Regular users can:

* Browse products
* Manage their cart
* Create orders
* View their orders
* Submit reviews
* Manage their profile

Authorization should be implemented using Laravel Policies / Gates where appropriate.

---

# Product Management

Products will contain information such as:

* Name
* Slug
* Description
* Price (see **Money Handling** below — never stored as `float`)
* Discount price
* SKU
* Category
* Stock
* Status
* Images
* Timestamps
* Soft-delete timestamp (`deleted_at`)

Supported operations:

```text
GET    /api/v1/products
GET    /api/v1/products/{id}
POST   /api/v1/products
PUT    /api/v1/products/{id}
DELETE /api/v1/products/{id}
```

The API should support:

* Pagination
* Filtering
* Sorting
* Searching
* Category filtering
* Price filtering

## Money Handling

Prices must **never** be stored as `float`. Floating-point rounding errors are a classic real-world bug in e-commerce systems.

Use one of:

* `DECIMAL(10,2)` column type in PostgreSQL, or
* An integer column storing the smallest currency unit (e.g. `price_cents`)

All price arithmetic (discounts, totals, coupon calculations) should be done using this same convention consistently across the codebase.

## Soft Deletes

`Product` (and `Order`) should use Laravel's `SoftDeletes` trait. If a product is deleted, historical orders that reference it must not break — this is one of the reasons product price/name are snapshotted onto `order_items` at purchase time rather than looked up live.

---

# Category Management

Categories will support:

* Create
* Read
* Update
* Delete
* Product relationships

Example:

```text
Electronics
 ├── Phones
 ├── Laptops
 └── Accessories
```

---

# Shopping Cart

Users can:

* Add products to cart
* Remove products
* Update quantities
* View cart
* Clear cart

Example flow:

```text
User
  |
  v
Add Product
  |
  v
Cart
  |
  v
Update Quantity
  |
  v
Checkout
```

---

# Order Management

The order system is one of the most important parts of the project.

Order lifecycle:

```text
Pending
   ↓
Confirmed
   ↓
Processing
   ↓
Shipped
   ↓
Delivered
```

Possible cancellation:

```text
Pending
   ↓
Cancelled
```

An order contains:

* User
* Order number (human-readable, e.g. `ORD-2026-000123` — never expose the raw internal `id` to the customer)
* Order items
* Quantity
* Product price at purchase time (snapshot)
* Subtotal
* Discount
* Total
* Payment status
* Order status
* Shipping information

## Idempotency

The checkout endpoint must be idempotent. A duplicate request (e.g. a user double-clicking "Place Order", or a retried network request) must not create two orders.

This is typically implemented by accepting an `Idempotency-Key` header from the client, storing it against the created order, and returning the original result if the same key is seen again — rather than only handling idempotency inside the payment layer.

---

# Inventory Management

Inventory management must be designed carefully because concurrent requests can modify the same stock.

Example:

```text
Product stock = 1

User A -> Checkout
User B -> Checkout
```

The system must prevent both users from successfully purchasing the same final item.

The implementation will investigate:

* Database transactions
* Row-level locking (pessimistic)
* `SELECT ... FOR UPDATE`
* Optimistic locking (version column comparison, as an alternative approach worth understanding and contrasting)
* Race conditions
* Concurrent requests
* Deadlocks
* Transaction isolation

Laravel's database locking mechanisms should be used where appropriate.

---

# Payment System

ShopFlow will initially implement a payment abstraction / simulated payment provider rather than processing real money.

Example:

```text
Order
  |
  v
Payment
  |
  +----> Success
  |
  +----> Failed
```

The payment layer should be designed so that a real provider could theoretically be integrated later without rewriting the order system.

Payment operations must be idempotent — a retried payment request must not create a duplicate payment record for the same order.

---

# Coupons

The system will support discount coupons.

Examples:

```text
WELCOME10
SUMMER20
FIRSTORDER
```

Possible rules:

* Percentage discount
* Fixed amount discount
* Expiration date
* Minimum order amount
* Maximum usage count
* Per-user usage limit
* Active/inactive status

Coupon validation must happen on the backend. Usage-count increments are subject to the same concurrency concerns as inventory and should be protected accordingly.

---

# Reviews

Users can review products after purchasing them.

The backend should prevent:

* Unauthorized reviews
* Duplicate reviews
* Reviews for products the user has never purchased

## Rating Aggregation

Rather than computing `AVG(rating)` on every product read, maintain denormalized `average_rating` and `reviews_count` columns on the `products` table, updated whenever a review is created, updated, or deleted. This ties the review system directly into the caching/invalidation strategy below.

This feature will demonstrate authorization and business-rule validation.

---

# Redis

Redis will be used for backend performance and infrastructure features.

Potential use cases:

* Product caching
* Category caching
* Rate limiting
* Temporary data
* Queue backend

Example:

```text
Request
   |
   v
Redis Cache
   |
   +---- HIT ----> Response
   |
   +---- MISS
          |
          v
      PostgreSQL
          |
          v
        Redis
          |
          v
       Response
```

Cache invalidation must be handled when relevant data changes (including when a review changes a product's cached rating).

---

# Queues

Slow operations should not unnecessarily block HTTP requests.

Potential queue jobs:

* Order confirmation emails
* Invoice generation
* Notifications
* Stock-related background operations
* Other expensive operations

Example:

```text
HTTP Request
     |
     v
Create Order
     |
     v
Dispatch Job
     |
     v
Return Response

Queue Worker
     |
     v
Process Job
```

The project will also handle:

* Retries
* Backoff
* Failed jobs
* Idempotency

---

# Events & Listeners

Domain events will be introduced where they provide real value.

Example:

```text
OrderCreated
      |
      +----> SendOrderConfirmation
      |
      +----> NotifyAdmin
      |
      +----> CreateInvoice
```

Events should not be introduced simply for the sake of using them.

---

# API Design

The API should follow REST principles and be versioned under `/api/v1`, so future breaking changes don't disrupt existing clients.

Common status codes:

```text
200 OK
201 Created
204 No Content
400 Bad Request
401 Unauthorized
403 Forbidden
404 Not Found
409 Conflict
422 Unprocessable Entity
429 Too Many Requests
500 Internal Server Error
```

API responses should have a predictable structure.

Example:

```json
{
  "data": {},
  "message": "Product created successfully"
}
```

Validation errors should return structured responses.

## Health Check

A `GET /api/health` endpoint should verify database and Redis connectivity, for use by Docker/monitoring in production.

---

# Database

Primary database:

```text
PostgreSQL
```

Expected core tables:

```text
users
products
categories
product_categories
carts
cart_items
orders
order_items
payments
inventories
coupons
coupon_usages
reviews
```

Additional tables may be introduced when required by the domain.

The database design should prioritize:

* Referential integrity
* Proper foreign keys
* Appropriate indexes
* Normalization
* Data consistency
* Query performance
* Correct monetary types (`DECIMAL` or integer minor units — never `float`)

---

# Testing

The project will contain automated tests.

Testing areas:

```text
Authentication
Authorization
Products
Categories
Cart
Orders
Inventory
Coupons
Payments
Reviews
Caching
Queues
```

Important business scenarios must be covered with feature/integration tests.

Examples:

```text
User cannot access another user's order

User cannot review a product they never purchased

User cannot purchase more items than available stock

Two concurrent checkouts cannot oversell inventory

Invalid coupon cannot be applied

Cancelled order cannot be shipped

Duplicate checkout request (same idempotency key) does not create a duplicate order
```

---

# Security

Security is an important part of the project.

The implementation should consider:

* Password hashing
* Authentication
* Authorization
* CSRF where applicable
* SQL injection protection
* Mass assignment protection
* Input validation
* Rate limiting
* Secure environment variables
* Proper error handling
* Sensitive data protection

Secrets must never be committed to Git.

---

# Docker

The application will be containerized.

Expected services:

```text
nginx
php
postgres
redis
```

Queue workers may run as separate containers/processes depending on the final architecture.

Example:

```text
Docker Compose
     |
     +---- nginx
     |
     +---- php
     |
     +---- postgres
     |
     +---- redis
     |
     +---- queue worker
```

---

# Development Principles

The project should follow these principles:

### 1. Understand before implementing

Do not blindly generate code.

Every important feature should be understood before implementation.

### 2. Keep business logic out of controllers

Controllers should remain relatively thin.

Complex business logic should live in appropriate services or domain-oriented classes.

### 3. Validate at the application boundary

Incoming API data should be validated before reaching business logic.

### 4. Database integrity matters

Do not rely only on PHP validation.

Use:

* Foreign keys
* Unique constraints
* Check constraints where appropriate
* Transactions

### 5. Avoid premature abstraction

Do not create repositories, interfaces, factories, or services without a real reason.

### 6. Write tests for business rules

The most important tests should protect business behavior rather than implementation details.

### 7. Optimize based on evidence

Do not add Redis, indexes, or complex architecture without understanding the problem they solve.

---

# Git Workflow

Commits should be small and meaningful.

Examples:

```text
feat: add product creation endpoint
feat: implement cart item management
fix: prevent inventory overselling
test: add checkout transaction tests
perf: cache product listing
refactor: extract checkout service
docs: update order workflow
```

Branches can follow:

```text
main
develop
feature/*
fix/*
refactor/*
```

---

# Project Development Strategy

ShopFlow will be developed incrementally.

The project is divided into weekly milestones.

Each week should contain:

1. Backend concept to learn
2. Architecture/design decisions
3. Implementation tasks
4. Testing tasks
5. Documentation
6. Git commits
7. Review/refactoring

The full development plan is available in:

```text
ROADMAP.md
```

---

# Final Project Outcome

By completing ShopFlow, the developer should be able to confidently explain and implement:

* REST APIs
* Authentication
* Authorization
* Database relationships
* Transactions
* ACID principles
* Concurrency
* Race conditions
* Database locking (pessimistic and optimistic)
* Redis caching
* Rate limiting
* Queues
* Workers
* Events
* Listeners
* Notifications
* Idempotent API design
* Testing
* Docker
* API architecture
* Database optimization
* Production-oriented backend design

ShopFlow is intended to be a portfolio project demonstrating backend engineering ability rather than simply framework knowledge.
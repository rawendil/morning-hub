# Project Architecture

## Thin Controller, Fat Service

- Controllers should be as lean as possible — their only responsibilities are:
  - Validation (via Form Request)
  - Calling the appropriate service
  - Returning a response (Inertia::render, redirect, JSON)
- All business logic MUST live in dedicated services under `app/Services/`.
- Controllers MUST NOT contain private methods with business logic.
- Services should be injected via constructor injection, not instantiated inline with `new`.
- Every new service should:
  - Have explicit return types and type hints
  - Include PHPDoc blocks with array shapes where appropriate
  - Be created via `php artisan make:class` in the `app/Services/` directory
- When building new functionality that requires business logic, ALWAYS create a service.
- When modifying an existing controller that contains business logic — propose a refactor to a service.

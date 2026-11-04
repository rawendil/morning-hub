---
paths:
  - 'app/Services/**'
---

# Services

## Query and create user-owned records through User relations
Read and create user-owned records through the User relationship (`$user->routineBlocks()->create(...)`), not static model queries, and keep `user_id` out of their `$fillable`.

## Credentialed API clients come from injected factories
When a service needs a per-user token or connection, build it through an `XxxServiceFactory::make(...)` in `app/Services`, inject the factory, and never `new` the client directly.

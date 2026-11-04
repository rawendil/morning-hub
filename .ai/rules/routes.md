---
paths:
  - 'routes/**'
---

# Routes

## Explicit verb routes, no resource routes
Register every endpoint as an explicit verb route to a controller method; do not use `Route::resource` or `Route::apiResource`. Use an invokable controller for a single-action endpoint.

## Inline throttle limits on routes
Rate-limit endpoints with inline `throttle:<max>,<minutes>` middleware on the route or group, not with named `RateLimiter::for` limiters.

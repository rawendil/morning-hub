---
paths:
  - 'app/Http/**'
---

# Http

## JSON responses without API Resources
Return `response()->json()` with the payload under a named top-level key (`['block' => $block]`), serializing models directly or via `->only([...])`. Do not add `JsonResource` classes. Use `response()->noContent()` for writes with no body.

## Get the current user from the request
Get the authenticated user with `$request->user()` (or `$this->user()` in Form Requests); do not use `auth()` or the `Auth` facade.

## Query and create user-owned records through User relations
Read and create user-owned records through the User relationship (`$user->routineBlocks()->create(...)`), not static model queries, and keep `user_id` out of their `$fillable`.

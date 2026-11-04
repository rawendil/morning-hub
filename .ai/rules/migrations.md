---
paths:
  - 'database/migrations/**'
---

# Migrations

## Foreign keys always declare delete behaviour
Declare foreign keys with `foreignId()->constrained()` and always set delete behaviour: `cascadeOnDelete()` for required owner or parent keys, `nullOnDelete()` for nullable optional references.

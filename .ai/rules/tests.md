---
paths:
  - 'tests/**/*Test.php'
---

# Tests

## Pest tests use test(), not it()
Write Pest tests as `test('lowercase sentence describing the behaviour', fn)`; do not use `it()`. Vitest specs in `tests/js` are not covered by this rule.

# Public Repository

This repository (`rawendil/morning-hub`) is **public** on GitHub. Every commit, commit message, test and config example is world-readable, and a pushed commit cannot be taken back without rewriting history.

- Before every commit, read the whole staged diff (`git diff --cached`) and the commit message, looking for anything that must not go public:
  - secrets: tokens, keys, passwords, DSNs, bypass or cookie secrets;
  - real `.env` values, including non-secret production config such as analytics website IDs or third-party instance hosts;
  - personal data;
  - infrastructure details: SSH hosts and users, server paths, key file names;
  - details of other private repositories or projects: names, code, architecture.
- In tests and examples, use placeholders: `example.test`, `203.0.113.x`, dummy UUIDs.
- `/docs` is git-ignored on purpose. Specs and plans (`docs/superpowers/`) are local working notes and must never be committed. Do not re-add them or copy their content into tracked files.
- If a leak has already been pushed, say so immediately. Rewriting history (force-push) needs the user's explicit consent.

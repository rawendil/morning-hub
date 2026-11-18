# Formatting (Pint + Prettier)

## Blade templates are formatted by Pint, not by Prettier

`pint.json` enables `Pint/laravel_blade`. Pint formats `.blade.php` files through its own Prettier worker, using the configuration bundled with Pint (`vendor/laravel/pint/resources/prettier/prettierrc.json`: parser `blade`, `printWidth` 120). It does not read the project `.prettierrc`.

- **Do not add `prettier-plugin-blade` to `.prettierrc`.** `npm run format` would then format Blade too, with different options than Pint, and the two formatters would keep rewriting each other's output.
- **Pint now needs Node and installed npm packages.** Run `npm ci` before `composer lint` or `composer ci`. Without `prettier`, `prettier-plugin-blade` and `prettier-plugin-tailwindcss` in `node_modules`, Pint stops with an error instead of formatting.
- **Bumping Pint can raise the npm minimums.** Pint checks the versions it requires (`prettierDependencies()` in its Blade fixer) and refuses to run when the installed packages are older. Update `package.json` in the same change.
- Pint never formats `resources/views/emails/`, `resources/views/mail/`, `Envoy.blade.php` or `resources/boost/guidelines/`, even with the rule enabled.

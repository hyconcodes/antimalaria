# Antimalaria Research

Antimalaria Research is a Laravel application for recording selected *Plasmodium falciparum* marker calls and evaluating them against an explicit, source-linked rule catalogue. It is intended for academic research, demonstration, and decision support only.

It does not diagnose malaria, establish clinical resistance or susceptibility, recommend treatment, or replace laboratory testing and qualified interpretation. It does not use machine learning.

## Technology

- PHP 8.4 and Laravel 13
- Blade, Livewire 4, and Flux UI
- MySQL for normal application usage
- SQLite in memory for automated tests
- Tailwind CSS 4, Vite, and the Laravel Vite plugin
- Laravel Fortify authentication, including the existing passkey, email verification, and two-factor features

## Local setup

Requirements include PHP 8.3+, Composer, Node.js 22+, and npm.

1. Install PHP dependencies and prepare an environment file:

   ```powershell
   composer install
   Copy-Item .env.example .env
   php artisan key:generate
   npm install
   ```

2. Configure `.env` for a **local, non-production database** before running migrations. Set `APP_NAME`, `APP_URL`, and the `DB_*` variables for the selected MySQL database. For disposable local development, SQLite can be used by setting `DB_CONNECTION=sqlite` and `DB_DATABASE=database/database.sqlite`.

3. Create the configured local database and run migrations:

   ```powershell
   php artisan migrate
   ```

   Inspect the active database configuration before running any migration command. Do not point local setup or tests at a production database. The existing `composer setup` script includes `php artisan migrate --force`; do not run it against a production or shared database.

4. Optionally configure an administrator as described below, then start the local services:

   ```powershell
   composer dev
   ```

   To build frontend assets for production:

   ```powershell
   npm run build
   ```

## Administrator configuration and first login

There is no user-facing administrator promotion. Add the email address(es) of designated rule administrators to the deployment environment variable:

```dotenv
ANTIMALARIA_ADMIN_EMAILS=reviewer@example.org
```

Multiple addresses may be comma-separated. Clear cached configuration after changing the value:

```powershell
php artisan config:clear
```

For the seeded local administrator, set `ANTIMALARIA_ADMIN_EMAILS=admin@antimalaria.com` in `.env`, clear cached configuration, then run `php artisan db:seed`. The seeder creates a verified account only if it does not already exist and prints a randomly generated temporary password once in the command output. Save it securely and change it after signing in. Re-running the seeder does not display or replace an existing account's password.

Run the seeder only against a local or otherwise approved database. It does not reset the database. Administrators must sign in with an allow-listed account and can access **Scientific rules** from the authenticated navigation. Users who are not on the allow-list cannot view or change the rule catalogue.

The workflow supports draft creation/editing, submission for review, approval, activation, retirement, rule revisions, and an actor-attributed audit history with before/after snapshots. Approval and activation are separate; only approved, enabled rules affect new assessments. A new revision replaces an enabled earlier revision with the same stable rule key. There is no independent second-person approval enforcement: one allow-listed administrator can submit and approve a rule. This workflow is an application authorization boundary, not evidence that scientific review has occurred.

## Assessment workflow

Authenticated, email-verified users can:

- Enter a pseudonymous sample reference, optional collection date and notes, and available marker calls.
- Save an assessment with its exact input snapshot, outcome, evaluated rule definitions and sources, and rule-set version.
- Search and filter their own paginated assessment history and reopen saved results.

The initial call catalogue is limited to PfCRT codon 76, the PfCRT codons 72–76 haplotype, and PfMDR1 codon 86. Calls may be recorded as known alleles or explicitly marked other, mixed, unknown, conflicting, or not tested. Missing or uncertain data is never converted to a reference allele. This version does not implement structured data uploads.

The rule editor accepts exact equality rules over the supported marker calls; it does not execute arbitrary expressions or code. The assessment engine:

- Evaluates approved and enabled rules only.
- Produces “Resistance-associated marker pattern identified” when at least one such rule matches; this is a marker-level finding only.
- Produces “No qualifying marker detected” only if the active rules were evaluable and none matched. This is not evidence of susceptibility.
- Produces an indeterminate result when there are no active rules or when no rule matches and a required call is incomplete, unknown, conflicting, mixed, not tested, or unsupported.

Each saved result is immutable with respect to later rule changes. Its rule-set version is a SHA-256 digest of the ordered active rule and source definitions recorded in the assessment snapshot.

## Scientific evidence and limitations

No scientific rule is installed as approved or enabled by default. An administrator must inspect the current evidence, dataset definitions, intended application, and limitations before creating, approving, and enabling a rule. A source citation alone does not validate a rule.

The WHO compendium currently describes the following chloroquine evidence context:

- PfCRT is the primary resistance-associated gene discussed; PfCRT K76T is described as necessary but not sufficient for chloroquine resistance.
- PfMDR1 alterations may modulate the interpretation. PfMDR1 N86Y is described as validated in the compendium but modulatory rather than independently determinative.
- The compendium notes limited clinical evidence for the SVMNT haplotype and lists it as a candidate marker.

Those statements are context, not executable assessment rules. This application does not infer a clinical outcome from any of those calls alone. Scientific reviewers should consult the live sources and the relevant genetic-marker dataset documentation before approving a rule:

- [WHO Compendium of Molecular Markers for Antimalarial Drug Resistance](https://www.who.int/tools/compendium-of-molecular-markers-for-antimalarial-drug-resistance)
- [WHO compendium results, including chloroquine](https://www.who.int/tools/compendium-of-molecular-markers-for-antimalarial-drug-resistance/results)

The marker input catalogue is intentionally narrow. It does not represent all PfCRT variants, extended haplotypes, parasite genetic backgrounds, phenotypic assays, or alternative resistance mechanisms. The application has not been clinically validated.

## Data and security

- Assessments are associated with the authenticated user; dashboard, history, and detail queries are owner-scoped.
- Assessment detail requests for another user's record return not found.
- Sample references are intended to be pseudonymous. Do not enter patient names, direct identifiers, or sensitive clinical data in notes.
- Rule administration is restricted through Laravel policies and the configured administrator allow-list.
- Rule lifecycle actions are transactional and audited. Rules are revised rather than silently changing the definition used by saved assessments.
- User deletion cascades that user's assessments. Database migrations do not delete or reset existing records.
- No upload or import endpoint is provided.

## Database structure

- `assessments`: account ownership, pseudonymous sample reference, submitted input snapshot, result, rule-set version, and immutable evaluation snapshot.
- `assessment_rules`: stable key and revision, exact marker/value condition, scientific rationale, interpretation, evidence classification, review state, and administrator attribution.
- `assessment_rule_sources`: source title, HTTPS URL, DOI/date where available, evidence notes, and review date.
- `assessment_rule_audits`: rule lifecycle/change event, actor, and before/after snapshots.

Foreign keys and JSON columns are used in both the MySQL application database and the SQLite test database.

## Development and verification commands

```powershell
php artisan test --compact
npm run build
composer lint
composer types:check
```

The configured CI runs PHP 8.3 and Node 22. `composer ci:check` runs the project's formatter check, static analysis, and tests.

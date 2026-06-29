# Critical Security Fix Plan

This change set is intentionally narrow: it fixes the severe issues used in the CTF attack chain without redesigning the application, changing the existing database schema, or rewriting the legacy UI flow.

## Fixed in this commit

### 1. Login SQL injection

`login.php` previously concatenated `POST username` into:

```sql
SELECT * FROM Finance_user WHERE username = '$username'
```

This allowed authentication bypass with `UNION SELECT` and also enabled blind SQL injection. The login lookup now uses a prepared statement and binds the username as data.

Registration duplicate checks and user creation were also moved to prepared statements because the registration path accepted unauthenticated username/email/password input.

### 2. Default `test/test` account exposure

Existing installations may still contain the historical default account:

```text
username: test
password: test
```

The login handler now refuses that exact legacy default credential combination. New installations still create the seed user required by the old installer flow, but the generated password hash is random and not the known `md5("test")` value.

This preserves the current table layout while preventing the known default password from being a valid production login.

### 3. Authenticated full-database read via search pages

`search.php` and `edit.php` previously concatenated `classid` / `accategory` directly into numeric SQL filters. That enabled authenticated `UNION SELECT` exfiltration through rendered search results.

Both pages now:

- cast the session user id to an integer before using it in SQL;
- accept only `quan`, `sr`, `zc`, or decimal integer ids for category/class filters;
- reject any other filter value before SQL is built;
- escape free-text remark search strings before using them in `LIKE`.

The existing query branches and output tables are preserved.

### 4. Client login SQL injection

`client.php` had a separate username lookup that directly concatenated `GET username`. It now escapes the username and limits the lookup to one row while preserving the legacy `cpassword` behavior used by existing databases.

### 5. Hardcoded database credential exposure

`datajson.php` previously opened a second database connection with a hardcoded username and password. It now reuses the existing `config.php` connection and current session user id.

### 6. Session uid query hardening

`config.php::user_shell()` now casts the session uid to an integer and uses a prepared statement for the session authorization lookup.

## Database compatibility

No schema changes are required.

The application still uses the existing:

```text
Finance_user
Finance_account
Finance_category
Finance_account_class
Finance_account_payway
```

and related tables exactly as before.

## Operational follow-up required

These code fixes should be deployed together with the following operational actions:

1. Rotate the database password, because a historical hardcoded credential existed in source.
2. Disable or reset any existing `test` account whose password is still `test`.
3. Force password resets for users if the database may have been exposed.
4. Search web logs for attack indicators:

```text
UNION
ORDER BY
information_schema
/**/
DUMPHEX
classid=0
username='
%23
```

5. Remove or protect `install.php` in production after installation.

## Residual legacy risk

This repository still contains many old direct SQL call sites. This commit fixes the high-impact authentication bypass and full-database read paths from the CTF chain, plus same-class login and credential exposure issues.

A later hardening pass should migrate all remaining SQL to prepared statements, add CSRF protection for destructive actions, and replace MD5 password hashes with `password_hash()` / `password_verify()` through a staged migration.


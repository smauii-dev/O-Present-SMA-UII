# Login / Session Fix — O-Present SMA UII

Handoff for humans and coding agents. **Do not trust older notes that claim cookie domain or `forceGlobalSecureRequests` were the root cause.**

Production: `https://presensi.smauiiyk.sch.id`  
Container: `smauii-opresent-app` (image **not** bind-mounted to host source)

---

## Symptom

After “login”, browser/API bounced back to `/login` (or API `401`). Looked like “session not persisting”.

## Real root cause

### 1. Broken / wrong password hashes in NeonDB (primary)

| User | Issue |
|------|--------|
| `admin@smauiiyk.sch.id` | Hash stored as `b$10$…` (58 chars) — missing `$2` prefix (classic shell `$2` expansion when someone ran `UPDATE` by hand). Also was **plain bcrypt**, not Myth\Auth. |
| `uji.siswa@smauiiyk.sch.id` | Hash not verifiable with expected password. |

**Myth\Auth does not use raw `password_hash($plain)`.** It does:

```text
password_hash( base64_encode( hash('sha384', $password, true) ), PASSWORD_DEFAULT )
```

Login path: `Myth\Auth\Password::verify()` in `app/Controllers/Web/Auth.php`.

Plain bcrypt of `admin123` (even with a fixed `$2b$10$…` prefix) **still fails** Myth verify.

### 2. Secondary / supporting changes (not the main bug)

- `app/Config/Cookie.php`: force `$secure = true` in production (HTTPS behind nginx-proxy).
- **Leave `$domain` empty** (host-only cookie). Do **not** set `domain = 'presensi.smauiiyk.sch.id'` unless you have a proven need — empty domain worked in production tests.
- `app/Config/App.php`: **`forceGlobalSecureRequests` left `false`** — not required for the fix.
- `app/Controllers/Web/Auth.php`: after login, redirect to `session('redirect_url')` or `/overview`; open-redirect guard; `session()->close()` before redirect.
- `update_student_passwords.php`: disabled — it used plain `password_hash()` and can re-break logins.

Session config already fine: FileHandler, `timeToUpdate = 0`, `matchIP = false`, path `/var/www/html/writable/session`.

---

## What was done

```bash
# Inside production container — rehash with Myth\Auth
docker exec smauii-opresent-app php spark opresent:fix-passwords
```

That command rewrote broken admin + uji.siswa hashes and verified all users.

Code files changed on host (and `docker cp`’d into running container):

- `app/Config/Cookie.php`
- `app/Controllers/Web/Auth.php`
- `update_student_passwords.php`

**DB-only fix is enough for login to work.** Code changes are hardening / UX.

---

## Credentials (after fix)

| Account | Email | Password |
|---------|-------|----------|
| Admin | `admin@smauiiyk.sch.id` | `admin123` |
| Test student | `uji.siswa@smauiiyk.sch.id` | `UjiSiswa@2026` |
| Bulk siswa (`siswaN@gmail.com`) | — | `password123` |

**Wrong:** testing uji.siswa with `password123` → login fails → looks like “session broken”.

---

## Verify

```bash
# Hash health
docker exec smauii-opresent-app php spark opresent:verify-passwords

# Login + session (admin)
rm -f /tmp/cj.txt
curl -sS -c /tmp/cj.txt -b /tmp/cj.txt -o /tmp/login.html https://presensi.smauiiyk.sch.id/login
# extract csrf_test_name from HTML, then:
curl -sS -c /tmp/cj.txt -b /tmp/cj.txt -D - -o /dev/null -X POST \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  --data-urlencode 'email=admin@smauiiyk.sch.id' \
  --data-urlencode 'password=admin123' \
  --data-urlencode "csrf_test_name=$CSRF" \
  https://presensi.smauiiyk.sch.id/login
# expect 302 → /overview, Set-Cookie: ci_session=…; secure; HttpOnly; SameSite=Lax

curl -sS -b /tmp/cj.txt https://presensi.smauiiyk.sch.id/overview   # 200
curl -sS -b /tmp/cj.txt -H 'Accept: application/json' \
  https://presensi.smauiiyk.sch.id/api/me                          # 200 + admin user
```

Observed (2026-07-31):

- Admin: `/overview`, admin pages, `/api/me` → **200**
- Student (`UjiSiswa@2026`): login **302 → /overview**
- Unauthenticated protected routes → **302 / 401** as expected

---

## Do not do this again

1. **Never** set password hashes via shell double-quoted SQL (`$2` gets eaten → `b$10$…`).
2. **Never** use plain `password_hash('…')` for this app — always `Myth\Auth\Password::hash()` or:

   ```bash
   php spark opresent:fix-passwords
   php spark students:update-passwords
   php spark auth:set_password
   ```

3. Do not “fix sessions” by forcing cookie `Domain` without measuring — host-only (`domain = ''`) is correct here.
4. After editing PHP on the host, **rebuild/redeploy or `docker cp`** — container does not bind-mount app source (only `.env.production` → `.env`).

---

## Incorrect prior handoff (ignore)

Another agent draft claimed:

- After hash = `$2b$10$wlvoLSL…` (that was the **broken plain** hash with `$2` glued back; Myth verify still fails)
- `Cookie::$domain = 'presensi.smauiiyk.sch.id'` required — **not applied; not needed**
- `forceGlobalSecureRequests = true` — **not changed**

Trust this file + `git diff` on Cookie/Auth + `opresent:fix-passwords` instead.

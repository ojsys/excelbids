# Deploying ExcelBids to cPanel

A complete walkthrough, written for someone using the cPanel web interface rather
than SSH. Budget about 30 minutes for the first deployment.

---

## Before you start

| Requirement | Minimum | Where to check in cPanel |
|---|---|---|
| PHP | 8.0 (8.1 or 8.2 recommended) | Software → **Select PHP Version** |
| MySQL / MariaDB | MySQL 5.7 / MariaDB 10.2 | Databases → **MySQL® Databases** |
| PHP extensions | `pdo_mysql`, `mbstring`, `fileinfo` | Select PHP Version → **Extensions** |
| Apache modules | `mod_rewrite` | Standard on every cPanel host |

No Composer, Node, or shell access is needed. There are no third-party
dependencies to install.

---

## Step 1 — Choose where the files go

The project separates the **web root** (`public_html/`) from the **application
code** (`app/`, `database/`, `storage/`). Keeping the code above the web root is
the safer layout and is the one these instructions use.

### Recommended layout

Upload the project so your home directory looks like this:

```
/home/youraccount/
├── excelbids/              ← application code, NOT web-accessible
│   ├── app/
│   ├── database/
│   └── storage/
└── public_html/            ← your document root
    ├── index.php
    ├── .htaccess
    ├── assets/
    └── install/
```

To achieve that:

1. Upload and extract the project into `/home/youraccount/excelbids/`.
2. Move the **contents** of `excelbids/public_html/` into `/home/youraccount/public_html/`.
3. Open `/home/youraccount/public_html/index.php` and change the first `require`
   so it points at the application folder:

   ```php
   require '/home/youraccount/excelbids/app/bootstrap.php';
   ```

### Simpler alternative

If that feels fiddly, upload the whole project **inside** `public_html` instead:

```
/home/youraccount/public_html/excelbids/
├── app/            ← protected by app/.htaccess
├── database/       ← protected by database/.htaccess
├── storage/        ← protected by storage/.htaccess
└── public_html/    ← point your domain here
```

Then set your domain's document root to
`public_html/excelbids/public_html` in cPanel → **Domains**.

Every folder that must not be served already ships with an `.htaccess` that
denies all access, so this layout is safe too — it just relies on Apache
honouring those files rather than on the folders being unreachable.

---

## Step 2 — Create the database

1. Go to **Databases → MySQL® Databases**.
2. Under *Create New Database*, enter `excelbids`. cPanel will prefix it with
   your account name, giving something like `youracct_excelbids`. **Write the
   full name down.**
3. Under *Add New User*, create a user (e.g. `ebuser` → `youracct_ebuser`) and
   use the password generator. **Write the password down.**
4. Under *Add User To Database*, pair the two and tick **ALL PRIVILEGES**.

---

## Step 3 — Set folder permissions

In **File Manager**, set these to **755**:

- `app/` — so the installer can write `config.php`
- `storage/` — uploads and logs are written here

Everything else can stay at the default 644 for files and 755 for folders.

---

## Step 4 — Run the installer

Visit **`https://yourdomain.co.uk/install/`** and work through four steps:

1. **Requirements** — every row must be green. If one is not, the fix is written
   next to it.
2. **Database** — enter the full prefixed database name, username and password
   from step 2. Host is almost always `localhost`.
3. **Your account** — your name, email address and a password of at least 10
   characters. This becomes the administrator account and the inbox that
   receives consultation requests.
4. **Done.**

The installer creates every table, loads the website content, creates your
account, and writes `app/config.php`.

---

## Step 5 — Secure the installation

Three things, in order of importance.

### 5.1 Delete the installer

In File Manager, delete `public_html/install/`. Leaving it in place is the single
biggest risk in a fresh deployment.

### 5.2 Turn on HTTPS

1. Go to **Security → SSL/TLS Status** and run **AutoSSL**.
2. Once the certificate is issued, edit `public_html/.htaccess` and uncomment
   the redirect block near the top:

   ```apache
   RewriteCond %{HTTPS} off
   RewriteCond %{HTTP:X-Forwarded-Proto} !https
   RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
   ```

3. Edit `app/config.php` and set `'secure' => true` inside the `session` array,
   and make sure `base_url` starts with `https://`.

Client bid data and portal passwords travel over this connection — do not skip it.

### 5.3 Confirm debug is off

`app/config.php` should contain `'debug' => false`. The installer sets this, but
check it if you have edited the file. With debug on, PHP errors are shown to
visitors along with file paths.

---

## Step 6 — Set up email

Email is the most common thing to break on shared hosting.

1. Sign in to the admin panel and go to **Settings → Email**.
2. Set the **From address** to a real mailbox **on your own domain**
   (e.g. `noreply@yourdomain.co.uk`), created under cPanel → **Email Accounts**.
   Sending as a Gmail or Outlook address from your server's IP is the fastest
   route to the spam folder.
3. Click **Send test email** and check it arrives.

If mail does not arrive, switch **Mail transport** to `SMTP` and fill in:

| Field | Typical cPanel value |
|---|---|
| Host | `mail.yourdomain.co.uk` |
| Port | `587` |
| Encryption | `TLS` |
| Username | the full email address |
| Password | that mailbox's password |

Send another test. Failures are recorded with their reason under
**Settings → Email log**.

### Improving deliverability

In cPanel → **Email Deliverability**, make sure SPF and DKIM records are
present and valid for your domain.

---

## Step 7 — Make it yours

Work through these in the admin panel:

0. **Settings → Logo & favicon** — upload your logo and browser-tab icon. A wide
   PNG or SVG works best for the logo; a square PNG of at least 180×180 for the
   favicon. If your logo is dark, also upload a white version under *Logo for
   dark backgrounds* so it stays visible on the navy admin and portal sidebars.
   Add a social sharing image too, so links to your site preview properly on
   LinkedIn and WhatsApp. Anything you leave empty falls back to the built-in
   ExcelBids wordmark.
1. **Website content → Statistics bar** — replace the placeholder figures
   (`92%`, `7`, `£—M`, `4`) with numbers you can evidence, and clear the
   "Placeholder figures" footnote.
2. **Website content → Case studies** — replace the illustrative case study with
   a real, permissioned client outcome, and clear its footnote.
3. **Website content → Testimonials** — only publish quotes you have written
   permission to use.
4. **Website content → Pages** — have the privacy policy and terms of service
   reviewed. The supplied text is a starting point, not legal advice.
5. **Settings → General** — check the contact email, phone and bid reference prefix.

---

## Updating an already-live installation

Upload the changed files over the top, keeping your `app/config.php` and the
`storage/` folder. New settings are created automatically the first time you open
the screen that uses them — there is no SQL to run by hand.

For the logo and favicon specifically: open **Settings → Logo & favicon** once
after updating and the fields will appear, whether or not your database predates
them.

---

## Ongoing maintenance

### Backups

cPanel's **Backup Wizard** covers both parts, but the two that matter are:

- The **database** — every client, bid, message and content change.
- The **`storage/uploads/` folder** — every document. These are *not* in the
  database.

Restoring one without the other leaves bid records pointing at missing files.

### Updating PHP

The system targets PHP 8.0+ and works on 8.1, 8.2 and 8.3. After changing the
version in **Select PHP Version**, re-check that `pdo_mysql`, `mbstring` and
`fileinfo` are still enabled — cPanel sometimes resets the extension list.

### Log files

- `storage/logs/php-error.log` — PHP errors. Check here first when something breaks.
- **Admin → Settings → Email log** — every message the system tried to send.
- **Admin → Activity log** — who changed what, and when.

---

## Troubleshooting

**"ExcelBids is not installed yet"**
`app/config.php` is missing. Either the installer was not run, or it could not
write the file. Set `app/` to 755 and run `/install/` again.

**500 error on every page**
Check `storage/logs/php-error.log`. The usual causes are a PHP version below 8.0
or a missing `pdo_mysql` extension.

**Pretty URLs 404 — only the homepage works**
`mod_rewrite` is not applying. Confirm `public_html/.htaccess` was uploaded
(File Manager hides dotfiles until you tick *Show Hidden Files* in Settings).

**The site is in a sub-folder and links are broken**
Set `base_path` in `app/config.php` to the sub-folder, e.g. `'base_path' => '/excelbids'`,
and uncomment `RewriteBase` in `.htaccess` to match.

**Uploads fail with "larger than the server allows"**
Raise `upload_max_filesize` and `post_max_size` in cPanel → **Select PHP Version → Options**,
then raise the matching limit in **Settings → Client portal**. The system always
enforces the lower of the two.

**Clients cannot sign in**
Check **Settings → Client portal → Enable client portal** is on, and that the
client's login is active on their client record. Invitation links expire after
seven days — use **Resend invite** to issue a fresh one.

**Locked out of the admin panel**
Use *Forgotten your password?* on the login screen. If email is not working yet,
reset the hash directly in **phpMyAdmin**: generate a bcrypt hash, then run
`UPDATE users SET password_hash = '<hash>' WHERE email = 'you@example.com';`
Five failed attempts lock an address for 15 minutes.

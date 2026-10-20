# Unicorn Landscaping website

Static site (`index.html` + `img/`) with a small PHP handler (`send.php`) for the estimate form.

## Deploy to Hostinger

**Option A – Git (recommended):** hPanel → Websites → Manage → Advanced → **Git**. Add this repository, branch `main`, install path empty (deploys to `public_html`). Enable auto-deployment if you want pushes to go live automatically.

**Option B – File Manager / FTP:** upload `index.html`, `send.php`, `.htaccess` and the `img/` folder into `public_html`.

## After deploying

1. Create the mailbox `info@unicornlandscaping.ca` in hPanel → Emails (mail sent by `send.php` must come from an address on the site's domain).
2. If the recipient or sender should differ, edit `TO_EMAIL` / `FROM_EMAIL` at the top of `send.php`.
3. Enable the free SSL certificate in hPanel → Security → SSL. `.htaccess` redirects all traffic to HTTPS without `www`.
4. Submit a test estimate on the live site and confirm it arrives.

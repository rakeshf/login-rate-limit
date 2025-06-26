# 🔐 Login Rate Limit Plugin

A lightweight WordPress firewall plugin with **rate-limiting for login protection** and **log viewer** — powered by [Symfony RateLimiter](https://symfony.com/doc/current/rate_limiter.html).

---

## ✅ Features

- 🧱 **Login Rate Limiting**  
  Blocks excessive login attempts using Symfony's fixed window limiter

- 🧾 **WAF Logs Viewer**  
  Logs blocked IPs and reasons to `wp-content/uploads/waf-login-cache`

---

## ⚙️ Installation

1. Upload the plugin to your `/wp-content/plugins/` directory:
    ```bash
    cd wp-content/plugins/
    git clone https://github.com/rakeshf/login-rate-limit.git
    cd login-rate-limit
    composer install
    ```

2. Or upload the `.zip` via WordPress Admin → Plugins → Add New → Upload

3. Activate the plugin from **Plugins → Installed Plugins**

---

## 🛠 Dependencies

Install required PHP packages using Composer:

```bash
composer require symfony/rate-limiter symfony/cache

# Munimail

A modern, lightweight, and fast **SMTP server** built with **Laravel** and **ReactPHP**, designed for reliable email transmission, real-time processing, and seamless integration into your applications.

> ✨ Inspired by the elegance of message delivery, with a personal touch.

---

## 🚀 Features

- 📩 Fully functional **SMTP server** built from scratch
- ⚙️ Asynchronous message handling with **ReactPHP**
- 🧰 Laravel-based configuration, logging, and queueing system
- 🛡️ Spam filtering and basic security features
- 📊 Dashboard-ready for tracking email status (coming soon)
- 🔌 Easily extendable for hooks, webhooks, or custom handlers

---

## 🧱 Tech Stack

- **PHP 8.2+**
- **Laravel 10+**
- **ReactPHP**
- **Docker** (for local development and service separation)
- **SQLite/MySQL/PostgreSQL** for queue & logs

---

## 🛠️ Installation

```bash
git clone https://github.com/yourusername/munimail.git
cd munimail
composer install
cp .env.example .env
php artisan key:generate
php artisan smtp:start
```
This starts the ReactPHP-based SMTP listener on the configured port.

📌 Roadmap
 [] Basic SMTP server (HELO, MAIL FROM, RCPT TO, DATA)

 [] DKIM & SPF checks(coming soon)

 [] Web dashboard for email log monitoring(coming soon)

 [] Webhook integrations (e.g., Laravel events)(coming soon)

 [] Message queueing for delayed delivery(coming soon)

❤️ Attribution
This project is a labor of love, inspired by simplicity, reliability — and someone special.

📄 License
This project is open-sourced under the MIT license.


# NFUH DMV System — New Mac Setup & Commands

## 1. Install Xcode Command Line Tools
```bash
xcode-select --install
sudo xcode-select --reset
git --version
```

## 2. Install Homebrew
```bash
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
```

### Apple Silicon
```bash
echo 'eval "$(/opt/homebrew/bin/brew shellenv)"' >> ~/.zshrc
eval "$(/opt/homebrew/bin/brew shellenv)"
```

### Intel Mac
```bash
echo 'eval "$(/usr/local/bin/brew shellenv)"' >> ~/.zshrc
eval "$(/usr/local/bin/brew shellenv)"
```

Verify:
```bash
brew --version
```

## 3. Install PHP and Composer
```bash
brew install php composer
php -v
composer -V
```

## 4. Clone the Project
```bash
cd ~/Documents
git clone https://github.com/njulio6/nfuh-dmv-system.git
cd nfuh-dmv-system
```

## 5. Install Laravel Dependencies
```bash
composer install
cp .env.example .env
php artisan key:generate
```

## 6. Setup SQLite Database
```bash
touch database/database.sqlite
open -e .env
```

Set this in `.env`:
```
DB_CONNECTION=sqlite
DB_DATABASE=/Users/YOUR_USERNAME/Documents/nfuh-dmv-system/database/database.sqlite
```

## 7. Run Migrations
```bash
php artisan migrate
```

## 8. Start the App
```bash
php artisan serve
```

Open:
http://127.0.0.1:8000

---

# Daily Workflow

## Before starting work
```bash
cd ~/Documents/nfuh-dmv-system
git pull
php artisan serve
```

## Before stopping or switching machines
```bash
git add .
git commit -m "checkpoint"
git push
```

---

# Troubleshooting

## Clear Laravel cache
```bash
php artisan optimize:clear
```

## Full cache clear
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## Reset database completely
```bash
php artisan migrate:fresh
```

---

# Project Reminder

After `migrate:fresh`, you must recreate or reseed:
- organization
- member ranks
- member roles
- members

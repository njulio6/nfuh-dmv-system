# Git Commands Reference (NFUH DMV System)

## 1. Initial Setup (First Time Only)

```bash
git init
```

### Create .gitignore

```bash
touch .gitignore
```

Add:
```
/vendor
/node_modules
.env
/storage/*.key
/storage/app/*
/storage/framework/*
/storage/logs/*
/bootstrap/cache/*
```

---

## 2. First Commit & Push

```bash
git add .
git commit -m "Initial commit - member management, roles, ranks, njangi migrations"
```

### Connect to GitHub

```bash
git remote add origin https://github.com/YOUR_USERNAME/nfuh-dmv-system.git
git branch -M main
git push -u origin main
```

---

## 3. Daily Workflow (VERY IMPORTANT)

Before switching devices or stopping work:

```bash
git add .
git commit -m "checkpoint"
git push
```

---

## 4. Cloning on Another Machine (Laptop Setup)

```bash
git clone https://github.com/YOUR_USERNAME/nfuh-dmv-system.git
cd nfuh-dmv-system
```

---

## 5. Laravel Setup on New Machine

```bash
composer install
cp .env.example .env
php artisan key:generate
```

### Configure Database (.env)

Update:
```
DB_DATABASE=nfuh_dmv
DB_USERNAME=root
DB_PASSWORD=
```

Then run:

```bash
php artisan migrate
php artisan serve
```

---

## 6. Pull Latest Changes (When Starting Work)

```bash
git pull
```

---

## 7. Push Changes (After Work)

```bash
git add .
git commit -m "describe your changes"
git push
```

---

## 8. Good Commit Message Examples

- "Add member roles functionality"
- "Fix role checkbox alignment"
- "Add member import template"
- "Prepare njangi migrations"

---

## 9. Safety Rules (DO NOT FORGET)

- NEVER push `.env`
- ALWAYS commit before switching devices
- ALWAYS pull before starting work on a new machine

---

## 10. Quick Start Checklist (New Machine)

1. Clone repo
2. Install dependencies
3. Setup .env
4. Create database
5. Run migrations
6. Start server

---

## 11. Project Status Checkpoint

Current system includes:
- Member management
- Roles & ranks
- Member profiles
- Njangi migrations (ready for next phase)

Next phase:
- Member import
- Njangi cycle engine


# Laravel Commands Cheat Sheet

## Run local Laravel server
php artisan serve

## Main module URL
http://127.0.0.1:8000/members

## Stop Server
Ctrl + C

## Open Tinker (database testing)
php artisan tinker

## Run Migrations
php artisan migrate

## Reset Database
php artisan migrate:fresh

## Clear Cache
php artisan optimize:clear

## Composer Autoload Fix
composer dump-autoload

## Create Controller
php artisan make:controller MemberController --resource

## Create Model + Migration
php artisan make:model Member -m

## Create Migration Only
php artisan make:migration create_members_table

## Check current folder
pwd
ls

## Go to Laravel project
cd ~/Documents/NFUH\ DMV/APP/nfuh-platform

## Run Laravel server
php artisan serve

## Create migration
php artisan make:migration add_status_to_members_table
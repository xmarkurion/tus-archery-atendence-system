This is quick project for archery club.

In fact there is no types or special fancy things as in real project. 
It works and that's enough for this small project.

```
I should spend more time add tests and proper types as we all know TS is better with types.
```

php artisan meetings:create-daily
php artisan schedule:work
php artisan schedule:list
php artisan schedule:run

To simulate the scheduler for production you should set up your OS scheduler to run:
Unix: * * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1

On cyberfolks server:
cd /home/USERNAME/domains/DOMAIN.COM/laravel_app_files && /usr/local/bin/php84 artisan schedule:run > /dev/null 2>&1

# Disable reg
- mod config/fortify.php    
  - uncomment
      // Features::registration(),
- modify auth/Login.vue
  - remove register function
  - import { register } from '@/routes';
- routes veb
  - uncomment //'canRegister' => Features::enabled(Features::registration()),
  - remove false

# User management cli
## INFO
php artisan user:manage update --help

## Create a user:
```php artisan user:manage create --name="John Doe" --email="john@example.com" --password="secret"```

## List all users:
```php artisan user:manage list```

## Remove a user:
```php artisan user:manage remove --id=1```
or
```php artisan user:manage remove --email="john@example.com"```

## Update a user’s name or email:
```php artisan user:manage update --id=1 --new-name="Jane Doe" --new-email="jane@example.com"```

## Change password
```
php artisan user:manage update --id=USER_ID --new-password="newpassword"
```

```
php artisan user:manage update --email="user@example.com" --new-password="newpassword"
```

**php artisan user:manage update --email="user@example.com" --new-password="newpassword"**


##
php artisan list | findstr user:manage


# Other
## Tools:
- [Vue use](https://vueuse.org/guide/)

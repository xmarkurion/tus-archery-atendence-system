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


Tools:
- [Vue use](https://vueuse.org/guide/)

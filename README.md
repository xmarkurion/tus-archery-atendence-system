This is quick project for archery club.

In fact there is no types or special fancy things as in real project. 
It works and that's enough for this small project.

```
I should spend more time add tests and proper types as we all know TS is better with types.
```

php artisan meetings:create-daily
php artisan schedule:work

To simulate the scheduler for production you should set up your OS scheduler to run:
Unix: * * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
Windows Task Scheduler: run php "G:\Coding\TUS\arch\artisan" schedule:run every minute. (For quick local testing you can call php artisan schedule:run manually.)

Tools:
- [Vue use](https://vueuse.org/guide/)

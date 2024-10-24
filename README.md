## Requirements

**php >= ^8.0.2**

**MySql >= 5.7 OR MariaDB >= 10.6**

**Current laravel version is ^9.19**

**Composer version 2**

## Installation
1. Clone this repository.
2. Rename *.env.example* file to *.env* file
3. Add required information to *.env* file like database name, user, password, email smtp info.
4. Run composer command in CLI: *composer install*
5. Run migration command in CLI: *php artisan migrate --seed*
6. Run passport command in CLI: *php artisan passport:install
7. Run storage command in CLI: *php artisan storage:link*
8. If you want to run this app in localhost then run this command: *php artisan serve* OR *php artisan serve --port=PORT_NUMBER* OR * php artisan serve --host=IP_ADDRESS_OR_URL --port=PORT_NUMBER
9. set cron job: * * * * * php /APPLICATION-PATH/artisan schedule:run 1>> /dev/null 2>&1

## Environment variables explanations
1. APP_NAME="APPLICATION NAME"
2. APP_ENV=local OR production
3. APP_DEBUG=true (in local envirnment set true else false)
4. APP_URL=https://API-URL-HERE
5. FRONT_URL=https://FORNTEND-URL-HERE 

6. DB_CONNECTION=mysql
7. DB_HOST=127.0.0.1
8. DB_PORT=3306
9. DB_DATABASE="DPR-DATABASE-NAME"
10. DB_USERNAME="DPR-DATABASE-USER"
11. DB_PASSWORD="DPR-DATABASE-PASSWORD"

12. KPMG_MASTER_DB_HOST=127.0.0.1
13. KPMG_MASTER_DB_PORT=3306
14. KPMG_MASTER_DB_DATABASE="COMMON-DATABASE-NAME"
15. KPMG_MASTER_DB_USERNAME="COMMON-DATABASE-USER"
16. KPMG_MASTER_DB_PASSWORD="COMMON-DATABASE-PASSWORD"

17. SESSION_LIFETIME=session timeout in minutes

18. MAIL_MAILER=smtp
19. MAIL_HOST=mail.domain.name
20. MAIL_PORT=587
21. MAIL_USERNAME="no-reply@domain.name"
22. MAIL_PASSWORD="EMAIL-PASSWORD"
23. MAIL_ENCRYPTION=tls
24. MAIL_FROM_ADDRESS="no-reply@domain.name"
25. MAIL_EHLO_DOMAIN=domain.name
26. MAIL_FROM_NAME="${APP_NAME}"

27. CDN_DOC_URL=https://API-URL-HERE-WITH-SLASH/

28. IS_MAIL_ENABLE=If Mail connected then true else false
29. TIME_ZONE=Asia/Calcutta
30. OTP_ATTEMPT_LIMIT=How many times user can attempt OTP in login form
31. LOCK_TIME_IN_SEC_INCORRECT_PWD_TIME=If user enters wrong password (LOGIN_ATTEMPT_LIMIT) account will be locked till this time (in seconds)
32. LOGIN_ATTEMPT_LIMIT=How many attempts a user can make to login

33. IMAP_MAIL_SERVER=HANGER-EMAIL-SERVER
34. IMAP_MAIL_PORT=HANGER-EMAIL-SERVER-PORT
35. IMAP_CONNECTED_MAIL="HANGER-EMAIL-PUT-HERE"
36. IMAP_MAIL_PASSWARD="HANGER-EMAIL-PWD-PUT-HERE""# cpcl-pact-api" 

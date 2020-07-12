Разворачивание проекта:
1. Запустить из папки docker команду ```docker-compose up -d --build```
2. Подключиться к bash внутри контейнера командой ```docker-compose exec web-app bash``` и выполнить следующие команды:
    - ```composer install```
    - ```php bin/console doctrine:migrations:migrate```
3. Перейти на http://localhost:8080/
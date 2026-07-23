@echo off
docker compose exec ^
  -e APP_ENV=testing ^
  -e DB_CONNECTION=mysql ^
  -e DB_HOST=db ^
  -e DB_PORT=3306 ^
  -e DB_DATABASE=saas_test ^
  -e DB_USERNAME=saas_user ^
  -e DB_PASSWORD=secret_pass ^
  app php artisan test %*

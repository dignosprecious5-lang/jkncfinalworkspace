@echo off
setlocal
cd /d "%~dp0"
set "ORDO_PHP=php"
where php >nul 2>nul
if errorlevel 1 (
  if exist "%~dp0..\.qa\php\php.exe" (set "ORDO_PHP=%~dp0..\.qa\php\php.exe") else (
    echo PHP 8.4+ with pdo_sqlite is required. Add PHP to PATH and try again.
    pause
    exit /b 1
  )
)
if not exist .env copy .env.example .env >nul
if not exist database\database.sqlite type nul > database\database.sqlite
if not exist vendor\autoload.php (
  if exist "%~dp0..\.qa\composer.phar" (
    "%ORDO_PHP%" "%~dp0..\.qa\composer.phar" install --no-interaction
  ) else (
    call composer install --no-interaction
  )
  if errorlevel 1 exit /b 1
)
"%ORDO_PHP%" -r "$v=parse_ini_file('.env'); exit(empty($v['APP_KEY']) ? 1 : 0);"
if errorlevel 1 "%ORDO_PHP%" artisan key:generate
if errorlevel 1 exit /b 1
"%ORDO_PHP%" artisan migrate --seed
if errorlevel 1 exit /b 1
echo Open http://127.0.0.1:8000 in your browser.
"%ORDO_PHP%" artisan serve --host=127.0.0.1 --port=8000

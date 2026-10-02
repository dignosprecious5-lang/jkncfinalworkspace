@echo off
title ORDO Prototype Server
echo ========================================================
echo Starting ORDO UI/UX Live Prototype Servers...
echo ========================================================
echo.
echo [1] ORDO Project : http://127.0.0.1:8080
echo [2] ORDO Regular : http://127.0.0.1:8081
echo [3] Portal Hub   : http://127.0.0.1:3000
echo.
echo Opening browser...
start http://127.0.0.1:8080
node server.js
pause

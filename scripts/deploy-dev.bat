@echo off
echo Deploying DEV branch to dev.batumi.zone...
ssh root@38.242.143.10 "/var/www/deploy/deploy-dev.sh"
echo.
echo Done! Check https://dev.batumi.zone
pause

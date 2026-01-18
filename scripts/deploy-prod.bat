@echo off
echo ========================================
echo  WARNING: PRODUCTION DEPLOYMENT
echo ========================================
echo.
echo This will deploy to batumi.zone (LIVE SITE)
echo.
set /p confirm="Are you sure? (yes/no): "
if /i "%confirm%"=="yes" (
    echo Deploying MAIN branch to batumi.zone...
    ssh root@38.242.143.10 "/var/www/deploy/deploy-prod.sh"
    echo.
    echo Done! Check https://batumi.zone
) else (
    echo Deployment cancelled.
)
pause

#!/bin/bash
set -e

# Extract database name from DB_DSN (format: sqlsrv:Server=...;Database=dbname;...)
DB_NAME=$(echo "$DB_DSN" | grep -oP 'Database=\K[^;]*')

if [ -z "$DB_NAME" ]; then
    DB_NAME="test"
fi

echo "Using database: $DB_NAME"

# Wait for SQL Server to be ready
echo "Waiting for SQL Server..."
until /opt/mssql-tools18/bin/sqlcmd -S mssql -U "$DB_USER" -P "$MSSQL_SA_PASSWORD" -C -Q "SELECT 1" >/dev/null 2>&1; do
    echo "SQL Server not ready, retrying..."
    sleep 2
done

echo "SQL Server is ready. Creating database if it doesn't exist..."
/opt/mssql-tools18/bin/sqlcmd -S mssql -U "$DB_USER" -P "$MSSQL_SA_PASSWORD" -C -Q "IF DB_ID('$DB_NAME') IS NULL CREATE DATABASE [$DB_NAME];"

echo "Running migrations..."
php yii migrate --interactive=0

echo "Initializing RBAC with roles and permissions..."
# php yii migrate --migrationPath=@yii/rbac/migrations
php yii rbac/init

echo "Seeding database(12 construction sites, 30 workers, 50 tasks)."
echo "Truncate enabled what means prev records deleted. Change in docker/php/init.sh if needed..."
php yii seed/employee 30 1
php yii seed/site 12 1
php yii seed/task 50 1


echo "Creating admin user..."
php yii user/create-admin

# echo "Loading fixtures..."
# php yii fixture/load '*' --interactive=0

echo "Init complete. Starting php-fpm..."
php-fpm

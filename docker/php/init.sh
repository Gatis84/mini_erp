#!/bin/bash

set -u   # fail on undefined variables (but NOT on command errors)

echo "=== PHP container init started ==="


# Resolve database name
DB_NAME=$(echo "${DB_DSN:-}" | grep -oP 'Database=\K[^;]*' || true)

if [ -z "$DB_NAME" ]; then
    DB_NAME="test"
fi

echo "Using database: $DB_NAME"


# Wait for SQL Server
echo "Waiting for SQL Server..."

until /opt/mssql-tools18/bin/sqlcmd \
    -S mssql \
    -U "$DB_USER" \
    -P "$MSSQL_SA_PASSWORD" \
    -C \
    -Q "SELECT 1" >/dev/null 2>&1
do
    echo "SQL Server not ready, retrying in 2s..."
    sleep 2
done

echo "SQL Server is reachable"


# Ensure database exists
echo "Ensuring database exists..."

/opt/mssql-tools18/bin/sqlcmd \
    -S mssql \
    -U "$DB_USER" \
    -P "$MSSQL_SA_PASSWORD" \
    -C \
    -Q "
IF DB_ID(N'$DB_NAME') IS NULL
BEGIN
    PRINT 'Creating database $DB_NAME';
    CREATE DATABASE [$DB_NAME];
END
ELSE
BEGIN
    PRINT 'Database already exists';
END
"

# Give MSSQL time to finalize DB metadata
sleep 5

# Run migrations
echo "Running migrations..."
php yii migrate --interactive=0 || echo "Migrations skipped / already applied"


# RBAC init
echo "Initializing RBAC..."
php yii rbac/init || echo "RBAC already initialized"


# Seed data
echo "Seeding database (safe mode)..."
echo "NOTE: truncate enabled inside seeders"

php yii seed/employee 30 1 || echo "Employee seed skipped"
php yii seed/site 12 1 || echo "Site seed skipped"
php yii seed/task 50 1 || echo "Task seed skipped"


# Create admin user
echo "Creating admin user..."
php yii user/create-admin || echo "Admin already exists"

# Start php-fpm
echo "=== Init finished. Starting php-fpm ==="
exec php-fpm -F

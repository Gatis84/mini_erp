#!/bin/bash
sleep 30  # Wait for SQL Server startup
/opt/mssql-tools/bin/sqlcmd -S localhost -U sa -P 'StrongPassword123!' -Q 'CREATE DATABASE worksys_db'
exec docker-entrypoint.sh "$@"
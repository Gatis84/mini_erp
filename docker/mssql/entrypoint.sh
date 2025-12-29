#!/bin/bash

# Start SQL Server in the background
/opt/mssql/bin/sqlservr &
SERVER_PID=$!

SA_PASSWORD="${MSSQL_SA_PASSWORD:-StrongPassword123!}"

echo "Waiting for SQL Server to start..."
for i in {1..60}; do
    /opt/mssql-tools18/bin/sqlcmd -S localhost -U sa -P "$SA_PASSWORD" -C -Q "SELECT 1" &>/dev/null
    if [ $? -eq 0 ]; then
        echo "SQL Server is ready."
        break
    fi
    echo "Attempt $i/60..."
    sleep 1
done

# Run init scripts from /docker-entrypoint-initdb.d/
if [ -d /docker-entrypoint-initdb.d ]; then
    echo "Running init scripts..."
    for f in /docker-entrypoint-initdb.d/*.sql; do
        if [ -f "$f" ]; then
            echo "Executing $f"
            /opt/mssql-tools18/bin/sqlcmd -S localhost -U sa -P "$SA_PASSWORD" -C -i "$f"
        fi
    done
fi

echo "Init complete. SQL Server running."

# Keep SQL Server running
wait $SERVER_PID

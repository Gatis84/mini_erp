FROM mcr.microsoft.com/mssql/server:2022-latest

COPY entrypoint.sh /usr/local/bin/entrypoint.sh

# Set the custom entrypoint
ENTRYPOINT ["/bin/bash", "/usr/local/bin/entrypoint.sh"]

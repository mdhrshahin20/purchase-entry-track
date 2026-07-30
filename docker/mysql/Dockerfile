FROM mysql:8.0

# Bake schema into the image so Docker Desktop does not need to bind-mount
# host paths under /Applications (often not in File Sharing).
COPY database/purchase_entry.sql /docker-entrypoint-initdb.d/01-schema.sql

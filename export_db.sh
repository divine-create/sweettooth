#!/bin/bash

# Export MySQL database to SQL file
DB_NAME="sweettooth"
DB_USER="root"
DB_PASSWORD="root"
OUTPUT_FILE="new_db.sql"

echo "Exporting database '$DB_NAME' to '$OUTPUT_FILE'..."

mysqldump -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" > "$OUTPUT_FILE"

if [ $? -eq 0 ]; then
    echo "Database exported successfully to $OUTPUT_FILE"
else
    echo "Error occurred during export"
    exit 1
fi
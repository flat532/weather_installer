#!/bin/bash

ARCHIVE_DIR="__INSTALL_PATH__/archive"
OLD_DIR="$ARCHIVE_DIR/old"
ARCHIVE_FILE="old.tar"

if [ ! -d "$OLD_DIR" ]; then
    mkdir -p "$OLD_DIR"
fi

file_count=$(find "$ARCHIVE_DIR" -maxdepth 1 -type f -mmin +1440 | wc -l)

if [ $file_count -eq 0 ]; then
    echo "Brak plików starszych niż 24 godziny w $ARCHIVE_DIR"
else
    find "$ARCHIVE_DIR" -maxdepth 1 -type f -mmin +1440 -exec mv "{}" "$OLD_DIR" \;

    if [ -f "$OLD_DIR/$ARCHIVE_FILE" ]; then
        find "$OLD_DIR" -type f ! -name "$ARCHIVE_FILE" -exec tar --append -f "$OLD_DIR/$ARCHIVE_FILE" "{}" \; -exec rm "{}" \;
    else
        tar -cvf "$OLD_DIR/$ARCHIVE_FILE" -C "$OLD_DIR" --exclude "$ARCHIVE_FILE" .
        find "$OLD_DIR" -type f ! -name "$ARCHIVE_FILE" -exec rm "{}" \;
    fi

    echo "Zarchiwizowano $file_count plików do $OLD_DIR/$ARCHIVE_FILE"
fi

#!/bin/bash
# sort-json.sh
# Sort all JSON files in a given directory using jq

# Exit immediately if a command fails
set -e

# Check if directory argument is given
if [ -z "$1" ]; then
  echo "Usage: $0 <directory>"
  exit 1
fi

DIR="$1"

# Check if jq is installed
if ! command -v jq &>/dev/null; then
  echo "Error: jq is not installed. Please install it first."
  exit 1
fi

# Loop through all .json files in the directory
for file in "$DIR"/*.json; do
  # Skip if no json files are found
  [ -e "$file" ] || continue

  echo "Sorting $file ..."
  tmpfile=$(mktemp)
  jq -S . "$file" > "$tmpfile" && mv "$tmpfile" "$file"
done

echo "✅ All JSON files in '$DIR' have been sorted."

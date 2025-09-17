#!/usr/bin/env bash

# Organizr Linux Update Script
# Docker-compatible automated update script

set -euo pipefail

# Configuration
GITHUB_REPO="${GITHUB_REPO:-metalcated/Organizr}"

# Determine branch
if [ -z "${1:-}" ]; then
    echo "No branch specified, using v2-master"
    BRANCH="v2-master"
elif [ "$1" == "v2-develop" ] || [ "$1" == "develop" ] || [ "$1" == "dev" ]; then
    BRANCH="v2-develop"
elif [ "$1" == "v2-master" ] || [ "$1" == "master" ]; then
    BRANCH="v2-master"
else
    echo "$1 is not a valid branch, exiting"
    exit 1
fi

# Setup paths
SCRIPTPATH="$(cd -- "$(dirname "$0")" >/dev/null 2>&1 ; pwd -P)"
UPGRADEPATH="$SCRIPTPATH/upgrade"
UPGRADEFILE="$UPGRADEPATH/upgrade.zip"
FOLDER="$UPGRADEPATH/Organizr-${BRANCH#v}"
URL="https://github.com/$GITHUB_REPO/archive/${BRANCH}.zip"

echo "Updating Organizr from $GITHUB_REPO:$BRANCH"

# Cleanup function
cleanup() {
    rm -rf "$UPGRADEPATH" 2>/dev/null || true
}
trap cleanup EXIT

# Create upgrade directory
mkdir -p "$UPGRADEPATH"

# Download with error handling
echo "Downloading update..."
if ! curl -sSL --fail --connect-timeout 30 "$URL" -o "$UPGRADEFILE"; then
    echo "Error: Failed to download update from $URL"
    exit 1
fi

# Extract with error handling  
echo "Extracting files..."
if ! unzip -q "$UPGRADEFILE" -d "$UPGRADEPATH"; then
    echo "Error: Failed to extract update files"
    exit 1
fi

# Verify extraction
if [ ! -d "$FOLDER" ]; then
    echo "Error: Expected folder not found: $FOLDER"
    exit 1
fi

# Apply update
echo "Applying update..."
cd "$FOLDER"
cp -r ./* "$SCRIPTPATH/../"

# Cleanup is handled by trap
echo "Update completed successfully"

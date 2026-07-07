#!/usr/bin/env bash
set -euo pipefail

# Usage: ./svn-publish.sh <version> [slug]
# Example: ./svn-publish.sh 1.0.0 login-rate-limit

VERSION=${1:-}
SLUG=${2:-login-rate-limit}

if [ -z "$VERSION" ]; then
  echo "Usage: $0 <version> [slug]"
  exit 1
fi

ZIPFILE="login-rate-limit-${VERSION}.zip"
if [ ! -f "$ZIPFILE" ]; then
  echo "ZIP $ZIPFILE not found — building with make-release.sh"
  ./make-release.sh "$VERSION"
fi

SVN_URL="https://plugins.svn.wordpress.org/${SLUG}"
SVN_DIR=$(mktemp -d)

echo "Prepare SVN checkout in $SVN_DIR"

# Read credentials from env or prompt
SVN_USER=${SVN_USER:-}
if [ -z "$SVN_USER" ]; then
  read -rp "SVN username: " SVN_USER
fi

echo "Checking out SVN trunk from $SVN_URL ..."
# Do not pass password on the command line; let svn prompt or use cached credentials.
svn checkout "$SVN_URL" "$SVN_DIR" --username "$SVN_USER" --non-interactive --trust-server-cert --no-auth-cache

echo "Cleaning trunk and copying release contents..."
rm -rf "$SVN_DIR/trunk"/*

tmp_unpack=$(mktemp -d)
unzip -q "$ZIPFILE" -d "$tmp_unpack"
# The zip contains a directory login-rate-limit-<version>/, copy its contents
RELEASE_DIR=$(find "$tmp_unpack" -maxdepth 1 -type d -name "${SLUG}*" | head -n1)
if [ -z "$RELEASE_DIR" ]; then
  # Fallback: if zip directly contains files
  RELEASE_DIR="$tmp_unpack"
fi

rsync -a --delete \
  --exclude='.git' \
  --exclude='.github' \
  --exclude='tests' \
  --exclude='phpcs.xml.dist' \
  --exclude='composer.json' \
  --exclude='composer.lock' \
  --exclude='README.md' \
  --exclude='*.sqlite' \
  --exclude='composer-setup.php' \
  --exclude='make-release.sh' \
  --exclude='phpunit.xml' \
  --exclude='phpunit.xml.dist' \
  --exclude='.gitignore' \
  --exclude='.gitattributes' \
  --exclude='.vscode' \
  --exclude='.idea' \
  --exclude='.env' \
  --exclude='.travis.yml' \
  --exclude='*.log' \
  --exclude='*.zip' \
  "$RELEASE_DIR/" "$SVN_DIR/trunk/"

pushd "$SVN_DIR/trunk" > /dev/null
# Add new files
svn add --force . >/dev/null || true
# Remove deleted files
svn status | awk '/^!/{print $2}' | xargs -r svn rm || true
popd > /dev/null

echo "Committing trunk..."
svn commit "$SVN_DIR/trunk" -m "Release ${VERSION}" --username "$SVN_USER" --non-interactive --trust-server-cert --no-auth-cache

echo "Tagging release as tags/${VERSION}"
svn copy "$SVN_URL/trunk" "$SVN_URL/tags/${VERSION}" -m "Tag ${VERSION}" --username "$SVN_USER" --non-interactive --trust-server-cert --no-auth-cache

echo "Cleaning up"
rm -rf "$SVN_DIR" "$tmp_unpack"

echo "Done. Plugin published to WordPress.org (version: ${VERSION})."

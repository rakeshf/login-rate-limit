#!/usr/bin/env bash
set -euo pipefail

# Usage: ./make-release.sh [version]
# If version is not provided, script uses git short hash as version.

VERSION=${1:-$(git rev-parse --short HEAD)}
RELEASE_NAME="login-rate-limit-${VERSION}"
TMPDIR=$(mktemp -d)
OUTFILE="${RELEASE_NAME}.zip"

echo "Building release ${OUTFILE} ..."

# Ensure vendor is present and production deps installed
composer install --no-dev --prefer-dist --optimize-autoloader

# Copy repository to temp dir, excluding dev/CI/config files not needed in ZIP
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
  --exclude='svn-publish.sh' \
  --exclude='*.zip' \
  --exclude='*.log' \
  ./ "$TMPDIR/$RELEASE_NAME/"

# Ensure readme.txt exists for WordPress.org
if [ ! -f "$TMPDIR/$RELEASE_NAME/readme.txt" ]; then
  cp readme.txt "$TMPDIR/$RELEASE_NAME/readme.txt" || true
fi

# Remove any stray files that slipped through
rm -f "$TMPDIR/$RELEASE_NAME/composer-setup.php" || true
rm -f "$TMPDIR/$RELEASE_NAME/make-release.sh" || true
rm -f "$TMPDIR/$RELEASE_NAME/phpunit.xml" || true
rm -f "$TMPDIR/$RELEASE_NAME/phpunit.xml.dist" || true
rm -f "$TMPDIR/$RELEASE_NAME/.gitignore" || true
rm -f "$TMPDIR/$RELEASE_NAME/.gitattributes" || true

# Create zip
pushd "$TMPDIR" > /dev/null
zip -r ../"$OUTFILE" "$RELEASE_NAME"
popd > /dev/null

# Move zip to project root
mv "$TMPDIR/../$OUTFILE" . || true

# Clean up
rm -rf "$TMPDIR"

echo "Created $OUTFILE"

echo "Next: upload $OUTFILE to WordPress.org SVN or import its contents into your SVN trunk/tags." 

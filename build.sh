#!/bin/bash

VERSION=${1:-dev}
BUILD_DIR="build"
PLUGIN_NAME="pldb"
SOURCE_DIR="wp-plugin"

echo "Building $PLUGIN_NAME version $VERSION..."

# Clean up old build
rm -rf $BUILD_DIR
mkdir -p $BUILD_DIR/$PLUGIN_NAME

# Copy plugin files
echo "Copying plugin files..."
rsync -av --exclude='.git*' \
          --exclude='*.md' \
          --exclude='node_modules' \
          --exclude='.DS_Store' \
          $SOURCE_DIR/ $BUILD_DIR/$PLUGIN_NAME/

# Create zip
echo "Creating zip archive..."
cd $BUILD_DIR
zip -r ../$PLUGIN_NAME-$VERSION.zip $PLUGIN_NAME
cd ..

echo ""
echo "Build complete!"
echo "File: $PLUGIN_NAME-$VERSION.zip"
echo ""
echo "To install:"
echo "  1. Upload zip to WordPress"
echo "  2. Or: unzip -d /path/to/wordpress/wp-content/plugins/"

#!/bin/bash

# Script to create a WordPress plugin zip file
# This will package all essential files for the okzea-chatbot plugin

echo "Creating WordPress plugin zip file..."

# Make sure the dist directory exists
if [ ! -d "dist" ]; then
  echo "Error: dist directory not found. Run 'gulp build' first."
  exit 1
fi

# Remove previous zip if it exists
if [ -f "okzea-chatbot.zip" ]; then
  echo "Removing previous zip file..."
  rm okzea-chatbot.zip
fi

# Create temporary directory
echo "Creating temporary directory..."
mkdir -p temp-plugin-dir

# Copy essential files
echo "Copying plugin files..."
cp -r dist temp-plugin-dir/
cp -r templates temp-plugin-dir/
cp -r images temp-plugin-dir/ 2>/dev/null || echo "No images directory, skipping..."
cp *.php temp-plugin-dir/

# Create zip file
echo "Creating zip file..."
cd temp-plugin-dir
zip -r ../okzea-chatbot.zip ./*
cd ..

# Clean up
echo "Cleaning up..."
rm -rf temp-plugin-dir

echo "Plugin zip created successfully: okzea-chatbot.zip" 

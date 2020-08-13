#!/bin/bash
ZIP=/usr/bin/zip

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )" #directory of the current script

source $DIR/package_config.sh

rm -rf $DIR/$FILENAME

cd $DIR
rm -rf package
mkdir -p package/copy_this
cd package

echo "Copying files..."
cp -r ../productive copy_this/$FOLDERNAME

echo "Compressing to $FILENAME..."
$ZIP -r -9 -q ../$FILENAME *
cd ..
rm -r package/

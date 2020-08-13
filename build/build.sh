#!/bin/bash
YUICOMPRESSOR="/usr/local/bin/yuicompressor"

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )" #directory of the current script
cd build

rm -rf tmp productive

mkdir tmp
cd tmp

echo "Retrieving code from GIT repo..."
git clone --quiet ../.. .
git submodule update --init --quiet
rm -rf .git .gitignore .gitmodules *.sublime-project *.sublime-workspace build/ inc/lib/.git
rm -rf encoder/encrypted_5 encoder/encrypted_5_7.0 encoder/encrypted_53 encoder/encrypted_53_7.0 encoder/encrypted_54

# copy to resolve all symlinks
cd $DIR
cp -r tmp tmp2
rm -r tmp
mv tmp2 tmp

echo "remove LESS, SASS and CoffeeScript files"
cd $DIR/tmp
find . -name "*.less" | xargs rm
find . -name "less" | xargs rmdir
find . -name "*.sass" | xargs rm
find . -name "*.scss" | xargs rm
find . -name "*.coffee" | xargs rm

echo "Compressing custom JavaScript Code"
cd $DIR/tmp
# combine all coffeescripts into one js file
ls -1 assets/js/src/*.js | grep -v .min.js | xargs cat > main.js
# add noty_config.js to the main.js
cat assets/js/noty_config.js main.js | grep -v "console.log" | grep -v CoffeeScript > main2.js
$YUICOMPRESSOR -o assets/js/main.min.js main2.js
rm -r assets/js/src
rm main.js main2.js
rm assets/js/noty_config.js


cd $DIR/tmp/assets
echo "Compressing jQuery Plugins JavaScript Code"
# combine almost all jQyery plugins into one file
ls -1 js/jquery.*.js | grep -v .min.js | xargs cat > js/jquery_plugins.js
ls -1 js/jquery.*.js | grep -v .min.js | xargs rm
$YUICOMPRESSOR --nomunge -o js/jquery_plugins.min.js js/jquery_plugins.js &

if [ -d datatables/ ]; then
  $YUICOMPRESSOR --nomunge -o datatables/media/js/jquery.dataTables.min.js datatables/media/js/jquery.dataTables.js &
  $YUICOMPRESSOR --nomunge -o datatables/media/js/DT_bootstrap.min.js datatables/media/js/DT_bootstrap.js &
fi
wait

rm js/jquery_plugins.js
rm -f datatables/media/js/jquery.dataTables.js datatables/media/js/DT_bootstrap.js

cd $DIR/tmp/assets/css
echo "Compressing custom CSS Code"
$YUICOMPRESSOR -o styles.min.css styles.css
rm styles.css

echo "Compressing jQuery Plugins CSS Code"
# combine almost all jQuery plugins into one file + font_awesome + demo_table (comes from the datatables)
ls -1 jquery.*.css font-awesome.css | grep -v .min.css | xargs cat > jquery_plugins_and_other.css
ls -1 jquery.*.css font-awesome.css | grep -v .min.css | xargs rm
$YUICOMPRESSOR -o jquery_plugins_and_other.min.css jquery_plugins_and_other.css
rm jquery_plugins_and_other.css

echo "Compressing Bootstrap CSS Code"
cd $DIR/tmp/assets/bootstrap/css
$YUICOMPRESSOR -o bootstrap.min.css bootstrap.css
rm bootstrap.css
rm -r ../img

# delete documentation generator config files, as well as the documentation itself
cd $DIR/tmp
rm -rf doc phpdoc.xml .codoopts

# copy default config file
cp $DIR/config.php .

php $DIR/build.php
mv index.new.php index.php
# cp $DIR/config.inc.php inc/config.inc.php

cd $DIR
mv tmp productive

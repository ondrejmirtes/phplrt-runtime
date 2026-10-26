#!/bin/bash
# Rebuilds the downgraded phplrt runtime fork from upstream phplrt/phplrt sources.
set -euo pipefail
UP=${1:?upstream phplrt/phplrt checkout}; OUT=${2:?output dir}; HERE=$(cd "$(dirname "$0")" && pwd)
VERSION=$(cd "$UP" && git describe --tags 2>/dev/null || echo 4.2.4)
rm -rf "$OUT"; mkdir -p "$OUT/src/Contracts"; cd "$OUT"
L=$UP/libs
cp -r $L/contracts/lexer/src src/Contracts/Lexer
cp -r $L/contracts/parser/src src/Contracts/Parser
mkdir -p src/Contracts/Position src/Contracts/Source
cp -r $L/contracts/position/src/. $L/contracts/position-factory/src/. src/Contracts/Position/
cp -r $L/contracts/source/src/. $L/contracts/source-factory/src/. src/Contracts/Source/
cp -r $L/components/lexer/src src/Lexer
cp -r $L/components/parser/src src/Parser
cp -r $L/components/source/src src/Source
cp -r $L/components/position/src src/Position
find src -name preload.php -delete; rm src/Source/polyfill.php
cp $UP/LICENSE.md .
cp "$HERE/composer.json" .
git init -q -b main
git add -A && git -c user.name="Ondrej Mirtes" -c user.email=ondrej@mirtes.cz commit -qm "phplrt runtime $VERSION sources (lexer, parser, source, position and their contracts)"
python3 "$HERE/manual.py" "$HERE"
git add -A && git -c user.name="Ondrej Mirtes" -c user.email=ondrej@mirtes.cz commit -qm "Manual downgrade to PHP 7.4 (enums, WeakMap, new in static initializer)"
sed "s#/tmp/fork/repo#$OUT#" "$HERE/rector.php" > /tmp/forkbuild-rector.php
cat > /tmp/forkbuild-autoload.php <<PHP
<?php spl_autoload_register(function (\$c) { if (strncmp(\$c, 'Phplrt\\\\', 7) === 0) { \$f = '$OUT/src/' . str_replace('\\\\', '/', substr(\$c, 7)) . '.php'; if (is_file(\$f)) require \$f; } });
PHP
"$HERE/tools/vendor/bin/rector" process --no-progress-bar --config=/tmp/forkbuild-rector.php --autoload-file=/tmp/forkbuild-autoload.php > /dev/null
git add -A && git -c user.name="Ondrej Mirtes" -c user.email=ondrej@mirtes.cz commit -qm "Downgrade to PHP 7.4 with Rector (DowngradeLevelSetList::DOWN_TO_PHP_74)"
for f in $(find src -name '*.php'); do php7.4 -l "$f" > /dev/null; done
mkdir -p build/tools
cp "$HERE"/{build.sh,manual.py,Channel.php,Mode.php,rector.php,composer.json} build/
cp "$HERE/tools/composer.json" "$HERE/tools/composer.lock" build/tools/
cp "$HERE/README.md" .
printf '/build/tools/vendor\n' > .gitignore; printf '/build export-ignore\n' > .gitattributes
git add -A && git -c user.name="Ondrej Mirtes" -c user.email=ondrej@mirtes.cz commit -qm "Add the tooling this fork is built with"
echo "built $OUT"

# phplrt runtime for PHP 7.4

The runtime half of [phplrt](https://github.com/phplrt/phplrt) (lexer, parser,
source, position and their contracts), downgraded to PHP 7.4 so that
[PHPStan](https://github.com/phpstan/phpstan-src) can run a parser generated
by the phplrt compiler everywhere PHPStan runs.

It replaces `phplrt/runtime` and the packages it is made of. Only the
runtime is here: grammars are compiled with upstream `phplrt/compiler`
on a recent PHP.

## How it is built

Every release is rebuilt from an upstream phplrt tag:

```bash
composer install --working-dir build/tools
git clone --branch 4.2.4 https://github.com/phplrt/phplrt.git /tmp/phplrt
build/build.sh /tmp/phplrt /tmp/phplrt-runtime
```

which creates these commits:

1. the upstream sources, copied verbatim,
2. manual changes Rector cannot do (`build/manual.py`): enums become
   singleton classes (`Channel::Hidden()`) and string constants,
   `WeakMap` becomes `SplObjectStorage`, and so on,
3. `DowngradeLevelSetList::DOWN_TO_PHP_74` applied by Rector,
4. this build tooling.

Versions follow upstream with a fourth number: `4.2.4.0` is upstream `4.2.4`.

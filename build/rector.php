<?php
use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\DowngradeLevelSetList;
return RectorConfig::configure()
    ->withPaths(['/tmp/fork/repo/src'])
    ->withSets([DowngradeLevelSetList::DOWN_TO_PHP_74])
    ->withAutoloadPaths(['/tmp/fork/repo/src']);

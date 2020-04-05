<?php
namespace phpamqp;

use DateTimeImmutable;

require_once __DIR__ . '/functions.php';

$nextVersion = $_SERVER['argv'][1];

assert(preg_match(re(VERSION_REGEX), $nextVersion));

archiveRelease();
setPackageVersion($nextVersion);
setSourceVersion($nextVersion);
setStability($nextVersion);
setDate(new DateTimeImmutable('NOW'));
setChangelog(buildChangelog('master', versionToTag(getPreviousVersion())));
savePackageXml();
validatePackage();
gitCommit(1, $nextVersion, 'back to dev');
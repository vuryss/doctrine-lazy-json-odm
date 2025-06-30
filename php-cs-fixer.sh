#!/bin/bash

export PHP_CS_FIXER_IGNORE_ENV=1

/library/vendor/bin/php-cs-fixer fix --dry-run --diff -v
exitCode=$?
/library/vendor/bin/php-cs-fixer fix -q
exit $exitCode

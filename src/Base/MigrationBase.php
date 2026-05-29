<?php

namespace Obelaw\Ium\Url\Base;

use Obelaw\Ium\Bases\MigrationBase as StackMigrationBase;

abstract class MigrationBase extends StackMigrationBase
{
    /**
     * Table postfix.
     *
     * @var string|null $module
     */
    protected ?string $module = 'url_';
}

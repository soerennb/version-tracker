<?php

namespace App\Mcp\Tools;

use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class ShowVersionTool extends ContentTool
{
    protected string $entity = 'versions';

    protected string $operation = 'show';

    protected string $name = 'show_versions';

    protected string $description = 'Show versions using application validation and permissions.';
}

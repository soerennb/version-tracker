<?php

namespace App\Mcp\Tools;

use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class ListVersionTool extends ContentTool
{
    protected string $entity = 'versions';

    protected string $operation = 'list';

    protected string $name = 'list_versions';

    protected string $description = 'List versions using application validation and permissions.';
}

<?php

namespace App\Mcp\Tools;

use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive]
class DeleteVersionTool extends ContentTool
{
    protected string $entity = 'versions';

    protected string $operation = 'delete';

    protected string $name = 'delete_versions';

    protected string $description = 'Delete versions using application validation and permissions.';
}

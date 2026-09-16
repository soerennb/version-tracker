<?php

namespace App\Mcp\Tools;

use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive]
class DeleteSoftwareTool extends ContentTool
{
    protected string $entity = 'software';

    protected string $operation = 'delete';

    protected string $name = 'delete_software';

    protected string $description = 'Delete software using application validation and permissions.';
}

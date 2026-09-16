<?php

namespace App\Mcp\Tools;

use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class ListSoftwareTool extends ContentTool
{
    protected string $entity = 'software';

    protected string $operation = 'list';

    protected string $name = 'list_software';

    protected string $description = 'List software using application validation and permissions.';
}

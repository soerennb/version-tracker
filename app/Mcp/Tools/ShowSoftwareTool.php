<?php

namespace App\Mcp\Tools;

use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class ShowSoftwareTool extends ContentTool
{
    protected string $entity = 'software';

    protected string $operation = 'show';

    protected string $name = 'show_software';

    protected string $description = 'Show software using application validation and permissions.';
}

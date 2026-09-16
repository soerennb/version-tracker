<?php

namespace App\Mcp\Tools;

use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class ShowSoftwareDependencyTool extends ContentTool
{
    protected string $entity = 'software_dependencies';

    protected string $operation = 'show';

    protected string $name = 'show_software_dependencies';

    protected string $description = 'Show software dependencies using application validation and permissions.';
}

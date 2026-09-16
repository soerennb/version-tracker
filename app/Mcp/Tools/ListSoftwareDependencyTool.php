<?php

namespace App\Mcp\Tools;

use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class ListSoftwareDependencyTool extends ContentTool
{
    protected string $entity = 'software_dependencies';

    protected string $operation = 'list';

    protected string $name = 'list_software_dependencies';

    protected string $description = 'List software dependencies using application validation and permissions.';
}

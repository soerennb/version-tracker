<?php

namespace App\Mcp\Tools;

use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive]
class DeleteSoftwareDependencyTool extends ContentTool
{
    protected string $entity = 'software_dependencies';

    protected string $operation = 'delete';

    protected string $name = 'delete_software_dependencies';

    protected string $description = 'Delete software dependencies using application validation and permissions.';
}

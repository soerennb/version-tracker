<?php

namespace App\Mcp\Tools;

class UpdateSoftwareDependencyTool extends ContentTool
{
    protected string $entity = 'software_dependencies';

    protected string $operation = 'update';

    protected string $name = 'update_software_dependencies';

    protected string $description = 'Update software dependencies using application validation and permissions.';
}

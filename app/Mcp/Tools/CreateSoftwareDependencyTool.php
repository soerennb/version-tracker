<?php

namespace App\Mcp\Tools;

class CreateSoftwareDependencyTool extends ContentTool
{
    protected string $entity = 'software_dependencies';

    protected string $operation = 'create';

    protected string $name = 'create_software_dependencies';

    protected string $description = 'Create software dependencies using application validation and permissions.';
}

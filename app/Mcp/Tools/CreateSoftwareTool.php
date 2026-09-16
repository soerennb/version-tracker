<?php

namespace App\Mcp\Tools;

class CreateSoftwareTool extends ContentTool
{
    protected string $entity = 'software';

    protected string $operation = 'create';

    protected string $name = 'create_software';

    protected string $description = 'Create software using application validation and permissions.';
}

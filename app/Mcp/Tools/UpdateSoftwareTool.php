<?php

namespace App\Mcp\Tools;

class UpdateSoftwareTool extends ContentTool
{
    protected string $entity = 'software';

    protected string $operation = 'update';

    protected string $name = 'update_software';

    protected string $description = 'Update software using application validation and permissions.';
}

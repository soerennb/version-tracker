<?php

namespace App\Mcp\Tools;

class CreateVersionTool extends ContentTool
{
    protected string $entity = 'versions';

    protected string $operation = 'create';

    protected string $name = 'create_versions';

    protected string $description = 'Create versions using application validation and permissions.';
}

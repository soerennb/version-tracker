<?php

namespace App\Mcp\Tools;

class UpdateVersionTool extends ContentTool
{
    protected string $entity = 'versions';

    protected string $operation = 'update';

    protected string $name = 'update_versions';

    protected string $description = 'Update versions using application validation and permissions.';
}

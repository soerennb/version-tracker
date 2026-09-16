<?php

namespace App\Mcp\Tools;

class CreateTextContentTool extends ContentTool
{
    protected string $entity = 'text_contents';

    protected string $operation = 'create';

    protected string $name = 'create_text_contents';

    protected string $description = 'Create text contents using application validation and permissions.';
}

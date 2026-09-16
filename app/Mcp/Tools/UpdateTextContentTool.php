<?php

namespace App\Mcp\Tools;

class UpdateTextContentTool extends ContentTool
{
    protected string $entity = 'text_contents';

    protected string $operation = 'update';

    protected string $name = 'update_text_contents';

    protected string $description = 'Update text contents using application validation and permissions.';
}

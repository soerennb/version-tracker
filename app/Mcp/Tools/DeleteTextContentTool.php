<?php

namespace App\Mcp\Tools;

use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive]
class DeleteTextContentTool extends ContentTool
{
    protected string $entity = 'text_contents';

    protected string $operation = 'delete';

    protected string $name = 'delete_text_contents';

    protected string $description = 'Delete text contents using application validation and permissions.';
}

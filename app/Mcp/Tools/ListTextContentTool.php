<?php

namespace App\Mcp\Tools;

use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class ListTextContentTool extends ContentTool
{
    protected string $entity = 'text_contents';

    protected string $operation = 'list';

    protected string $name = 'list_text_contents';

    protected string $description = 'List text contents using application validation and permissions.';
}

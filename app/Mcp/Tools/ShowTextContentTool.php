<?php

namespace App\Mcp\Tools;

use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class ShowTextContentTool extends ContentTool
{
    protected string $entity = 'text_contents';

    protected string $operation = 'show';

    protected string $name = 'show_text_contents';

    protected string $description = 'Show text contents using application validation and permissions.';
}

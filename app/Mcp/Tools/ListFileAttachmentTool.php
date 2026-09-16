<?php

namespace App\Mcp\Tools;

use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class ListFileAttachmentTool extends ContentTool
{
    protected string $entity = 'attachments';

    protected string $operation = 'list';

    protected string $name = 'list_attachments';

    protected string $description = 'List attachments using application validation and permissions.';
}

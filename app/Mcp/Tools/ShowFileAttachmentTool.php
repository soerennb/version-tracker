<?php

namespace App\Mcp\Tools;

use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class ShowFileAttachmentTool extends ContentTool
{
    protected string $entity = 'attachments';

    protected string $operation = 'show';

    protected string $name = 'show_attachments';

    protected string $description = 'Show attachments using application validation and permissions.';
}

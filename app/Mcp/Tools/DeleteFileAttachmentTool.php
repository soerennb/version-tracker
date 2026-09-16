<?php

namespace App\Mcp\Tools;

use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive]
class DeleteFileAttachmentTool extends ContentTool
{
    protected string $entity = 'attachments';

    protected string $operation = 'delete';

    protected string $name = 'delete_attachments';

    protected string $description = 'Delete attachments using application validation and permissions.';
}

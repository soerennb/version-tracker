<?php

namespace App\Mcp\Tools;

class UpdateFileAttachmentTool extends ContentTool
{
    protected string $entity = 'attachments';

    protected string $operation = 'update';

    protected string $name = 'update_attachments';

    protected string $description = 'Update attachments using application validation and permissions.';
}

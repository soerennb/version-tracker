<?php

namespace App\Mcp\Tools;

class ApproveVersionTool extends ContentTool
{
    protected string $entity = 'versions';

    protected string $operation = 'approve';

    protected string $name = 'approve_version';

    protected string $description = 'Approve a version using existing governance rules.';
}

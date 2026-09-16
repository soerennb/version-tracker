<?php

namespace App\Mcp\Tools;

class RejectVersionTool extends ContentTool
{
    protected string $entity = 'versions';

    protected string $operation = 'reject';

    protected string $name = 'reject_version';

    protected string $description = 'Reject a version using existing governance rules.';
}

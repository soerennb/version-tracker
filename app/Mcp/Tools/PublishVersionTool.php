<?php

namespace App\Mcp\Tools;

class PublishVersionTool extends ContentTool
{
    protected string $entity = 'versions';

    protected string $operation = 'publish';

    protected string $name = 'publish_version';

    protected string $description = 'Publish a version using existing governance rules.';
}

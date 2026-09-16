<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\ApproveVersionTool;
use App\Mcp\Tools\CompleteAttachmentUploadTool;
use App\Mcp\Tools\CreateSoftwareDependencyTool;
use App\Mcp\Tools\CreateSoftwareTool;
use App\Mcp\Tools\CreateTextContentTool;
use App\Mcp\Tools\CreateVersionTool;
use App\Mcp\Tools\CreateVulnerabilityTool;
use App\Mcp\Tools\DeleteFileAttachmentTool;
use App\Mcp\Tools\DeleteSoftwareDependencyTool;
use App\Mcp\Tools\DeleteSoftwareTool;
use App\Mcp\Tools\DeleteTextContentTool;
use App\Mcp\Tools\DeleteVersionTool;
use App\Mcp\Tools\DeleteVulnerabilityTool;
use App\Mcp\Tools\ListFileAttachmentTool;
use App\Mcp\Tools\ListSoftwareDependencyTool;
use App\Mcp\Tools\ListSoftwareTool;
use App\Mcp\Tools\ListTextContentTool;
use App\Mcp\Tools\ListVersionTool;
use App\Mcp\Tools\ListVulnerabilityTool;
use App\Mcp\Tools\PrepareAttachmentUploadTool;
use App\Mcp\Tools\PublishVersionTool;
use App\Mcp\Tools\RejectVersionTool;
use App\Mcp\Tools\ShowFileAttachmentTool;
use App\Mcp\Tools\ShowSoftwareDependencyTool;
use App\Mcp\Tools\ShowSoftwareTool;
use App\Mcp\Tools\ShowTextContentTool;
use App\Mcp\Tools\ShowVersionTool;
use App\Mcp\Tools\ShowVulnerabilityTool;
use App\Mcp\Tools\UpdateFileAttachmentTool;
use App\Mcp\Tools\UpdateSoftwareDependencyTool;
use App\Mcp\Tools\UpdateSoftwareTool;
use App\Mcp\Tools\UpdateTextContentTool;
use App\Mcp\Tools\UpdateVersionTool;
use App\Mcp\Tools\UpdateVulnerabilityTool;
use Laravel\Mcp\Server;

class VersionTrackerServer extends Server
{
    protected string $name = 'VersionTracker';

    protected string $version = '1.0.0';

    protected string $instructions = 'Manage software, versions, release texts, dependencies, vulnerabilities and attachments. Find records before modifying them. New versions are drafts. Approval and publication require separate tools and permissions. Upload files by preparing an upload, sending multipart HTTP with the same bearer token, then completing it. Never place tokens into tool arguments.';

    protected array $tools = [
        ListSoftwareTool::class,
        ShowSoftwareTool::class,
        CreateSoftwareTool::class,
        UpdateSoftwareTool::class,
        DeleteSoftwareTool::class,
        ListVersionTool::class,
        ShowVersionTool::class,
        CreateVersionTool::class,
        UpdateVersionTool::class,
        DeleteVersionTool::class,
        ListTextContentTool::class,
        ShowTextContentTool::class,
        CreateTextContentTool::class,
        UpdateTextContentTool::class,
        DeleteTextContentTool::class,
        ListSoftwareDependencyTool::class,
        ShowSoftwareDependencyTool::class,
        CreateSoftwareDependencyTool::class,
        UpdateSoftwareDependencyTool::class,
        DeleteSoftwareDependencyTool::class,
        ListVulnerabilityTool::class,
        ShowVulnerabilityTool::class,
        CreateVulnerabilityTool::class,
        UpdateVulnerabilityTool::class,
        DeleteVulnerabilityTool::class,
        ListFileAttachmentTool::class,
        ShowFileAttachmentTool::class,
        UpdateFileAttachmentTool::class,
        DeleteFileAttachmentTool::class,
        ApproveVersionTool::class,
        RejectVersionTool::class,
        PublishVersionTool::class,
        PrepareAttachmentUploadTool::class,
        CompleteAttachmentUploadTool::class,
    ];
}

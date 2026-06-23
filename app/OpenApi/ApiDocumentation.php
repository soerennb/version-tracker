<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Versiontracker API',
    description: 'Public and authenticated API endpoints for software releases, security advisories, impact analysis, subscriptions, imports, and exports.'
)]
#[OA\Server(url: '/api', description: 'API base path')]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Sanctum'
)]
#[OA\Get(
    path: '/security',
    summary: 'Public security advisories page',
    tags: ['Public'],
    servers: [new OA\Server(url: '/', description: 'Web base path')],
    responses: [
        new OA\Response(response: 200, description: 'Published security advisories without internal workflow data.'),
    ]
)]
#[OA\Get(
    path: '/public/timeline',
    summary: 'Public release timeline',
    tags: ['Public'],
    parameters: [
        new OA\Parameter(name: 'q', in: 'query', required: false, schema: new OA\Schema(type: 'string', maxLength: 100)),
        new OA\Parameter(name: 'software', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'date_from', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        new OA\Parameter(name: 'date_to', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        new OA\Parameter(name: 'support', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['supported', 'maintenance', 'deprecated', 'eol'])),
        new OA\Parameter(name: 'security', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['clear', 'attention'])),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Published release timeline.'),
        new OA\Response(response: 422, description: 'Invalid filters.'),
    ]
)]
#[OA\Get(
    path: '/public/products',
    summary: 'List products with published releases',
    tags: ['Public'],
    responses: [
        new OA\Response(response: 200, description: 'Public product catalog with current release health.'),
    ]
)]
#[OA\Get(
    path: '/public/products/{software}',
    summary: 'Get a public product profile',
    tags: ['Public'],
    parameters: [
        new OA\Parameter(name: 'software', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Published releases, lifecycle, security, and attachment metadata.'),
        new OA\Response(response: 404, description: 'Product has no published release.'),
    ]
)]
#[OA\Get(
    path: '/public/releases/{version}',
    summary: 'Get a published release',
    tags: ['Public'],
    parameters: [
        new OA\Parameter(name: 'version', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Localized notes, attachments, advisories, and lifecycle metadata.'),
        new OA\Response(response: 404, description: 'Release is not published.'),
    ]
)]
#[OA\Get(
    path: '/public/compare',
    summary: 'Compare two published releases of one product',
    tags: ['Public'],
    parameters: [
        new OA\Parameter(name: 'left', in: 'query', required: true, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'right', in: 'query', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Notes, security, attachments, and dependency changes.'),
        new OA\Response(response: 404, description: 'Versions are not public or belong to different products.'),
        new OA\Response(response: 422, description: 'Invalid version selection.'),
    ]
)]
#[OA\Get(
    path: '/downloads/{version}/{fileAttachment}',
    summary: 'Download an attachment of a published release',
    tags: ['Public'],
    servers: [new OA\Server(url: '/', description: 'Web base path')],
    parameters: [
        new OA\Parameter(name: 'version', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'fileAttachment', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Attachment stream.'),
        new OA\Response(response: 404, description: 'Attachment is unavailable or not public.'),
    ]
)]
#[OA\Get(
    path: '/impact/software/{software}',
    summary: 'Impact analysis for software',
    security: [['sanctum' => []]],
    tags: ['Impact'],
    parameters: [
        new OA\Parameter(name: 'software', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Transitive affected software graph.'),
        new OA\Response(response: 403, description: 'Not authorized.'),
    ]
)]
#[OA\Get(
    path: '/impact/versions/{version}',
    summary: 'Impact analysis for a version',
    security: [['sanctum' => []]],
    tags: ['Impact'],
    parameters: [
        new OA\Parameter(name: 'version', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Affected software for this version.'),
        new OA\Response(response: 403, description: 'Not authorized.'),
    ]
)]
#[OA\Get(
    path: '/impact/vulnerabilities/{vulnerability}',
    summary: 'Impact analysis for a vulnerability',
    security: [['sanctum' => []]],
    tags: ['Impact'],
    parameters: [
        new OA\Parameter(name: 'vulnerability', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Affected software for this vulnerability.'),
        new OA\Response(response: 403, description: 'Not authorized.'),
    ]
)]
#[OA\Get(
    path: '/subscriptions',
    summary: 'List current user subscriptions',
    security: [['sanctum' => []]],
    tags: ['Subscriptions'],
    responses: [
        new OA\Response(response: 200, description: 'Subscription list scoped to authenticated user.'),
    ]
)]
#[OA\Post(
    path: '/subscriptions',
    summary: 'Create a software subscription',
    security: [['sanctum' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['software_id', 'event'],
            properties: [
                new OA\Property(property: 'software_id', type: 'integer'),
                new OA\Property(property: 'event', type: 'string', enum: ['all', 'release', 'security', 'eol']),
            ]
        )
    ),
    tags: ['Subscriptions'],
    responses: [
        new OA\Response(response: 201, description: 'Subscription created.'),
        new OA\Response(response: 200, description: 'Subscription already existed.'),
        new OA\Response(response: 422, description: 'Validation failed.'),
    ]
)]
#[OA\Delete(
    path: '/subscriptions/{subscription}',
    summary: 'Delete own subscription',
    security: [['sanctum' => []]],
    tags: ['Subscriptions'],
    parameters: [
        new OA\Parameter(name: 'subscription', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(response: 204, description: 'Subscription deleted.'),
        new OA\Response(response: 404, description: 'Subscription not found for current user.'),
    ]
)]
#[OA\Get(
    path: '/export/versions/csv',
    summary: 'Export versions as CSV',
    security: [['sanctum' => []]],
    tags: ['Exports'],
    parameters: [
        new OA\Parameter(name: 'software_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'date_from', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        new OA\Parameter(name: 'date_to', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        new OA\Parameter(name: 'approval_status', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        new OA\Parameter(name: 'security', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['with_vulnerabilities', 'without_vulnerabilities', 'open_high_critical'])),
        new OA\Parameter(name: 'compliance_status', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'CSV file download.'),
        new OA\Response(response: 403, description: 'Not authorized.'),
    ]
)]
#[OA\Get(
    path: '/export/versions/pdf',
    summary: 'Export versions as PDF',
    security: [['sanctum' => []]],
    tags: ['Exports'],
    responses: [
        new OA\Response(response: 200, description: 'PDF file download. Supports the same filters as CSV export.'),
        new OA\Response(response: 403, description: 'Not authorized.'),
    ]
)]
final class ApiDocumentation {}

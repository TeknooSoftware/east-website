<?php

/*
 * East Website.
 *
 * LICENSE
 *
 * This source file is subject to the 3-Clause BSD license
 * it is available in LICENSE file at the root of this package
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richard@teknoo.software so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 *
 * @link        https://teknoo.software/east-collection/website Project website
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\East\Website\Tools\Resource;

use function implode;

/**
 * Catalogue of the resources and of the public endpoints of the API. The commands, the options and the schema are
 * all generated from it.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class Registry
{
    /**
     * @return list<ResourceDefinition>
     */
    public function resources(): array
    {
        $all = [Operation::List, Operation::Get, Operation::Create, Operation::Update, Operation::Delete];

        return [
            new ResourceDefinition(
                name: 'tag',
                label: 'tag',
                basePath: 'tag',
                listPath: 'tags',
                fields: [
                    new FieldDefinition('name', FieldKind::String, 'Name of the tag'),
                    new FieldDefinition('slug', FieldKind::String, 'Slug, generated from the name when empty'),
                    new FieldDefinition('isHighlighted', FieldKind::Bool, 'Highlight the tag (--no-is-highlighted)'),
                ],
                operations: $all,
            ),
            new ResourceDefinition(
                name: 'type',
                label: 'type of content',
                basePath: 'type',
                listPath: 'types',
                fields: [
                    new FieldDefinition('name', FieldKind::String, 'Name of the type'),
                    new FieldDefinition('template', FieldKind::String, 'Template used to render the contents'),
                    new FieldDefinition(
                        'blocks',
                        FieldKind::Blocks,
                        'Block of the type, as <name>:<kind> with kind in ' . implode('|', BlockTypes::kinds())
                        . '; the blocks replace all the existing ones',
                        'block',
                    ),
                ],
                operations: $all,
            ),
            new ResourceDefinition(
                name: 'content',
                label: 'content',
                basePath: 'content',
                listPath: 'contents',
                fields: $this->contentFields(),
                operations: $all,
                translatable: true,
                hasParts: true,
            ),
            new ResourceDefinition(
                name: 'post',
                label: 'blog post',
                basePath: 'post',
                listPath: 'posts',
                fields: $this->contentFields(),
                operations: $all,
                translatable: true,
                hasParts: true,
            ),
            new ResourceDefinition(
                name: 'item',
                label: 'menu item',
                basePath: 'item',
                listPath: 'items',
                fields: [
                    new FieldDefinition('name', FieldKind::String, 'Name of the item'),
                    new FieldDefinition('location', FieldKind::String, 'Location (menu) of the item'),
                    new FieldDefinition('parent', FieldKind::Id, 'Id of the parent item, empty for none'),
                    new FieldDefinition('content', FieldKind::Id, 'Id of the linked content, empty for none'),
                    new FieldDefinition('slug', FieldKind::String, 'Slug of the item'),
                    new FieldDefinition('hidden', FieldKind::Bool, 'Hide the item (--no-hidden to show it)'),
                    new FieldDefinition('position', FieldKind::Int, 'Position of the item in its menu'),
                    new FieldDefinition('localeField', FieldKind::String, 'Locale of the submitted translation'),
                ],
                operations: $all,
                translatable: true,
            ),
            new ResourceDefinition(
                name: 'user',
                label: 'user',
                basePath: 'user',
                listPath: 'users',
                fields: [
                    new FieldDefinition('firstName', FieldKind::String, 'First name'),
                    new FieldDefinition('lastName', FieldKind::String, 'Last name'),
                    new FieldDefinition('email', FieldKind::String, 'Email'),
                    new FieldDefinition(
                        'roles',
                        FieldKind::StringList,
                        'Role of the user',
                        'role',
                        ['ROLE_USER', 'ROLE_ADMIN'],
                    ),
                    new FieldDefinition('active', FieldKind::Bool, 'Activate the user (--no-active to disable it)'),
                ],
                operations: $all,
            ),
            new ResourceDefinition(
                name: 'media',
                label: 'media',
                basePath: 'media',
                listPath: 'media',
                fields: [],
                operations: [Operation::List, Operation::Get, Operation::Delete],
            ),
            new ResourceDefinition(
                name: 'comment',
                label: 'comment of a blog post',
                basePath: 'post/{post-id}/comment',
                listPath: 'post/{post-id}/comments',
                fields: [
                    new FieldDefinition('moderatedAuthor', FieldKind::String, 'Moderated author'),
                    new FieldDefinition('moderatedTitle', FieldKind::String, 'Moderated title'),
                    new FieldDefinition('moderatedContent', FieldKind::String, 'Moderated content'),
                ],
                operations: [Operation::List, Operation::Get, Operation::Update, Operation::Delete],
                parents: ['post-id'],
            ),
        ];
    }

    public function resource(string $name): ?ResourceDefinition
    {
        foreach ($this->resources() as $resource) {
            if ($resource->name === $name) {
                return $resource;
            }
        }

        return null;
    }

    /**
     * @return list<FrontEndpoint>
     */
    public function front(): array
    {
        return [
            new FrontEndpoint(
                'content:get',
                'Get a published content, the home page is the slug "default"',
                'content/{slug}',
                [new FrontArgument('slug', 'Slug of the content', 'default')],
            ),
            new FrontEndpoint(
                'post:get',
                'Get a published blog post with its comments',
                'post/{slug}',
                [new FrontArgument('slug', 'Slug of the post')],
            ),
            new FrontEndpoint('post:list', 'List the published blog posts', 'posts', [], true),
            new FrontEndpoint(
                'post:list-by-tag',
                'List the published blog posts of a tag',
                'posts/by/{tag}',
                [new FrontArgument('tag', 'Slug of the tag')],
                true,
            ),
        ];
    }

    /**
     * @return list<FieldDefinition>
     */
    private function contentFields(): array
    {
        return [
            new FieldDefinition('author', FieldKind::Id, 'Id of the author (a user)'),
            new FieldDefinition('type', FieldKind::Id, 'Id of the type of content'),
            new FieldDefinition('tags', FieldKind::IdList, 'Id of a tag', 'tag'),
            new FieldDefinition('title', FieldKind::String, 'Title'),
            new FieldDefinition('subtitle', FieldKind::String, 'Subtitle'),
            new FieldDefinition('slug', FieldKind::String, 'Slug, generated from the title when empty'),
            new FieldDefinition('description', FieldKind::String, 'Description'),
            new FieldDefinition('localeField', FieldKind::String, 'Locale of the submitted translation'),
        ];
    }
}

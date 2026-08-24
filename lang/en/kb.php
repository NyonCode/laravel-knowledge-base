<?php

declare(strict_types=1);

return [
    'kind' => [
        'tutorial' => 'Tutorial',
        'how_to' => 'How-to',
        'reference' => 'Reference',
        'explanation' => 'Background',
    ],

    // Shown under the title so a reader knows what kind of page they opened
    // before they invest three minutes in the wrong one.
    'promise' => [
        'tutorial' => 'Follow along from start to finish — you will end up with a working result.',
        'how_to' => 'Steps for one specific goal. Assumes you already know why.',
        'reference' => 'Look-up: values, options and limits. No narrative.',
        'explanation' => 'Why it works this way. Read it away from the keyboard.',
    ],

    'format' => [
        'markdown' => 'Markdown',
        'rich-text' => 'Rich text',
        'blocks' => 'Blocks',
    ],

    'status' => [
        'draft' => 'Draft',
        'in_review' => 'In review',
        'published' => 'Published',
        'archived' => 'Archived',
    ],

    'visibility' => [
        'internal' => 'Internal',
        'public' => 'Public',
    ],

    'home' => [
        'title' => 'Knowledge base',
        'lead' => 'Guides, procedures and reference for everything here.',
        'placeholder' => 'What do you need to do?',
        'popular' => 'Most read',
        'empty' => 'Nothing here yet.',
    ],

    'search' => [
        'results' => ':count result|:count results|:count results',
        'none' => 'Nothing matched “:term”.',
        'none_hint' => 'Try fewer words, or browse the categories.',
        'open' => 'Search',
        'hint' => 'Type to search, ↑↓ to move, ↵ to open.',
    ],

    'article' => [
        'edit' => 'Edit',
        'toc' => 'On this page',
        'reading_time' => ':minutes min read',
        'updated' => 'Updated :date',
        'reviewed' => 'Checked :date',
        'stale' => 'This page has not been checked in a while — it may be out of date.',
        'related' => 'Read next',
        'back' => 'Back to the knowledge base',
    ],

    'feedback' => [
        'question' => 'Was this helpful?',
        'yes' => 'Yes',
        'no' => 'No',
        'thanks' => 'Thanks — noted.',
        'why' => 'What was missing?',
        'why_placeholder' => 'The step that did not work, the thing you could not find…',
        'send' => 'Send',
        'sent' => 'Thank you. This is what the next revision gets written from.',
    ],

    'editor' => [
        'move_up' => 'Move up',
        'move_down' => 'Move down',
        'block' => [
            'heading' => 'Heading',
            'text' => 'Text',
            'callout' => 'Callout',
            'steps' => 'Steps',
            'code' => 'Code',
            'image' => 'Image',
            'language' => 'php, bash, json…',
            'callout_title' => 'Title (optional)',
            'alt' => 'Alt text',
            'caption' => 'Caption',
            'unknown' => 'Block of type “:type” — no editor for it in this installation.',
            'empty' => 'Empty. Add the first block below.',
        ],
        'link' => 'link',
        'markdown_hint' => 'Markdown · ⌘B ⌘I ⌘K',
    ],

    'admin' => [
        'category' => 'Category',
        'status' => 'Status',
        'slug_warning' => 'Changing the address breaks links people already shared.',
        'excerpt' => 'Summary',
        'excerpt_hint' => 'Shown in search results and listings.',
        'visibility_label' => 'Visible to',
        'categories' => 'Categories',
        'new_category' => 'New category',
        'category_name' => 'Name',
        'order' => 'Order',
        'cancel' => 'Cancel',
        'delete' => 'Delete',
        'article_count' => '{0} no articles|{1} :count article|[2,*] :count articles',
        'delete_category_confirm' => 'Delete this category? Its articles stay and can be filed elsewhere.',
        'needs_work' => 'Needs work',
        'all' => 'All articles',
        'stale' => 'Not checked lately',
        'unhelpful' => 'Rated poorly',
        'new' => 'New article',
        'save' => 'Save',
        'saved' => 'Saved',
        'preview' => 'Preview',
        'write' => 'Write',
        'mark_reviewed' => 'Still true',
        'note' => 'What changed?',
        'note_placeholder' => 'Kept with the revision, not shown to readers.',
        'empty' => 'Nothing to fix. The base is in good shape.',
    ],
];

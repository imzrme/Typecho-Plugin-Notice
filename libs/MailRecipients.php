<?php
namespace TypechoPlugin\Notice\libs;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

use Utils;
use Widget;

class MailRecipients
{
    /** Plan both SMTP and Graph deliveries before rendering any templates. */
    public static function forComment(Widget\Base\Comments $comment, bool $approval = false): array
    {
        $author = [
            'id' => (int)$comment->authorId,
            'mail' => trim((string)$comment->mail),
            'name' => (string)$comment->author
        ];
        $ownerId = (int)$comment->ownerId;
        $parentId = (int)$comment->parent;
        $cid = (int)$comment->cid;
        $status = (string)$comment->status;
        $isOwner = $ownerId > 0 && $author['id'] === $ownerId;

        $owner = null;
        if ($ownerId > 0) {
            $widget = Utils\Helper::widgetById('users', $ownerId);
            if ($widget instanceof Widget\Base\Users && $widget->have()) {
                $owner = ['id' => $ownerId, 'mail' => trim((string)$widget->mail), 'name' => (string)$widget->name];
            }
        }
        $parent = null;
        if ($parentId > 0) {
            $widget = Utils\Helper::widgetById('comments', $parentId);
            if ($widget instanceof Widget\Base\Comments && $widget->have() && (int)$widget->cid === $cid) {
                $parent = [
                    'id' => (int)$widget->authorId,
                    'mail' => trim((string)$widget->mail),
                    'name' => (string)$widget->author
                ];
            }
        }
        $parentIsOwner = $parent !== null && (
            ($ownerId > 0 && $parent['id'] === $ownerId)
            || ($owner !== null && $owner['mail'] !== '' && strcasecmp($parent['mail'], $owner['mail']) === 0)
        );

        $recipients = [];
        $add = static function (?array $recipient, string $template, bool $allowSelf = false) use (&$recipients, $author) {
            if ($recipient === null || !filter_var($recipient['mail'], FILTER_VALIDATE_EMAIL)) {
                return;
            }
            if (!$allowSelf && (
                ($author['id'] > 0 && $recipient['id'] === $author['id'])
                || ($author['mail'] !== '' && strcasecmp($recipient['mail'], $author['mail']) === 0)
            )) {
                return;
            }
            $key = strtolower($recipient['mail']);
            if (!isset($recipients[$key])) {
                $recipients[$key] = ['mail' => $recipient['mail'], 'name' => $recipient['name'], 'template' => $template];
            }
        };

        if ($approval) {
            // Approval confirmation is intentionally addressed to the commenter.
            $add($author, 'approved', true);
        } elseif (!$isOwner && ($parentId === 0 || $status === 'approved' || $status === 'waiting')) {
            // The owner template wins if owner and parent share an address.
            $add($owner, 'owner');
        }
        if ($parentId > 0 && ($approval || $status === 'approved') && !$parentIsOwner) {
            $add($parent, 'guest');
        }
        return array_values($recipients);
    }
}

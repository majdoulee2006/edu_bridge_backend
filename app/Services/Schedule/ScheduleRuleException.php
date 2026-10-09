<?php

namespace App\Services\Schedule;

/** إجراء على الجدول مرفوض لأنه يكسر قيدًا صلبًا أو شرطًا. رسائله عربية جاهزة للعرض. */
final class ScheduleRuleException extends \DomainException
{
    /** @param string[] $messages */
    public function __construct(public readonly array $messages)
    {
        parent::__construct(implode(' | ', $messages));
    }
}

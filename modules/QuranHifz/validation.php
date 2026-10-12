<?php
/** Pure validators, usable without a Gibbon runtime. */
function qhValidDate(string $date): bool
{
    $d = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    return $d !== false && $d->format('Y-m-d') === $date;
}
function qhValidVerseRange(int $from, int $to): bool
{
    return $from >= 1 && $to >= $from && $to <= 286;
}
function qhValidScore(int $score): bool
{
    return $score >= 0 && $score <= 100;
}
function qhValidAttendance(string $status): bool
{
    return in_array($status, ['present', 'absent', 'excused'], true);
}
function qhValidRecitationType(string $type): bool
{
    return in_array($type, ['new', 'review'], true);
}

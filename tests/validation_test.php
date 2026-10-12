<?php
require_once __DIR__ . '/../modules/QuranHifz/validation.php';
function testAssert(bool $valid, string $message): void {
    if (!$valid) { fwrite(STDERR, "FAIL: $message\n"); exit(1); }
}
testAssert(qhValidDate('2026-10-12'), 'valid date');
testAssert(!qhValidDate('2026-02-30'), 'invalid date');
testAssert(!qhValidDate('2026-1-2'), 'strict date format');
testAssert(qhValidVerseRange(1, 286), 'valid range');
testAssert(!qhValidVerseRange(10, 3), 'reverse range');
testAssert(!qhValidVerseRange(0, 1), 'zero verse');
testAssert(!qhValidVerseRange(1, 287), 'maximum range');
testAssert(qhValidScore(0) && qhValidScore(100), 'score endpoints');
testAssert(!qhValidScore(-1) && !qhValidScore(101), 'score boundaries');
testAssert(qhValidAttendance('excused'), 'attendance');
testAssert(!qhValidAttendance('unknown'), 'attendance whitelist');
testAssert(qhValidRecitationType('review'), 'recitation type');
testAssert(!qhValidRecitationType('deleted'), 'recitation type whitelist');
echo "Validation tests passed\n";

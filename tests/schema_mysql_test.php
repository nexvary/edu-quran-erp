<?php
/** MySQL contract tests for QuranHifz schema without requiring a live Gibbon install. */
require_once __DIR__ . '/../modules/QuranHifz/validation.php';

$host = getenv('MYSQL_HOST') ?: '127.0.0.1';
$dbName = getenv('MYSQL_DATABASE') ?: 'quran_test';
$user = getenv('MYSQL_USER') ?: 'root';
$password = getenv('MYSQL_PASSWORD') ?: '';
$db = new PDO("mysql:host={$host};dbname={$dbName};charset=utf8mb4", $user, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);
require __DIR__ . '/../modules/QuranHifz/manifest.php';
foreach (['qhRecitation','qhAttendance','qhMeeting','qhEnrollment','qhCircle'] as $table) {
    $db->exec('DROP TABLE IF EXISTS ' . $table);
}
foreach ($moduleTables as $query) {
    $db->exec($query);
}
$db->exec("INSERT INTO qhCircle (name,teacherPersonID) VALUES ('حلقة الإتقان', 12)");
$circle = (int)$db->lastInsertId();
$db->prepare("INSERT INTO qhEnrollment (circleID,studentPersonID) VALUES (?,?)")->execute([$circle, 45]);
$db->prepare("INSERT INTO qhMeeting (circleID,meetingDate,topic) VALUES (?,?,?)")->execute([$circle, '2026-10-12', 'سورة البقرة']);
$meeting = (int)$db->lastInsertId();
$attendance = $db->prepare("INSERT INTO qhAttendance (meetingID,studentPersonID,status) VALUES (?,?,?) ON DUPLICATE KEY UPDATE status=VALUES(status)");
$attendance->execute([$meeting,45,'present']);
$attendance->execute([$meeting,45,'excused']);
$db->prepare("INSERT INTO qhRecitation (meetingID,studentPersonID,recitationType,surahName,verseFrom,verseTo,score,remarks,createdBy) VALUES (?,?,?,?,?,?,?,?,?)")
    ->execute([$meeting,45,'review','البقرة',1,5,92,'مراجعة قوية',12]);
$counts = $db->query("SELECT (SELECT COUNT(*) FROM qhCircle) AS circles,
    (SELECT COUNT(*) FROM qhEnrollment) AS enrollments,
    (SELECT COUNT(*) FROM qhMeeting) AS meetings,
    (SELECT COUNT(*) FROM qhAttendance) AS attendance,
    (SELECT COUNT(*) FROM qhRecitation) AS recitations")->fetch();
foreach ($counts as $key => $value) {
    if ((int)$value !== 1) throw new RuntimeException("Count mismatch: $key");
}
$actual = $db->query("SELECT a.status, r.surahName, r.score, c.name AS circleName
 FROM qhAttendance a JOIN qhMeeting m ON m.qhMeetingID=a.meetingID
 JOIN qhCircle c ON c.qhCircleID=m.circleID
 JOIN qhRecitation r ON r.meetingID=m.qhMeetingID AND r.studentPersonID=a.studentPersonID")->fetch();
if ($actual['status'] !== 'excused' || $actual['surahName'] !== 'البقرة'
    || (int)$actual['score'] !== 92 || $actual['circleName'] !== 'حلقة الإتقان') {
    throw new RuntimeException('Database roundtrip mismatch');
}
$duplicatePrevented = false;
try {
    $db->prepare("INSERT INTO qhMeeting (circleID,meetingDate) VALUES (?,?)")->execute([$circle, '2026-10-12']);
} catch (PDOException $e) {
    $duplicatePrevented = $e->getCode() === '23000';
}
if (!$duplicatePrevented) throw new RuntimeException('Unique meeting constraint missing');
echo "MySQL QuranHifz schema tests passed\n";

<?php
/**
 * Administrative POST dispatcher. No GET mutations, CSRF, authorization, or raw SQL inputs.
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
require_once '../../gibbon.php';
require_once __DIR__ . '/moduleFunctions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit;
}
$kind = is_string($_POST['kind'] ?? null) ? $_POST['kind'] : '';
$destination = in_array($kind, ['circle','enrollment'], true) ? 'circles.php'
    : (in_array($kind, ['meeting'], true) ? 'meetings.php' : 'meeting.php');
$allowedPage = $destination;
$meetingID = filter_var($_POST['meeting'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$redirectArgs = $destination === 'meeting.php' && $meetingID ? ['id' => $meetingID] : [];
$redirect = static function (string $status) use ($destination, $redirectArgs): void {
    header('Location: ' . qhUrl($destination, array_merge($redirectArgs, ['return' => $status])), true, 303);
    exit;
};
if (!qhActionAllowed($allowedPage) || !qhCheckToken()) {
    http_response_code(403);
    exit('Forbidden');
}
$getInt = static function (string $key): int {
    $value = filter_var($_POST[$key] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $value === false || $value === null ? 0 : $value;
};
$circleID = $getInt('circle');
$studentID = $getInt('student');
try {
    if ($kind === 'circle') {
        $name = trim((string)($_POST['name'] ?? ''));
        $teacherID = $getInt('teacher');
        if ($name === '' || mb_strlen($name) > 120 || $teacherID === 0) $redirect('error');
        $check = $connection2->prepare("SELECT COUNT(*) FROM gibbonPerson p JOIN gibbonRole r ON r.gibbonRoleID=p.gibbonRoleIDPrimary WHERE p.gibbonPersonID=:id AND p.status='Full' AND r.category='Staff'");
        $check->execute(['id' => $teacherID]);
        if (!$check->fetchColumn()) $redirect('error');
        $stmt = $connection2->prepare("INSERT INTO qhCircle (name, teacherPersonID) VALUES (:name, :teacher)");
        $stmt->execute(['name' => $name, 'teacher' => $teacherID]);
    } elseif ($kind === 'enrollment') {
        if (!$circleID || !$studentID) $redirect('error');
        $check = $connection2->prepare("SELECT COUNT(*) FROM qhCircle WHERE qhCircleID=:id AND status='Active'");
        $check->execute(['id' => $circleID]);
        if (!$check->fetchColumn()) $redirect('error');
        $check = $connection2->prepare("SELECT COUNT(*) FROM gibbonPerson p JOIN gibbonRole r ON r.gibbonRoleID=p.gibbonRoleIDPrimary WHERE p.gibbonPersonID=:id AND p.status='Full' AND r.category='Student'");
        $check->execute(['id' => $studentID]);
        if (!$check->fetchColumn()) $redirect('error');
        $stmt = $connection2->prepare("INSERT INTO qhEnrollment (circleID,studentPersonID) VALUES (:circle,:student) ON DUPLICATE KEY UPDATE status='Active'");
        $stmt->execute(['circle' => $circleID,'student' => $studentID]);
    } elseif ($kind === 'meeting') {
        $date = (string)($_POST['date'] ?? '');
        $topic = trim((string)($_POST['topic'] ?? ''));
        if (!$circleID || !qhValidDate($date) || mb_strlen($topic) > 160) $redirect('error');
        $check = $connection2->prepare("SELECT COUNT(*) FROM qhCircle WHERE qhCircleID=:id AND status='Active'");
        $check->execute(['id' => $circleID]);
        if (!$check->fetchColumn()) $redirect('error');
        $stmt = $connection2->prepare("INSERT INTO qhMeeting (circleID,meetingDate,topic) VALUES (:circle,:date,:topic)");
        $stmt->execute(['circle' => $circleID, 'date' => $date, 'topic' => $topic]);
    } elseif ($kind === 'attendance' || $kind === 'recitation') {
        if (!$meetingID || !$studentID) $redirect('error');
        $check = $connection2->prepare("SELECT COUNT(*) FROM qhMeeting m JOIN qhEnrollment e ON e.circleID=m.circleID AND e.studentPersonID=:student AND e.status='Active' WHERE m.qhMeetingID=:meeting");
        $check->execute(['student' => $studentID, 'meeting' => $meetingID]);
        if (!$check->fetchColumn()) $redirect('error');
        if ($kind === 'attendance') {
            $status = (string)($_POST['status'] ?? '');
            if (!qhValidAttendance($status)) $redirect('error');
            $stmt = $connection2->prepare("INSERT INTO qhAttendance (meetingID,studentPersonID,status) VALUES (:meeting,:student,:status) ON DUPLICATE KEY UPDATE status=VALUES(status)");
            $stmt->execute(['meeting' => $meetingID,'student' => $studentID,'status' => $status]);
        } else {
            $type = (string)($_POST['type'] ?? '');
            $surah = trim((string)($_POST['surah'] ?? ''));
            $from = $getInt('from');
            $to = $getInt('to');
            $score = filter_var($_POST['score'] ?? null, FILTER_VALIDATE_INT);
            $remarks = trim((string)($_POST['remarks'] ?? ''));
            $createdBy = (int) $gibbon->session->get('gibbonPersonID');
            if (!qhValidRecitationType($type) || $surah === '' || mb_strlen($surah) > 80
                || !qhValidVerseRange($from,$to) || $score === false || $score === null
                || !qhValidScore((int)$score) || mb_strlen($remarks) > 500 || $createdBy <= 0) $redirect('error');
            $stmt = $connection2->prepare("INSERT INTO qhRecitation (meetingID,studentPersonID,recitationType,surahName,verseFrom,verseTo,score,remarks,createdBy)
                VALUES (:meeting,:student,:type,:surah,:fromVerse,:toVerse,:score,:remarks,:createdBy)");
            $stmt->execute(['meeting' => $meetingID,'student' => $studentID, 'type' => $type,
                'surah' => $surah, 'fromVerse' => $from, 'toVerse' => $to, 'score' => $score,
                'remarks' => $remarks,'createdBy' => $createdBy]);
        }
    } else {
        http_response_code(400); exit('Unsupported operation');
    }
    $redirect('success');
} catch (PDOException $e) {
    error_log('QuranHifz database operation failed: ' . $e->getCode());
    $redirect($e->getCode() === '23000' ? 'duplicate' : 'error');
}

<?php
require_once __DIR__ . '/moduleFunctions.php';
if (qhGuard('report.php')) {
    qhNav();
    echo '<section class="qh-shell" dir="rtl"><h2>تقرير الحلقات والحفظ والمراجعة</h2>';
    $sql = "SELECT c.name AS circleName, CONCAT(p.preferredName,' ',p.surname) studentName,
      (SELECT COUNT(*) FROM qhAttendance a JOIN qhMeeting m ON m.qhMeetingID=a.meetingID
       WHERE m.circleID=e.circleID AND a.studentPersonID=e.studentPersonID AND a.status='present') AS presentCount,
      (SELECT COUNT(*) FROM qhAttendance a JOIN qhMeeting m ON m.qhMeetingID=a.meetingID
       WHERE m.circleID=e.circleID AND a.studentPersonID=e.studentPersonID) AS recordedCount,
      (SELECT COUNT(*) FROM qhRecitation r JOIN qhMeeting m ON m.qhMeetingID=r.meetingID
       WHERE m.circleID=e.circleID AND r.studentPersonID=e.studentPersonID AND r.recitationType='new') AS newCount,
      (SELECT COUNT(*) FROM qhRecitation r JOIN qhMeeting m ON m.qhMeetingID=r.meetingID
       WHERE m.circleID=e.circleID AND r.studentPersonID=e.studentPersonID AND r.recitationType='review') AS reviewCount,
      (SELECT ROUND(AVG(r.score), 1) FROM qhRecitation r JOIN qhMeeting m ON m.qhMeetingID=r.meetingID
       WHERE m.circleID=e.circleID AND r.studentPersonID=e.studentPersonID) AS averageScore
      FROM qhEnrollment e JOIN qhCircle c ON c.qhCircleID=e.circleID
      JOIN gibbonPerson p ON p.gibbonPersonID=e.studentPersonID
      WHERE e.status='Active' ORDER BY c.name, p.surname, p.preferredName";
    echo '<table class="qh-table"><tr><th>الحلقة</th><th>الطالب</th><th>الحضور المسجل</th><th>حفظ جديد</th><th>مراجعة</th><th>متوسط التقييم</th></tr>';
    foreach ($connection2->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $attendance = (int)$r['presentCount'] . ' / ' . (int)$r['recordedCount'];
        echo '<tr><td>' . qhEsc($r['circleName']) . '</td><td>' . qhEsc($r['studentName']) . '</td><td>' . qhEsc($attendance) .
             '</td><td>' . (int)$r['newCount'] . '</td><td>' . (int)$r['reviewCount'] .
             '</td><td>' . ($r['averageScore'] === null ? '—' : qhEsc($r['averageScore'] . '%')) . '</td></tr>';
    }
    echo '</table><p>الحضور = عدد مرات الحضور / عدد سجلات الحضور المدخلة. الأيام دون سجل لا تُحتسب.</p></section>';
}

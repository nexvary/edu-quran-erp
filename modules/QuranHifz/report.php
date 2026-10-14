<?php
require_once __DIR__ . '/moduleFunctions.php';
if (qhGuard('report.php')) {
    qhNav();
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
    $rows = $connection2->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    echo '<main class="qh-shell" dir="rtl"><header class="qh-page-head"><div>'
       . '<span class="qh-overline">بيانات تستند إلى السجلات</span><h1>تقارير التقدم القرآني</h1>'
       . '<p>متابعة دقيقة لحضور الطلاب ومرات الحفظ والمراجعة ومتوسط جودة التسميع.</p></div>'
       . '<span class="qh-page-symbol">' . qhIcon('chart') . '</span></header>';
    echo '<section class="qh-panel"><div class="qh-panel-heading"><div>'
       . '<span class="qh-overline">كشف تفصيلي</span><h2>نتائج الطلاب حسب الحلقة</h2></div>'
       . '<span class="qh-count-tag">' . count($rows) . ' صف</span></div>';
    if (!$rows) {
        qhEmpty('لا توجد نتائج بعد', 'بعد إضافة الطلاب وتسجيل التسميع ستظهر النتائج هنا.',
            qhUrl('meetings.php'), 'فتح الجلسات');
    } else {
        echo '<div class="qh-table-scroll"><table class="qh-table"><thead><tr>'
           . '<th scope="col">الحلقة</th><th scope="col">الطالب</th>'
           . '<th scope="col">الحضور المُسجل</th><th scope="col">حفظ جديد</th>'
           . '<th scope="col">مراجعة</th><th scope="col">متوسط التقييم</th></tr></thead><tbody>';
        foreach ($rows as $r) {
            $attendance = (int)$r['presentCount'] . ' / ' . (int)$r['recordedCount'];
            echo '<tr><td>' . qhEsc($r['circleName']) . '</td><td><strong>'
               . qhEsc($r['studentName']) . '</strong></td><td>' . qhEsc($attendance)
               . '</td><td>' . (int)$r['newCount'] . '</td><td>' . (int)$r['reviewCount']
               . '</td><td><b>' . ($r['averageScore']===null?'—':qhEsc($r['averageScore'] . '%'))
               . '</b></td></tr>';
        }
        echo '</tbody></table></div>';
    }
    echo '<p class="qh-report-note">' . qhIcon('shield')
       . ' الحضور هنا عدد مرات الحضور من إجمالي السجلات المدخلة فقط؛ '
       . 'الأيام التي لم يُسجل حضورها لا تُعتبر غيابًا تلقائيًا.</p></section></main>';
}

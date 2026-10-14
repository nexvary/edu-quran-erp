<?php
require_once __DIR__ . '/moduleFunctions.php';
if (qhGuard('meeting.php')) {
    qhNav();
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $stmt = $connection2->prepare("SELECT m.*, c.name AS circleName FROM qhMeeting m JOIN qhCircle c ON c.qhCircleID=m.circleID WHERE m.qhMeetingID=:id");
    $stmt->execute(['id' => $id ?: 0]);
    $meeting = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$meeting) {
        echo '<main class="qh-shell" dir="rtl">';
        qhEmpty('الجلسة غير موجودة', 'قد يكون الرابط غير صحيح أو أن الجلسة حُذفت.', qhUrl('meetings.php'), 'العودة للجلسات');
        echo '</main>';
    } else {
        $studentsStmt = $connection2->prepare("SELECT p.gibbonPersonID AS id, p.preferredName, p.surname
            FROM qhEnrollment e JOIN gibbonPerson p ON p.gibbonPersonID=e.studentPersonID
            WHERE e.circleID=:circle AND e.status='Active' ORDER BY p.surname, p.preferredName");
        $studentsStmt->execute(['circle' => $meeting['circleID']]);
        $students = $studentsStmt->fetchAll(PDO::FETCH_ASSOC);
        $action = qhEsc(rtrim((string)$gibbon->session->get('absoluteURL'), '/') . '/modules/QuranHifz/save.php');
        $token = qhEsc(qhToken());
        echo '<main class="qh-shell" dir="rtl"><header class="qh-page-head"><div>'
           . '<span class="qh-overline">ملف جلسة الحلقة</span><h1>' . qhEsc($meeting['circleName']) . '</h1>'
           . '<p>' . qhEsc($meeting['meetingDate']) . ' · ' . qhEsc($meeting['topic'] ?: 'متابعة الحفظ والمراجعة') . '</p>'
           . '</div><a class="qh-btn qh-btn-outline" href="' . qhEsc(qhUrl('meetings.php')) . '">'
           . qhIcon('back') . ' جميع الجلسات</a></header>';
        qhNotice();
        if (!$students) {
            qhEmpty('الحلقة بلا طلاب مسجلين', 'أضف طلابًا للحلقة قبل تسجيل الحضور والتسميع.',
                qhUrl('circles.php'), 'إلحاق طالب');
        } else {
            echo '<div class="qh-dual-grid"><section class="qh-panel"><div class="qh-panel-heading"><div>'
               . '<span class="qh-overline">متابعة الانضباط</span><h2>تسجيل الحضور</h2></div>'
               . '<span class="qh-mini-icon">' . qhIcon('check') . '</span></div>';
            echo '<form class="qh-form" method="post" action="' . $action . '">'
               . '<input type="hidden" name="qh_csrf" value="' . $token . '">'
               . '<input type="hidden" name="kind" value="attendance"><input type="hidden" name="meeting" value="' . (int)$id . '">'
               . '<label>الطالب<select name="student" required><option value="">اختر الطالب</option>';
            foreach ($students as $p) {
                echo '<option value="' . (int)$p['id'] . '">' . qhEsc($p['preferredName'] . ' ' . $p['surname']) . '</option>';
            }
            echo '</select></label><label>الحالة<select name="status" required>'
               . '<option value="present">حاضر</option><option value="absent">غائب</option>'
               . '<option value="excused">غائب بعذر</option></select></label>'
               . '<button type="submit">' . qhIcon('check') . ' حفظ الحضور</button></form></section>';
            echo '<section class="qh-panel"><div class="qh-panel-heading"><div>'
               . '<span class="qh-overline">تتبع التقدم</span><h2>تسجيل التسميع</h2></div>'
               . '<span class="qh-mini-icon">' . qhIcon('book') . '</span></div>';
            echo '<form class="qh-form" method="post" action="' . $action . '">'
               . '<input type="hidden" name="qh_csrf" value="' . $token . '">'
               . '<input type="hidden" name="kind" value="recitation"><input type="hidden" name="meeting" value="' . (int)$id . '">'
               . '<label>الطالب<select name="student" required><option value="">اختر الطالب</option>';
            foreach ($students as $p) {
                echo '<option value="' . (int)$p['id'] . '">' . qhEsc($p['preferredName'] . ' ' . $p['surname']) . '</option>';
            }
            echo '</select></label><label>نوع النشاط<select name="type" required>'
               . '<option value="new">حفظ جديد</option><option value="review">مراجعة</option></select></label>'
               . '<label>اسم السورة<input name="surah" maxlength="80" required placeholder="مثال: البقرة"></label>'
               . '<label>من آية<input type="number" min="1" max="286" name="from" required></label>'
               . '<label>إلى آية<input type="number" min="1" max="286" name="to" required></label>'
               . '<label>تقييم التسميع من 100<input type="number" min="0" max="100" name="score" required></label>'
               . '<label class="qh-full">ملاحظات المعلم<textarea name="remarks" maxlength="500" placeholder="جودة الحفظ، مواضع التعثر، الواجب القادم"></textarea></label>'
               . '<button type="submit">' . qhIcon('plus') . ' حفظ التسميع</button></form></section></div>';
        }
        $query = $connection2->prepare("SELECT r.*, CONCAT(p.preferredName,' ',p.surname) studentName FROM qhRecitation r JOIN gibbonPerson p ON p.gibbonPersonID=r.studentPersonID WHERE r.meetingID=:id ORDER BY r.qhRecitationID DESC");
        $query->execute(['id' => $id]);
        $recitations = $query->fetchAll(PDO::FETCH_ASSOC);
        echo '<section class="qh-panel qh-spaced"><div class="qh-panel-heading"><div>'
           . '<span class="qh-overline">المتابعة والتوثيق</span><h2>سجل التسميع</h2></div>'
           . '<span class="qh-count-tag">' . count($recitations) . ' سجل</span></div>';
        if (!$recitations) {
            qhEmpty('لا توجد عمليات تسميع', 'أضف أول عملية حفظ أو مراجعة لهذه الجلسة.');
        } else {
            echo '<div class="qh-table-scroll"><table class="qh-table"><thead><tr>'
               . '<th scope="col">الطالب</th><th scope="col">النوع</th>'
               . '<th scope="col">السورة</th><th scope="col">الآيات</th><th scope="col">التقييم</th></tr></thead><tbody>';
            foreach ($recitations as $r) {
                echo '<tr><td><strong>' . qhEsc($r['studentName']) . '</strong></td>'
                   . '<td><span class="qh-pill">' . ($r['recitationType']==='new'?'حفظ':'مراجعة') . '</span></td>'
                   . '<td>' . qhEsc($r['surahName']) . '</td><td>'
                   . (int)$r['verseFrom'] . '–' . (int)$r['verseTo'] . '</td><td><b>'
                   . (int)$r['score'] . '%</b></td></tr>';
            }
            echo '</tbody></table></div>';
        }
        echo '</section></main>';
    }
}

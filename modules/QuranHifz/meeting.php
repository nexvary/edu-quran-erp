<?php
require_once __DIR__ . '/moduleFunctions.php';
if (qhGuard('meeting.php')) {
    qhNav(); qhNotice();
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $stmt = $connection2->prepare("SELECT m.*, c.name AS circleName FROM qhMeeting m JOIN qhCircle c ON c.qhCircleID=m.circleID WHERE m.qhMeetingID=:id");
    $stmt->execute(['id' => $id ?: 0]);
    $meeting = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$meeting) {
        $page->addError('الجلسة غير موجودة.');
    } else {
        echo '<section class="qh-shell" dir="rtl"><h2>' . qhEsc($meeting['circleName']) . ' — ' . qhEsc($meeting['meetingDate']) . '</h2>';
        $students = $connection2->prepare("SELECT p.gibbonPersonID AS id, p.preferredName, p.surname FROM qhEnrollment e JOIN gibbonPerson p ON p.gibbonPersonID=e.studentPersonID WHERE e.circleID=:circle AND e.status='Active' ORDER BY p.surname, p.preferredName");
        $students->execute(['circle' => $meeting['circleID']]);
        $students = $students->fetchAll(PDO::FETCH_ASSOC);
        $action = qhEsc(rtrim($gibbon->session->get('absoluteURL'), '/') . '/modules/QuranHifz/save.php');
        $token = qhEsc(qhToken());
        echo '<h3>تسجيل الحضور</h3><form class="qh-form" method="post" action="' . $action . '">';
        echo '<input type="hidden" name="qh_csrf" value="' . $token . '"><input type="hidden" name="kind" value="attendance"><input type="hidden" name="meeting" value="' . (int)$id . '">';
        echo '<label>الطالب<select name="student" required>';
        foreach ($students as $p) echo '<option value="' . (int)$p['id'] . '">' . qhEsc($p['preferredName'] . ' ' . $p['surname']) . '</option>';
        echo '</select></label><label>الحالة<select name="status"><option value="present">حاضر</option><option value="absent">غائب</option><option value="excused">بعذر</option></select></label><button>حفظ الحضور</button></form>';
        echo '<h3>تسجيل التسميع</h3><form class="qh-form" method="post" action="' . $action . '">';
        echo '<input type="hidden" name="qh_csrf" value="' . $token . '"><input type="hidden" name="kind" value="recitation"><input type="hidden" name="meeting" value="' . (int)$id . '">';
        echo '<label>الطالب<select name="student" required>';
        foreach ($students as $p) echo '<option value="' . (int)$p['id'] . '">' . qhEsc($p['preferredName'] . ' ' . $p['surname']) . '</option>';
        echo '</select></label><label>النوع<select name="type"><option value="new">حفظ جديد</option><option value="review">مراجعة</option></select></label>';
        echo '<label>السورة<input name="surah" maxlength="80" required></label><label>من آية<input type="number" min="1" max="286" name="from" required></label><label>إلى آية<input type="number" min="1" max="286" name="to" required></label>';
        echo '<label>التقييم من 100<input type="number" min="0" max="100" name="score" required></label><label>ملاحظات<textarea name="remarks" maxlength="500"></textarea></label><button>حفظ التسميع</button></form>';
        $query = $connection2->prepare("SELECT r.*, CONCAT(p.preferredName,' ',p.surname) studentName FROM qhRecitation r JOIN gibbonPerson p ON p.gibbonPersonID=r.studentPersonID WHERE r.meetingID=:id ORDER BY r.qhRecitationID DESC");
        $query->execute(['id' => $id]);
        echo '<h3>سجل التسميع</h3><table class="qh-table"><tr><th>الطالب</th><th>النوع</th><th>السورة</th><th>الآيات</th><th>التقييم</th></tr>';
        foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $r) {
            echo '<tr><td>' . qhEsc($r['studentName']) . '</td><td>' . ($r['recitationType'] === 'new' ? 'حفظ' : 'مراجعة') .
                 '</td><td>' . qhEsc($r['surahName']) . '</td><td>' . (int)$r['verseFrom'] . '–' . (int)$r['verseTo'] . '</td><td>' . (int)$r['score'] . '%</td></tr>';
        }
        echo '</table></section>';
    }
}

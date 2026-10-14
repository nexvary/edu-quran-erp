<?php
require_once __DIR__ . '/moduleFunctions.php';
if (qhGuard('meetings.php')) {
    qhNav();
    $circles = $connection2->query("SELECT qhCircleID, name FROM qhCircle WHERE status='Active' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    $sql = "SELECT m.qhMeetingID, m.meetingDate, m.topic, c.name AS circleName
            FROM qhMeeting m JOIN qhCircle c ON c.qhCircleID=m.circleID
            ORDER BY m.meetingDate DESC, m.qhMeetingID DESC LIMIT 100";
    $meetings = $connection2->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    echo '<main class="qh-shell" dir="rtl"><header class="qh-page-head"><div>'
       . '<span class="qh-overline">التعليم اليومي</span><h1>جلسات التحفيظ</h1>'
       . '<p>سجّل لقاءات الحلقات، وافتح كل جلسة لتوثيق الحضور والحفظ الجديد والمراجعة.</p></div>'
       . '<span class="qh-page-symbol">' . qhIcon('calendar') . '</span></header>';
    qhNotice();
    echo '<section class="qh-panel"><div class="qh-panel-heading"><div>'
       . '<span class="qh-overline">جدولة الحلقة</span><h2>إضافة جلسة جديدة</h2></div></div>';
    if (!$circles) {
        qhEmpty('لا توجد حلقات نشطة', 'أنشئ حلقة وأضف معلمًا أولاً.', qhUrl('circles.php'), 'إدارة الحلقات');
    } else {
        echo '<form class="qh-form qh-form-three" method="post" action="'
           . qhEsc(rtrim((string)$gibbon->session->get('absoluteURL'), '/') . '/modules/QuranHifz/save.php') . '">'
           . '<input type="hidden" name="qh_csrf" value="' . qhEsc(qhToken()) . '">'
           . '<input type="hidden" name="kind" value="meeting">'
           . '<label>الحلقة<select name="circle" required><option value="">اختر الحلقة</option>';
        foreach ($circles as $c) {
            echo '<option value="' . (int)$c['qhCircleID'] . '">' . qhEsc($c['name']) . '</option>';
        }
        echo '</select></label><label>تاريخ الجلسة<input type="date" name="date" required value="'
           . qhEsc(date('Y-m-d')) . '"></label>'
           . '<label>الموضوع<input name="topic" maxlength="160" placeholder="خطة اليوم"></label>'
           . '<button type="submit">' . qhIcon('plus') . ' إضافة الجلسة</button></form>';
    }
    echo '</section><section class="qh-panel qh-spaced"><div class="qh-panel-heading"><div>'
       . '<span class="qh-overline">الأرشيف التعليمي</span><h2>قائمة الجلسات</h2></div>'
       . '<span class="qh-count-tag">' . count($meetings) . ' معروضة</span></div>';
    if (!$meetings) {
        qhEmpty('لا جلسات مسجلة', 'بعد إنشاء جلسة ستظهر هنا ويمكن تسجيل الحضور والتسميع لها.');
    } else {
        echo '<div class="qh-table-scroll"><table class="qh-table"><thead><tr>'
           . '<th scope="col">التاريخ</th><th scope="col">الحلقة</th><th scope="col">الموضوع</th><th scope="col">إجراء</th></tr></thead><tbody>';
        foreach ($meetings as $m) {
            echo '<tr><td class="qh-nowrap">' . qhEsc($m['meetingDate']) . '</td>'
               . '<td><strong>' . qhEsc($m['circleName']) . '</strong></td>'
               . '<td>' . qhEsc($m['topic'] ?: '—') . '</td>'
               . '<td><a class="qh-row-link" href="' . qhEsc(qhUrl('meeting.php',['id'=>(int)$m['qhMeetingID']]))
               . '">فتح الجلسة ' . qhIcon('arrow') . '</a></td></tr>';
        }
        echo '</tbody></table></div>';
    }
    echo '</section></main>';
}

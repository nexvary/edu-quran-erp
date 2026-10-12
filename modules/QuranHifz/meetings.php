<?php
require_once __DIR__ . '/moduleFunctions.php';
if (qhGuard('meetings.php')) {
    qhNav(); qhNotice();
    echo '<section class="qh-shell" dir="rtl"><h2>الجلسات اليومية</h2>';
    $circles = $connection2->query("SELECT qhCircleID, name FROM qhCircle WHERE status='Active' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    echo '<form class="qh-form" method="post" action="' . qhEsc(rtrim($gibbon->session->get('absoluteURL'), '/') . '/modules/QuranHifz/save.php') . '">';
    echo '<input type="hidden" name="qh_csrf" value="' . qhEsc(qhToken()) . '"><input type="hidden" name="kind" value="meeting">';
    echo '<label>الحلقة<select name="circle" required><option value="">اختر الحلقة</option>';
    foreach ($circles as $c) echo '<option value="' . (int)$c['qhCircleID'] . '">' . qhEsc($c['name']) . '</option>';
    echo '</select></label><label>التاريخ<input type="date" name="date" required value="' . date('Y-m-d') . '"></label>';
    echo '<label>موضوع الجلسة<input name="topic" maxlength="160"></label><button>إضافة جلسة</button></form>';
    $sql = "SELECT m.qhMeetingID, m.meetingDate, m.topic, c.name AS circleName
            FROM qhMeeting m JOIN qhCircle c ON c.qhCircleID=m.circleID
            ORDER BY m.meetingDate DESC, m.qhMeetingID DESC LIMIT 100";
    echo '<table class="qh-table"><tr><th>التاريخ</th><th>الحلقة</th><th>الموضوع</th><th>التفاصيل</th></tr>';
    foreach ($connection2->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $m) {
        echo '<tr><td>' . qhEsc($m['meetingDate']) . '</td><td>' . qhEsc($m['circleName']) . '</td><td>' . qhEsc($m['topic']) .
             '</td><td><a href="' . qhEsc(qhUrl('meeting.php', ['id' => (int)$m['qhMeetingID']])) . '">الحضور والتسميع</a></td></tr>';
    }
    echo '</table></section>';
}

<?php
require_once __DIR__ . '/moduleFunctions.php';
if (qhGuard('index.php')) {
    qhNav();
    $stats = [
        ['label' => 'الحلقات النشطة', 'icon' => 'circles', 'sql' => "SELECT COUNT(*) FROM qhCircle WHERE status = 'Active'", 'accent' => 'teal'],
        ['label' => 'الطلاب المسجلون', 'icon' => 'cap', 'sql' => "SELECT COUNT(DISTINCT studentPersonID) FROM qhEnrollment WHERE status = 'Active'", 'accent' => 'blue'],
        ['label' => 'الجلسات المسجلة', 'icon' => 'calendar', 'sql' => 'SELECT COUNT(*) FROM qhMeeting', 'accent' => 'gold'],
        ['label' => 'مرات التسميع', 'icon' => 'book', 'sql' => 'SELECT COUNT(*) FROM qhRecitation', 'accent' => 'purple'],
    ];
    $circles = $connection2->query("
        SELECT c.qhCircleID, c.name,
               COALESCE(e.studentCount, 0) AS studentCount,
               a.averageScore
        FROM qhCircle c
        LEFT JOIN (
            SELECT circleID, COUNT(*) AS studentCount
            FROM qhEnrollment WHERE status = 'Active' GROUP BY circleID
        ) e ON e.circleID = c.qhCircleID
        LEFT JOIN (
            SELECT m.circleID, ROUND(AVG(r.score), 0) AS averageScore
            FROM qhMeeting m JOIN qhRecitation r ON r.meetingID = m.qhMeetingID
            GROUP BY m.circleID
        ) a ON a.circleID = c.qhCircleID
        WHERE c.status='Active'
        ORDER BY studentCount DESC, c.name ASC LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    $recitations = $connection2->query("
        SELECT r.recitationType, r.surahName, r.score, r.createdAt,
               CONCAT(p.preferredName,' ',p.surname) AS studentName,
               c.name AS circleName
        FROM qhRecitation r
        JOIN qhMeeting m ON m.qhMeetingID = r.meetingID
        JOIN qhCircle c ON c.qhCircleID = m.circleID
        JOIN gibbonPerson p ON p.gibbonPersonID = r.studentPersonID
        ORDER BY r.createdAt DESC, r.qhRecitationID DESC LIMIT 6
    ")->fetchAll(PDO::FETCH_ASSOC);
    $meetings = $connection2->query("
        SELECT m.qhMeetingID, m.meetingDate, c.name
        FROM qhMeeting m JOIN qhCircle c ON c.qhCircleID=m.circleID
        ORDER BY m.meetingDate DESC,m.qhMeetingID DESC LIMIT 4
    ")->fetchAll(PDO::FETCH_ASSOC);
    echo '<main class="qh-shell" dir="rtl"><div class="qh-hero">';
    echo '<div class="qh-hero-copy"><span class="qh-eyebrow"><i></i> EDU QURAN • لوحة الإدارة</span>';
    echo '<h1>إدارة ترتقي بالتعليم <span>وتُتقن الحفظ.</span></h1>';
    echo '<p>الحلقات والطلاب والتسميع والمراجعة في مساحة واحدة واضحة. جميع المؤشرات أدناه مستمدة من السجلات الفعلية.</p>';
    echo '<div class="qh-actions"><a class="qh-btn qh-btn-primary" href="' . qhEsc(qhUrl('meetings.php')) . '">'
        . qhIcon('plus') . 'إدارة جلسة</a>';
    echo '<a class="qh-btn qh-btn-outline" href="' . qhEsc(qhUrl('circles.php')) . '">'
        . qhIcon('circles') . 'إدارة الحلقات</a></div></div>';
    echo '<div class="qh-hero-art" aria-hidden="true"><div class="qh-orbit qh-orbit-a"></div><div class="qh-orbit qh-orbit-b"></div>'
        . '<div class="qh-symbol">' . qhIcon('book') . '</div><span class="qh-spark qh-spark-a"></span>'
        . '<span class="qh-spark qh-spark-b"></span></div></div>';
    qhNotice();
    echo '<div class="qh-section-title"><div><span class="qh-overline">نظرة عامة</span>'
        . '<h2>مؤشرات دار التحفيظ</h2></div><span class="qh-context">'
        . qhIcon('shield') . ' بيانات مباشرة من النظام</span></div>';
    echo '<div class="qh-kpis">';
    foreach ($stats as $item) {
        $count = (int)$connection2->query($item['sql'])->fetchColumn();
        echo '<article class="qh-kpi qh-accent-' . qhEsc($item['accent']) . '">'
            . '<div class="qh-kpi-icon">' . qhIcon($item['icon']) . '</div>'
            . '<div class="qh-kpi-label">' . qhEsc($item['label']) . '</div>'
            . '<strong class="qh-kpi-value">' . number_format($count) . '</strong>'
            . '<span class="qh-kpi-foot">إجمالي السجلات المتاحة</span></article>';
    }
    echo '</div><div class="qh-dashboard-grid">';
    echo '<section class="qh-panel qh-panel-wide"><div class="qh-panel-heading"><div>'
        . '<span class="qh-overline">التقدم التعليمي</span><h2>أداء الحلقات</h2>'
        . '<p>متوسط تقييمات التسميع المدخلة لكل حلقة</p></div>'
        . '<a class="qh-link" href="' . qhEsc(qhUrl('circles.php')) . '">كل الحلقات ' . qhIcon('arrow') . '</a></div>';
    if (!$circles) {
        qhEmpty('لم تُنشأ حلقات بعد', 'ابدأ بإنشاء حلقة وتعيين معلم ثم أضف الطلاب.',
            qhUrl('circles.php'), 'إنشاء حلقة');
    } else {
        echo '<div class="qh-performance">';
        foreach ($circles as $row) {
            $score = $row['averageScore'] === null ? null : min(100, max(0, (int)$row['averageScore']));
            echo '<div class="qh-progress-row"><div class="qh-progress-top"><strong>' . qhEsc($row['name']) . '</strong>'
                . '<span>' . (int)$row['studentCount'] . ' طالب</span></div>'
                . '<div class="qh-progress-bottom"><div class="qh-progress-track"><span style="width:'
                . ($score ?? 0) . '%"></span></div><b>'
                . ($score === null ? 'لا تقييم بعد' : ($score . '%')) . '</b></div></div>';
        }
        echo '</div>';
    }
    echo '</section><section class="qh-panel"><div class="qh-panel-heading"><div>'
        . '<span class="qh-overline">المتابعة اليومية</span><h2>أحدث الجلسات</h2></div>'
        . '<a class="qh-link" href="' . qhEsc(qhUrl('meetings.php')) . '">فتح السجل ' . qhIcon('arrow') . '</a></div>';
    if (!$meetings) {
        qhEmpty('لا توجد جلسات مسجلة', 'أضف الجلسة الأولى من صفحة الجلسات.', qhUrl('meetings.php'), 'إضافة جلسة');
    } else {
        echo '<div class="qh-meeting-list">';
        foreach ($meetings as $meeting) {
            echo '<a class="qh-meeting-item" href="' . qhEsc(qhUrl('meeting.php', ['id' => (int)$meeting['qhMeetingID']])) . '">'
                . '<span class="qh-meeting-icon">' . qhIcon('calendar') . '</span><span><b>'
                . qhEsc($meeting['name']) . '</b><small>' . qhEsc($meeting['meetingDate'])
                . '</small></span>' . qhIcon('arrow') . '</a>';
        }
        echo '</div>';
    }
    echo '</section></div>';
    echo '<section class="qh-panel qh-recent"><div class="qh-panel-heading"><div>'
        . '<span class="qh-overline">آخر الأنشطة</span><h2>سجل التسميع والمراجعة</h2>'
        . '</div><a class="qh-link" href="' . qhEsc(qhUrl('report.php')) . '">عرض التقارير '
        . qhIcon('arrow') . '</a></div>';
    if (!$recitations) {
        qhEmpty('السجل خالٍ حاليًا', 'ستظهر عمليات الحفظ والمراجعة فور تسجيلها في جلسات الحلقات.',
            qhUrl('meetings.php'), 'فتح الجلسات');
    } else {
        echo '<div class="qh-table-scroll"><table class="qh-table"><thead><tr><th scope="col">الطالب</th>'
            . '<th scope="col">الحلقة</th><th scope="col">النوع</th><th scope="col">السورة</th>'
            . '<th scope="col">التقييم</th><th scope="col">وقت التسجيل</th></tr></thead><tbody>';
        foreach ($recitations as $r) {
            echo '<tr><td><strong>' . qhEsc($r['studentName']) . '</strong></td>'
                . '<td>' . qhEsc($r['circleName']) . '</td><td><span class="qh-pill">'
                . ($r['recitationType'] === 'new' ? 'حفظ جديد' : 'مراجعة') . '</span></td>'
                . '<td>' . qhEsc($r['surahName']) . '</td><td><b>' . (int)$r['score'] . '%</b></td>'
                . '<td class="qh-nowrap">' . qhEsc($r['createdAt']) . '</td></tr>';
        }
        echo '</tbody></table></div>';
    }
    echo '</section><footer class="qh-footer">'
        . qhIcon('shield') . '<span>إدارة المدرسة الأساسية متاحة من لوحة Gibbon. '
        . 'هذه الوحدة مخصصة للتحفيظ والتسميع.</span>'
        . '</footer></main>';
}

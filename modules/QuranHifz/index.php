<?php
require_once __DIR__ . '/moduleFunctions.php';
if (qhGuard('index.php')) {
    qhNav();
    qhNotice();
    $stats = [
        'الحلقات النشطة' => "SELECT COUNT(*) FROM qhCircle WHERE status = 'Active'",
        'الطلاب المسجلون' => "SELECT COUNT(DISTINCT studentPersonID) FROM qhEnrollment WHERE status = 'Active'",
        'الجلسات' => "SELECT COUNT(*) FROM qhMeeting",
        'عمليات التسميع' => "SELECT COUNT(*) FROM qhRecitation",
    ];
    echo '<section class="qh-shell" dir="rtl"><h2>لوحة تحفيظ القرآن الكريم</h2><div class="qh-grid">';
    foreach ($stats as $label => $sql) {
        $count = (int) $connection2->query($sql)->fetchColumn();
        echo '<div class="qh-card"><strong>' . qhEsc($count) . '</strong>' . qhEsc($label) . '</div>';
    }
    echo '</div><p>تُدار بيانات المدرسة الأساسية بواسطة Gibbon، وتُدار الحلقات والتسميع داخل هذه الإضافة.</p></section>';
}

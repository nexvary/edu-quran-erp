<?php
require_once __DIR__ . '/moduleFunctions.php';
if (qhGuard('circles.php')) {
    qhNav(); qhNotice();
    echo '<div class="qh-shell" dir="rtl"><h2>إدارة حلقات القرآن</h2>';
    $sql = "SELECT c.qhCircleID, c.name, c.status, CONCAT(p.preferredName,' ',p.surname) teacherName,
            (SELECT COUNT(*) FROM qhEnrollment e WHERE e.circleID=c.qhCircleID AND e.status='Active') studentCount
            FROM qhCircle c LEFT JOIN gibbonPerson p ON p.gibbonPersonID=c.teacherPersonID ORDER BY c.qhCircleID DESC";
    $rows = $connection2->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    echo '<table class="qh-table"><tr><th>اسم الحلقة</th><th>المعلم</th><th>طلاب</th><th>الحالة</th></tr>';
    foreach ($rows as $r) {
        echo '<tr><td>' . qhEsc($r['name']) . '</td><td>' . qhEsc($r['teacherName']) . '</td><td>' . qhEsc($r['studentCount']) . '</td><td>' . qhEsc($r['status']) . '</td></tr>';
    }
    echo '</table><h3>إضافة حلقة</h3>';
    echo '<form class="qh-form" action="' . qhEsc(rtrim($gibbon->session->get('absoluteURL'), '/') . '/modules/QuranHifz/save.php') . '" method="post">';
    echo '<input type="hidden" name="qh_csrf" value="' . qhEsc(qhToken()) . '"><input type="hidden" name="kind" value="circle">';
    echo '<label>اسم الحلقة<input name="name" maxlength="120" required></label><label>المعلم<select name="teacher" required><option value="">اختر المعلم</option>';
    foreach (qhStaffOptions($connection2) as $p) {
        echo '<option value="' . (int) $p['id'] . '">' . qhEsc($p['preferredName'] . ' ' . $p['surname']) . '</option>';
    }
    echo '</select></label><button>إنشاء الحلقة</button></form>';
    echo '<h3>إلحاق طالب بحلقة</h3><form class="qh-form" action="' . qhEsc(rtrim($gibbon->session->get('absoluteURL'), '/') . '/modules/QuranHifz/save.php') . '" method="post">';
    echo '<input type="hidden" name="qh_csrf" value="' . qhEsc(qhToken()) . '"><input type="hidden" name="kind" value="enrollment">';
    echo '<label>الحلقة<select name="circle" required><option value="">اختر الحلقة</option>';
    foreach ($rows as $r) {
        if ($r['status'] === 'Active') echo '<option value="' . (int) $r['qhCircleID'] . '">' . qhEsc($r['name']) . '</option>';
    }
    echo '</select></label><label>الطالب<select name="student" required><option value="">اختر الطالب</option>';
    foreach (qhStudentOptions($connection2) as $p) {
        echo '<option value="' . (int) $p['id'] . '">' . qhEsc($p['preferredName'] . ' ' . $p['surname']) . '</option>';
    }
    echo '</select></label><button>إلحاق الطالب</button></form></div>';
}

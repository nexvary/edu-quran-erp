<?php
require_once __DIR__ . '/moduleFunctions.php';
if (qhGuard('circles.php')) {
    qhNav();
    $sql = "SELECT c.qhCircleID, c.name, c.status, CONCAT(p.preferredName,' ',p.surname) teacherName,
            (SELECT COUNT(*) FROM qhEnrollment e WHERE e.circleID=c.qhCircleID AND e.status='Active') studentCount
            FROM qhCircle c LEFT JOIN gibbonPerson p ON p.gibbonPersonID=c.teacherPersonID ORDER BY c.qhCircleID DESC";
    $rows = $connection2->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    $staff = qhStaffOptions($connection2);
    $students = qhStudentOptions($connection2);
    $save = qhEsc(rtrim((string)$gibbon->session->get('absoluteURL'), '/') . '/modules/QuranHifz/save.php');
    $token = qhEsc(qhToken());
    echo '<main class="qh-shell" dir="rtl"><header class="qh-page-head"><div><span class="qh-overline">إدارة المجموعات</span>'
       . '<h1>الحلقات والطلاب</h1><p>أنشئ حلقات التحفيظ، وزّع المعلمين، وسجّل الطلاب من سجلات المدرسة الموحدة.</p></div>'
       . '<span class="qh-page-symbol">' . qhIcon('circles') . '</span></header>';
    qhNotice();
    echo '<section class="qh-panel"><div class="qh-panel-heading"><div><span class="qh-overline">ملفات الحلقات</span>'
       . '<h2>الحلقات المسجلة</h2></div><span class="qh-count-tag">' . count($rows) . ' حلقة</span></div>';
    if (!$rows) {
        qhEmpty('لا توجد حلقات بعد', 'أضف الحلقة الأولى باستخدام النموذج الموجود بالأسفل.');
    } else {
        echo '<div class="qh-table-scroll"><table class="qh-table"><thead><tr>'
           . '<th scope="col">الحلقة</th><th scope="col">المعلم المسؤول</th>'
           . '<th scope="col">الطلاب</th><th scope="col">الحالة</th></tr></thead><tbody>';
        foreach ($rows as $r) {
            echo '<tr><td><strong>' . qhEsc($r['name']) . '</strong></td>'
               . '<td>' . qhEsc($r['teacherName']) . '</td>'
               . '<td><span class="qh-pill">' . (int)$r['studentCount'] . ' طالب</span></td>'
               . '<td>' . ($r['status'] === 'Active' ? 'نشطة' : 'مؤرشفة') . '</td></tr>';
        }
        echo '</tbody></table></div>';
    }
    echo '</section><div class="qh-dual-grid">';
    echo '<section class="qh-panel"><div class="qh-panel-heading"><div>'
       . '<span class="qh-overline">خطوة جديدة</span><h2>إنشاء حلقة</h2></div>'
       . '<span class="qh-mini-icon">' . qhIcon('plus') . '</span></div>';
    if (!$staff) {
        qhEmpty('لا توجد بيانات معلمين', 'أضف معلمًا ضمن موظفي Gibbon قبل إنشاء حلقة.');
    } else {
        echo '<form class="qh-form" method="post" action="' . $save . '">'
           . '<input type="hidden" name="qh_csrf" value="' . $token . '">'
           . '<input type="hidden" name="kind" value="circle">'
           . '<label>اسم الحلقة<input name="name" maxlength="120" placeholder="مثال: حلقة الإتقان" required></label>'
           . '<label>المعلم المسؤول<select name="teacher" required><option value="">اختر المعلم</option>';
        foreach ($staff as $p) {
            echo '<option value="' . (int)$p['id'] . '">' . qhEsc($p['preferredName'] . ' ' . $p['surname']) . '</option>';
        }
        echo '</select></label><button type="submit">' . qhIcon('plus') . ' إنشاء الحلقة</button></form>';
    }
    echo '</section><section class="qh-panel"><div class="qh-panel-heading"><div>'
       . '<span class="qh-overline">تنظيم الطلاب</span><h2>إلحاق طالب</h2></div>'
       . '<span class="qh-mini-icon">' . qhIcon('cap') . '</span></div>';
    if (!$students || !$rows) {
        qhEmpty('يلزم وجود طلاب وحلقات', 'أضف الطلاب من إدارة المدرسة وأنشئ حلقة نشطة أولاً.');
    } else {
        echo '<form class="qh-form" method="post" action="' . $save . '">'
           . '<input type="hidden" name="qh_csrf" value="' . $token . '">'
           . '<input type="hidden" name="kind" value="enrollment">'
           . '<label>الحلقة<select name="circle" required><option value="">اختر الحلقة</option>';
        foreach ($rows as $r) {
            if ($r['status'] === 'Active') {
                echo '<option value="' . (int)$r['qhCircleID'] . '">' . qhEsc($r['name']) . '</option>';
            }
        }
        echo '</select></label><label>الطالب<select name="student" required><option value="">اختر الطالب</option>';
        foreach ($students as $p) {
            echo '<option value="' . (int)$p['id'] . '">' . qhEsc($p['preferredName'] . ' ' . $p['surname']) . '</option>';
        }
        echo '</select></label><button type="submit">' . qhIcon('plus') . ' إضافة الطالب</button></form>';
    }
    echo '</section></div></main>';
}

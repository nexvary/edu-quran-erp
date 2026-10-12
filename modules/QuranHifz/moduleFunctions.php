<?php
/**
 * QuranHifz shared Gibbon helpers. SPDX-License-Identifier: GPL-3.0-or-later.
 */
require_once __DIR__ . '/validation.php';

function qhEsc($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function qhUrl(string $page, array $query = []): string
{
    global $gibbon;
    $base = rtrim((string) $gibbon->session->get('absoluteURL'), '/');
    $query = array_merge(['q' => '/modules/QuranHifz/' . $page], $query);
    return $base . '/index.php?' . http_build_query($query);
}
function qhToken(): string
{
    global $gibbon;
    $token = $gibbon->session->get('QuranHifzCSRF');
    if (!is_string($token) || strlen($token) !== 64) {
        $token = bin2hex(random_bytes(32));
        $gibbon->session->set('QuranHifzCSRF', $token);
    }
    return $token;
}
function qhCheckToken(): bool
{
    $submitted = $_POST['qh_csrf'] ?? null;
    return is_string($submitted) && hash_equals(qhToken(), $submitted);
}
function qhActionAllowed(string $page): bool
{
    global $guid, $connection2;
    return isActionAccessible($guid, $connection2, '/modules/QuranHifz/' . $page);
}
function qhGuard(string $page): bool
{
    global $page;
    if (qhActionAllowed($page)) {
        return true;
    }
    $page->addError(__('You do not have access to this action.'));
    return false;
}
function qhNav(): void
{
    $links = [
        'index.php' => 'لوحة التحكم',
        'circles.php' => 'الحلقات',
        'meetings.php' => 'الجلسات',
        'report.php' => 'التقارير'
    ];
    echo '<nav class="qh-nav" dir="rtl">';
    foreach ($links as $page => $label) {
        if (qhActionAllowed($page)) {
            echo '<a href="' . qhEsc(qhUrl($page)) . '">' . qhEsc($label) . '</a> ';
        }
    }
    echo '</nav>';
}
function qhNotice(): void
{
    $result = $_GET['return'] ?? '';
    if ($result === 'success') {
        echo '<p class="qh-success" role="status">تم حفظ البيانات بنجاح.</p>';
    } elseif ($result === 'error') {
        echo '<p class="qh-error" role="alert">تعذر الحفظ. راجع المدخلات أو السجل الإداري.</p>';
    } elseif ($result === 'duplicate') {
        echo '<p class="qh-error" role="alert">هذا السجل موجود مسبقاً.</p>';
    }
}
function qhStudentOptions(PDO $db): array
{
    $sql = "SELECT p.gibbonPersonID AS id, p.preferredName, p.surname
            FROM gibbonPerson p JOIN gibbonRole r ON r.gibbonRoleID = p.gibbonRoleIDPrimary
            WHERE p.status = 'Full' AND r.category = 'Student'
            ORDER BY p.surname, p.preferredName";
    return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}
function qhStaffOptions(PDO $db): array
{
    $sql = "SELECT p.gibbonPersonID AS id, p.preferredName, p.surname
            FROM gibbonPerson p JOIN gibbonRole r ON r.gibbonRoleID = p.gibbonRoleIDPrimary
            WHERE p.status = 'Full' AND r.category = 'Staff'
            ORDER BY p.surname, p.preferredName";
    return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

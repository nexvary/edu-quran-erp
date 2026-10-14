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
function qhGuard(string $actionPage): bool
{
    global $page;
    if (qhActionAllowed($actionPage)) {
        return true;
    }
    $page->addError(__('You do not have access to this action.'));
    return false;
}
function qhIcon(string $name): string
{
    $paths = [
        'home' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        'circles' => '<circle cx="9" cy="8" r="3"/><path d="M3 20v-2a6 6 0 0 1 12 0v2"/><circle cx="18" cy="8" r="2"/><path d="M18 14a5 5 0 0 1 4 5v1"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18M8 14h3M8 17h3"/>',
        'chart' => '<path d="M3 3v18h18M7 16l4-5 4 3 5-7"/><path d="M16 7h4v4"/>',
        'book' => '<path d="M12 7v14M3 18V5a2 2 0 0 1 2-2h3a4 4 0 0 1 4 4 4 4 0 0 1 4-4h3a2 2 0 0 1 2 2v13h-5a4 4 0 0 0-4 3 4 4 0 0 0-4-3H3Z"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'back' => '<path d="M4 12h16m-6-6 6 6-6 6"/>',
        'cap' => '<path d="m2 9 10-5 10 5-10 5L2 9Zm4 3v5c4 3 8 3 12 0v-5M22 9v7"/>',
        'check' => '<path d="m4 12 5 5L20 6"/>',
        'star' => '<path d="m12 3 2.7 5.5 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.8 1-6.1-4.4-4.3 6.1-.9L12 3Z"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l4 2"/>',
        'arrow' => '<path d="M5 12h14m-6-6 6 6-6 6"/>',
        'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/>',
    ];
    return '<svg class="qh-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" focusable="false" aria-hidden="true">'
        . ($paths[$name] ?? $paths['home']) . '</svg>';
}
function qhNav(): void
{
    global $gibbon;
    $base = rtrim((string)$gibbon->session->get('absoluteURL'), '/');
    $asset = $base . '/modules/QuranHifz/css/module.css?v=2';
    $links = [
        'index.php' => ['الرئيسية','home'],
        'circles.php' => ['الحلقات والطلاب','circles'],
        'meetings.php' => ['الجلسات','calendar'],
        'report.php' => ['التقارير','chart'],
    ];
    $current = basename((string)($_GET['q'] ?? 'index.php'));
    echo '<link rel="stylesheet" href="' . qhEsc($asset) . '">';
    echo '<div class="qh-toolbar" dir="rtl">';
    echo '<div class="qh-top-row"><div class="qh-brand">'
        . '<span class="qh-mark">' . qhIcon('book') . '</span>'
        . '<span><strong>EDU <em>QURAN</em> ERP</strong><small>مساحة إدارة حلقات القرآن</small></span></div>';
    echo '<a class="qh-school-link" href="' . qhEsc($base . '/index.php') . '">'
        . qhIcon('cap') . 'العودة للمدرسة' . qhIcon('back') . '</a></div>';
    echo '<nav class="qh-nav" aria-label="التنقل داخل وحدة التحفيظ">';
    foreach ($links as $name => $data) {
        if (!qhActionAllowed($name)) continue;
        $active = $name === $current;
        echo '<a class="' . ($active ? 'is-active' : '') . '" href="' . qhEsc(qhUrl($name)) . '"'
            . ($active ? ' aria-current="page"' : '') . '>'
            . qhIcon($data[1]) . '<span>' . qhEsc($data[0]) . '</span></a>';
    }
    echo '</nav></div>';
}
function qhEmpty(string $title, string $description, ?string $url = null, string $action = ''): void
{
    echo '<div class="qh-empty">' . qhIcon('book') . '<strong>' . qhEsc($title) . '</strong>'
       . '<p>' . qhEsc($description) . '</p>';
    if ($url !== null && $action !== '') {
        echo '<a class="qh-btn qh-btn-ghost" href="' . qhEsc($url) . '">' . qhEsc($action) . qhIcon('arrow') . '</a>';
    }
    echo '</div>';
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

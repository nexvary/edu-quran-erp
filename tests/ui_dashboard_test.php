<?php
/**
 * Dependency-free smoke rendering test for the QuranHifz dashboard.
 * It exercises rendered markup using a mocked Gibbon session and an empty DB.
 */
declare(strict_types=1);

final class FakeSession
{
    public function get(string $key): string
    {
        return $key === 'absoluteURL' ? 'https://school.example.invalid' : '';
    }
}
final class FakeStatement
{
    public function fetchColumn(): int { return 0; }
    public function fetchAll(int $mode = PDO::FETCH_ASSOC): array { return []; }
}
final class FakeConnection
{
    public array $queries = [];
    public function query(string $sql): FakeStatement
    {
        $this->queries[] = $sql;
        return new FakeStatement();
    }
}
function isActionAccessible($guid, $connection, $path): bool
{
    return str_starts_with((string)$path, '/modules/QuranHifz/');
}
$guid = 'test';
$gibbon = (object)['session' => new FakeSession()];
$connection2 = new FakeConnection();
$_GET['q'] = '/modules/QuranHifz/index.php';
ob_start();
require __DIR__ . '/../modules/QuranHifz/index.php';
$html = ob_get_clean();

$required = [
    'lang' => 'dir="rtl"',
    'navigation' => 'aria-label="التنقل داخل وحدة التحفيظ"',
    'dashboard' => 'مؤشرات دار التحفيظ',
    'performance' => 'أداء الحلقات',
    'activities' => 'سجل التسميع والمراجعة',
    'empty state' => 'لم تُنشأ حلقات بعد',
    'CSS' => '/modules/QuranHifz/css/module.css?v=2',
    'working link' => 'q=%2Fmodules%2FQuranHifz%2Fcircles.php',
];
foreach ($required as $key => $needle) {
    if (!str_contains($html, $needle)) {
        fwrite(STDERR, "Dashboard missing $key: $needle\n");
        exit(1);
    }
}
if (substr_count($html, 'class="qh-kpi ') !== 4) {
    fwrite(STDERR, "Expected four data-driven KPI cards\n");
    exit(1);
}
if (count($connection2->queries) < 7) {
    fwrite(STDERR, "The dashboard did not query its live data sources\n");
    exit(1);
}
echo "Dashboard render contract passed\n";

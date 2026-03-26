<?php
require_once __DIR__.'/../includes/functions.php';
require_login(['admin','manager']);
verify_csrf();

$pdo = db();
$date = $_GET['date'] ?? date('Y-m-d');
$branchFilter = (role()==='manager' && manager_scoped()) ? (int)user_branch_id() : null;

if (isset($_POST['generate_now']) && role()==='admin') {
    generate_daily_reports_if_needed($date);
    flash('ok', 'تم توليد التقرير وإرسال الإيميلات حسب الإعدادات');
    header('Location: reports.php?date='.urlencode($date));
    exit;
}

$rows = report_rows_for_date($date, $branchFilter);

if (isset($_GET['export']) && $_GET['export']==='xls') {
    $filename = 'daily_booking_report_'.$date.'.xls';
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    echo build_excel_html($rows);
    exit;
}

$title = 'التقارير';
include __DIR__.'/../includes/header.php';
?>
<div class='card p-3'>
  <a href='dashboard.php' class='btn btn-sm btn-outline-secondary mb-2'>رجوع</a>

  <form method='get' class='row g-2 mb-2'>
    <div class='col-md-3'><input class='form-control' type='date' name='date' value='<?= e($date) ?>'></div>
    <div class='col-md-2'><button class='btn btn-dark w-100'>عرض</button></div>
    <div class='col-md-3'><a class='btn btn-success w-100' href='?date=<?= e($date) ?>&export=xls'>تنزيل Excel</a></div>
  </form>

  <?php if(role()==='admin'): ?>
  <form method='post' class='mb-3'>
    <input type='hidden' name='<?= e($config['security']['csrf_key']) ?>' value='<?= e(csrf_token()) ?>'>
    <button class='btn btn-primary' name='generate_now' value='1'>توليد الآن + إرسال الإيميل</button>
  </form>
  <?php endif; ?>

  <table class='table table-bordered table-sm'>
    <tr><th>الاسم</th><th>رقم الموبايل</th><th>التاريخ</th><th>الوقت</th><th>الفرع</th></tr>
    <?php foreach($rows as $r): ?>
      <tr>
        <td><?= e($r['full_name']) ?></td>
        <td><?= e($r['phone']) ?></td>
        <td><?= e($r['booking_date']) ?></td>
        <td><?= e(($r['slot_time'] ?? '').' - '.($r['slot_to'] ?? '')) ?></td>
        <td><?= e($r['branch_name']) ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php include __DIR__.'/../includes/footer.php';

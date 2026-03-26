<?php
require_once __DIR__.'/../includes/functions.php';
require_login(['admin','manager','employee','branch_employee']);
verify_csrf();
$pdo = db();

if (isset($_POST['cancel']) && in_array(role(), ['admin','manager'], true)) {
    $p = [':id' => (int)$_POST['id']];
    $q = 'UPDATE appointments SET status="cancelled" WHERE id=:id';
    if (role() === 'manager' && manager_scoped()) {
        $q .= ' AND branch_id=:b';
        $p[':b'] = (int)user_branch_id();
    }
    $pdo->prepare($q)->execute($p);
    flash('ok', 'تم إلغاء الموعد بنجاح');
    header('Location: appointments.php');
    exit;
}

$params = [];
$cl = allowed_branch_clause($params);
$s = $pdo->prepare('SELECT a.*,b.name branch_name,c.name company_name FROM appointments a LEFT JOIN branches b ON b.id=a.branch_id LEFT JOIN remittance_companies c ON c.id=a.company_id WHERE 1=1 '.$cl.' ORDER BY a.id DESC');
$s->execute($params);
$rows = $s->fetchAll();
$title = 'المواعيد';
include __DIR__.'/../includes/header.php';
?>
<div class='card p-3'>
  <div class='d-flex justify-content-between mb-2'>
    <a class='btn btn-sm btn-outline-secondary' href='dashboard.php'>رجوع</a>
    <input id='apptSearch' class='form-control form-control-sm w-auto' placeholder='بحث بالاسم/الهاتف/الفرع'>
  </div>

  <div class='table-responsive'>
    <table class='table table-bordered table-sm align-middle' id='apptTable'>
      <tr><th>ID</th><th>فرع</th><th>شركة</th><th>تاريخ</th><th>وقت</th><th>الاسم</th><th>هاتف</th><th>حالة</th><th></th></tr>
      <?php foreach($rows as $r): ?>
      <tr>
        <td><?= $r['id'] ?></td>
        <td><?= e($r['branch_name']) ?></td>
        <td><?= e($r['company_name']) ?></td>
        <td><?= e($r['booking_date']) ?></td>
        <td><?= e($r['slot_time']) ?>-<?= e($r['slot_to']) ?></td>
        <td><?= e($r['full_name']) ?></td>
        <td><?= e($r['phone']) ?></td>
        <td>
          <?php if ($r['status'] === 'booked'): ?>
            <span class='badge text-bg-success'>booked</span>
          <?php else: ?>
            <span class='badge text-bg-secondary'><?= e($r['status']) ?></span>
          <?php endif; ?>
        </td>
        <td>
          <?php if(in_array(role(),['admin','manager'],true) && $r['status']==='booked'): ?>
            <form method='post' class='js-cancel-form'>
              <input type='hidden' name='<?= e($config['security']['csrf_key']) ?>' value='<?= e(csrf_token()) ?>'>
              <input type='hidden' name='id' value='<?= $r['id'] ?>'>
              <button class='btn btn-sm btn-danger' name='cancel' value='1'>إلغاء</button>
            </form>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>
</div>
<script>
  document.querySelectorAll('.js-cancel-form').forEach(function(form){
    form.addEventListener('submit', function(e){
      if (!confirm('تأكيد إلغاء هذا الموعد؟')) e.preventDefault();
    });
  });
  document.getElementById('apptSearch')?.addEventListener('input', function(){
    const q = this.value.trim().toLowerCase();
    document.querySelectorAll('#apptTable tr').forEach(function(tr, idx){
      if (idx === 0) return;
      tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
  });
</script>
<?php include __DIR__.'/../includes/footer.php';

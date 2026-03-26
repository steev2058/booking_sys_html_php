<?php
require_once __DIR__.'/includes/functions.php';
verify_csrf();
$pdo = db();

if (!isset($_SESSION['captcha'])) $_SESSION['captcha'] = (string)random_int(10000, 99999);
if (isset($_POST['refresh_captcha'])) { $_SESSION['captcha'] = (string)random_int(10000, 99999); header('Location: /'); exit; }

if (isset($_POST['send_otp'])) {
  $phone = trim($_POST['phone'] ?? '');
  $name = trim($_POST['full_name'] ?? '');
  $tn = trim($_POST['transfer_number'] ?? '');
  $cap = trim($_POST['captcha'] ?? '');

  $branchId = (int)($_POST['branch_id'] ?? 0);
  $companyId = (int)($_POST['company_id'] ?? 0);
  $bookingDate = trim((string)($_POST['booking_date'] ?? ''));
  $slotTime = trim((string)($_POST['slot_time'] ?? ''));

  if ($branchId <= 0 || $companyId <= 0 || !$bookingDate || !$slotTime) flash('err', 'يرجى اختيار الفرع والتاريخ والوقت والشركة أولاً');
  elseif (!booking_date_allowed($bookingDate)) flash('err', 'يمكن الحجز بدءاً من تاريخ الغد فقط');
  elseif (!is_valid_phone($phone)) flash('err', 'رقم الهاتف يجب أن يبدأ بـ 09 ويتكون من 10 أرقام');
  elseif (!is_valid_full_name($name)) flash('err', 'الاسم يجب أن يحتوي أحرفاً فقط وبحد أدنى 3 أحرف');
  elseif (!is_valid_transfer_number($tn)) flash('err', 'رقم الحوالة يجب أن يكون أحرف/أرقام إنجليزية فقط');
  elseif ($cap !== ($_SESSION['captcha'] ?? '')) flash('err', 'كابتشا غير صحيحة');
  elseif (otp_locked($phone)) flash('err', 'تم قفل المحاولات مؤقتاً');
  else {
    [$canSend, $otpErr] = otp_can_send($phone);
    if (!$canSend) flash('err', $otpErr ?: 'تجاوزت حد طلبات OTP ضمن النافذة الزمنية');
    else {
      $otp = (string)random_int(1000, 9999);
      $pdo->prepare('INSERT INTO otp_codes (phone,full_name,code,transfer_number,expires_at,used,created_at) VALUES (?,?,?,?,DATE_ADD(NOW(),INTERVAL 5 MINUTE),0,NOW())')->execute([$phone, $name, $otp, $tn]);
      $sms = send_sms_otp($phone, $otp);
      if (!$sms['ok']) {
        flash('err', 'فشل إرسال OTP عبر مزود SMS: ' . ($sms['text'] ?? 'provider error'));
      } else {
        $_SESSION['pending'] = [
          'branch_id' => $branchId,
          'company_id' => $companyId,
          'booking_date' => $bookingDate,
          'slot_time' => $slotTime,
          'phone' => $phone,
          'full_name' => $name,
          'transfer_number' => $tn,
        ];
        flash('ok', 'تم إرسال رمز التحقق بنجاح');
      }
    }
  }
  header('Location: /');
  exit;
}

if (isset($_POST['confirm_booking'])) {
  $p = $_SESSION['pending'] ?? null;
  $otp = trim($_POST['otp_code'] ?? '');

  if (!$p) flash('err', 'ابدأ الطلب أولاً');
  elseif (!booking_date_allowed($p['booking_date'])) flash('err', 'الحجز من الغد فقط');
  elseif (is_holiday($p['booking_date'])) flash('err', 'هذا اليوم عطلة');
  elseif (otp_locked($p['phone'])) flash('err', 'تم قفل المحاولات مؤقتاً بسبب كثرة الإدخال الخاطئ');
  else {
    $day = date('l', strtotime($p['booking_date']));
    $d = $pdo->prepare('SELECT * FROM business_days WHERE branch_id=? AND day_name=? AND active=1');
    $d->execute([$p['branch_id'], $day]);
    $cfg = $d->fetch();

    if (!$cfg) flash('err', 'اليوم غير متاح');
    else {
      $oc = $pdo->prepare('SELECT * FROM otp_codes WHERE phone=? AND transfer_number=? AND code=? AND used=0 ORDER BY id DESC LIMIT 1');
      $oc->execute([$p['phone'], $p['transfer_number'], $otp]);
      $or = $oc->fetch();

      if (!$or) {
        $locked = otp_track_verify_fail($p['phone']);
        flash('err', $locked ? 'تم قفل المحاولات مؤقتاً بسبب إدخال OTP خاطئة' : 'OTP غير صحيحة');
      } else {
        $c = $pdo->prepare('SELECT COUNT(*) FROM appointments WHERE branch_id=? AND booking_date=? AND slot_time=? AND status="booked"');
        $c->execute([$p['branch_id'], $p['booking_date'], $p['slot_time']]);
        if ((int)$c->fetchColumn() >= 3) flash('err', 'الشريحة ممتلئة');
        else {
          $to = date('H:i', strtotime($p['slot_time'] . ' +' . $cfg['interval_minutes'] . ' minutes'));
          $pdo->prepare('INSERT INTO appointments (transfer_number,branch_id,company_id,day_name,booking_date,slot_time,slot_to,phone,full_name,status,created_at) VALUES (?,?,?,?,?,?,?,?,?,"booked",NOW())')->execute([$p['transfer_number'], $p['branch_id'], $p['company_id'], $day, $p['booking_date'], $p['slot_time'], $to, $p['phone'], $p['full_name']]);
          $pdo->prepare('UPDATE otp_codes SET used=1 WHERE id=?')->execute([$or['id']]);
          otp_reset_verify_fail($p['phone']);

          $bn = $pdo->prepare('SELECT name FROM branches WHERE id=?');
          $bn->execute([$p['branch_id']]);
          $branchName = (string)$bn->fetchColumn();
          $confirmMsg = 'السيد ' . $p['full_name'] . ' تم حجز دور لمراجعة فرع ' . $branchName . ' لاستلام حوالة ' . $p['transfer_number'] . ' من الساعة ' . $p['slot_time'] . ' إلى الساعة ' . $to . ' بتاريخ ' . $p['booking_date'] . '.';
          send_sms_raw($p['phone'], $confirmMsg);
          send_daily_reports_emails_if_needed(date('Y-m-d'));

          unset($_SESSION['pending']);
          flash('ok', 'تم الحجز بنجاح');
        }
      }
    }
  }
  header('Location: /');
  exit;
}

$branches = $pdo->query('SELECT id,name FROM branches WHERE active=1 ORDER BY name')->fetchAll();
$companies = $pdo->query('SELECT id,name FROM remittance_companies WHERE active=1 ORDER BY name')->fetchAll();
$days = []; $slots = [];
$bid = (int)($_GET['branch_id'] ?? 0);
$bdate = $_GET['booking_date'] ?? '';

if ($bid) {
  $cfg = $pdo->prepare('SELECT * FROM business_days WHERE branch_id=? AND active=1');
  $cfg->execute([$bid]);
  $rows = $cfg->fetchAll();
  $allowed = array_column($rows, 'day_name');

  for ($i = 1; $i <= 21; $i++) {
    $d = date('Y-m-d', strtotime("+$i day"));
    if (in_array(date('l', strtotime($d)), $allowed, true) && !is_holiday($d)) $days[] = $d;
  }

  if ($bdate) {
    $day = date('l', strtotime($bdate));
    $r = null;
    foreach ($rows as $x) if ($x['day_name'] === $day) $r = $x;
    if ($r) {
      $s = strtotime($r['start_time']);
      $e = strtotime($r['end_time']);
      $int = (int)$r['interval_minutes'] * 60;
      for ($t = $s; $t + $int <= $e; $t += $int) {
        $from = date('H:i', $t);
        $c = $pdo->prepare('SELECT COUNT(*) FROM appointments WHERE branch_id=? AND booking_date=? AND slot_time=? AND status="booked"');
        $c->execute([$bid, $bdate, $from]);
        $slots[] = ['time' => $from, 'available' => (int)$c->fetchColumn() < 3];
      }
    }
  }
}

$title='الحجز'; include __DIR__.'/includes/header.php';
?>
<div class='card p-3'>
  <form method='get' class='row g-2 js-busy-form'>
    <div class='col-md-4'>
      <label class='form-label'>الفرع</label>
      <select class='form-select' name='branch_id' required>
        <option value=''>اختر</option>
        <?php foreach($branches as $b): ?>
          <option value='<?= $b['id'] ?>' <?= $bid===$b['id']?'selected':'' ?>><?= e($b['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class='col-md-4'>
      <label class='form-label'>التاريخ</label>
      <select class='form-select' name='booking_date' required>
        <?php foreach($days as $d): ?>
          <option value='<?= e($d) ?>' <?= $bdate===$d?'selected':'' ?>><?= e($d) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class='col-md-4 d-flex align-items-end'>
      <button class='btn btn-dark w-100 js-busy-btn'>تحميل المواعيد المتاحة</button>
    </div>
  </form>

  <hr>

  <form method='post' class='row g-2 js-busy-form' id='bookingForm'>
    <input type='hidden' name='<?= e($config['security']['csrf_key']) ?>' value='<?= e(csrf_token()) ?>'>
    <input type='hidden' name='branch_id' value='<?= $bid ?>'>
    <input type='hidden' name='booking_date' value='<?= e($bdate) ?>'>
    <div class='col-md-4'>
      <label class='form-label'>الشركة</label>
      <select class='form-select' name='company_id' required>
        <?php foreach($companies as $c): ?><option value='<?= $c['id'] ?>'><?= e($c['name']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class='col-md-4'>
      <label class='form-label'>الوقت</label>
      <select class='form-select' name='slot_time' required>
        <?php foreach($slots as $s): if(!$s['available']) continue; ?><option value='<?= e($s['time']) ?>'><?= e($s['time']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class='col-md-4'>
      <label class='form-label'>رقم الحوالة</label>
      <input class='form-control' name='transfer_number' required pattern='[A-Za-z0-9]+' title='أحرف وأرقام إنجليزية فقط'>
    </div>
    <div class='col-md-4'>
      <label class='form-label'>الهاتف</label>
      <input class='form-control' name='phone' required inputmode='numeric' pattern='09\d{8}' placeholder='09xxxxxxxx'>
    </div>
    <div class='col-md-4'>
      <label class='form-label'>الاسم الثلاثي</label>
      <input class='form-control' name='full_name' required minlength='3'>
    </div>
    <div class='col-md-2'>
      <label class='form-label'>كابتشا</label>
      <input class='form-control' readonly value='<?= e($_SESSION['captcha']) ?>'>
    </div>
    <div class='col-md-2'>
      <label class='form-label'>أدخل</label>
      <input class='form-control' name='captcha' required>
    </div>
    <div class='col-md-4 d-flex align-items-end gap-2'>
      <button class='btn btn-primary w-100 js-busy-btn' name='send_otp' value='1'>إرسال OTP</button>
      <button class='btn btn-outline-secondary w-100' name='refresh_captcha' value='1'>تحديث</button>
    </div>
  </form>

  <?php if(!empty($_SESSION['pending'])): ?>
    <hr>
    <form method='post' class='row g-2 js-busy-form'>
      <input type='hidden' name='<?= e($config['security']['csrf_key']) ?>' value='<?= e(csrf_token()) ?>'>
      <div class='col-md-4'><input class='form-control' name='otp_code' maxlength='4' minlength='4' required placeholder='OTP'></div>
      <div class='col-md-4'><button class='btn btn-success w-100 js-busy-btn' name='confirm_booking' value='1'>تأكيد الحجز</button></div>
    </form>
  <?php endif; ?>
</div>
<script>
  document.querySelectorAll('.js-busy-form').forEach(function(form){
    form.addEventListener('submit', function(){
      const btn = form.querySelector('.js-busy-btn');
      if (!btn) return;
      btn.disabled = true;
      const old = btn.innerHTML;
      btn.dataset.old = old;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> جارٍ التنفيذ...';
    });
  });
</script>
<?php include __DIR__.'/includes/footer.php';

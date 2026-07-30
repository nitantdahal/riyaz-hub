<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');

$db = getDB();
$instruments = $db->query('SELECT i.*, (SELECT COUNT(*) FROM users u WHERE u.instrument_id = i.id) AS student_count,
                            (SELECT COUNT(*) FROM practice_sessions ps WHERE ps.instrument_id = i.id) AS session_count
                            FROM instruments i ORDER BY i.name')->fetchAll();

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : null;
$editInstrument = null;
if ($editId) {
    foreach ($instruments as $i) { if ((int)$i['id'] === $editId) { $editInstrument = $i; break; } }
}

$pageTitle = 'Manage Instruments';
$pageSubtitle = count($instruments) . ' instrument(s) configured';
$activeNav = 'instruments';
require __DIR__ . '/../includes/sidebar.php';
?>

<div class="card">
  <div class="card-header">
    <h3>Instrument Catalogue</h3>
    <button class="btn btn-primary btn-sm" onclick="openModal('addInsModal')">+ Add Instrument</button>
  </div>
  <div class="grid grid-3">
    <?php foreach ($instruments as $ins): ?>
      <div class="card" style="background:var(--surface);">
        <div style="font-size:2rem; margin-bottom:8px;"><?= h($ins['icon']) ?></div>
        <h3 style="font-size:1.05rem;"><?= h($ins['name']) ?></h3>
        <p class="text-faint" style="font-size:.82rem; min-height:2.6em;"><?= h($ins['description']) ?></p>
        <div class="flex justify-between" style="font-size:.78rem; color:var(--text-faint); margin-bottom:14px;">
          <span><?= (int)$ins['student_count'] ?> students</span>
          <span><?= (int)$ins['session_count'] ?> sessions</span>
        </div>
        <div class="flex gap-8">
          <a class="btn btn-outline btn-sm" style="flex:1;" href="?edit=<?= (int)$ins['id'] ?>">Edit</a>
          <form action="instrument_action.php" method="POST" onsubmit="return confirmDelete('Delete this instrument? Students using it will be unassigned.')" style="flex:1;">
            <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= (int)$ins['id'] ?>">
            <button type="submit" class="btn btn-danger btn-sm btn-block">Delete</button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<div class="modal-overlay" id="addInsModal">
  <div class="modal">
    <div class="modal-header"><h3>Add Instrument</h3><button class="modal-close" onclick="closeModal('addInsModal')">✕</button></div>
    <form action="instrument_action.php" method="POST">
      <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
      <input type="hidden" name="action" value="create">
      <div class="field"><label>Name</label><input type="text" name="name" required></div>
      <div class="field"><label>Icon (emoji)</label><input type="text" name="icon" value="🎵" maxlength="10"></div>
      <div class="field"><label>Description</label><textarea name="description"></textarea></div>
      <button type="submit" class="btn btn-primary btn-block">Add Instrument</button>
    </form>
  </div>
</div>

<?php if ($editInstrument): ?>
<div class="modal-overlay open" id="editInsModal">
  <div class="modal">
    <div class="modal-header"><h3>Edit Instrument</h3><a class="modal-close" href="manage_instruments.php">✕</a></div>
    <form action="instrument_action.php" method="POST">
      <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="id" value="<?= (int)$editInstrument['id'] ?>">
      <div class="field"><label>Name</label><input type="text" name="name" value="<?= h($editInstrument['name']) ?>" required></div>
      <div class="field"><label>Icon (emoji)</label><input type="text" name="icon" value="<?= h($editInstrument['icon']) ?>" maxlength="10"></div>
      <div class="field"><label>Description</label><textarea name="description"><?= h($editInstrument['description']) ?></textarea></div>
      <button type="submit" class="btn btn-primary btn-block">Save Changes</button>
    </form>
  </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>

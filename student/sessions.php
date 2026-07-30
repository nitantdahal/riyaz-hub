<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('student');

$db = getDB();
$studentId = $_SESSION['user_id'];
$instruments = $db->query('SELECT id, name, icon FROM instruments ORDER BY name')->fetchAll();

$stmt = $db->prepare('SELECT ps.*, i.name AS instrument_name, i.icon FROM practice_sessions ps
                       LEFT JOIN instruments i ON i.id = ps.instrument_id
                       WHERE ps.student_id = ? ORDER BY ps.session_date DESC, ps.created_at DESC');
$stmt->execute([$studentId]);
$sessions = $stmt->fetchAll();

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : null;
$editSession = null;
if ($editId) {
    foreach ($sessions as $s) { if ((int)$s['id'] === $editId) { $editSession = $s; break; } }
}

$pageTitle = 'Practice Sessions';
$pageSubtitle = 'Every session you log builds your streak and your skill.';
$activeNav = 'sessions';
require __DIR__ . '/../includes/sidebar.php';
?>

<div class="card">
  <div class="card-header">
    <h3>All Sessions (<?= count($sessions) ?>)</h3>
    <button class="btn btn-primary btn-sm" onclick="openModal('addSessionModal')">+ Add Session</button>
  </div>

  <?php if (!$sessions): ?>
    <div class="empty-state">
      <div class="eq-bars"><span></span><span></span><span></span><span></span><span></span></div>
      <h3>No sessions logged yet</h3>
      <p>Click "Add Session" to record your first practice log.</p>
    </div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Date</th><th>Instrument</th><th>Focus</th><th>Duration</th><th>Mood</th><th>Notes</th><th>Video</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($sessions as $s): ?>
        <tr>
          <td><?= date('M j, Y', strtotime($s['session_date'])) ?></td>
          <td class="tag-instrument"><?= h($s['icon'] ?? '🎵') ?> <?= h($s['instrument_name'] ?? '—') ?></td>
          <td><?= h($s['focus_area'] ?: '—') ?></td>
          <td><?= (int)$s['duration_minutes'] ?> min</td>
          <td>
            <?php
              $moodBadge = ['great'=>'badge-success','good'=>'badge-gold','okay'=>'badge-cyan','tough'=>'badge-danger'];
              $cls = $moodBadge[$s['mood']] ?? 'badge-muted';
            ?>
            <span class="badge <?= $cls ?>"><?= h(ucfirst($s['mood'])) ?></span>
          </td>
          <td class="text-faint" style="max-width:220px; white-space:normal;"><?= h($s['notes'] ?: '—') ?></td>
          <td>
            <?php if (!empty($s['video_path'])): ?>
              <button type="button" class="btn btn-outline btn-sm" onclick="openModal('watch-<?= (int)$s['id'] ?>')">🎥 Watch</button>
            <?php else: ?>
              <span class="text-faint">—</span>
            <?php endif; ?>
          </td>
          <td>
            <div class="flex gap-8">
              <a class="btn btn-outline btn-sm" href="?edit=<?= (int)$s['id'] ?>">Edit</a>
              <form action="session_action.php" method="POST" onsubmit="return confirmDelete('Delete this practice session? Its video (if any) will also be removed.')">
                <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- Video Watch Modals -->
<?php foreach ($sessions as $s): if (!empty($s['video_path'])): ?>
<div class="modal-overlay" id="watch-<?= (int)$s['id'] ?>">
  <div class="modal" style="max-width:640px;">
    <div class="modal-header">
      <h3>🎥 <?= h($s['focus_area'] ?: ($s['instrument_name'] ?? 'Practice Video')) ?></h3>
      <button class="modal-close" onclick="closeModal('watch-<?= (int)$s['id'] ?>')">✕</button>
    </div>
    <?php if (isAudioOnlyMedia($s['video_path'])): ?>
      <audio controls style="width:100%;" src="../<?= h($s['video_path']) ?>"></audio>
    <?php else: ?>
      <video controls style="width:100%; border-radius: var(--radius-sm); background:#000;" src="../<?= h($s['video_path']) ?>"></video>
    <?php endif; ?>
    <p class="text-faint" style="margin-top:12px; font-size:.82rem;"><?= date('M j, Y', strtotime($s['session_date'])) ?> · <?= (int)$s['duration_minutes'] ?> min</p>
  </div>
</div>
<?php endif; endforeach; ?>

<!-- Add Modal -->
<div class="modal-overlay" id="addSessionModal">
  <div class="modal">
    <div class="modal-header"><h3>Log Practice Session</h3><button class="modal-close" onclick="closeModal('addSessionModal')">✕</button></div>
    <form action="session_action.php" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
      <input type="hidden" name="action" value="create">
      <div class="field"><label>Instrument</label>
        <select name="instrument_id">
          <?php foreach ($instruments as $ins): ?><option value="<?= (int)$ins['id'] ?>"><?= h($ins['icon'].' '.$ins['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="field"><label>Date</label><input type="date" name="session_date" value="<?= date('Y-m-d') ?>" required></div>
      <div class="field"><label>Duration (minutes)</label><input type="number" name="duration_minutes" min="1" max="600" required></div>
      <div class="field"><label>Focus area</label><input type="text" name="focus_area" placeholder="e.g. Scales, sight reading"></div>
      <div class="field"><label>Mood</label>
        <div class="chip-select">
          <label><input type="radio" name="mood" value="great" checked><span>😄 Great</span></label>
          <label><input type="radio" name="mood" value="good"><span>🙂 Good</span></label>
          <label><input type="radio" name="mood" value="okay"><span>😐 Okay</span></label>
          <label><input type="radio" name="mood" value="tough"><span>😓 Tough</span></label>
        </div>
      </div>
      <div class="field"><label>Notes</label><textarea name="notes"></textarea></div>
      <div class="field">
        <label>🎥🎙️ Practice video or voice recording <span class="text-faint">(optional)</span></label>
        <input type="file" name="practice_video" accept="video/mp4,video/quicktime,video/webm,video/ogg,video/x-msvideo,video/x-matroska,audio/mpeg,audio/wav,audio/mp4,audio/aac,audio/webm,audio/ogg">
        <p class="hint">MP4, MOV, WEBM, OGG, AVI, MKV, MP3, WAV, M4A — up to 150MB.</p>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Save Session 🎶</button>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<?php if ($editSession): ?>
<div class="modal-overlay open" id="editSessionModal">
  <div class="modal">
    <div class="modal-header"><h3>Edit Session</h3><a class="modal-close" href="sessions.php">✕</a></div>
    <form action="session_action.php" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="id" value="<?= (int)$editSession['id'] ?>">
      <div class="field"><label>Instrument</label>
        <select name="instrument_id">
          <?php foreach ($instruments as $ins): ?><option value="<?= (int)$ins['id'] ?>" <?= $ins['id']==$editSession['instrument_id']?'selected':'' ?>><?= h($ins['icon'].' '.$ins['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="field"><label>Date</label><input type="date" name="session_date" value="<?= h($editSession['session_date']) ?>" required></div>
      <div class="field"><label>Duration (minutes)</label><input type="number" name="duration_minutes" min="1" max="600" value="<?= (int)$editSession['duration_minutes'] ?>" required></div>
      <div class="field"><label>Focus area</label><input type="text" name="focus_area" value="<?= h($editSession['focus_area']) ?>"></div>
      <div class="field"><label>Mood</label>
        <div class="chip-select">
          <?php foreach (['great'=>'😄 Great','good'=>'🙂 Good','okay'=>'😐 Okay','tough'=>'😓 Tough'] as $val=>$lbl): ?>
          <label><input type="radio" name="mood" value="<?= $val ?>" <?= $editSession['mood']==$val?'checked':'' ?>><span><?= $lbl ?></span></label>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="field"><label>Notes</label><textarea name="notes"><?= h($editSession['notes']) ?></textarea></div>
      <div class="field">
        <label>🎥🎙️ Practice video or voice recording <span class="text-faint">(optional)</span></label>
        <?php if (!empty($editSession['video_path'])): ?>
          <?php if (isAudioOnlyMedia($editSession['video_path'])): ?>
            <audio controls style="width:100%; margin-bottom:10px;" src="../<?= h($editSession['video_path']) ?>"></audio>
          <?php else: ?>
            <video controls style="width:100%; border-radius: var(--radius-sm); background:#000; margin-bottom:10px;" src="../<?= h($editSession['video_path']) ?>"></video>
          <?php endif; ?>
          <label style="display:flex; align-items:center; gap:8px; font-weight:500; font-size:.85rem; color:var(--text-dim); margin-bottom:10px;">
            <input type="checkbox" name="remove_video" value="1" style="width:auto;"> Remove current video
          </label>
        <?php endif; ?>
        <input type="file" name="practice_video" accept="video/mp4,video/quicktime,video/webm,video/ogg,video/x-msvideo,video/x-matroska,audio/mpeg,audio/wav,audio/mp4,audio/aac,audio/webm,audio/ogg">
        <p class="hint">Uploading a new file replaces the current one. MP4, MOV, WEBM, OGG, AVI, MKV, MP3, WAV, M4A — up to 150MB.</p>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Update Session</button>
    </form>
  </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>

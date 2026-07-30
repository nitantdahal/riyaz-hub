<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');

$db = getDB();
$instruments = $db->query('SELECT id, name, icon FROM instruments ORDER BY name')->fetchAll();
$instructorsList = $db->query("SELECT id, full_name FROM users WHERE role='instructor' ORDER BY full_name")->fetchAll();

$roleFilter = $_GET['role'] ?? '';
$search = trim($_GET['q'] ?? '');

$sql = 'SELECT u.*, i.name AS instrument_name, i.icon, ins.full_name AS instructor_name
        FROM users u
        LEFT JOIN instruments i ON i.id = u.instrument_id
        LEFT JOIN users ins ON ins.id = u.instructor_id
        WHERE 1=1';
$params = [];
if (in_array($roleFilter, ['admin','instructor','student'], true)) {
    $sql .= ' AND u.role = ?';
    $params[] = $roleFilter;
}
if ($search !== '') {
    $sql .= ' AND (u.full_name LIKE ? OR u.email LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$sql .= ' ORDER BY u.created_at DESC';
$stmt = $db->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : null;
$editUser = null;
if ($editId) {
    $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$editId]);
    $editUser = $stmt->fetch();
}

$pageTitle = 'Manage Users';
$pageSubtitle = count($users) . ' user(s) found';
$activeNav = 'users';
require __DIR__ . '/../includes/sidebar.php';
?>

<div class="card" style="margin-bottom:20px;">
  <form method="GET" class="flex gap-12 items-center" style="flex-wrap:wrap;">
    <div class="field mb-0 search-box">
      <label>Search</label>
      <input type="text" name="q" placeholder="Name or email…" value="<?= h($search) ?>">
    </div>
    <div class="field mb-0">
      <label>Role</label>
      <select name="role" onchange="this.form.submit()">
        <option value="">All roles</option>
        <option value="admin" <?= $roleFilter==='admin'?'selected':'' ?>>Admin</option>
        <option value="instructor" <?= $roleFilter==='instructor'?'selected':'' ?>>Instructor</option>
        <option value="student" <?= $roleFilter==='student'?'selected':'' ?>>Student</option>
      </select>
    </div>
    <button type="submit" class="btn btn-outline" style="align-self:flex-end;">Filter</button>
    <button type="button" class="btn btn-primary" style="margin-left:auto; align-self:flex-end;" onclick="openModal('addUserModal')">+ Add User</button>
  </form>
</div>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Instrument</th><th>Instructor</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($users as $u): ?>
        <tr>
          <td class="flex items-center gap-12"><div class="avatar" style="width:30px;height:30px;font-size:.75rem;background:<?= h($u['avatar_color']) ?>"><?= h(strtoupper(substr($u['full_name'],0,1))) ?></div><?= h($u['full_name']) ?></td>
          <td><?= h($u['email']) ?></td>
          <td><span class="badge badge-<?= $u['role']==='admin'?'magenta':($u['role']==='instructor'?'cyan':'gold') ?>"><?= h(ucfirst($u['role'])) ?></span></td>
          <td class="tag-instrument"><?= $u['instrument_name'] ? h($u['icon'].' '.$u['instrument_name']) : '—' ?></td>
          <td><?= h($u['instructor_name'] ?? '—') ?></td>
          <td><span class="badge <?= $u['status']==='active'?'badge-success':'badge-danger' ?>"><?= h(ucfirst($u['status'])) ?></span></td>
          <td>
            <div class="flex gap-8">
              <a class="btn btn-outline btn-sm" href="?edit=<?= (int)$u['id'] ?>&role=<?= h($roleFilter) ?>&q=<?= urlencode($search) ?>">Edit</a>
              <?php if ($u['id'] != $_SESSION['user_id']): ?>
              <form action="user_action.php" method="POST" onsubmit="return confirmDelete('Delete this user account? This cannot be undone.')">
                <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
              </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add User Modal -->
<div class="modal-overlay" id="addUserModal">
  <div class="modal">
    <div class="modal-header"><h3>Add New User</h3><button class="modal-close" onclick="closeModal('addUserModal')">✕</button></div>
    <form action="user_action.php" method="POST">
      <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
      <input type="hidden" name="action" value="create">
      <div class="field"><label>Full name</label><input type="text" name="full_name" required></div>
      <div class="field"><label>Email</label><input type="email" name="email" required></div>
      <div class="field"><label>Password</label>
        <div class="password-wrap">
          <input type="password" id="new_user_password" name="password" minlength="6" required>
          <button type="button" class="password-toggle" data-target="new_user_password" aria-label="Show password">👁️</button>
        </div>
      </div>
      <div class="field"><label>Role</label>
        <select name="role" id="addRoleSelect" onchange="document.getElementById('addStudentFields').style.display = this.value==='student'?'block':'none'">
          <option value="student">Student</option>
          <option value="instructor">Instructor</option>
          <option value="admin">Admin</option>
        </select>
      </div>
      <div id="addStudentFields">
        <div class="field"><label>Instrument</label>
          <select name="instrument_id">
            <option value="">—</option>
            <?php foreach ($instruments as $ins): ?><option value="<?= (int)$ins['id'] ?>"><?= h($ins['icon'].' '.$ins['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="field"><label>Instructor</label>
          <select name="instructor_id">
            <option value="">—</option>
            <?php foreach ($instructorsList as $ins): ?><option value="<?= (int)$ins['id'] ?>"><?= h($ins['full_name']) ?></option><?php endforeach; ?>
          </select>
        </div>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Create User</button>
    </form>
  </div>
</div>

<!-- Edit User Modal -->
<?php if ($editUser): ?>
<div class="modal-overlay open" id="editUserModal">
  <div class="modal">
    <div class="modal-header"><h3>Edit User</h3><a class="modal-close" href="manage_users.php">✕</a></div>
    <form action="user_action.php" method="POST">
      <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="id" value="<?= (int)$editUser['id'] ?>">
      <div class="field"><label>Full name</label><input type="text" name="full_name" value="<?= h($editUser['full_name']) ?>" required></div>
      <div class="field"><label>Email</label><input type="email" name="email" value="<?= h($editUser['email']) ?>" required></div>
      <div class="field"><label>New password <span class="text-faint">(leave blank to keep current)</span></label>
        <div class="password-wrap">
          <input type="password" id="edit_user_password" name="password" minlength="6">
          <button type="button" class="password-toggle" data-target="edit_user_password" aria-label="Show password">👁️</button>
        </div>
      </div>
      <div class="field"><label>Role</label>
        <select name="role">
          <option value="student" <?= $editUser['role']==='student'?'selected':'' ?>>Student</option>
          <option value="instructor" <?= $editUser['role']==='instructor'?'selected':'' ?>>Instructor</option>
          <option value="admin" <?= $editUser['role']==='admin'?'selected':'' ?>>Admin</option>
        </select>
      </div>
      <div class="field"><label>Instrument</label>
        <select name="instrument_id">
          <option value="">—</option>
          <?php foreach ($instruments as $ins): ?><option value="<?= (int)$ins['id'] ?>" <?= $editUser['instrument_id']==$ins['id']?'selected':'' ?>><?= h($ins['icon'].' '.$ins['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="field"><label>Instructor</label>
        <select name="instructor_id">
          <option value="">—</option>
          <?php foreach ($instructorsList as $ins): ?><option value="<?= (int)$ins['id'] ?>" <?= $editUser['instructor_id']==$ins['id']?'selected':'' ?>><?= h($ins['full_name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="field"><label>Status</label>
        <select name="status">
          <option value="active" <?= $editUser['status']==='active'?'selected':'' ?>>Active</option>
          <option value="inactive" <?= $editUser['status']==='inactive'?'selected':'' ?>>Inactive</option>
        </select>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Save Changes</button>
    </form>
  </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>

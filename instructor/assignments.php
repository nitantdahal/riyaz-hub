<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('instructor');

$db = getDB();
$instructorId = $_SESSION['user_id'];

$stmt = $db->prepare('
    SELECT id, full_name 
    FROM users 
    WHERE instructor_id = ? 
    ORDER BY full_name
');
$stmt->execute([$instructorId]);
$myStudents = $stmt->fetchAll();


$stmt = $db->prepare('
    SELECT 
        a.*, 
        u.full_name AS student_name, 
        u.avatar_color
    FROM assignments a
    JOIN users u ON u.id = a.student_id
    WHERE a.instructor_id = ?
    ORDER BY 
        COALESCE(a.reviewed_at, a.created_at) DESC
');

$stmt->execute([$instructorId]);
$assignments = $stmt->fetchAll();


$pageTitle = 'Assignments';
$pageSubtitle = 'Give tasks and practice routines to your students.';
$activeNav = 'assignments';
require __DIR__ . '/../includes/sidebar.php';
?>


<div class="card">

<div class="card-header">
    <h3>All Assignments (<?= count($assignments) ?>)</h3>

    <button 
        class="btn btn-primary btn-sm" 
        onclick="openModal('addAssignmentModal')"
        <?= !$myStudents ? 'disabled' : '' ?>>
        + New Assignment
    </button>
</div>


<?php if (!$myStudents): ?>

<div class="empty-state">
    <h3>No students assigned to you yet</h3>
    <p>You'll be able to assign tasks once students choose you as their instructor.</p>
</div>


<?php elseif (!$assignments): ?>

<div class="empty-state">
    <h3>No assignments yet</h3>
    <p>Click "New Assignment" to give your first task.</p>
</div>


<?php else: foreach ($assignments as $a): ?>

<div class="list-item">

<div class="avatar" style="width:36px;height:36px;background:<?= h($a['avatar_color']) ?>">
    <?= h(strtoupper(substr($a['student_name'],0,1))) ?>
</div>


<div style="flex:1">

<div class="flex justify-between items-center">

<div class="li-title">
    <?= h($a['title']) ?>
</div>


<div class="flex gap-8 items-center">

<?php
$statusClass = match($a['status']) {
    'accepted' => 'badge-success',
    'submitted' => 'badge-warning',
    'rejected' => 'badge-danger',
    default => 'badge-gold'
};
?>

<span class="badge <?= $statusClass ?>">
    <?= h(ucfirst($a['status'])) ?>
</span>

<?= renderAssignmentTimingBadge($a) ?>

</div>

</div>


<p class="text-faint" style="font-size:.85rem;margin:4px 0;">
<?= h($a['description']) ?>
</p>


<div class="li-meta">

For <?= h($a['student_name']) ?>

<?php if($a['due_date']): ?>
 · Due <?= date('M j, Y', strtotime($a['due_date'])) ?>
<?php endif; ?>

</div>


<?php if($a['status'] === 'submitted'): ?>

<div style="margin-top:10px;">

    <div class="flex gap-8">

        <!-- Accept -->
        <form action="assignment_action.php" method="POST">

            <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
            <input type="hidden" name="action" value="accept">
            <input type="hidden" name="assignment_id" value="<?= (int)$a['id'] ?>">

            <button class="btn btn-success btn-sm">
                Accept ✓
            </button>

        </form>


        <!-- Reject -->
        <form action="assignment_action.php" method="POST">

            <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
            <input type="hidden" name="action" value="reject">
            <input type="hidden" name="assignment_id" value="<?= (int)$a['id'] ?>">

            <button class="btn btn-danger btn-sm">
                Reject ✕
            </button>

        </form>

    </div>


    <!-- Feedback Box -->
    <form action="assignment_action.php" method="POST" style="margin-top:12px;">

        <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
        <input type="hidden" name="action" value="reject">
        <input type="hidden" name="assignment_id" value="<?= (int)$a['id'] ?>">

        <input
            type="text"
            name="feedback"
            placeholder="Write feedback..."
            style="width:300px;"
        >

        <button class="btn btn-outline btn-sm" style="margin-top:8px;">
            Save Feedback
        </button>

    </form>

</div>

<?php endif; ?>


<?php if(!empty($a['instructor_feedback'])): ?>

<p class="text-faint" style="margin-top:8px;">
<strong>Feedback:</strong>
<?= h($a['instructor_feedback']) ?>
</p>

<?php endif; ?>


</div>

</div>


<?php endforeach; endif; ?>

</div>



<!-- New Assignment Modal -->
<div class="modal-overlay" id="addAssignmentModal">

<div class="modal">

<div class="modal-header">
<h3>New Assignment</h3>
<button class="modal-close" onclick="closeModal('addAssignmentModal')">✕</button>
</div>


<form action="assignment_action.php" method="POST">

<input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
<input type="hidden" name="action" value="create">


<div class="field">
<label>Student</label>

<select name="student_id" required>

<?php foreach ($myStudents as $s): ?>

<option value="<?= (int)$s['id'] ?>">
<?= h($s['full_name']) ?>
</option>

<?php endforeach; ?>

</select>

</div>


<div class="field">
<label>Title</label>

<input 
type="text"
name="title"
placeholder="e.g. Practice C major scale, 2 octaves"
required>

</div>


<div class="field">
<label>Description</label>

<textarea 
name="description"
placeholder="Instructions or details"></textarea>

</div>


<div class="field">
<label>Due date</label>

<input type="date" name="due_date">

</div>


<button type="submit" class="btn btn-primary btn-block">
Assign Task 📝
</button>


</form>

</div>

</div>


<?php require __DIR__ . '/../includes/footer.php'; ?>
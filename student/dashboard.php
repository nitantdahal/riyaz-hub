<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('student');

$db = getDB();
$studentId = $_SESSION['user_id'];


// =======================
// STREAK DATA
// =======================

$stmt = $db->prepare('SELECT * FROM streaks WHERE student_id = ?');
$stmt->execute([$studentId]);

$streak = $stmt->fetch() ?: [
    'current_streak' => 0,
    'longest_streak' => 0
];


// =======================
// PRACTICE TOTALS
// =======================

$stmt = $db->prepare('
    SELECT 
        COALESCE(SUM(duration_minutes),0) m,
        COUNT(*) c
    FROM practice_sessions
    WHERE student_id = ?
');

$stmt->execute([$studentId]);
$totals = $stmt->fetch();


// =======================
// WEEK DATA
// =======================

$weekMinutes = weeklyMinutes($db, $studentId);

$chartData = dailyMinutes($db, $studentId, 6);

$maxMinutes = max(
    1,
    max(array_column($chartData, 'minutes'))
);


// =======================
// ACTIVE GOAL
// =======================

$stmt = $db->prepare('
    SELECT g.*,
    (
        SELECT COALESCE(SUM(duration_minutes),0)
        FROM practice_sessions
        WHERE student_id = g.student_id
        AND created_at >= g.created_at
    ) AS progress_minutes

    FROM goals g

    WHERE g.student_id = ?
    AND g.status = "in_progress"

    ORDER BY g.deadline DESC
    LIMIT 1
');

$stmt->execute([$studentId]);

$activeGoal = $stmt->fetch();



// =======================
// ASSIGNMENTS
// =======================

$stmt = $db->prepare('
    SELECT *
    FROM assignments
    WHERE student_id = ?
    ORDER BY created_at DESC
');

$stmt->execute([$studentId]);

$assignments = $stmt->fetchAll();


$stmt = $db->prepare('
    SELECT COUNT(*) 
    FROM assignments
    WHERE student_id = ?
    AND status = "pending"
');

$stmt->execute([$studentId]);

$pendingAssignments = (int)$stmt->fetchColumn();



// =======================
// RECENT PRACTICE
// =======================

$stmt = $db->prepare('
    SELECT 
        ps.*,
        i.name AS instrument_name,
        i.icon

    FROM practice_sessions ps

    LEFT JOIN instruments i 
    ON i.id = ps.instrument_id

    WHERE ps.student_id = ?

    ORDER BY ps.created_at DESC

    LIMIT 5
');

$stmt->execute([$studentId]);

$recentSessions = $stmt->fetchAll();



// =======================
// FEEDBACK
// =======================

$stmt = $db->prepare('
    SELECT 
        f.*,
        u.full_name AS instructor_name

    FROM feedback f

    JOIN users u 
    ON u.id = f.instructor_id

    WHERE f.student_id = ?

    ORDER BY f.created_at DESC

    LIMIT 3
');

$stmt->execute([$studentId]);

$recentFeedback = $stmt->fetchAll();



$instruments = $db
    ->query(
        'SELECT id,name,icon FROM instruments ORDER BY name'
    )
    ->fetchAll();



$pageTitle = 'My Dashboard';

$pageSubtitle =
    'Welcome back, '
    . $_SESSION['full_name']
    . '. Keep the streak alive!';


$activeNav = 'dashboard';


require __DIR__ . '/../includes/sidebar.php';

?>



<!-- ===========================
     STAT GRID
=========================== -->


<div class="grid grid-5">


<div class="card stat-card">

<div class="stat-icon">
⏱️
</div>

<div class="stat-value">
<?= (int)$totals['m'] ?>
</div>

<div class="stat-label">
Total Minutes Practiced
</div>

</div>



<div class="card stat-card accent-cyan">

<div class="stat-icon">
🎼
</div>

<div class="stat-value">
<?= (int)$totals['c'] ?>
</div>

<div class="stat-label">
Sessions Logged
</div>

</div>




<div class="card stat-card accent-magenta">

<div class="stat-icon">
🔥
</div>

<div class="stat-value">
<?= (int)$streak['current_streak'] ?>d
</div>

<div class="stat-label">
Current Streak
</div>


<div class="stat-delta up">
Longest:
<?= (int)$streak['longest_streak'] ?> days
</div>

</div>




<div class="card stat-card accent-violet">

<div class="stat-icon">
📅
</div>

<div class="stat-value">
<?= (int)$weekMinutes ?>
</div>

<div class="stat-label">
Minutes This Week
</div>

</div>




<!-- NEW ASSIGNMENT CARD -->

<div class="card stat-card accent-orange">

<div class="stat-icon">
📝
</div>

<div class="stat-value">
<?= $pendingAssignments ?>
</div>

<div class="stat-label">
New Assignments
</div>

</div>


</div>



<div class="eq-divider">
Practice Timer
</div>



<div class="two-col">


<div class="card timer-card">


<div class="eq-bars is-paused" id="timerEq">

<span></span>
<span></span>
<span></span>
<span></span>
<span></span>
<span></span>
<span></span>

</div>


<div class="timer-display" id="timerDisplay">
00:00:00
</div>


<div class="timer-controls">


<button class="btn btn-primary" id="timerStart">
▶ Start
</button>


<button class="btn btn-ghost" id="timerPause">
⏸ Pause
</button>


<button class="btn btn-outline" id="timerReset">
↺ Reset
</button>


</div>


<span id="recStatus" class="rec-pill" style="display:none;"></span>


<button 
class="btn btn-block"
style="
background:var(--surface-2);
border:1px solid var(--line-strong);
color:var(--gold);
margin-top:14px;"
id="timerLog">

🎧 Log This Session

</button>



<p class="hint" style="margin-top:10px;">

🎙️ Pressing Start also records your microphone for the full session.

</p>


</div>

<div class="card">

<div class="card-header">

<h3>
Last 6 Days
</h3>

<span class="link">
📈 Activity
</span>

</div>



<div class="bar-chart">


<?php foreach ($chartData as $day): ?>


<div class="bc-col">


<span class="bc-val">
<?= $day['minutes'] ?>m
</span>


<div 
class="bc-bar"
style="
height:
<?= max(4, round(($day['minutes'] / $maxMinutes) * 100)) ?>%;
">
</div>


<span class="bc-label">
<?= h($day['label']) ?>
</span>


</div>


<?php endforeach; ?>


</div>





<?php if ($activeGoal): ?>


<div class="eq-divider">
Active Goal
</div>


<p style="color:var(--text);font-weight:600;">

<?= h($activeGoal['title']) ?>

</p>


<?php

$pct = min(
    100,
    round(
        ($activeGoal['progress_minutes']
        /
        max(1,$activeGoal['target_minutes']))
        * 100
    )
);

?>


<div class="progress">

<span style="width:<?= $pct ?>%"></span>

</div>



<p class="text-faint"
style="margin-top:8px;font-size:.8rem;">

<?= (int)$activeGoal['progress_minutes'] ?>

/

<?= (int)$activeGoal['target_minutes'] ?>

minutes

·

<?= $pct ?>% complete

</p>



<?php else: ?>


<p class="text-faint" style="margin-top:16px;">

No active goal yet.

<a href="goals.php"
style="color:var(--cyan);font-weight:600;">

Set one →

</a>

</p>


<?php endif; ?>


</div>


</div>



<!-- ===========================
     ASSIGNMENTS SECTION
=========================== -->


<div class="eq-divider">

My Assignments

</div>



<div class="card">


<div class="card-header">

<h3>
Practice Tasks
</h3>


<a href="feedback.php" class="link">

View all →

</a>


</div>



<?php if (!$assignments): ?>


<div class="empty-state">

<h3>
No assignments yet
</h3>

<p>
Your instructor will assign practice tasks here.
</p>


</div>



<?php else: ?>


<?php foreach(array_slice($assignments,0,5) as $a): ?>


<div class="list-item">


<div class="li-icon">

📝

</div>



<div style="flex:1">


<div class="li-title">

<?= h($a['title']) ?>

</div>



<div class="li-meta">


<?php if($a['due_date']): ?>

Due:

<?= date(
'M j, Y',
strtotime($a['due_date'])
) ?>


<?php endif; ?>


</div>


</div>



<?php
$statusClass = match ($a['status']) {
    'pending'  => 'badge-gold',
    'submitted' => 'badge-warning',
    'accepted'  => 'badge-success',
    'rejected'  => 'badge-danger',
    default     => 'badge-gold',
};
?>

<span class="badge <?= $statusClass ?>">
    <?= ucfirst($a['status']) ?>
</span>



</div>


<?php endforeach; ?>



<?php endif; ?>


</div>





<div class="eq-divider">

Recent Activity

</div>



<div class="two-col">


<!-- RECENT SESSIONS -->


<div class="card">


<div class="card-header">


<h3>
Recent Sessions
</h3>


<a class="link" href="sessions.php">

View all →

</a>


</div>



<?php if(!$recentSessions): ?>


<div class="empty-state">


<div class="eq-bars">

<span></span>
<span></span>
<span></span>
<span></span>
<span></span>


</div>



<h3>
No sessions logged yet
</h3>


<p>
Start practicing to build your streak.
</p>


</div>



<?php else: ?>


<?php foreach($recentSessions as $s): ?>


<div class="list-item">


<div class="li-icon">

<?= h($s['icon'] ?? '🎵') ?>

</div>



<div style="flex:1">


<div class="li-title">

<?= h(
$s['focus_area']
?: 
($s['instrument_name'] ?? 'Practice Session')
) ?>


</div>



<div class="li-meta">


<?= h($s['instrument_name'] ?? '') ?>

·

<?= (int)$s['duration_minutes'] ?> min

·

<?= date(
'M j',
strtotime($s['session_date'])
) ?>


<?php if(!empty($s['video_path'])): ?>

· 🎥 video

<?php endif; ?>


</div>



</div>



<span class="badge badge-gold">

<?= h(
ucfirst($s['mood'])
) ?>


</span>



</div>


<?php endforeach; ?>



<?php endif; ?>


</div>


<!-- ===========================
     INSTRUCTOR FEEDBACK
=========================== -->


<div class="card">


<div class="card-header">


<h3>
Instructor Feedback
</h3>


<a class="link" href="feedback.php">

View all →

</a>


</div>




<?php if (!$recentFeedback): ?>


<div class="empty-state">


<h3>
No feedback yet
</h3>


<p>
Your instructor's notes will show up here.
</p>


</div>




<?php else: ?>


<?php foreach($recentFeedback as $f): ?>


<div class="list-item">


<div class="li-icon">

💬

</div>



<div>


<div class="li-title">

<?= h($f['instructor_name']) ?>

</div>



<div class="li-meta">

<?= h($f['message']) ?>

</div>



<div class="li-meta text-faint"
style="margin-top:4px;">


<?= timeAgo($f['created_at']) ?>


</div>


</div>



</div>



<?php endforeach; ?>



<?php endif; ?>


</div>



</div>





<!-- ===========================
     LOG SESSION MODAL
=========================== -->


<div class="modal-overlay" id="addSessionModal">


<div class="modal">



<div class="modal-header">


<h3>
Log Practice Session
</h3>


<button 
class="modal-close"
onclick="closeModal('addSessionModal')">

✕

</button>


</div>





<form 
action="session_action.php"
method="POST"
enctype="multipart/form-data">



<input 
type="hidden"
name="csrf_token"
value="<?= h(csrfToken()) ?>">



<input 
type="hidden"
name="action"
value="create">





<div class="field">


<label>
Instrument
</label>


<select name="instrument_id">


<?php foreach($instruments as $ins): ?>


<option value="<?= (int)$ins['id'] ?>">


<?= h(
$ins['icon'].' '.$ins['name']
) ?>


</option>


<?php endforeach; ?>


</select>


</div>






<div class="field">


<label>
Date
</label>


<input 
type="date"
name="session_date"
value="<?= date('Y-m-d') ?>"
required>


</div>






<div class="field">


<label>
Duration (minutes)
</label>


<input 

type="number"

id="duration_minutes"

name="duration_minutes"

min="1"

max="600"

required>


</div>






<div class="field">


<label>
Focus Area
</label>


<input

type="text"

name="focus_area"

placeholder="e.g. Scales, sight reading, a specific piece">


</div>







<div class="field">


<label>
How did it go?
</label>




<div class="chip-select">


<label>

<input 
type="radio"
name="mood"
value="great"
checked>

<span>
😄 Great
</span>

</label>




<label>

<input 
type="radio"
name="mood"
value="good">

<span>
🙂 Good
</span>

</label>





<label>

<input 
type="radio"
name="mood"
value="okay">

<span>
😐 Okay
</span>

</label>





<label>

<input 
type="radio"
name="mood"
value="tough">

<span>
😓 Tough
</span>

</label>


</div>


</div>








<div class="field">


<label>
Notes
</label>


<textarea

name="notes"

placeholder="What did you work on?">

</textarea>


</div>








<div class="field">


<label>

🎥🎙️ Practice video or voice recording

<span class="text-faint">
(optional)
</span>


</label>




<div 
id="recPreview"
style="display:none;margin-bottom:10px;">


<p class="hint">

🎙️ Voice recording captured from timer:

</p>



<audio controls style="width:100%;">

</audio>


</div>






<input

type="file"

id="practice_video_input"

name="practice_video"

accept="
video/mp4,
video/quicktime,
video/webm,
video/ogg,
video/x-msvideo,
video/x-matroska,
audio/mpeg,
audio/wav,
audio/mp4,
audio/aac,
audio/webm,
audio/ogg">


<p class="hint">


Auto-filled if recorded with timer,
or upload your own file.

MP4, MOV, WEBM, OGG, AVI, MKV,
MP3, WAV, M4A — up to 150MB.


</p>


</div>







<button 
type="submit"
class="btn btn-primary btn-block">


Save Session 🎶


</button>





</form>



</div>



</div>





<?php require __DIR__ . '/../includes/footer.php'; ?>
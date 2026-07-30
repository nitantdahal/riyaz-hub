<?php

require_once __DIR__ . '/../includes/auth.php';
requireRole('instructor');


if (
    $_SERVER['REQUEST_METHOD'] !== 'POST' ||
    !verifyCsrf($_POST['csrf_token'] ?? '')
) {
    redirect('assignments.php');
}


$db = getDB();

$instructorId = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';



/*
|--------------------------------------------------------------------------
| ACCEPT ASSIGNMENT
|--------------------------------------------------------------------------
*/

if ($action === 'accept') {

    $assignmentId = (int)($_POST['assignment_id'] ?? 0);


    if ($assignmentId <= 0) {
        setFlash('error', 'Invalid assignment.');
        redirect('assignments.php');
    }


    $stmt = $db->prepare("
        UPDATE assignments
        SET
            status = 'accepted',
            reviewed_at = NOW(),
            instructor_feedback = NULL
        WHERE id = ?
        AND instructor_id = ?
        AND status = 'submitted'
    ");


    $stmt->execute([
        $assignmentId,
        $instructorId
    ]);


    if ($stmt->rowCount()) {

        setFlash(
            'success',
            'Assignment accepted successfully. Student leaderboard updated.'
        );

    } else {

        setFlash(
            'error',
            'Unable to accept assignment.'
        );

    }


    redirect('assignments.php');

}



/*
|--------------------------------------------------------------------------
| REJECT ASSIGNMENT
|--------------------------------------------------------------------------
*/

if ($action === 'reject') {


    $assignmentId = (int)($_POST['assignment_id'] ?? 0);

    $feedback = trim($_POST['feedback'] ?? '');



    if ($assignmentId <= 0) {

        setFlash(
            'error',
            'Invalid assignment.'
        );

        redirect('assignments.php');

    }



    $stmt = $db->prepare("
        UPDATE assignments
        SET
            status = 'rejected',
            reviewed_at = NOW(),
            instructor_feedback = ?
        WHERE id = ?
        AND instructor_id = ?
        AND status = 'submitted'
    ");



    $stmt->execute([
        $feedback,
        $assignmentId,
        $instructorId
    ]);



    if ($stmt->rowCount()) {


        setFlash(
            'success',
            'Assignment rejected with feedback.'
        );


    } else {


        setFlash(
            'error',
            'Unable to reject assignment.'
        );


    }



    redirect('assignments.php');

}



/*
|--------------------------------------------------------------------------
| CREATE NEW ASSIGNMENT
|--------------------------------------------------------------------------
*/


$studentId = (int)($_POST['student_id'] ?? 0);

$title = trim($_POST['title'] ?? '');

$description = trim($_POST['description'] ?? '');

$dueDate = !empty($_POST['due_date'])
    ? $_POST['due_date']
    : null;



if ($studentId <= 0 || $title === '') {


    setFlash(
        'error',
        'Student and title are required.'
    );


    redirect('assignments.php');

}




/*
|--------------------------------------------------------------------------
| Verify student belongs to instructor
|--------------------------------------------------------------------------
*/


$stmt = $db->prepare("
    SELECT id
    FROM users
    WHERE id = ?
    AND instructor_id = ?
    AND role = 'student'
");


$stmt->execute([
    $studentId,
    $instructorId
]);



if (!$stmt->fetch()) {


    setFlash(
        'error',
        'You cannot assign work to this student.'
    );


    redirect('assignments.php');

}




/*
|--------------------------------------------------------------------------
| Insert Assignment
|--------------------------------------------------------------------------
*/


$stmt = $db->prepare("
    INSERT INTO assignments
    (
        instructor_id,
        student_id,
        title,
        description,
        due_date,
        status
    )

    VALUES
    (
        ?,
        ?,
        ?,
        ?,
        ?,
        'pending'
    )
");



$stmt->execute([

    $instructorId,
    $studentId,
    $title,
    $description,
    $dueDate

]);



setFlash(
    'success',
    'Assignment created and sent to student.'
);



redirect('assignments.php');

?>
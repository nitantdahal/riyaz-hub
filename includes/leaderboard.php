<?php
/**
 * Leaderboard scoring.
 *
 * Score formula (documented on the leaderboard page too):
 *   score = total_practice_minutes
 *         + (average_teacher_rating_out_of_5 × 20)
 *         + (on_time_assignment_percentage × 0.5)
 *
 * This weights raw practice time as the main driver, while rewarding
 * students who consistently earn strong instructor ratings and submit
 * assignments on or before their due date.
 */

const LEADERBOARD_RATING_WEIGHT = 20;   // points per star (avg out of 5)
const LEADERBOARD_ONTIME_WEIGHT = 0.5;  // points per 1% on-time rate

/**
 * Compute the three raw metrics + composite score for one student,
 * scoped to feedback/assignments from a specific instructor.
 */
function computeStudentLeaderboardStats(PDO $db, int $studentId, int $instructorId): array {
    $stmt = $db->prepare('SELECT COALESCE(SUM(duration_minutes),0) m, COUNT(*) c FROM practice_sessions WHERE student_id = ?');
    $stmt->execute([$studentId]);
    $sessionRow = $stmt->fetch();
    $minutes = (int) $sessionRow['m'];
    $sessionCount = (int) $sessionRow['c'];

    $stmt = $db->prepare('SELECT AVG(rating) a, COUNT(rating) c FROM feedback WHERE student_id = ? AND instructor_id = ? AND rating IS NOT NULL');
    $stmt->execute([$studentId, $instructorId]);
    $ratingRow = $stmt->fetch();
    $avgRating = $ratingRow['a'] !== null ? round((float) $ratingRow['a'], 2) : null;
    $ratingCount = (int) $ratingRow['c'];

$stmt = $db->prepare('
    SELECT status, due_date, completed_at 
    FROM assignments 
    WHERE student_id = ?
    AND instructor_id = ?
    AND due_date IS NOT NULL
');

$stmt->execute([
    $studentId,
    $instructorId
]);

$assignments = $stmt->fetchAll();

    $considered = 0;
    $onTime = 0;
    $today = date('Y-m-d');
    foreach ($assignments as $a) {

    // Accepted assignments are counted
    if ($a['status'] === 'accepted') {

        $considered++;

        if (
            $a['completed_at'] &&
            substr($a['completed_at'],0,10) <= $a['due_date']
        ) {
            $onTime++;
        }

    }

    // Rejected assignments reduce performance
    elseif ($a['status'] === 'rejected') {

        $considered++;

    }


    // Overdue submitted/pending work
    elseif (
        $a['status'] === 'pending' &&
        $a['due_date'] < $today
    ) {

        $considered++;

    }
}
    $onTimePct = $considered > 0 ? round(($onTime / $considered) * 100, 1) : null;

    $score = $minutes
        + (($avgRating ?? 0) * LEADERBOARD_RATING_WEIGHT)
        + (($onTimePct ?? 0) * LEADERBOARD_ONTIME_WEIGHT);

    return [
        'minutes'                => $minutes,
        'session_count'          => $sessionCount,
        'avg_rating'             => $avgRating,
        'rating_count'           => $ratingCount,
        'on_time_pct'            => $onTimePct,
        'assignments_considered' => $considered,
        'score'                  => round($score, 1),
    ];
}

/** Ranked leaderboard of every student assigned to this instructor */
function getInstructorLeaderboard($db, $instructorId)
{

    $stmt = $db->prepare("
        SELECT

            u.id,
            u.full_name,
            u.avatar_color,


            /* Total practice minutes */
            (
                SELECT COALESCE(SUM(ps.duration_minutes),0)
                FROM practice_sessions ps
                WHERE ps.student_id = u.id
            ) AS minutes,


            /* Total practice sessions */
            (
                SELECT COUNT(*)
                FROM practice_sessions ps
                WHERE ps.student_id = u.id
            ) AS session_count,


            /* Average instructor rating */
            (
                SELECT ROUND(AVG(r.rating),1)
                FROM feedback r
WHERE r.student_id = u.id
AND r.instructor_id = ?
            ) AS avg_rating,


            /* Number of ratings */
            (
                SELECT COUNT(*)
                FROM feedback r
WHERE r.student_id = u.id
AND r.instructor_id = ?
            ) AS rating_count,


            /*
              Accepted assignments submitted before deadline
            */
            (
                SELECT

                CASE

                    WHEN COUNT(*) = 0 THEN NULL

                    ELSE ROUND(

                        (
                            SUM(
                                CASE

                                WHEN a.status = 'accepted'
                                AND a.completed_at <= a.due_date

                                THEN 1

                                ELSE 0

                                END
                            )

                            /

                            COUNT(*)

                        ) * 100

                    ,1)

                END

                FROM assignments a

WHERE 
    a.student_id = u.id
    AND a.instructor_id = ?

            ) AS on_time_pct


        FROM users u


        WHERE 
            u.instructor_id = ?

        AND u.role = 'student'


        ORDER BY minutes DESC

    ");


    $stmt->execute([
    $instructorId,
    $instructorId,
    $instructorId,
    $instructorId
]);


    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);



    foreach($students as &$student){


        $ratingScore = 
($student['avg_rating'] ?? 0) * LEADERBOARD_RATING_WEIGHT;


$assignmentScore =
($student['on_time_pct'] ?? 0) * LEADERBOARD_ONTIME_WEIGHT;



        // Final leaderboard score
        $student['score'] = round(
            $student['minutes']
            +
            $ratingScore
            +
            $assignmentScore
        );

    }



    // Highest score first

    usort(
        $students,
        function($a,$b){

            return $b['score'] <=> $a['score'];

        }
    );


    return $students;

}

/** Platform-wide leaderboard (used by admin monthly reports) — scores each student against their own instructor */
function getPlatformLeaderboard(PDO $db): array {
    $stmt = $db->query('SELECT id, full_name, avatar_color, instrument_id, instructor_id FROM users WHERE role = "student" ORDER BY full_name');
    $students = $stmt->fetchAll();

    $rows = [];
    foreach ($students as $s) {
        $stats = computeStudentLeaderboardStats($db, (int) $s['id'], (int) ($s['instructor_id'] ?? 0));
        $rows[] = array_merge($s, $stats);
    }

    usort($rows, function ($a, $b) { return $b['score'] <=> $a['score']; });
    return $rows;
}

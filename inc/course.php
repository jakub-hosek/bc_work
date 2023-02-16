<?php
require_once __DIR__.'/../classes/Course.php';

$coursesDataQuery = $db->prepare('SELECT Course.Ident, TeachedCourse.Year, TeachedCourse.Semester, SeminarStudent.StudentId, Seminar.SeminarId from SeminarStudent INNER JOIN Seminar ON 
SeminarStudent.SeminarId=Seminar.SeminarId AND StudentId=:UserId INNER JOIN TeachedCourse ON 
TeachedCourse.TeachedCourseId=Seminar.TeachedCourseId INNER JOIN Course ON
Course.CourseId=TeachedCourse.CourseId;');

$coursesDataQuery->execute([
    ':UserId' => $_SESSION['UserId']
]);

$coursesData = $coursesDataQuery->fetchAll(PDO::FETCH_ASSOC);

$courses = array();

if (!empty($coursesData)) {
    foreach ($coursesData as $courseData) {
        $course = new classes\Course($courseData['Ident'], $courseData['Year'], $courseData['Semester'], array('SeminarId'=>$courseData['SeminarId']));
        $courses[] = $course;
        print($course);
    }
}

$_SESSION['courses'] = $courses;
<?php
require_once __DIR__ . '/inc/db.php';
session_start();

$homework = null;

if (empty($_SESSION['UserId'])){
    //uživatel už je přihlášený, nemá smysl, aby se přihlašoval znovu
    header('Location: /login');
    exit();
}

include __DIR__ . '/inc/header.php';

if (empty($_POST)) {
    header('Location: /error/404');
    exit();
}


if (isset($_POST['HomeworkId'])) {
    $homeworkQuery = $db->prepare('SELECT Homework.*, SeminarHomework.Visible FROM Homework INNER JOIN SeminarHomework ON Homework.HomeworkId = SeminarHomework.HomeworkId
                  WHERE Homework.HomeworkId=:HomeworkId AND Homework.AddedBy=:UserId AND Homework.General=1  LIMIT 1;');

    $homeworkQuery->execute([
        ':HomeworkId' => $_POST['HomeworkId'],
        ':UserId' => $_SESSION['UserId']
    ]);

    if ($homeworkQuery->rowCount()!=1) {
        header('Location: /error/404');
        exit();
    }

    $homeworkQuery->bindColumn('InputFile', $test, PDO::PARAM_LOB);

    $homework = $homeworkQuery->fetch();

    if (isset($_POST['editHomework']) && $_POST['editHomework'] = 'true') {

        if (isset($_POST['Visible']) && $_POST['Visible'] == 'visible') {
            $visible = 1;
        } else {
            $visible = 0;
        }

        $inputFile = $homework['InputFile'];
        if (isset($_FILES['InputFile']) && !empty($_FILES['InputFile']['name'])) {
            $inputFile = substr(file_get_contents($_FILES['InputFile']['tmp_name']), 0, $_FILES['InputFile']['size']);
        }

        $homeworkUpdateQuery = $db->prepare('UPDATE Homework SET Name=:Name, Description=:Description, Marking=:Marking, InputFile=:InputFile WHERE HomeworkId=:HomeworkId;');

        $homeworkUpdateQuery->execute([
            ':Name' => $_POST['Name'],
            ':Description' => $_POST['Description'],
            ':Marking' => $_POST['Marking'],
            ':InputFile' => $inputFile,
            ':HomeworkId' => $_POST['HomeworkId']
        ]);

        $seminarHomeworkUpdateQuery = $db->prepare('UPDATE SeminarHomework SET Visible=:Visible WHERE HomeworkId=:HomeworkId;');

        $seminarHomeworkUpdateQuery->execute([
            ':Visible' => $visible,
            ':HomeworkId' => $_POST['HomeworkId']
        ]);

        header('Location: '.$_SESSION['rdrurl']);
        exit();
    }
}

?>

<div class="breadcrumb_div">
    <div class="breadcrumbPath">
        <a href="/">Home</a>
        <p class="arrow">→</p>
    </div>
    <div class="breadcrumbPath">
        <a href="<?php echo $_SESSION['rdrurl']?>">Course (<?php echo htmlspecialchars($_GET['Ident']). ' in ' . htmlspecialchars($_GET['Semester']) . ' in ' . htmlspecialchars($_GET['Year'] . '/' . (substr($_GET['Year'], 2, 2) +1)) ?>)</a>
        <p class="arrow">→</p>
        <p></p>
    </div>
    <div class="breadcrumbPath">
        <p>edit: <?php echo htmlspecialchars($homework['Name']) ?></p>
    </div>
</div>

<form method="post" enctype="multipart/form-data">
                <div class="field">
                    <label for="Name">Name: </label>
                    <input type="text" name="Name" id="Name" placeholder="ex. Hello World!" pattern="^\S+(\s)?\S*$" value="<?php echo htmlspecialchars($homework['Name']) ?>" required>
                </div>
                <div class="field">
                    <label for="Description" >Description:</label>
                    <textarea name="Description" id="Description" cols="40" rows="6" placeholder="ex. Print \'Hello world!\' on standard output." required><?php echo htmlspecialchars($homework['Description']) ?></textarea>
                </div>
                <div class="field">
                    <label for="Marking">Marking:</label>
                    <textarea name="Marking" id="Marking" cols="40" rows="12" placeholder='ex. {
  "maximum": 1,
  "marking": [
      {"text": "Hello World!",
        "weight": "0.5"
      },
      {"text": "How are you?",
        "weight": "0.5"
      }
  ]
}'  required><?php echo $homework['Marking'] ?></textarea>
                    <!--Marking regular expression: ^{\s*\"maximum\":\s*[1-9]+,\s*"marking":\s*\[\s*({"text":\s*".*",\s*"weight":\s*"\d(.\d+)?"\s*},\s*)+(?!,)\s*({"text":\s*".*",\s*"weight":\s*"\d(.\d+)?"\s*}\s*)\s*]\s*}$-->
                </div>
                <div class="field">
                    <label for="InputFile">Input: </label>
                    <input type="file" name="InputFile" id="InputFile">
                </div>
                <div class="field">
                    <label for="Visible">Visible: </label>
                    <input type="checkbox" name="Visible" id="Visible" value="visible"
 <?php if($homework['Visible']) {
    echo 'checked';
}?>
            >
                </div>
                <input type="hidden" name="HomeworkId" id="HomeworkId" value="<?php echo htmlspecialchars($_POST['HomeworkId']) ?>">
                <button type="submit" name="editHomework" value="true" >Edit</button>
            </form>
<?php

// Shigoto - https://0xff.nu/shigoto

$time = microtime(true);
session_start();


if (!isset($_SESSION['auth'])) {
  echo 'NOPE';
  header("Location: ./login.php");
  exit();
}

require 'todo.php';

$todo = new Shigoto('todo.txt', 'done.txt');
$todo->parse();

if (isset($_GET['logout']) || (microtime(true) - $_SESSION['auth']) >= 3600) {
  unset($_SESSION['auth']);
  session_destroy();
  header("Location: ./login.php");
}

if (isset($_POST['create_task'])) {
    $todo->create($_POST['create_task']);
    $todo->parse();
}

if (isset($_GET['complete'])) {
    $todo->complete($_GET['complete']);
    $todo->parse();
}

if (isset($_GET['archive'])) {
    $todo->archive($_GET['archive']);
    $todo->parse();
}

if (isset($_GET['track'])) {
  $todo->track($_GET['track']);
  $todo->parse();
}

?>

<html lang="en">

<head>
  <title>Shigoto</title>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <link href="https://fonts.googleapis.com/css2?family=Inter&display=swap" rel="stylesheet"> 
  <link href="style.css" rel="stylesheet">
</head>

<body>
  <div class="container">
    <div class="side mb2">
      <span class="db mb2" style="line-height:32px"><a href="/"><svg width="40" height="34" viewBox="0 0 40 34" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M16 1C13.8783 1 11.8434 1.84285 10.3431 3.34314C8.84286 4.84343 8 6.87827 8 9M8 9C8 11.1217 7.15714 13.1566 5.65685 14.6569C4.15656 16.1571 2.12173 17 0 17C2.12173 17 4.15656 17.8429 5.65685 19.3431C7.15714 20.8434 8 22.8783 8 25M8 9L24 25M8 25C8 27.1217 8.84286 29.1566 10.3431 30.6568C11.8434 32.1571 13.8783 33 16 33M8 25L24 9M16 9L32 25M32 25C32 22.8783 32.8429 20.8434 34.3431 19.3431C35.8434 17.8429 37.8783 17 40 17C37.8783 17 35.8434 16.1571 34.3431 14.6569C32.8429 13.1566 32 11.1217 32 9M32 25C32 27.1217 31.1571 29.1566 29.6568 30.6568C28.1566 32.1571 26.1217 33 24 33M16 25L32 9M32 9C32 6.87827 31.1571 4.84343 29.6568 3.34314C28.1566 1.84285 26.1217 1 24 1" stroke="black" stroke-width="2" stroke-linecap="square"/>
</svg></a></span>
      <span class="db">Projects</span>
      <ul class="mb2">
        <?php
        $projects = $todo->get_projects();
        foreach ($projects as $project) {
            echo '<li><a href=index.php?project=' . $project . '>' . $project . '</a></li>';
        }
        ?>
      </ul>
      <span class="db">Contexts</span>
      <ul class="mb2">
        <?php
        $contexts = $todo->get_contexts();
        foreach ($contexts as $context) {
            echo '<li><a href=index.php?context=' . $context . '>' . $context . '</a></li>';
        }
        ?>
      </ul>
      <span class="db">Priorities</span>
      <ul>
        <?php
        $priorities = $todo->get_priorities();
        foreach ($priorities as $priority) {
            echo '<li><a href=index.php?priority=' . $priority . '>' . $priority . '</a></li>';
        }
        ?>
      </ul>
    </div>
    <div class="main">
      <?php
        if (isset($_GET['project'])) {
            $list = $todo->filter('project', $_GET['project']);
            echo '<span class="title db mb2">'.$_GET['project'].' &mdash; '.count($list).' task(s)</span>';
            echo '<ul class="mb2">';
            foreach ($list as $item) {
                echo '<li>'.$todo->get_task($item).' <a href="index.php?complete=' . $item . '">{X}</a> <a href="index.php?archive=' . $item . '">{A}</a></li>';
            }
            echo '</ul>';
        } elseif (isset($_GET['context'])) {
            $list = $todo->filter('context', $_GET['context']);
            echo '<span class="title db mb2">'.$_GET['context'].' &mdash; '.count($list).' task(s)</span>';
            echo '<ul class="mb2">';
            foreach ($list as $item) {
                echo '<li>'.$todo->get_task($item).' <a href="index.php?complete=' . $item . '">{X}</a> <a href="index.php?archive=' . $item . '">{A}</a></li>';
            }
            echo '</ul>';
        } elseif (isset($_GET['priority'])) {
          $list = $todo->filter('priority', $_GET['priority']);
          echo '<span class="title db mb2">'.$_GET['priority'].' &mdash; '.count($list).' task(s)</span>';
          echo '<ul class="mb2">';
          foreach ($list as $item) {
              echo '<li>'.$todo->get_task($item).' <a href="index.php?complete=' . $item . '">{X}</a> <a href="index.php?archive=' . $item . '">{A}</a></li>';
          }
          echo '</ul>';
        } else {
            $list = $todo->get_todo_keys();
            echo '<span class="title db mb2">'.count($list).' tasks</span>';
            echo '<ul class="mb2">';
            foreach ($list as $item) {
                echo $todo->get_task_html($item).' <a href="index.php?complete=' . $item
                 . '">{X}</a> <a href="index.php?archive=' . $item . '">{A}</a> ' .
                 '<a href="?track=' . $item . '">{T}</a>' . 
                 '</li>';
            }
            echo '</ul>';
        }
        ?>
        <form action="index.php" method="POST" style="width:100%">
          <input type="text" placeholder="task" id="task" name="create_task">
          <button>Add</button>
      </form>
    </div>
  </div>
  <span class="db"><a href="?logout">{Logout}</a></span>
  <?php echo '<span class="db g">' . round((microtime(true) - $time) * 1000, 2) . 'ms</span>'; ?>
</body>

</html>
<?php

$method = $_SERVER["REQUEST_METHOD"];
$parsed = parse_url($_SERVER['REQUEST_URI']);
$path = $parsed['path'];

$routes = [
    'GET' => [
        '/' => 'homeHandler',
        '/edit-task' => 'editTaskHandler',
    ],
    'POST' => [
        '/create-task' => 'createTaskHandler',
        '/edit-task-method' => 'editTaskMethodHandler',
        '/delete-task' => 'deleteTaskHandler'
    ],
];

$handlerFunction = $routes[$method][$path] ?? "notFoundHandler";

$safeHandlerFunction = function_exists($handlerFunction) ? $handlerFunction : "notFoundHandler";

$handlerFunction();

function editTaskMethodHandler(): void
{
    $connection = getConnection();
    $statement = $connection->prepare("UPDATE tasks SET title = ?, description = ?, status = ?, updated_at = ? WHERE id = ?");
    $statement->execute([
        $_POST['title'],
        $_POST['description'],
        $_POST['done'] == 'on' ? 3 : 2,
        time(),
        $_GET['id'] ?? null
    ]);

    header("Location: /");
}

function editTaskHandler(): void
{
    $connection = getConnection();
    $statement = $connection->prepare("SELECT * FROM tasks WHERE id = ?");
    $statement->execute([
        $_GET['id'] ?? null
    ]);
    $task = $statement->fetch(PDO::FETCH_ASSOC);

    echo render('wrapper.phtml', [
        'content' => render('edit.phtml', [
            'task' => $task
        ])
    ]);
}

function deleteTaskHandler(): void
{
    $connection = getConnection();
    $statement = $connection->prepare("DELETE FROM tasks WHERE id = ?");
    $statement->execute([
        $_GET['id'] ?? null
    ]);

    header("Location: /");
}

function createTaskHandler(): void
{
    $connection = getConnection();
    $statement = $connection->prepare("INSERT INTO tasks (title, description, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?)");
    $statement->execute([
        $_POST['title'],
        $_POST['description'],
        1,
        time(),
        time()
    ]);

    header("Location: /");
}

function homeHandler(): void
{
    $connection = getConnection();
    $statement = $connection->prepare("SELECT * FROM tasks");
    $statement->execute();
    $tasks = $statement->fetchAll(PDO::FETCH_ASSOC);

    echo render('wrapper.phtml', [
        'content' => render('home.phtml', [
            'tasks' => $tasks
        ])
    ]);
}

function notFoundHandler(): void
{
    echo render('wrapper.phtml', [
        'content' => render('404.phtml'),
    ]);
}

function render($path, $params = []): bool|string
{
    ob_start();
    require __DIR__ . '/views/' . $path;
    return ob_get_clean();
}

function getConnection(): PDO
{
    return new PDO(
        'mysql:host=' . $_SERVER['DB_HOST'] . ';dbname=' . $_SERVER['DB_NAME'],
        $_SERVER['DB_USER'],
        $_SERVER['DB_PASSWORD']
    );
}

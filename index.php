<?php

$method = $_SERVER["REQUEST_METHOD"];
$parsed = parse_url($_SERVER['REQUEST_URI']);
$path = $parsed['path'];

$routes = [
    'GET' => [
        '/' => 'homeHandler',
    ],
    'POST' => [
        '/create-task' => 'createTaskHandler',
        '/delete-task/$id' => 'deleteTaskHandler'
    ],
];

$handlerFunction = $routes[$method][$path] ?? "notFoundHandler";

$handlerFunction();

function deleteTaskHandler(): void
{
    $connection = getConnection();
    $statement = $connection->prepare("DELETE FROM tasks WHERE id = ?");
    $statement->execute([
        $_GET['id']
    ]);
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

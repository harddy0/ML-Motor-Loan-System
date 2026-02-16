<?php
namespace App;

class TaskService {
    private $db;

    public function __construct(\PDO $db) {
        $this->db = $db;
    }

    public function getTasks() {
        // This is a placeholder. Later, you can use: 
        // return $this->db->query("SELECT * FROM tasks")->fetchAll();
        return [
            ['id' => 1, 'title' => 'Set up Loan Database'],
            ['id' => 2, 'title' => 'Verify Customer Documents'],
            ['id' => 3, 'title' => 'Process Motor Loan Application']
        ];
    }

    public function addTask($title) {
    $stmt = $this->db->prepare("INSERT INTO tasks (title) VALUES (?)");
    return $stmt->execute([$title]);
}

}
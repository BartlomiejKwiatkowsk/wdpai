<?php

class Equipment {
    private $id;
    private $tankId;
    private $name;
    private $type;
    private $status;

    public function __construct($id, $tankId, $name, $type, $status) {
        $this->id = $id;
        $this->tankId = $tankId;
        $this->name = $name;
        $this->type = $type;
        $this->status = $status;
    }

    public function getId() { return $this->id; }
    public function getName() { return $this->name; }
    public function getType() { return $this->type; }
    public function getStatus() { return $this->status; }
}
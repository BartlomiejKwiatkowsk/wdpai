<?php

class Tank {
    private $id;
    private $name;
    private $waterType;
    private $volume;
    private $status;
    private $livestockCount;
    private $imagePath;
    private $notes;

    public function __construct($id, $name, $waterType, $volume, $status, $livestockCount = 0, $imagePath = '/public/img/tanks/default-tank.png', $notes = null) {
        $this->id = $id;
        $this->name = $name;
        $this->waterType = $waterType;
        $this->volume = $volume;
        $this->status = $status;
        $this->livestockCount = $livestockCount;
        $this->imagePath = $imagePath;
        $this->notes = $notes;
    }

    public function getId() { return $this->id; }
    public function getName() { return $this->name; }
    public function getWaterType() { return $this->waterType; }
    public function getVolume() { return $this->volume; }
    public function getStatus() { return $this->status; }
    public function getLivestockCount() { return $this->livestockCount; }
    public function getImagePath() { return $this->imagePath; }
    public function getNotes() { return $this->notes; }
}
<?php

class Log {
    private $id;
    private $tankId;
    private $phLevel;
    private $temperature;
    private $notes;
    private $loggedAt;

    public function __construct($id, $tankId, $phLevel, $temperature, $notes, $loggedAt) {
        $this->id = $id;
        $this->tankId = $tankId;
        $this->phLevel = $phLevel;
        $this->temperature = $temperature;
        $this->notes = $notes;
        $this->loggedAt = $loggedAt;
    }

    public function getId() { return $this->id; }
    public function getPhLevel() { return $this->phLevel; }
    public function getTemperature() { return $this->temperature; }
    public function getLoggedAt() { return $this->loggedAt; }
}
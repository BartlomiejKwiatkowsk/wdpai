<?php

class Livestock {
    private $id;
    private $speciesName;
    private $quantity;
    private $health;

    public function __construct($id, $speciesName, $quantity, $health) {
        $this->id = $id;
        $this->speciesName = $speciesName;
        $this->quantity = $quantity;
        $this->health = $health;
    }

    public function getId() { return $this->id; }
    public function getSpeciesName() { return $this->speciesName; }
    public function getQuantity() { return $this->quantity; }
    public function getHealth() { return $this->health; }
}
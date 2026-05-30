<?php

class Species {
    private $id;
    private $commonName;
    private $scientificName;
    private $waterType;
    private $phMin;
    private $phMax;
    private $tempMin;
    private $tempMax;
    private $imagePath;

    public function __construct($id, $commonName, $scientificName, $waterType, $phMin, $phMax, $tempMin, $tempMax, $imagePath) {
        $this->id = $id;
        $this->commonName = $commonName;
        $this->scientificName = $scientificName;
        $this->waterType = $waterType;
        $this->phMin = $phMin;
        $this->phMax = $phMax;
        $this->tempMin = $tempMin;
        $this->tempMax = $tempMax;
        $this->imagePath = $imagePath;
    }

    public function getId() { return $this->id; }
    public function getCommonName() { return $this->commonName; }
    public function getScientificName() { return $this->scientificName; }
    public function getWaterType() { return $this->waterType; }
    public function getPhMin() { return $this->phMin; }
    public function getPhMax() { return $this->phMax; }
    public function getTempMin() { return $this->tempMin; }
    public function getTempMax() { return $this->tempMax; }
    public function getImagePath() { return $this->imagePath; }
}
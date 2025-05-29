package com.example.arangkada_rentals.models;

public class Car {
    private String id;
    private String make;
    private String model;
    private int year;
    private String plateNumber;
    private double price;
    private String status;
    private String imageUrl;

    public Car(String id, String make, String model, int year, String plateNumber, double price, String status, String imageUrl) {
        this.id = id;
        this.make = make;
        this.model = model;
        this.year = year;
        this.plateNumber = plateNumber;
        this.price = price;
        this.status = status;
        this.imageUrl = imageUrl;
    }

    // Getters
    public String getId() { return id; }
    public String getMake() { return make; }
    public String getModel() { return model; }
    public int getYear() { return year; }
    public String getPlateNumber() { return plateNumber; }
    public double getPrice() { return price; }
    public String getStatus() { return status; }
    public String getImageUrl() { return imageUrl; }

    // Setters
    public void setId(String id) { this.id = id; }
    public void setMake(String make) { this.make = make; }
    public void setModel(String model) { this.model = model; }
    public void setYear(int year) { this.year = year; }
    public void setPlateNumber(String plateNumber) { this.plateNumber = plateNumber; }
    public void setPrice(double price) { this.price = price; }
    public void setStatus(String status) { this.status = status; }
    public void setImageUrl(String imageUrl) { this.imageUrl = imageUrl; }
} 
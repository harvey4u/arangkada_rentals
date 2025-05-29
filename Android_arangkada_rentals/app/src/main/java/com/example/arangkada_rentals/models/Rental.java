package com.example.arangkada_rentals.models;

import java.io.Serializable;
import java.util.Date;

public class Rental implements Serializable {
    private String id;
    private String carId;
    private String driverId;
    private Date startDate;
    private Date endDate;
    private double totalAmount;
    private String status; // pending, active, completed, cancelled
    private Car car; // Reference to the rented car

    public Rental() {
        // Required empty constructor
    }

    public Rental(String id, String carId, String driverId, Date startDate, Date endDate, 
                 double totalAmount, String status, Car car) {
        this.id = id;
        this.carId = carId;
        this.driverId = driverId;
        this.startDate = startDate;
        this.endDate = endDate;
        this.totalAmount = totalAmount;
        this.status = status;
        this.car = car;
    }

    // Getters and Setters
    public String getId() { return id; }
    public void setId(String id) { this.id = id; }

    public String getCarId() { return carId; }
    public void setCarId(String carId) { this.carId = carId; }

    public String getDriverId() { return driverId; }
    public void setDriverId(String driverId) { this.driverId = driverId; }

    public Date getStartDate() { return startDate; }
    public void setStartDate(Date startDate) { this.startDate = startDate; }

    public Date getEndDate() { return endDate; }
    public void setEndDate(Date endDate) { this.endDate = endDate; }

    public double getTotalAmount() { return totalAmount; }
    public void setTotalAmount(double totalAmount) { this.totalAmount = totalAmount; }

    public String getStatus() { return status; }
    public void setStatus(String status) { this.status = status; }

    public Car getCar() { return car; }
    public void setCar(Car car) { this.car = car; }
} 
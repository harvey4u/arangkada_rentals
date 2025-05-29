package com.example.arangkada_rentals.models;

public class User {
    private String id;
    private String username;
    private String email;
    private String role;
    private String status;
    private String createdAt;

    public User(String id, String username, String email, String role, String status, String createdAt) {
        this.id = id;
        this.username = username;
        this.email = email;
        this.role = role;
        this.status = status;
        this.createdAt = createdAt;
    }

    // Getters
    public String getId() { return id; }
    public String getUsername() { return username; }
    public String getEmail() { return email; }
    public String getRole() { return role; }
    public String getStatus() { return status; }
    public String getCreatedAt() { return createdAt; }

    // Setters
    public void setId(String id) { this.id = id; }
    public void setUsername(String username) { this.username = username; }
    public void setEmail(String email) { this.email = email; }
    public void setRole(String role) { this.role = role; }
    public void setStatus(String status) { this.status = status; }
    public void setCreatedAt(String createdAt) { this.createdAt = createdAt; }
} 
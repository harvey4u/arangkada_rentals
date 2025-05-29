package com.example.arangkada_rentals;

import android.content.Intent;
import android.os.Bundle;
import android.widget.Button;

import androidx.appcompat.app.AppCompatActivity;

public class HomePageActivity extends AppCompatActivity {

    private Button btnViewCars;
    private Button btnLogin;
    private Button btnRegister;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_homepage);

        // Initialize views
        initializeViews();
        
        // Set click listeners
        setClickListeners();
    }

    private void initializeViews() {
        btnViewCars = findViewById(R.id.btnViewCars);
        btnLogin = findViewById(R.id.btnLogin);
        btnRegister = findViewById(R.id.btnRegister);
    }

    private void setClickListeners() {
        // View Available Cars button click
        btnViewCars.setOnClickListener(view -> {
            Intent intent = new Intent(HomePageActivity.this, AvailableCarsActivity.class);
            startActivity(intent);
        });

        // Login button click
        btnLogin.setOnClickListener(view -> {
            Intent intent = new Intent(HomePageActivity.this, MainActivity.class);
            startActivity(intent);
        });

        // Register button click
        btnRegister.setOnClickListener(view -> {
            Intent intent = new Intent(HomePageActivity.this, RegisterActivity.class);
            startActivity(intent);
        });
    }
} 
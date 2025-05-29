package com.example.arangkada_rentals;

import androidx.appcompat.app.AppCompatActivity;
import androidx.appcompat.widget.Toolbar;
import androidx.cardview.widget.CardView;
import android.content.Intent;
import android.os.Bundle;
import android.view.View;

public class DriverDashboardActivity extends AppCompatActivity implements View.OnClickListener {
    private CardView cvMyRentals, cvAvailableCars, cvProfile;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_driver_dashboard);

        initializeViews();
        setupToolbar();
        setupClickListeners();
    }

    private void initializeViews() {
        cvMyRentals = findViewById(R.id.cvMyRentals);
        cvAvailableCars = findViewById(R.id.cvAvailableCars);
        cvProfile = findViewById(R.id.cvProfile);
    }

    private void setupToolbar() {
        Toolbar toolbar = findViewById(R.id.toolbar);
        setSupportActionBar(toolbar);
        if (getSupportActionBar() != null) {
            getSupportActionBar().setTitle("Driver Dashboard");
        }
    }

    private void setupClickListeners() {
        cvMyRentals.setOnClickListener(this);
        cvAvailableCars.setOnClickListener(this);
        cvProfile.setOnClickListener(this);
    }

    @Override
    public void onClick(View view) {
        Intent intent = null;
        int id = view.getId();

        if (id == R.id.cvMyRentals) {
            intent = new Intent(this, MyRentalsActivity.class);
        } else if (id == R.id.cvAvailableCars) {
            intent = new Intent(this, AvailableCarsActivity.class);
        } else if (id == R.id.cvProfile) {
            intent = new Intent(this, EditProfileActivity.class);
        }

        if (intent != null) {
            startActivity(intent);
        }
    }
} 
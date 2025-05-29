package com.example.arangkada_rentals;

import androidx.appcompat.app.AppCompatActivity;
import androidx.appcompat.widget.Toolbar;
import androidx.cardview.widget.CardView;
import android.content.Intent;
import android.os.Bundle;
import android.view.View;

public class StaffDashboardActivity extends AppCompatActivity implements View.OnClickListener {
    private CardView cvManageCars, cvManageRentals, cvManageUsers, cvReports;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_staff_dashboard);

        initializeViews();
        setupToolbar();
        setupClickListeners();
    }

    private void initializeViews() {
        cvManageCars = findViewById(R.id.cvManageCars);
        cvManageRentals = findViewById(R.id.cvManageRentals);
        cvManageUsers = findViewById(R.id.cvManageUsers);
        cvReports = findViewById(R.id.cvReports);
    }

    private void setupToolbar() {
        Toolbar toolbar = findViewById(R.id.toolbar);
        setSupportActionBar(toolbar);
        if (getSupportActionBar() != null) {
            getSupportActionBar().setTitle("Staff Dashboard");
        }
    }

    private void setupClickListeners() {
        cvManageCars.setOnClickListener(this);
        cvManageRentals.setOnClickListener(this);
        cvManageUsers.setOnClickListener(this);
        cvReports.setOnClickListener(this);
    }

    @Override
    public void onClick(View view) {
        Intent intent = null;
        int id = view.getId();

        if (id == R.id.cvManageCars) {
            intent = new Intent(this, ManageCarsActivity.class);
        } else if (id == R.id.cvManageRentals) {
            intent = new Intent(this, ManageRentalsActivity.class);
        } else if (id == R.id.cvManageUsers) {
            intent = new Intent(this, ManageUsersActivity.class);
        } else if (id == R.id.cvReports) {
            intent = new Intent(this, ReportsActivity.class);
        }

        if (intent != null) {
            startActivity(intent);
        }
    }
} 
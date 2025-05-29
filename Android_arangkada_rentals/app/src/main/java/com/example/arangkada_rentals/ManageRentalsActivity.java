package com.example.arangkada_rentals;

import androidx.appcompat.app.AppCompatActivity;
import androidx.appcompat.widget.Toolbar;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;
import android.os.Bundle;
import android.view.View;
import android.widget.ProgressBar;
import android.widget.TextView;

public class ManageRentalsActivity extends AppCompatActivity {
    private RecyclerView rvRentals;
    private ProgressBar progressBar;
    private TextView tvEmpty;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_manage_rentals);

        initializeViews();
        setupToolbar();
        setupRecyclerView();
        loadRentals();
    }

    private void initializeViews() {
        rvRentals = findViewById(R.id.rvRentals);
        progressBar = findViewById(R.id.progressBar);
        tvEmpty = findViewById(R.id.tvEmpty);
    }

    private void setupToolbar() {
        Toolbar toolbar = findViewById(R.id.toolbar);
        setSupportActionBar(toolbar);
        if (getSupportActionBar() != null) {
            getSupportActionBar().setDisplayHomeAsUpEnabled(true);
            getSupportActionBar().setDisplayShowHomeEnabled(true);
            getSupportActionBar().setTitle("Manage Rentals");
        }
    }

    private void setupRecyclerView() {
        rvRentals.setLayoutManager(new LinearLayoutManager(this));
        // TODO: Implement rental adapter
    }

    private void loadRentals() {
        // TODO: Implement rental loading logic
    }

    @Override
    public boolean onSupportNavigateUp() {
        onBackPressed();
        return true;
    }
} 
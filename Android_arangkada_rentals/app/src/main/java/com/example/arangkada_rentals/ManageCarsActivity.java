package com.example.arangkada_rentals;

import androidx.appcompat.app.AppCompatActivity;
import androidx.appcompat.widget.Toolbar;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;
import android.os.Bundle;
import android.view.View;
import android.widget.ProgressBar;
import android.widget.TextView;

import com.example.arangkada_rentals.adapters.CarAdapter;
import com.example.arangkada_rentals.models.Car;
import com.google.android.material.floatingactionbutton.FloatingActionButton;

import java.util.ArrayList;
import java.util.List;

public class ManageCarsActivity extends AppCompatActivity implements CarAdapter.OnCarClickListener {
    private RecyclerView rvCars;
    private ProgressBar progressBar;
    private TextView tvEmpty;
    private FloatingActionButton fabAddCar;
    private CarAdapter carAdapter;
    private List<Car> carList;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_manage_cars);

        initializeViews();
        setupToolbar();
        setupRecyclerView();
        loadCars();
    }

    private void initializeViews() {
        rvCars = findViewById(R.id.rvCars);
        progressBar = findViewById(R.id.progressBar);
        tvEmpty = findViewById(R.id.tvEmpty);
        fabAddCar = findViewById(R.id.fabAddCar);

        fabAddCar.setOnClickListener(v -> {
            // TODO: Implement add car functionality
        });
    }

    private void setupToolbar() {
        Toolbar toolbar = findViewById(R.id.toolbar);
        setSupportActionBar(toolbar);
        if (getSupportActionBar() != null) {
            getSupportActionBar().setDisplayHomeAsUpEnabled(true);
            getSupportActionBar().setDisplayShowHomeEnabled(true);
            getSupportActionBar().setTitle("Manage Cars");
        }
    }

    private void setupRecyclerView() {
        carList = new ArrayList<>();
        carAdapter = new CarAdapter(this, carList, this);
        rvCars.setLayoutManager(new LinearLayoutManager(this));
        rvCars.setAdapter(carAdapter);
    }

    private void loadCars() {
        // TODO: Implement car loading logic
    }

    @Override
    public void onCarClick(Car car) {
        // TODO: Implement car click handling
    }

    @Override
    public boolean onSupportNavigateUp() {
        onBackPressed();
        return true;
    }
} 
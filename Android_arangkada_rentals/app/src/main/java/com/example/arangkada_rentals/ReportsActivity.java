package com.example.arangkada_rentals;

import androidx.appcompat.app.AppCompatActivity;
import androidx.appcompat.widget.Toolbar;
import android.os.Bundle;
import android.widget.ProgressBar;
import android.widget.TextView;

public class ReportsActivity extends AppCompatActivity {
    private ProgressBar progressBar;
    private TextView tvReportSummary;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_reports);

        initializeViews();
        setupToolbar();
        loadReports();
    }

    private void initializeViews() {
        progressBar = findViewById(R.id.progressBar);
        tvReportSummary = findViewById(R.id.tvReportSummary);
    }

    private void setupToolbar() {
        Toolbar toolbar = findViewById(R.id.toolbar);
        setSupportActionBar(toolbar);
        if (getSupportActionBar() != null) {
            getSupportActionBar().setDisplayHomeAsUpEnabled(true);
            getSupportActionBar().setDisplayShowHomeEnabled(true);
            getSupportActionBar().setTitle("Reports");
        }
    }

    private void loadReports() {
        // TODO: Implement reports loading logic
    }

    @Override
    public boolean onSupportNavigateUp() {
        onBackPressed();
        return true;
    }
} 
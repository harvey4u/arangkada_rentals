package com.example.arangkada_rentals;

import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.view.MenuItem;
import android.view.View;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.appcompat.app.ActionBarDrawerToggle;
import androidx.appcompat.app.AppCompatActivity;
import androidx.appcompat.widget.Toolbar;
import androidx.cardview.widget.CardView;
import androidx.core.view.GravityCompat;
import androidx.drawerlayout.widget.DrawerLayout;

import com.google.android.material.navigation.NavigationView;

public class AdminDashboardActivity extends AppCompatActivity 
        implements NavigationView.OnNavigationItemSelectedListener {

    private DrawerLayout drawerLayout;
    private NavigationView navigationView;
    private TextView tvWelcome;
    private CardView cardManageUsers, cardManageCars, cardManageRentals, cardReports;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_admin_dashboard);

        // Initialize views
        initializeViews();

        // Setup toolbar and navigation drawer
        setupNavigation();

        // Set click listeners for dashboard cards
        setupDashboardCards();

        // Load user data
        loadUserData();
    }

    private void initializeViews() {
        drawerLayout = findViewById(R.id.drawerLayout);
        navigationView = findViewById(R.id.navView);
        Toolbar toolbar = findViewById(R.id.toolbar);
        setSupportActionBar(toolbar);

        tvWelcome = findViewById(R.id.tvWelcome);
        cardManageUsers = findViewById(R.id.cardManageUsers);
        cardManageCars = findViewById(R.id.cardManageCars);
        cardManageRentals = findViewById(R.id.cardManageRentals);
        cardReports = findViewById(R.id.cardReports);
    }

    private void setupNavigation() {
        ActionBarDrawerToggle toggle = new ActionBarDrawerToggle(
            this, drawerLayout, findViewById(R.id.toolbar),
            R.string.navigation_drawer_open,
            R.string.navigation_drawer_close);
        
        drawerLayout.addDrawerListener(toggle);
        toggle.syncState();

        navigationView.setNavigationItemSelectedListener(this);
    }

    private void setupDashboardCards() {
        cardManageUsers.setOnClickListener(v -> {
            // TODO: Navigate to Manage Users screen
            startActivity(new Intent(this, ManageUsersActivity.class));
        });

        cardManageCars.setOnClickListener(v -> {
            // TODO: Navigate to Manage Cars screen
            startActivity(new Intent(this, ManageCarsActivity.class));
        });

        cardManageRentals.setOnClickListener(v -> {
            // TODO: Navigate to Manage Rentals screen
            startActivity(new Intent(this, ManageRentalsActivity.class));
        });

        cardReports.setOnClickListener(v -> {
            // TODO: Navigate to Reports screen
            startActivity(new Intent(this, ReportsActivity.class));
        });
    }

    private void loadUserData() {
        SharedPreferences prefs = getSharedPreferences("ArangkadaPrefs", MODE_PRIVATE);
        String username = prefs.getString("username", "Admin");
        String email = prefs.getString("email", "");

        // Update welcome message
        tvWelcome.setText(String.format("Welcome, %s", username));

        // Update navigation header
        View headerView = navigationView.getHeaderView(0);
        TextView tvUsername = headerView.findViewById(R.id.tvUsername);
        TextView tvEmail = headerView.findViewById(R.id.tvEmail);
        
        tvUsername.setText(username);
        tvEmail.setText(email);
    }

    @Override
    public boolean onNavigationItemSelected(@NonNull MenuItem item) {
        int id = item.getItemId();

        if (id == R.id.nav_dashboard) {
            // Already on dashboard
            drawerLayout.closeDrawer(GravityCompat.START);
            return true;
        }
        
        Intent intent = null;

        if (id == R.id.nav_manage_users) {
            intent = new Intent(this, ManageUsersActivity.class);
        } else if (id == R.id.nav_manage_cars) {
            intent = new Intent(this, ManageCarsActivity.class);
        } else if (id == R.id.nav_manage_rentals) {
            intent = new Intent(this, ManageRentalsActivity.class);
        } else if (id == R.id.nav_reports) {
            intent = new Intent(this, ReportsActivity.class);
        } else if (id == R.id.nav_profile) {
            intent = new Intent(this, EditProfileActivity.class);
        } else if (id == R.id.nav_logout) {
            logout();
            return true;
        }

        if (intent != null) {
            startActivity(intent);
        }

        drawerLayout.closeDrawer(GravityCompat.START);
        return true;
    }

    private void logout() {
        // Clear user data
        SharedPreferences.Editor editor = getSharedPreferences("ArangkadaPrefs", MODE_PRIVATE).edit();
        editor.clear();
        editor.apply();

        // Navigate to login screen
        Intent intent = new Intent(this, LoginActivity.class);
        intent.setFlags(Intent.FLAG_ACTIVITY_NEW_TASK | Intent.FLAG_ACTIVITY_CLEAR_TASK);
        startActivity(intent);
        finish();
    }

    @Override
    public void onBackPressed() {
        if (drawerLayout.isDrawerOpen(GravityCompat.START)) {
            drawerLayout.closeDrawer(GravityCompat.START);
        } else {
            super.onBackPressed();
        }
    }
} 
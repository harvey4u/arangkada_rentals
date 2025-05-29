package com.example.arangkada_rentals;

import android.content.Intent;
import android.os.Bundle;
import android.text.Editable;
import android.text.TextWatcher;
import android.view.View;
import android.widget.EditText;
import android.widget.ProgressBar;
import android.widget.TextView;
import android.widget.Toast;

import androidx.appcompat.app.AlertDialog;
import androidx.appcompat.app.AppCompatActivity;
import androidx.appcompat.widget.Toolbar;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;

import com.example.arangkada_rentals.adapters.UserAdapter;
import com.example.arangkada_rentals.models.User;
import com.google.android.material.chip.Chip;
import com.google.android.material.chip.ChipGroup;
import com.google.android.material.floatingactionbutton.FloatingActionButton;

import org.json.JSONArray;
import org.json.JSONObject;
import org.json.JSONException;

import java.io.BufferedReader;
import java.io.InputStreamReader;
import java.net.HttpURLConnection;
import java.net.URL;
import java.util.ArrayList;
import java.util.List;
import java.util.stream.Collectors;

public class ManageUsersActivity extends AppCompatActivity implements UserAdapter.OnUserActionListener {

    private EditText etSearch;
    private ChipGroup chipGroupRoles;
    private RecyclerView rvUsers;
    private ProgressBar progressBar;
    private TextView tvEmpty;
    private FloatingActionButton fabAddUser;

    private UserAdapter userAdapter;
    private List<User> userList;
    private List<User> filteredList;

    private static final String USERS_URL = "http://192.168.100.45/ARANGKADA/arangkada_rentals/api/users.php";

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_manage_users);

        // Initialize views
        initializeViews();

        // Setup toolbar
        setupToolbar();

        // Setup RecyclerView
        setupRecyclerView();

        // Set click listeners
        setClickListeners();

        // Load users
        loadUsers();
    }

    private void initializeViews() {
        etSearch = findViewById(R.id.etSearch);
        chipGroupRoles = findViewById(R.id.chipGroupRoles);
        rvUsers = findViewById(R.id.rvUsers);
        progressBar = findViewById(R.id.progressBar);
        tvEmpty = findViewById(R.id.tvEmpty);
        fabAddUser = findViewById(R.id.fabAddUser);

        userList = new ArrayList<>();
        filteredList = new ArrayList<>();
    }

    private void setupToolbar() {
        Toolbar toolbar = findViewById(R.id.toolbar);
        setSupportActionBar(toolbar);
        if (getSupportActionBar() != null) {
            getSupportActionBar().setDisplayHomeAsUpEnabled(true);
            getSupportActionBar().setDisplayShowHomeEnabled(true);
        }
    }

    private void setupRecyclerView() {
        userAdapter = new UserAdapter(this, filteredList, this);
        rvUsers.setLayoutManager(new LinearLayoutManager(this));
        rvUsers.setAdapter(userAdapter);
    }

    private void setClickListeners() {
        etSearch.addTextChangedListener(new TextWatcher() {
            @Override
            public void beforeTextChanged(CharSequence s, int start, int count, int after) {}

            @Override
            public void onTextChanged(CharSequence s, int start, int before, int count) {
                filterUsers();
            }

            @Override
            public void afterTextChanged(Editable s) {}
        });

        chipGroupRoles.setOnCheckedChangeListener((group, checkedId) -> filterUsers());

        fabAddUser.setOnClickListener(v -> {
            // TODO: Navigate to Add User screen
            Intent intent = new Intent(this, AddUserActivity.class);
            startActivity(intent);
        });
    }

    private void loadUsers() {
        setLoading(true);

        new Thread(() -> {
            try {
                URL url = new URL(USERS_URL);
                HttpURLConnection conn = (HttpURLConnection) url.openConnection();
                conn.setRequestMethod("GET");

                int responseCode = conn.getResponseCode();
                if (responseCode != HttpURLConnection.HTTP_OK) {
                    showError("Server returned error code: " + responseCode);
                    return;
                }

                BufferedReader reader = new BufferedReader(new InputStreamReader(conn.getInputStream()));
                StringBuilder response = new StringBuilder();
                String line;

                while ((line = reader.readLine()) != null) {
                    response.append(line);
                }
                reader.close();

                try {
                    JSONObject jsonResponse = new JSONObject(response.toString());
                    if (jsonResponse.getString("status").equals("success")) {
                        JSONArray usersArray = jsonResponse.getJSONArray("users");
                        userList.clear();

                        for (int i = 0; i < usersArray.length(); i++) {
                            try {
                                JSONObject userObject = usersArray.getJSONObject(i);
                                User user = new User(
                                    userObject.getString("id"),
                                    userObject.getString("username"),
                                    userObject.getString("email"),
                                    userObject.getString("role"),
                                    userObject.getString("status"),
                                    userObject.getString("created_at")
                                );
                                userList.add(user);
                            } catch (JSONException e) {
                                // Log the error but continue processing other users
                                e.printStackTrace();
                            }
                        }

                        runOnUiThread(() -> {
                            filterUsers();
                            setLoading(false);
                        });
                    } else {
                        showError(jsonResponse.optString("message", "Error loading users"));
                    }
                } catch (JSONException e) {
                    showError("Error parsing server response: " + e.getMessage());
                }
            } catch (Exception e) {
                e.printStackTrace();
                showError("Network error: " + e.getMessage());
            }
        }).start();
    }

    private void filterUsers() {
        String searchQuery = etSearch.getText().toString().toLowerCase().trim();
        int checkedChipId = chipGroupRoles.getCheckedChipId();
        String selectedRole = "";

        if (checkedChipId != View.NO_ID) {
            Chip checkedChip = chipGroupRoles.findViewById(checkedChipId);
            if (checkedChip != null && !checkedChip.getText().toString().equals("All")) {
                selectedRole = checkedChip.getText().toString().toLowerCase();
            }
        }

        filteredList.clear();
        for (User user : userList) {
            boolean matchesSearch = searchQuery.isEmpty() || 
                user.getUsername().toLowerCase().contains(searchQuery) ||
                user.getEmail().toLowerCase().contains(searchQuery);
            
            boolean matchesRole = selectedRole.isEmpty() || 
                user.getRole().toLowerCase().equals(selectedRole);

            if (matchesSearch && matchesRole) {
                filteredList.add(user);
            }
        }

        userAdapter.notifyDataSetChanged();
        updateEmptyState();
    }

    private void updateEmptyState() {
        if (filteredList.isEmpty()) {
            tvEmpty.setVisibility(View.VISIBLE);
            rvUsers.setVisibility(View.GONE);
        } else {
            tvEmpty.setVisibility(View.GONE);
            rvUsers.setVisibility(View.VISIBLE);
        }
    }

    private void setLoading(boolean isLoading) {
        runOnUiThread(() -> {
            progressBar.setVisibility(isLoading ? View.VISIBLE : View.GONE);
            rvUsers.setVisibility(isLoading ? View.GONE : View.VISIBLE);
            etSearch.setEnabled(!isLoading);
            chipGroupRoles.setEnabled(!isLoading);
            fabAddUser.setEnabled(!isLoading);
        });
    }

    private void showError(String message) {
        runOnUiThread(() -> {
            Toast.makeText(this, message, Toast.LENGTH_LONG).show();
            setLoading(false);
        });
    }

    @Override
    public void onEditUser(User user) {
        Intent intent = new Intent(this, EditUserActivity.class);
        intent.putExtra("user_id", user.getId());
        startActivity(intent);
    }

    @Override
    public void onDeleteUser(User user) {
        new AlertDialog.Builder(this)
            .setTitle("Delete User")
            .setMessage("Are you sure you want to delete " + user.getUsername() + "?")
            .setPositiveButton("Delete", (dialog, which) -> {
                // TODO: Implement delete user API call
                Toast.makeText(this, "Delete user functionality coming soon", Toast.LENGTH_SHORT).show();
            })
            .setNegativeButton("Cancel", null)
            .show();
    }

    @Override
    public void onViewDetails(User user) {
        Intent intent = new Intent(this, UserDetailsActivity.class);
        intent.putExtra("user_id", user.getId());
        startActivity(intent);
    }

    @Override
    public boolean onSupportNavigateUp() {
        onBackPressed();
        return true;
    }
} 
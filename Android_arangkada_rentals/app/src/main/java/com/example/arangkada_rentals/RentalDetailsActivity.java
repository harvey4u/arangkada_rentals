package com.example.arangkada_rentals;

import android.os.Bundle;
import android.view.View;
import android.widget.ImageView;
import android.widget.TextView;

import androidx.appcompat.app.AppCompatActivity;
import androidx.core.content.ContextCompat;

import com.bumptech.glide.Glide;
import com.example.arangkada_rentals.models.Rental;

import java.text.NumberFormat;
import java.text.SimpleDateFormat;
import java.util.Locale;
import java.util.concurrent.TimeUnit;

public class RentalDetailsActivity extends AppCompatActivity {
    public static final String EXTRA_RENTAL = "rental";

    private ImageView ivCarImage;
    private TextView tvStatus, tvCarName, tvPlateNumber;
    private TextView tvDates, tvDuration, tvDailyRate, tvTotalAmount;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_rental_details);

        // Initialize views
        initializeViews();

        // Get rental from intent
        Rental rental = (Rental) getIntent().getSerializableExtra(EXTRA_RENTAL);
        if (rental == null) {
            finish();
            return;
        }

        // Display rental details
        displayRentalDetails(rental);
    }

    private void initializeViews() {
        ivCarImage = findViewById(R.id.ivCarImage);
        tvStatus = findViewById(R.id.tvStatus);
        tvCarName = findViewById(R.id.tvCarName);
        tvPlateNumber = findViewById(R.id.tvPlateNumber);
        tvDates = findViewById(R.id.tvDates);
        tvDuration = findViewById(R.id.tvDuration);
        tvDailyRate = findViewById(R.id.tvDailyRate);
        tvTotalAmount = findViewById(R.id.tvTotalAmount);

        // Set up toolbar
        setSupportActionBar(findViewById(R.id.toolbar));
        if (getSupportActionBar() != null) {
            getSupportActionBar().setTitle("Rental Details");
            getSupportActionBar().setDisplayHomeAsUpEnabled(true);
        }
    }

    private void displayRentalDetails(Rental rental) {
        // Set car image
        if (rental.getCar().getImageUrl() != null && !rental.getCar().getImageUrl().isEmpty()) {
            Glide.with(this)
                .load(rental.getCar().getImageUrl())
                .placeholder(R.drawable.car_placeholder)
                .error(R.drawable.car_placeholder)
                .into(ivCarImage);
        } else {
            ivCarImage.setImageResource(R.drawable.car_placeholder);
        }

        // Set status with color
        tvStatus.setText(rental.getStatus().toUpperCase());
        int statusColor;
        switch (rental.getStatus().toLowerCase()) {
            case "active":
                statusColor = R.color.available_green;
                break;
            case "completed":
                statusColor = R.color.completed_blue;
                break;
            case "cancelled":
                statusColor = R.color.rented_red;
                break;
            default:
                statusColor = R.color.maintenance_yellow;
                break;
        }
        tvStatus.setBackgroundTintList(ContextCompat.getColorStateList(this, statusColor));

        // Set car details
        tvCarName.setText(String.format("%s %s", rental.getCar().getMake(), rental.getCar().getModel()));
        tvPlateNumber.setText(rental.getCar().getPlateNumber());

        // Set dates
        SimpleDateFormat dateFormat = new SimpleDateFormat("MMM dd, yyyy", Locale.getDefault());
        tvDates.setText(String.format("%s - %s",
            dateFormat.format(rental.getStartDate()),
            dateFormat.format(rental.getEndDate())));

        // Calculate and set duration
        long durationMillis = rental.getEndDate().getTime() - rental.getStartDate().getTime();
        long days = TimeUnit.MILLISECONDS.toDays(durationMillis);
        tvDuration.setText(String.format("%d day%s", days, days > 1 ? "s" : ""));

        // Set payment details
        NumberFormat currencyFormat = NumberFormat.getCurrencyInstance(new Locale("en", "PH"));
        tvDailyRate.setText(String.format("Daily Rate: %s",
            currencyFormat.format(rental.getCar().getPrice())));
        tvTotalAmount.setText(String.format("Total Amount: %s",
            currencyFormat.format(rental.getTotalAmount())));
    }

    @Override
    public boolean onSupportNavigateUp() {
        onBackPressed();
        return true;
    }
} 
package com.example.arangkada_rentals.adapters;

import android.content.Context;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ImageView;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.core.content.ContextCompat;
import androidx.recyclerview.widget.RecyclerView;

import com.bumptech.glide.Glide;
import com.example.arangkada_rentals.R;
import com.example.arangkada_rentals.models.Rental;

import java.text.NumberFormat;
import java.text.SimpleDateFormat;
import java.util.List;
import java.util.Locale;

public class RentalAdapter extends RecyclerView.Adapter<RentalAdapter.RentalViewHolder> {
    private Context context;
    private List<Rental> rentals;
    private OnRentalClickListener listener;

    public interface OnRentalClickListener {
        void onRentalClick(Rental rental);
    }

    public RentalAdapter(Context context, List<Rental> rentals, OnRentalClickListener listener) {
        this.context = context;
        this.rentals = rentals;
        this.listener = listener;
    }

    @NonNull
    @Override
    public RentalViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(context).inflate(R.layout.item_rental, parent, false);
        return new RentalViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull RentalViewHolder holder, int position) {
        Rental rental = rentals.get(position);
        
        // Set car details
        holder.tvCarName.setText(String.format("%s %s", rental.getCar().getMake(), rental.getCar().getModel()));
        holder.tvPlateNumber.setText(rental.getCar().getPlateNumber());
        
        // Set dates
        SimpleDateFormat dateFormat = new SimpleDateFormat("MMM dd, yyyy", Locale.getDefault());
        holder.tvDates.setText(String.format("%s - %s", 
            dateFormat.format(rental.getStartDate()),
            dateFormat.format(rental.getEndDate())));
        
        // Set amount
        NumberFormat currencyFormat = NumberFormat.getCurrencyInstance(new Locale("en", "PH"));
        holder.tvAmount.setText(currencyFormat.format(rental.getTotalAmount()));
        
        // Set status with color
        holder.tvStatus.setText(rental.getStatus().toUpperCase());
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
        holder.tvStatus.setTextColor(ContextCompat.getColor(context, statusColor));

        // Load car image
        if (rental.getCar().getImageUrl() != null && !rental.getCar().getImageUrl().isEmpty()) {
            Glide.with(context)
                .load(rental.getCar().getImageUrl())
                .placeholder(R.drawable.car_placeholder)
                .error(R.drawable.car_placeholder)
                .into(holder.ivCarImage);
        } else {
            holder.ivCarImage.setImageResource(R.drawable.car_placeholder);
        }

        // Set click listener
        holder.itemView.setOnClickListener(v -> {
            if (listener != null) {
                listener.onRentalClick(rental);
            }
        });
    }

    @Override
    public int getItemCount() {
        return rentals.size();
    }

    public void updateRentals(List<Rental> newRentals) {
        rentals.clear();
        rentals.addAll(newRentals);
        notifyDataSetChanged();
    }

    static class RentalViewHolder extends RecyclerView.ViewHolder {
        ImageView ivCarImage;
        TextView tvCarName;
        TextView tvPlateNumber;
        TextView tvDates;
        TextView tvAmount;
        TextView tvStatus;

        RentalViewHolder(View itemView) {
            super(itemView);
            ivCarImage = itemView.findViewById(R.id.ivCarImage);
            tvCarName = itemView.findViewById(R.id.tvCarName);
            tvPlateNumber = itemView.findViewById(R.id.tvPlateNumber);
            tvDates = itemView.findViewById(R.id.tvDates);
            tvAmount = itemView.findViewById(R.id.tvAmount);
            tvStatus = itemView.findViewById(R.id.tvStatus);
        }
    }
} 
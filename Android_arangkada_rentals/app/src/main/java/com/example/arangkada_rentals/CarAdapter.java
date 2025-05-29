package com.example.arangkada_rentals;

import android.app.AlertDialog;
import android.content.Context;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ImageButton;
import android.widget.ImageView;
import android.widget.PopupMenu;
import android.widget.TextView;
import android.widget.Toast;

import androidx.annotation.NonNull;
import androidx.core.content.ContextCompat;
import androidx.recyclerview.widget.RecyclerView;

import com.bumptech.glide.Glide;
import com.example.arangkada_rentals.models.Car;

import java.util.List;
import java.util.Locale;
import java.text.NumberFormat;

public class CarAdapter extends RecyclerView.Adapter<CarAdapter.CarViewHolder> {

    private List<Car> carList;
    private Context context;
    private OnCarClickListener listener;

    public interface OnCarClickListener {
        void onCarClick(Car car);
    }

    public CarAdapter(Context context, List<Car> carList, OnCarClickListener listener) {
        this.context = context;
        this.carList = carList;
        this.listener = listener;
    }

    @NonNull
    @Override
    public CarViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(context)
                .inflate(R.layout.item_car, parent, false);
        return new CarViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull CarViewHolder holder, int position) {
        Car car = carList.get(position);
        
        holder.tvMakeModel.setText(String.format("%s %s", car.getMake(), car.getModel()));
        holder.tvYear.setText(String.valueOf(car.getYear()));
        holder.tvPlateNumber.setText(car.getPlateNumber());
        
        NumberFormat formatter = NumberFormat.getCurrencyInstance(new Locale("en", "PH"));
        holder.tvPrice.setText(formatter.format(car.getPrice()) + "/day");

        // Load car image if available
        if (car.getImageUrl() != null && !car.getImageUrl().isEmpty()) {
            Glide.with(context)
                .load(car.getImageUrl())
                .placeholder(R.drawable.car_placeholder)
                .error(R.drawable.car_placeholder)
                .into(holder.ivCarImage);
        } else {
            holder.ivCarImage.setImageResource(R.drawable.car_placeholder);
        }

        // Set status indicator
        int statusColor;
        switch (car.getStatus().toLowerCase()) {
            case "available":
                statusColor = ContextCompat.getColor(context, R.color.available_green);
                break;
            case "rented":
                statusColor = ContextCompat.getColor(context, R.color.rented_red);
                break;
            default:
                statusColor = ContextCompat.getColor(context, R.color.maintenance_yellow);
                break;
        }
        holder.vStatusIndicator.setBackgroundColor(statusColor);

        // Set up more options menu
        holder.btnMore.setOnClickListener(v -> {
            PopupMenu popup = new PopupMenu(context, holder.btnMore);
            popup.inflate(R.menu.car_options_menu);
            
            popup.setOnMenuItemClickListener(item -> {
                int id = item.getItemId();
                if (id == R.id.action_edit) {
                    if (listener != null) {
                        listener.onCarClick(car);
                    }
                    return true;
                } else if (id == R.id.action_delete) {
                    showDeleteConfirmation(car);
                    return true;
                }
                return false;
            });
            
            popup.show();
        });
    }

    private void showDeleteConfirmation(Car car) {
        new AlertDialog.Builder(context)
            .setTitle("Delete Car")
            .setMessage("Are you sure you want to delete this car?")
            .setPositiveButton("Delete", (dialog, which) -> {
                if (listener != null && listener instanceof CarsFragment) {
                    ((CarsFragment) listener).deleteCar(car.getId());
                }
            })
            .setNegativeButton("Cancel", null)
            .show();
    }

    @Override
    public int getItemCount() {
        return carList.size();
    }

    public void updateList(List<Car> newList) {
        carList.clear();
        carList.addAll(newList);
        notifyDataSetChanged();
    }

    static class CarViewHolder extends RecyclerView.ViewHolder {
        ImageView ivCarImage;
        TextView tvMakeModel;
        TextView tvYear;
        TextView tvPlateNumber;
        TextView tvPrice;
        View vStatusIndicator;
        ImageButton btnMore;

        CarViewHolder(View itemView) {
            super(itemView);
            ivCarImage = itemView.findViewById(R.id.ivCarImage);
            tvMakeModel = itemView.findViewById(R.id.tvMakeModel);
            tvYear = itemView.findViewById(R.id.tvYear);
            tvPlateNumber = itemView.findViewById(R.id.tvPlateNumber);
            tvPrice = itemView.findViewById(R.id.tvPrice);
            vStatusIndicator = itemView.findViewById(R.id.vStatusIndicator);
            btnMore = itemView.findViewById(R.id.btnMore);
        }
    }
} 
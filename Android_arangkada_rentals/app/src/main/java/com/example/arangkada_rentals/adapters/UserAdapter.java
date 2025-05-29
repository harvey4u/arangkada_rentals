package com.example.arangkada_rentals.adapters;

import android.content.Context;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ImageButton;
import android.widget.ImageView;
import android.widget.PopupMenu;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;

import com.example.arangkada_rentals.R;
import com.example.arangkada_rentals.models.User;

import java.util.List;

public class UserAdapter extends RecyclerView.Adapter<UserAdapter.UserViewHolder> {

    private List<User> userList;
    private Context context;
    private OnUserActionListener listener;

    public interface OnUserActionListener {
        void onEditUser(User user);
        void onDeleteUser(User user);
        void onViewDetails(User user);
    }

    public UserAdapter(Context context, List<User> userList, OnUserActionListener listener) {
        this.context = context;
        this.userList = userList;
        this.listener = listener;
    }

    @NonNull
    @Override
    public UserViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(context)
                .inflate(R.layout.item_user, parent, false);
        return new UserViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull UserViewHolder holder, int position) {
        User user = userList.get(position);
        
        holder.tvUsername.setText(user.getUsername());
        holder.tvEmail.setText(user.getEmail());
        holder.tvRole.setText(user.getRole().toUpperCase());

        // Set role badge color based on role
        int badgeColor;
        switch (user.getRole().toLowerCase()) {
            case "admin":
                badgeColor = 0xFF0D47A1; // Blue
                break;
            case "staff":
                badgeColor = 0xFF4CAF50; // Green
                break;
            case "driver":
                badgeColor = 0xFFF57C00; // Orange
                break;
            default:
                badgeColor = 0xFF9E9E9E; // Grey
                break;
        }
        holder.tvRole.getBackground().setTint(badgeColor);

        // Set click listeners
        holder.itemView.setOnClickListener(v -> listener.onViewDetails(user));
        
        holder.btnMore.setOnClickListener(v -> {
            PopupMenu popup = new PopupMenu(context, holder.btnMore);
            popup.inflate(R.menu.user_item_menu);
            
            popup.setOnMenuItemClickListener(item -> {
                int id = item.getItemId();
                if (id == R.id.menu_edit) {
                    listener.onEditUser(user);
                    return true;
                } else if (id == R.id.menu_delete) {
                    listener.onDeleteUser(user);
                    return true;
                }
                return false;
            });
            
            popup.show();
        });
    }

    @Override
    public int getItemCount() {
        return userList.size();
    }

    public void updateList(List<User> newList) {
        userList.clear();
        userList.addAll(newList);
        notifyDataSetChanged();
    }

    static class UserViewHolder extends RecyclerView.ViewHolder {
        ImageView ivAvatar;
        TextView tvUsername;
        TextView tvEmail;
        TextView tvRole;
        ImageButton btnMore;

        UserViewHolder(View itemView) {
            super(itemView);
            ivAvatar = itemView.findViewById(R.id.ivAvatar);
            tvUsername = itemView.findViewById(R.id.tvUsername);
            tvEmail = itemView.findViewById(R.id.tvEmail);
            tvRole = itemView.findViewById(R.id.tvRole);
            btnMore = itemView.findViewById(R.id.btnMore);
        }
    }
} 
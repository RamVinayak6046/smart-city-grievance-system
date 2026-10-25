import React from "react";
import PostUpdate from "./PostUpdate";

function AdminDashboard({ onLogout }) {
    return (
        <div className="min-h-screen bg-slate-100 p-8">
            <div className="flex justify-between mb-6">
                <h1 className="text-3xl font-black">
                    Admin Dashboard
                </h1>
                <button
                    onClick={onLogout}
                    className="px-4 py-2 bg-red-600 text-white rounded-xl font-black"
                >
                    Logout
                </button>
            </div>

            <PostUpdate role="superadmin" />
        </div>
    );
}

export default AdminDashboard;

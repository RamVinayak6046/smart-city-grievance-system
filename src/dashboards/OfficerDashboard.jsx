import React from "react";
import PostUpdate from "./PostUpdate";

function OfficerDashboard({ user, onLogout }) {
    const [jobs, setJobs] = React.useState([]);

    React.useEffect(() => {
        if (!window.currentDB) return;

        return window.currentDB
            .collection("issues")
            .where("ward", "==", user.ward)
            .onSnapshot(snap => {
                const data = snap.docs.map(d => ({
                    id: d.id,
                    ...d.data()
                }));
                setJobs(data);
            });
    }, [user.ward]);

    const updateStatus = async (id, status) => {
        await window.currentDB
            .collection("issues")
            .doc(id)
            .update({ status });
    };

    return (
        <div className="min-h-screen bg-slate-950 text-white p-8">
            <div className="flex justify-between mb-6">
                <div>
                    <h1 className="text-3xl font-black">
                        Officer Dashboard
                    </h1>
                    <p className="text-xs text-slate-400">
                        Ward: {user.ward}
                    </p>
                </div>
                <button
                    onClick={onLogout}
                    className="px-4 py-2 bg-red-600 rounded-xl font-black"
                >
                    Logout
                </button>
            </div>

            <PostUpdate role="officer" ward={user.ward} />

            <div className="grid gap-6">
                {jobs.map(job => (
                    <div
                        key={job.id}
                        className="bg-white/10 p-6 rounded-2xl"
                    >
                        <p className="font-bold">
                            {job.category}
                        </p>
                        <p className="text-xs text-slate-400">
                            Status: {job.status}
                        </p>

                        <div className="flex gap-3 mt-4">
                            <button
                                onClick={() =>
                                    updateStatus(
                                        job.id,
                                        "In Progress"
                                    )
                                }
                                className="flex-1 py-2 bg-blue-600 rounded-xl text-xs font-black"
                            >
                                In Progress
                            </button>

                            <button
                                onClick={() =>
                                    updateStatus(
                                        job.id,
                                        "Resolved"
                                    )
                                }
                                className="flex-1 py-2 bg-green-600 rounded-xl text-xs font-black"
                            >
                                Resolved
                            </button>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}

export default OfficerDashboard;

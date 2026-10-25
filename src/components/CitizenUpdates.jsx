import React from "react";

function CitizenUpdates({ ward, onBack }) {
    const [updates, setUpdates] = React.useState([]);

    React.useEffect(() => {
        if (!window.currentDB) return;

        const unsub = window.currentDB
            .collection("updates")
            .orderBy("createdAt", "desc")
            .onSnapshot(snapshot => {
                const data = snapshot.docs
                    .map(d => d.data())
                    .filter(
                        u => u.ward === "ALL" || u.ward === ward
                    );
                setUpdates(data);
            });

        return () => unsub();
    }, [ward]);

    return (
        <div className="min-h-screen bg-slate-50 p-8">
            <div className="flex justify-between items-center mb-6">
                <h2 className="text-2xl font-black">
                    Official Updates
                </h2>
                <button
                    onClick={onBack}
                    className="px-4 py-2 bg-slate-200 rounded-xl font-black"
                >
                    Back
                </button>
            </div>

            {updates.length === 0 && (
                <p className="text-slate-400">
                    No updates available
                </p>
            )}

            {updates.map((u, i) => (
                <div
                    key={i}
                    className="bg-white p-5 mb-4 rounded-2xl shadow"
                >
                    <div className="text-xs font-bold uppercase text-slate-400 mb-1">
                        {u.category}
                    </div>
                    <h3 className="text-lg font-black mb-2">
                        {u.title}
                    </h3>
                    <p className="text-slate-700">
                        {u.description}
                    </p>
                </div>
            ))}
        </div>
    );
}

export default CitizenUpdates;

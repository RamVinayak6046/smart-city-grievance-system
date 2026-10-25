function CitizenDashboard({ userId, onLogout }) {
    const { useState, useEffect, useRef } = React;

    const [mode, setMode] = useState("new");
    const [step, setStep] = useState("locate");
    const [ward, setWard] = useState("");
    const [image, setImage] = useState(null);
    const [category, setCategory] = useState("");
    const [tickets, setTickets] = useState([]);
    const [busy, setBusy] = useState(false);

    const imgRef = useRef();

    // ================== TRACK MODE ==================
    useEffect(() => {
        if (mode !== "track") return;
        if (!window.currentDB) return;

        const unsub = window.currentDB
            .collection("issues")
            .where("userId", "==", userId)
            .onSnapshot(snap => {
                const data = snap.docs.map(d => ({
                    id: d.id,
                    ...d.data()
                }));
                setTickets(data);
            });

        return () => unsub();
    }, [mode, userId]);

    // ================== GPS ==================
    const detectWard = () => {
        setBusy(true);
        navigator.geolocation.getCurrentPosition(
            () => {
                setWard("Ward 1");
                setStep("photo");
                setBusy(false);
            },
            () => {
                setWard("Ward 1");
                setStep("photo");
                setBusy(false);
            }
        );
    };

    // ================== AI ==================
    const runAI = e => {
        const file = e.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = () => {
            setImage(reader.result);
            setStep("verify");
            setCategory("General Maintenance");
        };
        reader.readAsDataURL(file);
    };

    // ================== SUBMIT ==================
    const submitIssue = async () => {
        setBusy(true);
        try {
            await window.currentDB.collection("issues").add({
                ward,
                category,
                image,
                status: "Pending",
                userId,
                timestamp: firebase.firestore.FieldValue.serverTimestamp()
            });
            setStep("success");
        } catch (e) {
            console.error(e);
            alert("Submit failed. Check Firestore rules.");
        }
        setBusy(false);
    };

    // ================== TRACK VIEW ==================
    if (mode === "track") {
        return (
            <div className="min-h-screen bg-slate-50 p-8">
                <div className="flex justify-between items-center mb-6">
                    <h2 className="text-2xl font-black">My Reports</h2>
                    <button
                        onClick={() => setMode("new")}
                        className="px-4 py-2 bg-blue-600 text-white rounded-xl font-black"
                    >
                        New Report
                    </button>
                </div>

                {tickets.length === 0 && (
                    <p className="text-slate-500 font-bold">
                        No issues reported yet.
                    </p>
                )}

                {tickets.map(t => (
                    <div
                        key={t.id}
                        className="bg-white p-6 rounded-2xl shadow mb-4"
                    >
                        <p className="font-black">{t.category}</p>
                        <p className="text-sm text-slate-500">{t.ward}</p>
                        <p className="text-sm font-bold text-blue-600 mt-2">
                            {t.status}
                        </p>
                    </div>
                ))}

                <button
                    onClick={onLogout}
                    className="mt-6 px-6 py-3 bg-red-600 text-white rounded-xl font-black"
                >
                    Logout
                </button>
            </div>
        );
    }

    // ================== NEW FLOW ==================
    return (
        <div className="min-h-screen bg-white p-8 flex flex-col items-center justify-center">
            <div className="absolute top-6 right-6 flex gap-3">
                <button
                    onClick={() => setMode("track")}
                    className="px-4 py-2 bg-slate-100 rounded-xl text-xs font-black uppercase"
                >
                    My Reports
                </button>

                <button
                    onClick={onLogout}
                    className="px-4 py-2 bg-red-600 text-white rounded-xl text-xs font-black uppercase"
                >
                    Logout
                </button>
            </div>

            {step === "locate" && (
                <button
                    onClick={detectWard}
                    className="px-10 py-5 bg-blue-600 text-white rounded-2xl font-black"
                >
                    {busy ? "Detecting..." : "Detect Location"}
                </button>
            )}

            {step === "photo" && (
                <label className="cursor-pointer text-center">
                    <input
                        type="file"
                        className="hidden"
                        accept="image/*"
                        onChange={runAI}
                    />
                    <div className="px-10 py-5 bg-slate-100 rounded-2xl font-black">
                        Upload Issue Photo ({ward})
                    </div>
                </label>
            )}

            {step === "verify" && (
                <div className="text-center max-w-md">
                    <img src={image} className="rounded-2xl mb-4" alt="" />
                    <p className="font-black mb-4">
                        Category: {category}
                    </p>

                    <button
                        onClick={submitIssue}
                        disabled={busy}
                        className="px-10 py-4 bg-green-600 text-white rounded-2xl font-black"
                    >
                        {busy ? "Submitting..." : "Confirm"}
                    </button>
                </div>
            )}

            {step === "success" && (
                <div className="text-center">
                    <h2 className="text-3xl font-black mb-6">
                        Report Submitted
                    </h2>

                    <button
                        onClick={() => {
                            setStep("locate");
                            setImage(null);
                            setWard("");
                        }}
                        className="px-8 py-4 bg-slate-200 rounded-2xl font-black"
                    >
                        New Report
                    </button>
                </div>
            )}
        </div>
    );
}
export default CitizenDashboard;

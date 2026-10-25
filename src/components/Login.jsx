function Login({ onLogin }) {
    const { useState } = React;

    const OFFICER_PIN = "1234";
    const ADMIN_PIN = "9999";

    const [role, setRole] = useState("citizen");
    const [ward, setWard] = useState("");
    const [pin, setPin] = useState("");
    const [phone, setPhone] = useState("");
    const [loading, setLoading] = useState(false);

    // ================= GOOGLE LOGIN =================
    const loginWithGoogle = async () => {
        // 🔐 VALIDATION BEFORE AUTH
        if (role === "officer") {
            if (!ward) {
                alert("Please select a ward");
                return;
            }
            if (!pin) {
                alert("Please enter Officer PIN");
                return;
            }
            if (pin !== OFFICER_PIN) {
                alert("Invalid Officer PIN");
                return;
            }
        }

        if (role === "superadmin") {
            if (!pin) {
                alert("Please enter Admin PIN");
                return;
            }
            if (pin !== ADMIN_PIN) {
                alert("Invalid Admin PIN");
                return;
            }
        }

        try {
            setLoading(true);
            const provider = new firebase.auth.GoogleAuthProvider();
            const result = await authInstance.signInWithPopup(provider);

            onLogin({
                role,
                uid: result.user.uid,
                email: result.user.email,
                ward
            });
        } catch (e) {
            alert(e.message);
        } finally {
            setLoading(false);
        }
    };

    // ================= PHONE OTP LOGIN =================
    const loginWithPhone = async () => {
        // 🔐 VALIDATION BEFORE AUTH
        if (role === "officer") {
            if (!ward) {
                alert("Please select a ward");
                return;
            }
            if (!pin) {
                alert("Please enter Officer PIN");
                return;
            }
            if (pin !== OFFICER_PIN) {
                alert("Invalid Officer PIN");
                return;
            }
        }

        if (role === "superadmin") {
            if (!pin) {
                alert("Please enter Admin PIN");
                return;
            }
            if (pin !== ADMIN_PIN) {
                alert("Invalid Admin PIN");
                return;
            }
        }

        try {
            setLoading(true);

            window.recaptchaVerifier =
                new firebase.auth.RecaptchaVerifier(
                    "recaptcha-container",
                    { size: "invisible" }
                );

            const confirmation =
                await authInstance.signInWithPhoneNumber(
                    phone,
                    window.recaptchaVerifier
                );

            const code = prompt("Enter OTP sent to your phone");
            const result = await confirmation.confirm(code);

            onLogin({
                role,
                uid: result.user.uid,
                ward
            });
        } catch (e) {
            alert(e.message);
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="min-h-screen flex items-center justify-center bg-slate-950 text-white">
            <div className="bg-white/5 p-10 rounded-3xl w-full max-w-sm">

                <h1 className="text-3xl font-black mb-6 text-center">
                    CivicFix Login
                </h1>

                {/* ROLE SELECTION */}
                <div className="flex gap-2 mb-4">
                    {["citizen", "officer", "superadmin"].map(r => (
                        <button
                            key={r}
                            onClick={() => {
                                setRole(r);
                                setWard("");
                                setPin("");
                            }}
                            className={`flex-1 py-2 rounded-xl text-xs font-black uppercase ${
                                role === r
                                    ? "bg-blue-600"
                                    : "bg-white/10"
                            }`}
                        >
                            {r}
                        </button>
                    ))}
                </div>

                {/* WARD SELECTION */}
                {role === "officer" && (
                    <select
                        value={ward}
                        onChange={e => setWard(e.target.value)}
                        className="w-full p-3 mb-4 rounded-xl bg-slate-900 text-white"
                    >
                        <option value="">Select Ward</option>
                        <option value="Ward 1">Ward 1</option>
                        <option value="Ward 2">Ward 2</option>
                        <option value="Ward 3">Ward 3</option>
                    </select>
                )}

                {/* PIN INPUT */}
                {(role === "officer" || role === "superadmin") && (
                    <input
                        type="password"
                        placeholder="Enter PIN"
                        value={pin}
                        onChange={e => setPin(e.target.value)}
                        className="w-full p-3 mb-4 rounded-xl bg-slate-900 text-white"
                    />
                )}

                <button
                    onClick={loginWithGoogle}
                    disabled={loading}
                    className="w-full py-3 mb-4 bg-blue-600 rounded-xl font-black"
                >
                    Login with Google
                </button>

                <div className="text-center text-xs text-slate-400 mb-3">
                    OR
                </div>

                <input
                    type="tel"
                    placeholder="+91XXXXXXXXXX"
                    value={phone}
                    onChange={e => setPhone(e.target.value)}
                    className="w-full p-3 mb-4 rounded-xl bg-slate-900"
                />

                <button
                    onClick={loginWithPhone}
                    disabled={loading}
                    className="w-full py-3 bg-green-600 rounded-xl font-black"
                >
                    Login with Phone OTP
                </button>

                <div id="recaptcha-container"></div>
            </div>
        </div>
    );
}

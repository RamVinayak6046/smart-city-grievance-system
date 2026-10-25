const { useState, useEffect } = React;

function App() {
    const [user, setUser] = useState(null);
    const [ready, setReady] = useState(false);
    const [isLocalMode, setIsLocalMode] = useState(false);

    useEffect(() => {
        async function init() {
            try {
                if (!firebase.apps.length) {
                    firebase.initializeApp(firebaseConfig);
                }
                window.currentDB = firebase.firestore();
                window.authInstance = firebase.auth();

                try {
                    await currentDB.collection("issues").limit(1).get();
                } catch {
                    window.currentDB = new MockDB();
                    setIsLocalMode(true);
                }

                window.aiModelInstance = await mobilenet.load();
            } catch (e) {
                console.warn("Fallback to Local Mode");
                window.currentDB = new MockDB();
                setIsLocalMode(true);
            }
            setReady(true);
        }
        init();
    }, []);

    if (!ready) {
        return (
            <div className="min-h-screen flex items-center justify-center bg-slate-950 text-white">
                <div className="spinner"></div>
            </div>
        );
    }

    if (!user) {
        return <Login onLogin={setUser} isLocalMode={isLocalMode} />;
    }

    if (user.role === "citizen") {
        return (
            <CitizenDashboard
                userId={user.uid}
                onLogout={() => setUser(null)}
            />
        );
    }

    if (user.role === "officer") {
    return (
        <OfficerDashboard
            user={user}
            onLogout={() => setUser(null)}
        />
    );
}

if (user.role === "superadmin") {
    return <AdminDashboard onLogout={() => setUser(null)} />;
}

return (
    <div className="min-h-screen flex items-center justify-center">
        <p className="text-red-600 font-black">
            Unknown role: {user.role}
        </p>
    </div>
);


    return <AdminDashboard onLogout={() => setUser(null)} />;
}

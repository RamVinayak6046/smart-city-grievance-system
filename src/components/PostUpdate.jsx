import React from "react";

function PostUpdate({ role, ward }) {
    const [title, setTitle] = React.useState("");
    const [description, setDescription] = React.useState("");
    const [category, setCategory] = React.useState("Service");
    const [loading, setLoading] = React.useState(false);

    const submitUpdate = async () => {
        if (!title || !description) {
            alert("All fields required");
            return;
        }

        setLoading(true);
        try {
            await window.currentDB.collection("updates").add({
                title,
                description,
                category,
                ward: role === "superadmin" ? "ALL" : ward,
                createdBy: role,
                createdAt: firebase.firestore.FieldValue.serverTimestamp()
            });

            setTitle("");
            setDescription("");
            alert("Update posted");
        } catch (e) {
            alert("Failed to post update");
        }
        setLoading(false);
    };

    return (
        <div className="bg-white p-6 rounded-2xl shadow mb-8">
            <h2 className="text-xl font-black mb-4">
                Post Official Update
            </h2>

            <input
                className="w-full p-3 mb-3 border rounded-xl"
                placeholder="Title"
                value={title}
                onChange={e => setTitle(e.target.value)}
            />

            <textarea
                className="w-full p-3 mb-3 border rounded-xl"
                placeholder="Description"
                rows="4"
                value={description}
                onChange={e => setDescription(e.target.value)}
            />

            <select
                className="w-full p-3 mb-4 border rounded-xl"
                value={category}
                onChange={e => setCategory(e.target.value)}
            >
                <option value="Service">Service</option>
                <option value="Governance">Governance</option>
            </select>

            <button
                onClick={submitUpdate}
                disabled={loading}
                className="px-6 py-3 bg-blue-600 text-white rounded-xl font-black"
            >
                {loading ? "Posting..." : "Post Update"}
            </button>
        </div>
    );
}

export default PostUpdate;

class MockDB {
    constructor() {
        this.store =
            JSON.parse(localStorage.getItem("civicfix_mock_db")) || [];
        this.listeners = [];
        console.warn("⚠️ Using Local Mock Database");
    }

    save() {
        localStorage.setItem(
            "civicfix_mock_db",
            JSON.stringify(this.store)
        );
        this.listeners.forEach(l => l());
    }

   collection() {
    const self = this;

    return {
        add(data) {
            const doc = {
                ...data,
                id: "doc_" + Math.random().toString(36).slice(2),
                timestamp: { seconds: Date.now() / 1000 }
            };
            self.store.unshift(doc);
            self.save();
            return Promise.resolve({ id: doc.id });
        },

        doc(id) {
            return {
                update(data) {
                    const index = self.store.findIndex(
                        item => item.id === id
                    );

                    if (index !== -1) {
                        self.store[index] = {
                            ...self.store[index],
                            ...data
                        };
                        self.save();
                    }

                    return Promise.resolve();
                }
            };
        },

        where(field, op, value) {
            return {
                onSnapshot(callback) {
                    const notify = () => {
                        const filtered = self.store.filter(
                            item => item[field] === value
                        );
                        callback({
                            docs: filtered.map(d => ({
                                id: d.id,
                                data: () => d
                            }))
                        });
                    };

                    const listener = { notify };
                    self.listeners.push(listener);
                    notify();

                    return () => {
                        self.listeners = self.listeners.filter(
                            l => l !== listener
                        );
                    };
                }
            };
        },

        onSnapshot(callback) {
            const notify = () => {
                callback({
                    docs: self.store.map(d => ({
                        id: d.id,
                        data: () => d
                    }))
                });
            };

            const listener = { notify };
            self.listeners.push(listener);
            notify();

            return () => {
                self.listeners = self.listeners.filter(
                    l => l !== listener
                );
            };
        }
    };
}}

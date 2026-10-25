function Icon({ name, className = "w-5 h-5" }) {
    React.useEffect(() => {
        if (window.lucide) {
            lucide.createIcons();
        }
    }, [name]);

    return <i data-lucide={name} className={className}></i>;
}

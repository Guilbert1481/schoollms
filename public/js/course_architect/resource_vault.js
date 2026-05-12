// Course Architect — Resource Vault
window.caResourceVault = function () {
    return {
        assets: [],
        filter: '',
        init() {
            if (window.lucide?.createIcons) window.lucide.createIcons();
        },
    };
};

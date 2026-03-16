window.TestTypeRegistry = {};

window.registerTestType = function (key, config) {
    window.TestTypeRegistry[key] = config;
};

document.addEventListener('change', function (e) {

    if (!e.target.classList.contains('question-type-select')) return;

    const card = e.target.closest('.question-card');
    if (!card) return;

    const type = e.target.value;
    const uid = card.dataset.uid || Date.now();
    card.dataset.uid = uid;

    // 🔹 Ensure .options container exists
    let optionsContainer = card.querySelector('.options');

    if (!optionsContainer) {
        optionsContainer = document.createElement('div');
        optionsContainer.className = 'options';
        card.appendChild(optionsContainer);
    }

    if (!window.TestTypeRegistry[type]) {
        optionsContainer.innerHTML =
            `<p style="color:red">Module not registered: ${type}</p>`;
        return;
    }

    const module = window.TestTypeRegistry[type];
    optionsContainer.innerHTML = module.render(uid);
});

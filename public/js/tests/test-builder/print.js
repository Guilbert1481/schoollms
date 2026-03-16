document.addEventListener("DOMContentLoaded", function () {

    const printBtn = document.getElementById("printTestBtn");

    if (!printBtn) return;

    printBtn.addEventListener("click", function () {

        const testId = document.getElementById("testId")?.value;

        if (!testId) {
            alert("Test ID not found.");
            return;
        }

        window.open(`/teacher/tests/${testId}/print`, "_blank");

    });

});
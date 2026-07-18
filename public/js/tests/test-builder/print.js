document.addEventListener("DOMContentLoaded", function () {

    // "Preview Test" — the single entry point to the print hub, which hosts the
    // Questionnaire / Answer Sheet / Answer Key tabs (Questionnaire is the default
    // tab) plus Reshuffle, Record Answers and Scan (Camera). Those used to be four
    // separate buttons on the builder's action bar.
    //
    // Deliberately NOT id="previewTestBtn": the dormant previewTest.js binds that id
    // and opens a /preview route that was never built, so sharing it would risk a
    // double handler the day anyone includes that script.
    const previewBtn = document.getElementById("printHubBtn");

    if (!previewBtn) return;

    previewBtn.addEventListener("click", function () {

        const testId = document.getElementById("testId")?.value;

        if (!testId) {
            alert("Test ID not found.");
            return;
        }

        window.open(`/teacher/tests/${testId}/print-hub`, "_blank");

    });

});

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

    // OMR answer sheets — opens the section picker, then one sheet per student.
    const answerBtn = document.getElementById("answerSheetsBtn");
    if (answerBtn) {
        answerBtn.addEventListener("click", function () {
            const testId = document.getElementById("testId")?.value;
            if (!testId) {
                alert("Test ID not found.");
                return;
            }
            window.open(`/teacher/tests/${testId}/answer-sheets`, "_blank");
        });
    }

    // Record OMR answers — manual entry / grading page.
    const recordBtn = document.getElementById("recordOmrBtn");
    if (recordBtn) {
        recordBtn.addEventListener("click", function () {
            const testId = document.getElementById("testId")?.value;
            if (!testId) {
                alert("Test ID not found.");
                return;
            }
            window.open(`/teacher/tests/${testId}/omr/record`, "_blank");
        });
    }

});
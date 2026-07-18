{{-- ================= PROGRAM ASSIGNMENT ================= --}}
<div class="card">

    <h3>Program Assignment</h3>
    <p style="color:#6b7280;font-size:13px;margin-bottom:16px;">
        Select which programs include this subject.
    </p>

    <form method="POST" action="#">
        @csrf

        <div class="overflow-x-auto">
        <table class="table" style="width:100%;border-collapse:collapse;">
            <thead>
                <tr>
                    <th style="width:60px;text-align:left;">Use</th>
                    <th style="text-align:left;">Program</th>
                </tr>
            </thead>
            <tbody>

                {{-- Example static rows (replace with loop later) --}}
                <tr>
                    <td>
                        <input type="checkbox" name="programs[]" value="1">
                    </td>
                    <td>BS Accountancy</td>
                </tr>

                <tr>
                    <td>
                        <input type="checkbox" name="programs[]" value="2">
                    </td>
                    <td>BS Management</td>
                </tr>

                <tr>
                    <td>
                        <input type="checkbox" name="programs[]" value="3">
                    </td>
                    <td>Senior High – ABM</td>
                </tr>

                <tr>
                    <td>
                        <input type="checkbox" name="programs[]" value="4">
                    </td>
                    <td>Senior High – STEM</td>
                </tr>

            </tbody>
        </table>
        </div>

        <div style="margin-top:20px;">
            <button type="submit" class="btn-primary">
                Save Program Assignment
            </button>
        </div>

    </form>

</div>

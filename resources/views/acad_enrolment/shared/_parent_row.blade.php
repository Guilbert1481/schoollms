<div class="family-card" data-parent-row="{{ $i }}">
    <h4>
        <span>Parent / Guardian #{{ $i + 1 }}</span>
        <button type="button" class="remove-btn">Remove</button>
    </h4>
    <div class="family-grid">
        <div class="input-group">
            <label>Relationship</label>
            <select name="parents[{{ $i }}][relationship]">
                @php $rel = old("parents.$i.relationship", $p->relationship ?? ''); @endphp
                <option value="">— Select —</option>
                @foreach (['mother','father','grandparent','sibling','aunt','uncle','guardian','other'] as $r)
                    <option value="{{ $r }}" @selected($rel === $r)>{{ ucfirst($r) }}</option>
                @endforeach
            </select>
        </div>
        <div class="input-group">
            <label>First Name</label>
            <input type="text" name="parents[{{ $i }}][first_name]" value="{{ old("parents.$i.first_name", $p->first_name ?? '') }}">
        </div>
        <div class="input-group">
            <label>Last Name</label>
            <input type="text" name="parents[{{ $i }}][last_name]"  value="{{ old("parents.$i.last_name",  $p->last_name  ?? '') }}">
        </div>
        <div class="input-group">
            <label>Occupation</label>
            <input type="text" name="parents[{{ $i }}][occupation]" value="{{ old("parents.$i.occupation", $p->occupation ?? '') }}">
        </div>
        <div class="input-group">
            <label>Employer</label>
            <input type="text" name="parents[{{ $i }}][employer]"   value="{{ old("parents.$i.employer",   $p->employer   ?? '') }}">
        </div>
        <div class="input-group">
            <label>Mobile Number</label>
            <input type="text" name="parents[{{ $i }}][mobile_number]" value="{{ old("parents.$i.mobile_number", $p->mobile_number ?? '') }}">
        </div>
        <div class="input-group">
            <label>Email</label>
            <input type="email" name="parents[{{ $i }}][email]" value="{{ old("parents.$i.email", $p->email ?? '') }}">
        </div>
        <div class="input-group" style="grid-column: span 2; align-items: flex-start; padding-top: 26px;">
            <label style="display:flex;align-items:center;gap:8px;font-size:12px;text-transform:none;color:#1e293b;">
                <input type="checkbox" name="parents[{{ $i }}][is_primary]" value="1" @checked(old("parents.$i.is_primary", $p->is_primary ?? ($i === 0)))>
                Primary contact
            </label>
        </div>
    </div>
</div>

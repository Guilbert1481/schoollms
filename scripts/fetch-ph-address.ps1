# Fetch PSGC datasets and convert into the format used by AddressController.
# Output files land in resources/data/ph-address/{regions,provinces,cities,barangays}.json
$ErrorActionPreference = 'Stop'
$ProgressPreference    = 'SilentlyContinue'

$out = "resources\data\ph-address"
New-Item -ItemType Directory -Path $out -Force | Out-Null

function Get-Json($url) {
    Write-Host "GET $url"
    return (Invoke-WebRequest -Uri $url -UseBasicParsing).Content | ConvertFrom-Json
}

# PHP's json_decode chokes on a UTF-8 BOM, so write with a BOM-less encoder.
function Write-JsonNoBom($obj, $path) {
    $json = $obj | ConvertTo-Json -Depth 3
    [System.IO.File]::WriteAllText($path, $json, (New-Object System.Text.UTF8Encoding $false))
}

# ---- regions ---------------------------------------------------------------
$regions = Get-Json 'https://psgc.gitlab.io/api/regions/'
$regionsOut = $regions | ForEach-Object {
    [pscustomobject]@{ value = $_.code; label = $_.name }
}
Write-JsonNoBom $regionsOut (Join-Path $out 'regions.json')
Write-Host ("regions.json  -> {0} rows" -f $regionsOut.Count)

# ---- provinces -------------------------------------------------------------
$provinces = Get-Json 'https://psgc.gitlab.io/api/provinces/'
$provincesOut = $provinces | ForEach-Object {
    [pscustomobject]@{ value = $_.code; label = $_.name; region = $_.regionCode }
}
Write-JsonNoBom $provincesOut (Join-Path $out 'provinces.json')
Write-Host ("provinces.json -> {0} rows" -f $provincesOut.Count)

# ---- cities + municipalities ----------------------------------------------
$cities = Get-Json 'https://psgc.gitlab.io/api/cities-municipalities/'
$citiesOut = $cities | ForEach-Object {
    # NCR cities have no province code in PSGC; fall back to district code so they still group.
    $prov = if ($_.provinceCode) { $_.provinceCode } elseif ($_.districtCode) { $_.districtCode } else { $_.regionCode }
    [pscustomobject]@{
        value    = $_.code
        label    = $_.name
        province = $prov
        region   = $_.regionCode
    }
}
Write-JsonNoBom $citiesOut (Join-Path $out 'cities.json')
Write-Host ("cities.json    -> {0} rows" -f $citiesOut.Count)

# ---- barangays (large: ~42k) -----------------------------------------------
$barangays = Get-Json 'https://psgc.gitlab.io/api/barangays/'
$brgyOut = $barangays | ForEach-Object {
    # Barangays attach to a city/municipality OR (for NCR district barangays) directly to a sub-municipality.
    $cityCode = if ($_.cityCode) { $_.cityCode }
                elseif ($_.municipalityCode) { $_.municipalityCode }
                elseif ($_.subMunicipalityCode) { $_.subMunicipalityCode }
                else { $_.districtCode }
    [pscustomobject]@{ value = $_.code; label = $_.name; city = $cityCode }
}
Write-JsonNoBom $brgyOut (Join-Path $out 'barangays.json')
Write-Host ("barangays.json -> {0} rows" -f $brgyOut.Count)

Write-Host "Done."

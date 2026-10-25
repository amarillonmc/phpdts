param(
    [Parameter(Mandatory=$true)][string]$Source,
    [Parameter(Mandatory=$true)][string]$Destination,
    [Parameter(Mandatory=$true)][string]$Archive,
    [int]$Size = 451
)
$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.Drawing
foreach ($directory in @((Split-Path -Parent $Destination), (Split-Path -Parent $Archive))) {
    if (!(Test-Path -LiteralPath $directory)) { New-Item -ItemType Directory -Path $directory -Force | Out-Null }
}
if (Test-Path -LiteralPath $Destination) { throw "Destination already exists: $Destination" }
Copy-Item -LiteralPath $Source -Destination $Archive
$original = [System.Drawing.Image]::FromFile($Source)
$bitmap = New-Object System.Drawing.Bitmap($Size, $Size)
$graphics = [System.Drawing.Graphics]::FromImage($bitmap)
try {
    $graphics.Clear([System.Drawing.Color]::Transparent)
    $graphics.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
    $graphics.CompositingQuality = [System.Drawing.Drawing2D.CompositingQuality]::HighQuality
    $graphics.PixelOffsetMode = [System.Drawing.Drawing2D.PixelOffsetMode]::HighQuality
    $scale = [Math]::Min($Size / $original.Width, $Size / $original.Height)
    $width = [int][Math]::Round($original.Width * $scale)
    $height = [int][Math]::Round($original.Height * $scale)
    $graphics.DrawImage($original, [int](($Size-$width)/2), [int](($Size-$height)/2), $width, $height)
    $bitmap.Save($Destination, [System.Drawing.Imaging.ImageFormat]::Png)
    Write-Output "$Destination : ${Size}x${Size}; original $($original.Width)x$($original.Height)"
} finally {
    $graphics.Dispose(); $bitmap.Dispose(); $original.Dispose()
}

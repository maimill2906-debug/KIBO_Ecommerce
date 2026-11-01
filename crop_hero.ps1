Add-Type -AssemblyName System.Drawing

$srcPath = "C:\Users\VELORA\.gemini\antigravity\brain\0431b922-e29d-4418-846e-6dc1619da9ea\.user_uploaded\media_1793363506084_e5875ad3.png"
$outPath = "C:\xampp\htdocs\kibo\assets\images\hero_banner_figma.png"

$src = [System.Drawing.Image]::FromFile($srcPath)
$cropRect = New-Object System.Drawing.Rectangle(0, 140, $src.Width, (608 - 140))
$dest = New-Object System.Drawing.Bitmap($cropRect.Width, $cropRect.Height)
$g = [System.Drawing.Graphics]::FromImage($dest)
$g.DrawImage($src, 0, 0, $cropRect, [System.Drawing.GraphicsUnit]::Pixel)

$copy = New-Object System.Drawing.Bitmap($dest)
$g.Dispose()
$dest.Dispose()
$src.Dispose()
$copy.Save($outPath, [System.Drawing.Imaging.ImageFormat]::Jpeg)
$copy.Dispose()

Write-Output "Successfully cropped hero_banner_figma.png"

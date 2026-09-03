$server = "ftp://ftpupload.net/htdocs/"
$user = "ezyro_42742254"
$pass = "c45483b8a78869e"
$localFolder = "c:\xampp\htdocs\little-steps"

function Upload-FtpDirectory ($localPath, $remotePath) {
    $items = Get-ChildItem -Path $localPath
    foreach ($item in $items) {
        # Skip git and sql dump
        if ($item.Name -eq ".git" -or $item.Name -eq "littlesteps_production.sql" -or $item.Name -match "\.ps1$") { continue }
        
        $itemRemotePath = $remotePath + $item.Name
        if ($item.PSIsContainer) {
            # Try to create directory
            try {
                $request = [System.Net.FtpWebRequest]::Create($itemRemotePath)
                $request.Method = [System.Net.WebRequestMethods+Ftp]::MakeDirectory
                $request.Credentials = New-Object System.Net.NetworkCredential($user, $pass)
                $response = $request.GetResponse()
                $response.Close()
            } catch {
                # Directory might already exist, ignore error
            }
            Upload-FtpDirectory $item.FullName ($itemRemotePath + "/")
        } else {
            # Upload file
            Write-Host "Uploading $($item.FullName)..."
            $request = [System.Net.FtpWebRequest]::Create($itemRemotePath)
            $request.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
            $request.Credentials = New-Object System.Net.NetworkCredential($user, $pass)
            $request.UseBinary = $true
            
            $content = [System.IO.File]::ReadAllBytes($item.FullName)
            $request.ContentLength = $content.Length
            
            $stream = $request.GetRequestStream()
            $stream.Write($content, 0, $content.Length)
            $stream.Close()
            $response = $request.GetResponse()
            $response.Close()
        }
    }
}
Write-Host "Starting FTP Deployment..."
Upload-FtpDirectory $localFolder $server
Write-Host "Deployment Completed Successfully!"

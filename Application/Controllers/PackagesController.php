<?php

const BASE_WORK_FOLDER = '/var/www/html/workfolder';
const BASE_PACKAGE_FOLDER = 'package';
const ASSETS_FOLDER = 'Assets/';
const PACKAGE_ENDING = '.unitypackage';
const META_ENDING = '.meta';


class PackagesController extends Controller
{
    public function Get($user = "", $package = "", $commit = "")
    {
        if($package == "" || $package == ""){
            return $this->HttpNotFound();
        }

        $resultFile = $this->GetPackage($user, $package, $commit);
        if($resultFile === false){
            return $this->Text("Not a valid unity package. No Asset folder found.");
        }
        return $this->Redirect('/file/' . $resultFile);
    }

    public function Package($user = "", $package = "")
    {
        if($package == "" || $package == ""){
            return $this->HttpNotFound();
        }

        $this->Title = $user . '/' . $package;
        $repo = $this->DownloadPackage($user, $package);
        $this->ResetRepository($repo, "");
        $this->Set('Logs', $this->GetLogs($repo, $user, $package));
        return $this->View();
    }

    public function File()
    {
        $path = implode('/', $this->Parameters);
        $response = new HttpResult();
        $response->Content = file_get_contents($path, FILE_USE_INCLUDE_PATH);
        $response->MimeType = 'application/x-gzip';

        return $response;
    }

    private function GetLastCommit($repo)
    {
        $log = $repo->logs();
        return $log[0]->hash;
    }

    private function GetLogs($repo, $user, $package)
    {
        $logs = $repo->logs();
        foreach($logs as $log){
            $log->user = $user;
            $log->package = $package;
        }

        return $logs;
    }

    private function CreatePackageUrl($user, $package)
    {
        return 'https://github.com/' . $user . '/' . $package . '.git';
    }

    private function CreateWorkFolder($user, $package)
    {
        return BASE_WORK_FOLDER . '/' . $user . '/' . $package;
    }

    private function GetTarName($packageName, $commit)
    {
        return BASE_PACKAGE_FOLDER . '/' . $packageName . $commit . PACKAGE_ENDING;
    }

    private function GetAssetFolder($workFolder)
    {
        $assetFolder = $workFolder . '/' . 'Assets';
        if(!is_dir($assetFolder)){
            return false;
        }

        return $assetFolder;
    }

    private function DownloadPackage($user, $package)
    {
        $packageUrl = $this->CreatePackageUrl($user, $package);
        $workFolder = $this->CreateWorkFolder($user, $package);

        if(is_dir($workFolder)){
            $repo = new \Cz\Git\GitRepository($workFolder);
            $repo->pull('origin');
        }else {
            \Cz\Git\GitRepository::cloneRepository($packageUrl, $workFolder);
            $repo = new \Cz\Git\GitRepository($workFolder);
        }

        return $repo;
    }

    private function ResetRepository($repo, $commit)
    {
        $repo->reset($commit);
    }

    private function GetPackage($user, $package, $commit)
    {
        $workFolder = $this->CreateWorkFolder($user, $package);

        $repo = $this->DownloadPackage($user, $package);

        if($commit == ""){
            $commit = $this->GetLastCommit($repo);
            $this->ResetRepository($repo, $commit);
        }

        $assetFolder = $this->GetAssetFolder($workFolder);
        if($assetFolder === false){
            return false;
        }

        $packageName = $this->GetTarName($package, $commit);
        $this->PackFolder($assetFolder . '/', $packageName);

        return $packageName;
    }

    private function PackFolder(String $folder, String $tarFileName)
    {
        // If the file already exists, there is no need to repack it
        if(file_exists($tarFileName)){
            return;
        }

        $files = $this->GetAllFiles($folder, '');

        $tarFile = new PharData($tarFileName);

        foreach($files as $name){
            $this->PackAsset($tarFile, $folder, $name);
        }
    }

    private function PackAsset(PharData $tarFile, String $folder, String $fileName)
    {
        $content = file_get_contents($folder . $fileName);
        $metaContent = file_get_contents($folder . $fileName . META_ENDING);
        $metaGuid = $this->GetAssetHash($metaContent);

        $tarFile->addFromString($metaGuid . '/asset', $content);
        $tarFile->addFromString($metaGuid . '/asset.meta', $metaContent);
        $tarFile->addFromString($metaGuid . '/pathname', ASSETS_FOLDER . $fileName);
    }

    private function GetAssetHash($metaContent)
    {
        foreach(explode("\n", $metaContent) as $line){
            if(startsWith($line, 'guid')){
                return trim(explode(":", $line)[1]);
            }
        }

        return "";
    }

    private function GetAllFiles($folder, $root)
    {
        if(strpos(strrev($folder), strrev('.')) === 0){
            return array();
        }

        $result = array();

        foreach (scandir($folder) as $file) {
            if(!($file == '.' || $file == '..' || endsWith($file,'.meta') || startsWith($file, '.'))) {
                $filePath = $folder . '/' . $file;
                if($root == ''){
                    $localFilePath = $file;
                }else {
                    $localFilePath = $root . '/' . $file;
                }
                if (is_dir($filePath)) {
                    foreach ($this->GetAllFiles($filePath, $localFilePath) as $dirFile) {
                        $result[] = $dirFile;
                    }
                } else {
                    $result[] = $localFilePath;
                }
            }
        }

        return $result;
    }

    public function GetCommitLink($logEntry)
    {
        return '/get/' . $logEntry->user . '/' . $logEntry->package . '/' . $logEntry->hash;
    }

    public function GetCommitFileName($logEntry)
    {
        return $logEntry->package;
    }
}
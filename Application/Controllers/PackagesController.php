<?php

const BASE_WORK_FOLDER = '/var/www/html/workfolder';
const BASE_PACKAGE_FOLDER = './package';
const ZIP_ENDING = '.zip';


class PackagesController extends Controller
{
    public function Get($user, $package)
    {
        $resultFile = $this->GetPackage($user, $package);

        return $this->Text(file_get_contents($resultFile));
        //return $this->View();
    }

    private function CreatePackageUrl($user, $package)
    {
        return 'https://github.com/' . $user . '/' . $package . '.git';
    }

    private function CreateWorkFolder($user, $package)
    {
        return BASE_WORK_FOLDER . '/' . $user . '/' . $package;
    }

    private function GetZipName($packageName)
    {
        return BASE_PACKAGE_FOLDER . '/' . $packageName . ZIP_ENDING;
    }

    private function GetAssetFolder($workFolder)
    {
        $assetFolder = $workFolder . '/' . 'Assets';
        if(!is_dir($assetFolder)){
            return false;
        }

        return $assetFolder;
    }

    private function GetPackage($user, $package)
    {

        $packageUrl = $this->CreatePackageUrl($user, $package);
        $workFolder = $this->CreateWorkFolder($user, $package);

        if(is_dir($workFolder)){
            $repo = new \Cz\Git\GitRepository($workFolder);
            $repo->pull('origin');
        }else {
            \Cz\Git\GitRepository::cloneRepository($packageUrl, $workFolder);
        }

        $assetFolder = $this->GetAssetFolder($workFolder);
        $zipName = $this->GetZipName($package);
        $this->PackFolder($assetFolder . '/', $zipName);

        return $zipName;
        //return $this->GetFile($zipName);
    }

    private function PackFolder($folder, $zipName)
    {
        $files = $this->GetAllFiles($folder, '');

        $zipFile = new ZipArchive();
        $zipFile->open($zipName, ZipArchive::CREATE);

        foreach($files as $name){
            $zipFile->addFile($name);
        }

        $zipFile->close();
    }

    private function GetAllFiles($folder, $root)
    {
        if(strpos(strrev($folder), strrev('.')) === 0){
            return array();
        }

        $result = array();

        foreach (scandir($folder) as $file) {
            if(!($file == '.' || $file == '..')) {
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
}
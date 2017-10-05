<?php

const BASE_WORK_FOLDER = '/var/www/html/workfolder';
const BASE_PACKAGE_FOLDER = 'package';
const PACKAGE_ENDING = '.unitypackage';
const META_ENDING = '.meta';


class PackagesController extends Controller
{
    public function Get($user = "", $package = "", $commit = '-SNAPSHOT')
    {
        if($package == "" || $package == ""){
            return $this->HttpNotFound();
        }

        $resultFile = $this->GetPackage($user, $package);
        return $this->Redirect('/file/' . $resultFile);
    }

    public function File()
    {
        $path = implode('/', $this->Parameters);
        $response = new HttpResult();
        $response->Content = file_get_contents($path, FILE_USE_INCLUDE_PATH);
        $response->MimeType = 'application/x-gzip';

        return $response;
    }

    private function CreatePackageUrl($user, $package)
    {
        return 'https://github.com/' . $user . '/' . $package . '.git';
    }

    private function CreateWorkFolder($user, $package)
    {
        return BASE_WORK_FOLDER . '/' . $user . '/' . $package;
    }

    private function GetTarName($packageName)
    {
        return BASE_PACKAGE_FOLDER . '/' . $packageName . PACKAGE_ENDING;
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
        $packageName = $this->GetTarName($package);
        $this->PackFolder($assetFolder . '/', $packageName);

        return $packageName;
        //return $this->GetFile($zipName);
    }

    private function PackFolder(String $folder, String $tarFileName)
    {
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
        $tarFile->addFromString($metaGuid . '/pathname', $fileName);
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
            if(!($file == '.' || $file == '..' || endsWith($file,'.meta'))) {
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
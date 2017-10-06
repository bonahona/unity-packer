<?php

class HomeController extends Controller
{
    public function Index()
    {
        $this->Title = 'Unity Packer';
        return $this->View();
    }
}
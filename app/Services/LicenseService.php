<?php


namespace App\Services;

use Illuminate\Support\Facades\File;

class LicenseService
{
    protected $paths = array();
    protected $file = '';
    protected $extensions = [
        'PHP' => '#\.php#',
        'JS' => '#\.js$#',
        'CSS' => '#\.css#',
        'JSP' => '#\.jsp#',
        'Java'=> '#\.java#'
    ];
    protected $filter = array();

    function licensesForDir($path)
    {
        foreach(glob($path.'/*') as $eachPath)
        {
            if(is_dir($eachPath))
            {
                $this->licensesForDir($eachPath);
            }
            foreach ($this->extensions as $key => $extension)
            {
                if(in_array($key, $this->filter))
                {
                    if(preg_match($extension, $eachPath))
                    {
                        $this->paths[] = $eachPath;
                    }
                }
            }
        }
    }

    function exec($directory, $file = '', $extensions = [])
    {
        $directory = $directory.'/';
        $this->filter = explode(',', $extensions);

        if ($file != '')
        {
            $this->file = $file;
        }

        $this->licensesForDir($directory);
        foreach($this->paths as $path)
        {
            if($this->handleFile($path) == false)
            {
                return false;
            }
        }

        return true;
    }

    function handleFile($path)
    {
        if ($this->file != '')
        {
            $license_header = File::get($this->file);
        } else {
            $license_header = File::get('copyright.txt');
        }

        $source = file_get_contents($path);
        if(preg_match('#\.php#',$path))
        {
            $source = preg_replace('#\<\?php\n#',"<?php\n".$license_header,$source,1);
            $success = file_put_contents($path,$source);
        } else {
            $success = file_put_contents($path, $license_header."\r\n".$source);
        }

        return $success;
    }
}

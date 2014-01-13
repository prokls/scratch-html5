<?php
    $resource_folder = './assets/';
    $cache_folder = './.cache/';
    $resource = $_GET['resource'];

    function error($msg) {
        header($_SERVER['SERVER_PROTOCOL'] . " 500 Internal server error", true, 500);
        header("Content-type: text/plain");
        echo "  Error occured.\n";
        foreach (func_get_args() as $key => $val) {
            if ($key === 0)
                echo "  Error message: ", $val;
            else {
                echo "\n\n";
                var_dump($val);
            }
        }
        echo "\n=== BACKTRACE ===\n";
        debug_print_backtrace();
        exit(1);
    }

    function retrieve_file_from_cache($res) {
        global $cache_folder;
        $base = getcwd();
        if (!chdir($cache_folder))
            error("Could not change directory.", getcwd(), $cache_folder);

        foreach (glob('*') as $filename) {
            if ($filename === $res) {
                if (!chdir($base))
                    error("Could not change back to base directory.", getcwd(), $base);
                return $cache_folder.$filename;
            }
        }
        if (!chdir($base))
            error("Could not change back to base directory.", getcwd(), $base);

        return NULL;
    }

    function unzip_archive($zip_name) {
        global $cache_folder, $resource_folder;
        $base = getcwd();
        if (!chdir($resource_folder))
            error("Could not change into resource folder", $resource_folder);

        $zip = zip_open($zip_name);
        if (!is_resource($zip)) {
            chdir($base);
            error('ZIP archive cannot be read.', $zip, getcwd(), $zip_name);
        }

        if (!chdir($base) || !chdir($cache_folder)) {
            zip_close($zip);
            error('Could not change from resource folder to cache folder',
                $base, $cache_folder);
        }
        
        $hashes = array();

        while ($zip_entry = zip_read($zip)) {
            // read zip entry and store content in file
            if (!zip_entry_open($zip, $zip_entry, "r")) {
                chdir($base);
                zip_entry_close($zip_entry);
                zip_close($zip);
                error('Error while unzipping file.', zip_entry_name($zip_entry));
            }

            $filename = basename(zip_entry_name($zip_entry));
            $fileext = pathinfo($filename, PATHINFO_EXTENSION);
            //error_log((string)$zip_name . ' ' . (string)$filename);

            $fp = fopen($filename, 'w');
            if ($fp === false) {
                chdir($base);
                zip_entry_close($zip_entry);
                zip_close($zip);
                error('Could not open file ', $filename);
            }
            while ($data = zip_entry_read($zip_entry)) {
                if (fwrite($fp, $data) === false) {
                    chdir($base);
                    zip_entry_close($zip_entry);
                    zip_close($zip);
                    fclose($fp);
                    error('Could not write data to file ', $filename);
                }
            }

            if ($data === false) {
                chdir($base);
                zip_entry_close($zip_entry);
                zip_close($zip);
                fclose($fp);
                error('Data could not be read from file', $filename);
            }
            if (fclose($fp) === false) {
                chdir($base);
                zip_entry_close($zip_entry);
                zip_close($zip);
                error('Could not close file', $filename);
            }
            error_log($filename . " - " . filesize($filename));
            zip_entry_close($zip_entry);

            // rename to hash filename
            $hash_filename = md5_file($filename).'.'.$fileext;
            if (file_exists($hash_filename)) {
                unlink($filename);
                continue;
            }
            if (!rename($filename, $hash_filename)) {
                $action_pwd = getcwd();
                chdir($base);
                zip_close($zip);
                error("Could not rename file.", $filename, $hash_filename, $action_pwd);
            }

            // if project.json, read project ID
            if ($filename === 'project.json') {
                $content = file_get_contents($hash_filename);
                assert($content !== false);
                $json = json_decode($content, true);
                $projID = $json['info']['projectID'];
                if (strlen($projID) === 0) {
                    chdir($base);
                    zip_close($zip);
                    error("Project ID could not be read", $content, $json, $projID);
                }

                $hashes['proj_id'] = $projID;
            }

            $hashes[$hash_filename] = $filename;
        }

        // store hashes in info file
        $proj_file = $hashes['proj_id'].'.json';
        $fp = fopen($proj_file, 'w');
        if ($fp === false) {
            chdir($base);
            zip_close($zip);
            error("Could not write info file.", $proj_file);
        }
        unset($hashes['proj_id']);
        fwrite($fp, json_encode($hashes) . "\n");
        fclose($fp);
        zip_close($zip);

        chdir($base);
    }

    function unzip_all_archives() {
        global $resource_folder;
        $base = getcwd();
        if (!chdir($resource_folder))
            error("Could not change into resource folder", $resource_folder);
        $filepaths = glob("*");
        chdir($base);

        foreach ($filepaths as $filename) {
            unzip_archive($filename);
            //echo "Unzipping $filename.\n";
        }
    }

    function return_file($resource) {
        $extension = pathinfo($resource, PATHINFO_EXTENSION);

        header('Access-Control-Allow-Origin: *');

        $contents = file_get_contents($resource);

        switch ($extension) {
            case 'json':header('Content-type: text/plain'); break;
            case 'png': header('Content-type: image/png'); break;
            case 'jpg': header('Content-type: image/jpeg'); break;
            case 'svg':
                header('Content-type: image/svg+xml');
                // Extremely ugly hack to temporarily repair broken SVGs generated
                // by the Scratch editor for blank backgrounds.
                // TODO: Fix the Scratch editor
                $contents = str_replace('<svg width="480" height="360">',
                    '<svg width="480" height="360" xmlns="http://www.w3.org/2000/svg" ' .
                    'version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink">',
                    $contents
                );
                break;
            case 'swf':
                // For testing
                header('Content-type: application/x-shockwave-flash');
                header("Cache-Control: no-cache, must-revalidate");
                header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
                break;
            default:
                header('Content-type: text/plain');
                break;
        }
        echo $contents;
    }

    function return_asset($filename) {
        if (($filepath = retrieve_file_from_cache($filename)) != null) {
            return_file($filepath);
            exit(0);
        }

        error("Could not find file in cache.", $filename);
    }

    function return_project($project_id) {
        assert($project_id !== 0);

        global $cache_folder;
        $base = getcwd();
        chdir($cache_folder);

        $json = file_get_contents($project_id . ".json");
        if ($json === false)
            error("Could not read project file.", $project_id . '.json');

        $json = json_decode($json, true);
        $proj_file = "";
        foreach ($json as $hash => $filename) {
            if ($filename === 'project.json')
                $proj_file = $hash;
        }

        if ($proj_file === '')
            error("Project file did not provide reference to project.json");

        chdir($base);
        return_file($cache_folder . $proj_file);
        exit(0);
    }


    // Main routine

    if (!file_exists($cache_folder)) {
        if (!mkdir($cache_folder))
            error("Could not create directory.", $cache_folder);
    }
    if (!file_exists($resource_folder)) {
        if (!mkdir($resource_folder))
            error("Could not create directory.", $resource_folder);
    }

    unzip_all_archives();

    $parts = explode('/', $resource);
    assert($parts[0] === 'internalapi');
    if ($parts[1] === 'asset')
        return_asset($parts[2]);
    else if ($parts[1] === 'project')
        return_project((int)$parts[2]);
    else
        error("Request is unknown.", $parts);
?>

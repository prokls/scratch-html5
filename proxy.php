<?php
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

        return_file($cache_folder.$project_id.'.json');
        exit(0);
    }


    // Main routine

    if (!file_exists($cache_folder))
        error("Cache directory does not exist. Run unpack.py with "
             ."project archives first.", $cache_folder);

    $parts = explode('/', $resource);
    assert($parts[0] === 'internalapi');
    if ($parts[1] === 'asset')
        return_asset($parts[2]);
    else if ($parts[1] === 'project')
        return_project((int)$parts[2]);
    else
        error("Request is unknown.", $parts);
?>

